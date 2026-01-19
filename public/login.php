<?php
require "../app/config/database.php";
require "../app/models/User.php";

if ($_POST) {
    $u = new User($conn);
    if ($u->login($_POST['email'], $_POST['password'])) {
        header("Location: index.php");
        exit;
    }
    $error = "Invalid email or password";
}
?>