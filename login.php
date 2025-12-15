<?php
include "config/db.php";
session_start();

if (isset($_POST['login'])) {
    $identity = trim($_POST['identity']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email=? OR username=?"
    );
    $stmt->bind_param("ss", $identity, $identity);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;

        if ($user['role'] === "admin") {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] === "host") {
            header("Location: host/dashboard.php");
        } else {
            header("Location: customer/dashboard.php");
        }
    } else {
        header("Location: index.php");
    }
}
