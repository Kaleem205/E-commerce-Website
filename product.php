<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

// Get the product ID from the URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    // If no ID is provided, redirect back to home
    header("Location: index.php");
    exit();
}

// Fetch product details along with its category name
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// If product doesn't exist, redirect to home
if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$product = $result->fetch_assoc();
$page_title = $product['name'];

require 'includes/header.php';
?>

<div class="breadcrumb-bar">
    <div class="container">
        <a href="index.php">Home</a> &raquo; 
        <a href="index.php?category=<?= htmlspecialchars($product['slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a> &raquo; 
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>
</div>

<section class="product-detail-section">
    <div class="container">
        <div class="product-detail-grid">
            
            <!-- Product Image Gallery -->
            <div class="product-detail-image">
                <img src="/shopping_system/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                     onerror="this.src='/shopping_system/uploads/products/default.jpg'" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <!-- Product Information -->
            <div class="product-detail-info">
                <span class="product-category-label"><?= htmlspecialchars($product['category_name']) ?></span>
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="product-price-large">
                    Rs. <?= number_format($product['price'], 0) ?>
                </div>

                <div class="product-description">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>

                <div class="product-status">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="status-in-stock">✓ In Stock (<?= $product['stock'] ?> available)</span>
                    <?php else: ?>
                        <span class="status-out-stock">✗ Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart Form -->
                <div class="product-actions">
                    <?php if ($product['stock'] > 0): ?>
                        <form action="/shopping_system/cart.php" method="POST" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            
                            <div class="quantity-selector">
                                <label for="quantity">Quantity:</label>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                            </div>
                            
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-full product-btn">🛒 Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-full product-btn" disabled>Currently Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>