<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "config/db.php";
session_start();

if (isset($_POST['register'])) {
    $role = $_POST['role']; // customer or host
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Debug: Check what values we have
    echo "DEBUG: Role = $role, Username = $username, Email = $email<br>";
    
    // First, check if user already exists
    $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ss", $email, $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        die("User already exists with this email or username!");
    }
    $check_stmt->close();

    // Prepare the insert statement
    $stmt = $conn->prepare(
        "INSERT INTO users (role, username, email, password)
         VALUES (?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssss", $role, $username, $email, $password);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;

        $_SESSION['user'] = [
            "id" => $id,
            "role" => $role,
            "username" => $username,
            "email" => $email,
            "profile_pic" => "default.png"
        ];

        if ($role === "host") {
            header("Location: host/dashboard.php");
        } else {
            header("Location: customer/dashboard.php");
        }
        exit();
    } else {
        die("Registration failed: " . $stmt->error);
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Luxe Voyage</title>
</head>
<body>
    <h2>Register</h2>
    <form method="POST" action="">
        <div>
            <label>Role:</label><br>
            <input type="radio" name="role" value="customer" checked> Customer
            <input type="radio" name="role" value="host"> Host
        </div>
        <div>
            <label>Username:</label><br>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Email:</label><br>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Password:</label><br>
            <input type="password" name="password" required>
        </div>
        <button type="submit" name="register">Register</button>
    </form>
</body>
</html>