<?php
// NO WHITESPACE BEFORE THIS
session_start();
include "config/db.php";

if (isset($_POST['login'])) {
    $identity = trim($_POST['identity']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
    $stmt->bind_param("ss", $identity, $identity);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        
        // DEBUG: Force output to see what's happening
        echo "Login successful! Role: " . $user['role'] . "<br>";
        echo "Redirecting in 3 seconds...";
        
        // JavaScript redirect as fallback
        echo '<script>
            setTimeout(function() {
                window.location.href = "' . 
                ($user['role'] === "admin" ? "admin/dashboard.php" : 
                 ($user['role'] === "host" ? "host/dashboard.php" : "customer/dashboard.php")) . '";
            }, 3000);
        </script>';
        exit;
    } else {
        echo "Invalid credentials!<br>";
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 2000);</script>';
        exit;
    }
}
?>