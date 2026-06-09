<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

requireAdmin();
$page_title = 'Manage Products';
$success_msg = '';

// Handle Delete Product
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Fetch image name to delete the physical file if it's not the default
    $img_stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $img_stmt->bind_param("i", $delete_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    
    if ($img_result->num_rows > 0) {
        $img_row = $img_result->fetch_assoc();
        if ($img_row['image'] !== 'default.jpg' && file_exists("../uploads/products/" . $img_row['image'])) {
            unlink("../uploads/products/" . $img_row['image']);
        }
        
        $del_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        if ($del_stmt->execute()) {
            $success_msg = "Product deleted successfully.";
        }
    }
}

// Fetch all products
$products = $conn->query("
    SELECT p.id, p.name, p.price, p.stock, p.image, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        
        <div class="admin-header">
            <h1 class="section-title">Manage Products</h1>
            <a href="add_product.php" class="btn btn-primary">+ Add New Product</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><p><?= $success_msg ?></p></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="/shopping_system/uploads/products/<?= htmlspecialchars($p['image']) ?>" 
                                     onerror="this.src='/shopping_system/uploads/products/default.jpg'" 
                                     alt="img" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td>Rs. <?= number_format($p['price'], 0) ?></td>
                            <td>
                                <?php if ($p['stock'] < 5): ?>
                                    <span style="color: var(--danger); font-weight:bold;"><?= $p['stock'] ?></span>
                                <?php else: ?>
                                    <?= $p['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<?php require '../includes/footer.php'; ?>