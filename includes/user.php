<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function isHost() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'host';
}

function isCustomer() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
}
