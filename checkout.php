<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Checkout';
$errors = [];
$success_order_id = null;

// Fetch Cart Items
$stmt = $conn->prepare("
    SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items) && !isset($_GET['success'])) {
    header("Location: cart.php");
    exit();
}

$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += ($item['price'] * $item['quantity']);
}

// Fetch user details to pre-fill the form
$user_stmt = $conn->prepare("SELECT full_name, phone, address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_name    = trim($_POST['shipping_name']);
    $shipping_phone   = trim($_POST['shipping_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method   = $_POST['payment_method'];

    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address)) {
        $errors[] = "All shipping fields are required.";
    }

    // Verify stock one last time before placing order
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "Not enough stock for " . htmlspecialchars($item['name']) . ". Only " . $item['stock'] . " left.";
        }
    }

    if (empty($errors)) {
        // Begin Database Transaction for Data Integrity
        $conn->begin_transaction();

        try {
            // 1. Insert into orders table
            $status = 'pending';
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_name, shipping_phone, shipping_address, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $order_stmt->bind_param("idsssss", $user_id, $cart_total, $status, $shipping_name, $shipping_phone, $shipping_address, $payment_method);
            $order_stmt->execute();
            $new_order_id = $order_stmt->insert_id;

            // 2. Insert into order_items and update product stock
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                $item_stmt->bind_param("iiid", $new_order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();

                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }

            // 3. Clear the user's cart
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();

            // Commit transaction
            $conn->commit();
            
            header("Location: checkout.php?success=" . $new_order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Order processing failed. Please try again.";
        }
    }
}

require 'includes/header.php';
?>

<section class="checkout-section">
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-state">
                <h2>Order Placed Successfully</h2>
                <p>Thank you for your purchase. Your order ID is <strong>#<?= (int)$_GET['success'] ?></strong>.</p>
                <p>We will contact you shortly to confirm your delivery details.</p>
                <div class="success-actions">
                    <a href="orders.php" class="btn btn-primary">View My Orders</a>
                    <a href="index.php" class="btn btn-outline">Return to Home</a>
                </div>
            </div>
        <?php else: ?>
            
            <h1 class="section-title">Checkout</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e): ?>
                        <p><?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="checkout-layout">
                <!-- Checkout Form -->
                <div class="checkout-form-container">
                    <h3>Shipping Details</h3>
                    <form action="checkout.php" method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="shipping_name" required 
                                   value="<?= htmlspecialchars($_POST['shipping_name'] ?? $user_data['full_name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="shipping_phone" required 
                                   value="<?= htmlspecialchars($_POST['shipping_phone'] ?? $user_data['phone']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Delivery Address</label>
                            <textarea name="shipping_address" required><?= htmlspecialchars($_POST['shipping_address'] ?? $user_data['address']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required>
                                <option value="Cash on Delivery">Cash on Delivery</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-primary btn-full checkout-submit-btn">Place Order</button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="checkout-summary-container">
                    <h3>Order Summary</h3>
                    <div class="checkout-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="checkout-item-row">
                                <span class="checkout-item-name">
                                    <?= htmlspecialchars($item['name']) ?> &times; <?= $item['quantity'] ?>
                                </span>
                                <span class="checkout-item-price">
                                    Rs. <?= number_format($item['price'] * $item['quantity'], 0) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr class="summary-divider">
                    
                    <div class="checkout-total-row">
                        <span>Total to Pay</span>
                        <span class="total-amount">Rs. <?= number_format($cart_total, 0) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require 'includes/footer.php'; ?>