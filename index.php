<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

$page_title = 'Home';

// search and category filter
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$where    = "WHERE 1=1";
$params   = [];
$types    = "";

if ($search !== '') {
    $where   .= " AND p.name LIKE ?";
    $params[] = "%$search%";
    $types   .= "s";
}

if ($category !== '') {
    $where   .= " AND c.slug = ?";
    $params[] = $category;
    $types   .= "s";
}

// --- PAGINATION LOGIC ---
$limit = 8; // Show 8 products per page
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 1. Get Total Count for Pagination
$count_sql = "SELECT COUNT(p.id) as total FROM products p JOIN categories c ON p.category_id = c.id $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// 2. Fetch Products for Current Page
$sql  = "SELECT p.*, c.name as category_name FROM products p 
         JOIN categories c ON p.category_id = c.id 
         $where ORDER BY p.featured DESC, p.created_at DESC 
         LIMIT ? OFFSET ?";

$fetch_params = $params;
$fetch_params[] = $limit;
$fetch_params[] = $offset;
$fetch_types = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($fetch_types, ...$fetch_params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch featured products for hero section
$featured_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 LIMIT 3");
$featured_stmt->execute();
$featured = $featured_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all categories for filter bar
$cats = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require 'includes/header.php';
?>

<?php if (empty($search) && empty($category) && $page == 1): ?>
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Shop the Latest <span>Trends</span></h1>
            <p>Discover thousands of products at unbeatable prices. Fast delivery across Pakistan.</p>
            <a href="#products" class="btn btn-primary">Shop Now</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-outline">Join Free</a>
            <?php endif; ?>
        </div>
        <div class="hero-cards">
            <?php foreach ($featured as $f): ?>
            <div class="hero-card">
                <div class="hero-card-img">
                    <img src="/shopping_system/uploads/products/<?= htmlspecialchars($f['image']) ?>" onerror="this.src='/shopping_system/uploads/products/default.jpg'" alt="<?= htmlspecialchars($f['name']) ?>">
                </div>
                <div class="hero-card-info">
                    <span><?= htmlspecialchars($f['category_name']) ?></span>
                    <p><?= htmlspecialchars($f['name']) ?></p>
                    <strong>Rs. <?= number_format($f['price'], 0) ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="category-bar">
    <div class="container">
        <a href="index.php#products" class="cat-pill <?= empty($category) ? 'active' : '' ?>">All</a>
        
        <?php foreach ($cats as $cat): ?>
            <a href="index.php?category=<?= $cat['slug'] ?>#products" 
               class="cat-pill <?= $category === $cat['slug'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="products-section" id="products">
    <div class="container">
        <?php if ($search): ?>
            <h2 class="section-title">Search results for "<?= htmlspecialchars($search) ?>"</h2>
        <?php elseif ($category): ?>
            <h2 class="section-title"><?= htmlspecialchars(ucfirst($category)) ?></h2>
        <?php else: ?>
            <h2 class="section-title">All Products</h2>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>😕 No products found.</p>
                <a href="index.php" class="btn btn-primary">View All Products</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <a href="product.php?id=<?= $p['id'] ?>" class="product-img-link">
                        <img src="/shopping_system/uploads/products/<?= htmlspecialchars($p['image']) ?>" onerror="this.src='/shopping_system/uploads/products/default.jpg'" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php if ($p['featured']): ?>
                            <span class="badge-featured">Featured</span>
                        <?php endif; ?>
                        <?php if ($p['stock'] == 0): ?>
                            <span class="badge-out">Out of Stock</span>
                        <?php endif; ?>
                    </a>
                    <div class="product-info">
                        <span class="product-category"><?= htmlspecialchars($p['category_name']) ?></span>
                        <h3><a href="product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
                        <div class="product-footer">
                            <span class="product-price">Rs. <?= number_format($p['price'], 0) ?></span>
                            <?php if ($p['stock'] > 0): ?>
                                <button class="btn btn-primary btn-sm ajax-add-to-cart" data-id="<?= $p['id'] ?>">Add to Cart</button>
                            <?php else: ?>
                                <button class="btn btn-sm" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php 
                            $url = "?page=" . $i;
                            if ($search) $url .= "&search=" . urlencode($search);
                            if ($category) $url .= "&category=" . urlencode($category);
                        ?>
                        <a href="<?= $url ?>" class="page-link <?= ($page === $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>