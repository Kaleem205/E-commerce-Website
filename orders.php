<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'My Account';

// Fetch User Profile Data
$user_stmt = $conn->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// Fetch All Orders
$stmt = $conn->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch All Items for these orders efficiently
$items_stmt = $conn->prepare("
    SELECT oi.order_id, oi.quantity, oi.price, p.name, p.image, p.id as product_id
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = ?
");
$items_stmt->bind_param("i", $user_id);
$items_stmt->execute();
$all_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group items by their Order ID so we can loop through them easily later
$order_items = [];
foreach ($all_items as $item) {
    $order_items[$item['order_id']][] = $item;
}

require 'includes/header.php';
?>

<section class="account-section">
    <div class="container">
        <h1 class="section-title">My Account</h1>

        <div class="account-tabs">
            <button class="tab-btn active" data-target="orders-tab">📦 My Orders</button>
            <button class="tab-btn" data-target="profile-tab">👤 Profile Details</button>
        </div>

        <div id="orders-tab" class="tab-content active">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <p>You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $o): ?>
                        <div class="interactive-order-card <?= $o['status'] === 'cancelled' ? 'order-is-cancelled' : '' ?>">
                            <div class="order-card-header">
                                <div>
                                    <strong>Order #<?= $o['id'] ?></strong>
                                    <span style="display:block; font-size:0.85rem; color:var(--gray);">Placed on <?= date('M j, Y', strtotime($o['created_at'])) ?></span>
                                </div>
                                <div class="order-total">
                                    Rs. <?= number_format($o['total_amount'], 0) ?>
                                </div>
                            </div>
                            
                            <div class="order-status-container">
                                <?php 
                                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                    $current_index = array_search($o['status'], $statuses);
                                ?>
                                
                                <?php if ($o['status'] === 'cancelled'): ?>
                                    <div class="status-alert cancelled-alert">
                                        <span class="alert-icon">⚠️</span>
                                        <div class="alert-text">
                                            <strong>Order Cancelled</strong>
                                            <p>This order was cancelled. Any deducted stock has been restored to the inventory.</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="order-tracker">
                                        <?php foreach ($statuses as $index => $status): ?>
                                            <div class="tracker-step <?= $index <= $current_index ? 'active' : '' ?>">
                                                <div class="step-circle">✓</div>
                                                <span class="step-label"><?= ucfirst($status) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="purchased-items-list">
                                <h4>Items in this order:</h4>
                                <?php if (isset($order_items[$o['id']])): ?>
                                    <?php foreach ($order_items[$o['id']] as $item): ?>
                                        <div class="purchased-item">
                                            <div class="p-item-left">
                                                <img src="/shopping_system/uploads/products/<?= htmlspecialchars($item['image']) ?>" 
                                                     onerror="this.src='/shopping_system/uploads/products/default.jpg'" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>">
                                                <a href="product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                            </div>
                                            <div class="p-item-right">
                                                <span class="p-qty">Qty: <?= $item['quantity'] ?></span>
                                                <span class="p-price">Rs. <?= number_format($item['price'] * $item['quantity'], 0) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="profile-tab" class="tab-content">
            <div class="profile-card">
                <h3>Personal Information</h3>
                <div class="profile-grid">
                    <div class="profile-item">
                        <label>Full Name</label>
                        <p><?= htmlspecialchars($user_data['full_name']) ?></p>
                    </div>
                    <div class="profile-item">
                        <label>Email Address</label>
                        <p><?= htmlspecialchars($user_data['email']) ?></p>
                    </div>
                    <div class="profile-item">
                        <label>Phone Number</label>
                        <p><?= htmlspecialchars($user_data['phone'] ?? 'Not provided') ?></p>
                    </div>
                    <div class="profile-item">
                        <label>Delivery Address</label>
                        <p><?= nl2br(htmlspecialchars($user_data['address'] ?? 'Not provided')) ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require 'includes/footer.php'; ?>