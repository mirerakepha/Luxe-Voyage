<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        // Simple redirect
        echo '<script>window.location.href = "../index.php";</script>';
        exit;
    }
}

function requireRole($role) {
    if (!isset($_SESSION['user'])) {
        echo '<script>window.location.href = "../index.php";</script>';
        exit;
    }
    
    if ($_SESSION['user']['role'] !== $role) {
        echo '<script>window.location.href = "../index.php";</script>';
        exit;
    }
}
?>