<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ShopZone</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-logo">
            <a href="index.php" class="logo" style="justify-content: center;">
                <svg class="brand-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <div class="brand-name">
                    <span class="brand-text-thick">Shop</span><span class="brand-text-thin">Zone</span>
                </div>
            </a>
        </div>

        <h2>Welcome Back</h2>
        <p class="auth-sub">Enter your credentials to access your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="e.g. ali@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Login to My Account</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

</body>
</html>