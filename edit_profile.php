<?php
include "includes/auth.php";
include "config/db.php";
requireLogin();

$user = $_SESSION['user'];

if (isset($_POST['save'])) {
    $username = trim($_POST['username']);

    if (!empty($_FILES['profile']['name'])) {
        $img = time() . "_" . $_FILES['profile']['name'];
        move_uploaded_file($_FILES['profile']['tmp_name'],
            "assets/images/profiles/" . $img
        );

        $stmt = $conn->prepare(
            "UPDATE users SET username=?, profile_pic=? WHERE id=?"
        );
        $stmt->bind_param("ssi", $username, $img, $user['id']);
        $_SESSION['user']['profile_pic'] = $img;
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET username=? WHERE id=?"
        );
        $stmt->bind_param("si", $username, $user['id']);
    }

    $stmt->execute();
    $_SESSION['user']['username'] = $username;

    header("Location: profile.php");
}
?>

<form method="POST" enctype="multipart/form-data">
    <input name="username" value="<?php echo $user['username']; ?>" required>
    <input type="file" name="profile">
    <button name="save">Save Changes</button>
</form>
