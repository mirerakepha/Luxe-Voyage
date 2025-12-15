<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Travel Agency</title>
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<nav>
    <a href="index.php">Home</a>

    <?php if (!isset($_SESSION['user'])) { ?>
        <a href="login.php">Login</a>
        <a href="register.php">Sign Up</a>
    <?php } else { ?>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    <?php } ?>
</nav>
