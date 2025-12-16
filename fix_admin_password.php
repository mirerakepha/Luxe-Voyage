<?php
include "config/db.php";

echo "<h2>Fix Admin Password</h2>";

// Generate correct hash for "admin123"
$password = "admin123";
$correct_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Correct hash for 'admin123':<br>";
echo "<code>$correct_hash</code><br><br>";

// Update admin password
$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correct_hash);

if ($stmt->execute()) {
    echo "✅ Admin password updated!<br>";
    echo "New password: <strong>admin123</strong><br><br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Verify
$result = $conn->query("SELECT password FROM users WHERE username='admin'");
$admin = $result->fetch_assoc();

echo "Stored hash now: " . $admin['password'] . "<br>";
echo "Verification: " . (password_verify('admin123', $admin['password']) ? "✅ CORRECT" : "❌ WRONG") . "<br>";

echo '<hr><a href="verify_admin.php">Verify Again</a> | ';
echo '<a href="login.php">Test Login</a>';
?>