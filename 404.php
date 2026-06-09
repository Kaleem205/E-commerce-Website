<?php
session_start();
require 'includes/db.php';
// We don't require auth here so anyone can see the error page safely
$page_title = 'Page Not Found';
require 'includes/header.php';
?>

<section style="padding: 100px 20px; text-align: center; min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <h1 style="font-size: 6rem; color: var(--primary); margin-bottom: 10px; font-family: var(--font-display);">404</h1>
    <h2 style="font-size: 2rem; color: var(--dark); margin-bottom: 20px;">Oops! Page not found.</h2>
    <p style="color: var(--gray); margin-bottom: 30px; font-size: 1.1rem; max-width: 500px;">
        The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
    </p>
    <a href="/shopping_system/index.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">Return to Homepage</a>
</section>

<?php require 'includes/footer.php'; ?>