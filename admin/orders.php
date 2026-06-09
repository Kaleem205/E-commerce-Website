<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Secure this page - ONLY admins allowed
requireAdmin();

$page_title = 'Manage Orders';
$success_msg = '';
$error_msg = '';

// Handle Status Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        
        // --- INVENTORY RESTORATION LOGIC ---
        // 1. Get current status to see if we need to adjust stock
        $check_curr = $conn->prepare("SELECT status FROM orders WHERE id = ?");
        $check_curr->bind_param("i", $order_id);
        $check_curr->execute();
        $curr_status = $check_curr->get_result()->fetch_assoc()['status'];

        // 2. If changing TO cancelled from something else (Add back to stock)
        if ($new_status === 'cancelled' && $curr_status !== 'cancelled') {
            $item_q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $item_q->bind_param("i", $order_id);
            $item_q->execute();
            $items = $item_q->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($items as $item) {
                $upd = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $upd->bind_param("ii", $item['quantity'], $item['product_id']);
                $upd->execute();
            }
        } 
        // 3. If restoring FROM cancelled (Deduct stock again)
        elseif ($new_status !== 'cancelled' && $curr_status === 'cancelled') {
            $item_q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $item_q->bind_param("i", $order_id);
            $item_q->execute();
            $items = $item_q->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($items as $item) {
                $upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $upd->bind_param("ii", $item['quantity'], $item['product_id']);
                $upd->execute();
            }
        }

        // Final step: Update the status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $success_msg = "Order #$order_id status updated. Inventory adjusted.";
        } else {
            $error_msg = "Failed to update order status.";
        }
    }
}

// Check if viewing a specific order's details
$view_order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($view_order_id > 0) {
    $stmt = $conn->prepare("
        SELECT o.*, u.email as account_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $view_order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        header("Location: orders.php");
        exit();
    }
    
    $order = $order_result->fetch_assoc();
    
    $items_stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $items_stmt->bind_param("i", $view_order_id);
    $items_stmt->execute();
    $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} else {
    $orders = $conn->query("SELECT id, total_amount, status, created_at, shipping_name FROM orders ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="margin-bottom:20px; border-radius:12px;"><?= $success_msg ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error" style="margin-bottom:20px; border-radius:12px;"><?= $error_msg ?></div>
        <?php endif; ?>

        <?php if ($view_order_id > 0): ?>
            <div class="admin-header">
                <h1 class="section-title">Manage Order #<?= $order['id'] ?></h1>
                <a href="orders.php" class="btn btn-outline btn-sm">&larr; Back to All Orders</a>
            </div>

            <div class="order-details-grid <?= $order['status'] === 'cancelled' ? 'admin-order-cancelled' : '' ?>">
                
                <div class="order-info-card" style="border-top: 4px solid var(--primary);">
                    <h3>Status Management</h3>
                    <form action="orders.php?id=<?= $order['id'] ?>" method="POST" class="status-update-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <div class="form-group">
                            <select name="status" class="status-select-large">
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-full">Save Changes</button>
                    </form>
                </div>

                <div class="order-info-card">
                    <h3>Summary</h3>
                    <div class="info-row"><span>Date:</span> <strong><?= date('M j, Y', strtotime($order['created_at'])) ?></strong></div>
                    <div class="info-row"><span>Total:</span> <strong class="text-primary">Rs. <?= number_format($order['total_amount'], 0) ?></strong></div>
                    <div class="info-row"><span>Status:</span> <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></div>
                </div>
                
                <div class="order-info-card">
                    <h3>Customer Info</h3>
                    <p><strong><?= htmlspecialchars($order['shipping_name']) ?></strong></p>
                    <p style="font-size:0.9rem; color:var(--gray);"><?= htmlspecialchars($order['account_email']) ?></p>
                    <p style="margin-top:10px; font-size:0.85rem;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>

            <div class="order-items-card mt-4">
                <h3 style="margin-bottom:20px;">Purchased Items</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="product-cell">
                                        <img src="/shopping_system/uploads/products/<?= htmlspecialchars($item['image']) ?>" onerror="this.src='/shopping_system/uploads/products/default.jpg'">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                    </td>
                                    <td>Rs. <?= number_format($item['price'], 0) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><strong>Rs. <?= number_format($item['price'] * $item['quantity'], 0) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="admin-header">
                <h1 class="section-title">All Customer Orders</h1>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong>#<?= $o['id'] ?></strong></td>
                                <td><?= htmlspecialchars($o['shipping_name']) ?></td>
                                <td>Rs. <?= number_format($o['total_amount'], 0) ?></td>
                                <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Manage</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require '../includes/footer.php'; ?>