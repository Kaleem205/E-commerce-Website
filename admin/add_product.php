<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

requireAdmin();
$page_title = 'Add Product';
$errors = [];
$success = '';

// Fetch categories for the dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $featured    = isset($_POST['featured']) ? 1 : 0;
    
    // Create a URL-friendly slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    
    // Handle Image Upload
    $image_name = 'default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            $destination = '../uploads/products/' . $image_name;
            move_uploaded_file($_FILES['image']['tmp_name'], $destination);
        } else {
            $errors[] = "Invalid image format. Only JPG, PNG, and WEBP are allowed.";
        }
    }

    if (empty($name) || $price <= 0) {
        $errors[] = "Product name and a valid price are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, price, stock, image, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdisi", $category_id, $name, $slug, $description, $price, $stock, $image_name, $featured);
        
        if ($stmt->execute()) {
            $success = "Product added successfully!";
            // Reset POST array so form clears out
            $_POST = [];
        } else {
            $errors[] = "Failed to add product. Make sure the name is unique.";
        }
    }
}

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="admin-header">
            <h1 class="section-title">Add New Product</h1>
            <a href="products.php" class="btn btn-outline">&larr; Back to Products</a>
        </div>

        <div class="admin-form-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><p><?= $success ?></p></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (Rs.)</label>
                        <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" required value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Description</label>
                    <textarea name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="featured" id="featured" value="1" <?= isset($_POST['featured']) ? 'checked' : '' ?>>
                    <label for="featured" style="margin:0;">Feature this product on the home page</label>
                </div>

                <button type="submit" class="btn btn-primary mt-4">Save Product</button>
            </form>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>