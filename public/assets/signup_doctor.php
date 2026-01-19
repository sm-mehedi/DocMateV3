<?php
session_start();
require "../app/config/database.php";
require "../app/models/User.php";
require "../app/models/Doctor.php";

$error = '';

if($_POST){
    $user = new User($conn);

    // Try to create user first
    if(!$user->create($_POST['email'], $_POST['password'], 'doctor')){
        $error = "Email already exists!";
    } else {
        // Create doctor profile
        $doctor = new Doctor($conn);
        $doctor->create($conn->lastInsertId(), [
            'name' => $_POST['name'],
            'degree' => $_POST['degree'] ?? [],
            'phone' => $_POST['phone'],
            'bmdc' => $_POST['bmdc'],
            'nid' => $_POST['nid'],
            'address' => $_POST['address'],
            'chamber' => $_POST['chamber'],
            'days' => $_POST['days'] ?? [],
            'time' => $_POST['available_time'], // Add this
            'desc' => $_POST['desc']
        ]);
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Signup</title>
    <link rel="stylesheet" href="../public/assets/css/signD.css">
</head>
<body>

<div class="signup-container">
    <h2>Doctor Registration</h2>

    <?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="post" id="doctorSignupForm" onsubmit="return validateForm()">