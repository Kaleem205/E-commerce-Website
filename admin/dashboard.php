<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Secure this page - ONLY admins allowed
requireAdmin();

$page_title = 'Admin Dashboard';

// Fetch Statistics
$stats = [
    'users'    => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetch_row()[0],
    'products' => $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0],
    'orders'   => $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0],
    'revenue'  => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetch_row()[0] ?? 0
];

// Fetch Recent Orders for the dashboard overview
$recent_orders = $conn->query("
    SELECT id, total_amount, status, created_at, shipping_name 
    FROM orders 
    ORDER BY created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

require '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        
        <div class="admin-header">
            <h1 class="section-title">Dashboard Overview</h1>
            <div class="admin-nav-links">
                <a href="products.php" class="btn btn-primary">Manage Products</a>
                <a href="orders.php" class="btn btn-outline">Manage Orders</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-data">
                    <h3>Total Customers</h3>
                    <p><?= $stats['users'] ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-data">
                    <h3>Total Products</h3>
                    <p><?= $stats['products'] ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div class="stat-data">
                    <h3>Total Orders</h3>
                    <p><?= $stats['orders'] ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-data">
                    <h3>Total Revenue</h3>
                    <p>Rs. <?= number_format($stats['revenue'], 0) ?></p>
                </div>
            </div>
        </div>

        <div class="recent-orders-card mt-4">
            <h3 class="mb-3">Recent Orders</h3>
            <?php if (empty($recent_orders)): ?>
                <p>No orders placed yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                                <tr>
                                    <td><strong>#<?= $o['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($o['shipping_name']) ?></td>
                                    <td>Rs. <?= number_format($o['total_amount'], 0) ?></td>
                                    <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                    <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require '../includes/footer.php'; ?>