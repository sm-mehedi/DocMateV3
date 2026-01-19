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

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../public/assets/css/login.css">
</head>
<body>

<div class="login-container">
    <h2>DocMate💊</h2>

    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <div class="signup-link">
        <p>New here?</p>
        <a href="signup_patient.php">Signup as Patient</a><br>
        <a href="signup_doctor.php">Signup as Doctor</a>
    </div>
</div>

</body>
</html>
