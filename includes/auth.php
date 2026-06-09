<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
}
?>