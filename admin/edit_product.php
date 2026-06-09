<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

requireAdmin();
$page_title = 'Edit Product';
$errors = [];
$success = '';

// Get Product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header("Location: products.php");
    exit();
}

// Fetch existing product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $featured    = isset($_POST['featured']) ? 1 : 0;
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    
    if (empty($name) || $price <= 0) {
        $errors[] = "Product name and a valid price are required.";
    }

    // --- RELIABLE IMAGE UPLOAD LOGIC ---
    $image_name = $product['image']; // Keep current image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // 1. Define Absolute Path
            $upload_dir = __DIR__ . '/../uploads/products/';
            $new_image_name = time() . '_' . uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_image_name;
            
            // 2. Ensure folder exists and is writable
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // 3. Delete old image if it's not the default
                if ($image_name !== 'default.jpg' && file_exists($upload_dir . $image_name)) {
                    unlink($upload_dir . $image_name);
                }
                $image_name = $new_image_name;
            } else {
                $errors[] = "Failed to move uploaded file. Check folder permissions.";
            }
        } else {
            $errors[] = "Invalid image format. Only JPG, PNG, and WEBP are allowed.";
        }
    }

    if (empty($errors)) {
        $update_stmt = $conn->prepare("
            UPDATE products 
            SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, image = ?, featured = ? 
            WHERE id = ?
        ");
        $update_stmt->bind_param("isssdisii", $category_id, $name, $slug, $description, $price, $stock, $image_name, $featured, $product_id);
        
        if ($update_stmt->execute()) {
            $success = "Product updated successfully!";
            // Refresh local data
            $product['image'] = $image_name; 
            $product['name'] = $name;
            $product['price'] = $price;
            $product['stock'] = $stock;
            $product['description'] = $description;
            $product['category_id'] = $category_id;
            $product['featured'] = $featured;
        } else {
            $errors[] = "Failed to update database. Product name may be a duplicate.";
        }
    }
}

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="admin-header">
            <h1 class="section-title">Edit Product</h1>
            <a href="products.php" class="btn btn-outline">&larr; Back to Products</a>
        </div>

        <div class="admin-form-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e) echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><p><?= htmlspecialchars($success) ?></p></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $product['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (Rs.)</label>
                        <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($product['price']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" required value="<?= htmlspecialchars($product['stock']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Description</label>
                    <textarea name="description" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <div class="edit-image-preview">
                        <img src="/shopping_system/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                             onerror="this.src='/shopping_system/uploads/products/default.jpg'" 
                             alt="Current Image">
                        <div class="image-info">
                            <span class="label">Current Image:</span>
                            <span class="filename"><?= htmlspecialchars($product['image']) ?></span>
                        </div>
                    </div>
                    <input type="file" name="image" accept="image/*" class="file-input">
                    <small class="help-text">Select a new file only if you want to replace the image.</small>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="featured" id="featured" value="1" <?= $product['featured'] ? 'checked' : '' ?>>
                    <label for="featured">Show this product in Featured section</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>