<?php
include "includes/auth.php";
requireLogin();

$user = $_SESSION['user'];
?>

<h2>My Profile</h2>

<img src="assets/images/profiles/<?php echo $user['profile_pic']; ?>" width="120">

<p>Username: <?php echo $user['username']; ?></p>
<p>Email: <?php echo $user['email']; ?></p>
<p>Role: <?php echo ucfirst($user['role']); ?></p>

<a href="edit_profile.php">Edit Profile</a>
