<?php if (!isset($conn)) require_once __DIR__ . '/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' — ShopZone' : 'ShopZone' ?></title>
    <link rel="stylesheet" href="/shopping_system/css/style.css">
</head>
<body>

<header class="main-header">
    <div class="container">
        <div class="header-inner">

            <a href="/shopping_system/index.php" class="logo">
                <svg class="brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <div class="brand-name">
                    <span class="brand-text-thick">Shop</span><span class="brand-text-thin">Zone</span>
                </div>
            </a>

            <div class="header-actions">
                <form action="/shopping_system/index.php" method="GET" class="search-form" id="live-search-form">
                    <input type="text" name="search" id="live-search-input" autocomplete="off" 
                           placeholder="Search products..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit">🔍</button>
                </form>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/shopping_system/cart.php" class="cart-icon">
                        🛒 <span class="cart-count">
                            <?php
                                $uid = $_SESSION['user_id'];
                                $cq  = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
                                $cq->bind_param("i", $uid);
                                $cq->execute();
                                $cr = $cq->get_result()->fetch_assoc();
                                echo $cr['total'] ?? 0;
                            ?>
                        </span>
                    </a>
                    <div class="user-menu">
                        <span>👤 <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                        <div class="dropdown">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="/shopping_system/admin/dashboard.php">Admin Panel</a>
                            <?php endif; ?>
                            <a href="/shopping_system/orders.php">My Orders</a>
                            <a href="/shopping_system/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/shopping_system/login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="/shopping_system/register.php" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</header>