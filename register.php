<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

redirectIfLoggedIn();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if (empty($full_name))              $errors[] = "Full name is required.";
    if (empty($email))                  $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password))               $errors[] = "Password is required.";
    if (strlen($password) < 6)          $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)         $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $hashed, $phone, $address);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
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
    <title>Register — ShopZone</title>
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

        <h2>Create an Account</h2>
        <p class="auth-sub">Join thousands of shoppers across Pakistan</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <p><?= $success ?></p>
                <a href="login.php" style="font-weight:700; text-decoration:underline;">Click here to Login</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="john@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="e.g. 03001234567"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Delivery Address</label>
                <textarea name="address" placeholder="Enter your full street address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:10px;">Create My Account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

</body>
</html>