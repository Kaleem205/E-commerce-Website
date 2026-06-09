<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

// The cart requires a user to be logged in based on your database schema
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Shopping Cart';

// Handle Add to Cart (From GET link or POST form)
if (isset($_GET['add']) || isset($_POST['add_to_cart'])) {
    $product_id = isset($_GET['add']) ? (int)$_GET['add'] : (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id > 0) {
        // Check if product exists and fetch available stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();
            
            // Check if item is already in the user's cart
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();
            $cart_result = $check_stmt->get_result();

            if ($cart_result->num_rows > 0) {
                // Update existing cart item
                $cart_row = $cart_result->fetch_assoc();
                $new_quantity = $cart_row['quantity'] + $quantity;
                
                // Prevent exceeding available stock
                if ($new_quantity > $product['stock']) {
                    $new_quantity = $product['stock'];
                }
                
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $cart_row['id']);
                $update_stmt->execute();
            } else {
                // Insert new cart item
                $insert_qty = ($quantity > $product['stock']) ? $product['stock'] : $quantity;
                $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $user_id, $product_id, $insert_qty);
                $insert_stmt->execute();
            }
        }
    }
    // Redirect to prevent form resubmission on page refresh
    header("Location: cart.php");
    exit();
}

// Handle Remove Item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $cart_id, $user_id);
    $delete_stmt->execute();
    
    header("Location: cart.php");
    exit();
}

// Handle Update Quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_id => $qty) {
            $cart_id = (int)$cart_id;
            $qty = (int)$qty;
            
            if ($qty > 0) {
                // Update quantity, ensuring it doesn't exceed stock limit
                $update_qty_stmt = $conn->prepare("
                    UPDATE cart c 
                    JOIN products p ON c.product_id = p.id 
                    SET c.quantity = LEAST(?, p.stock) 
                    WHERE c.id = ? AND c.user_id = ?
                ");
                $update_qty_stmt->bind_param("iii", $qty, $cart_id, $user_id);
                $update_qty_stmt->execute();
            }
        }
    }
    header("Location: cart.php");
    exit();
}

// Fetch Cart Items for Display
$fetch_cart_stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$fetch_cart_stmt->bind_param("i", $user_id);
$fetch_cart_stmt->execute();
$cart_items = $fetch_cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cart_total = 0;

require 'includes/header.php';
?>

<section class="cart-section">
    <div class="container">
        <h1 class="section-title">Your Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-state">
                <p>Your cart is currently empty.</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                
                <div class="cart-items-container">
                    <form action="cart.php" method="POST">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <?php 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $cart_total += $subtotal;
                                    ?>
                                    <tr>
                                        <td class="cart-product-col">
                                            <img src="/shopping_system/uploads/products/<?= htmlspecialchars($item['image']) ?>" 
                                                 onerror="this.src='/shopping_system/uploads/products/default.jpg'" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>">
                                            <div class="cart-product-details">
                                                <a href="product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                                <?php if ($item['stock'] < 5): ?>
                                                    <span class="stock-warning">Only <?= $item['stock'] ?> left</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>Rs. <?= number_format($item['price'], 0) ?></td>
                                        <td>
                                            <input type="number" name="quantities[<?= $item['cart_id'] ?>]" 
                                                   value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" class="cart-qty-input">
                                        </td>
                                        <td class="cart-subtotal">Rs. <?= number_format($subtotal, 0) ?></td>
                                        <td>
                                            <a href="cart.php?remove=<?= $item['cart_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?');">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="cart-actions-row">
                            <button type="submit" name="update_cart" class="btn btn-outline">Update Cart</button>
                        </div>
                    </form>
                </div>

                <div class="cart-summary-container">
                    <div class="cart-summary-box">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>Rs. <?= number_format($cart_total, 0) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>Rs. <?= number_format($cart_total, 0) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary btn-full checkout-btn">Proceed to Checkout</a>
                        <a href="index.php" class="btn btn-outline btn-full mt-2">Continue Shopping</a>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>