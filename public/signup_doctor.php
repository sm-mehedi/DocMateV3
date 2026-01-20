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

      <!-- Personal Information -->
        <div class="form-group">
            <label class="required">Full Name</label>
            <input type="text" name="name" id="name" required placeholder="Dr. First Last">
            <div class="validation-error" id="nameError">Name is required</div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="required">Email</label>
                <input type="email" name="email" id="email" required placeholder="doctor@example.com">
                <div class="validation-error" id="emailError">Valid email is required</div>
            </div>
            <div class="form-group">
                <label class="required">Phone Number</label>
                <input type="text" name="phone" id="phone" required placeholder="01XXXXXXXXX">
                <div class="validation-error" id="phoneError">Phone number is required</div>
            </div>
        </div>

        <!-- Medical Qualifications -->
        <div class="form-group">
            <label class="required">Medical Degrees & Specializations</label>
            <div class="checkbox-group">
                <!-- Basic Medical Degrees -->
                <label><input type="checkbox" name="degree[]" value="MBBS"> MBBS</label>
                <label><input type="checkbox" name="degree[]" value="BDS"> BDS</label>
                <label><input type="checkbox" name="degree[]" value="DDS"> DDS</label>
                <label><input type="checkbox" name="degree[]" value="BAMS"> BAMS</label>
                
                <!-- Postgraduate Degrees -->
                <label><input type="checkbox" name="degree[]" value="MD"> MD</label>
                <label><input type="checkbox" name="degree[]" value="MS"> MS</label>
                <label><input type="checkbox" name="degree[]" value="MPhil"> MPhil</label>
                <label><input type="checkbox" name="degree[]" value="PhD"> PhD</label>
                
                <!-- Specializations -->
                <label><input type="checkbox" name="degree[]" value="FCPS"> FCPS</label>
                <label><input type="checkbox" name="degree[]" value="FRCS"> FRCS</label>
                <label><input type="checkbox" name="degree[]" value="MRCP"> MRCP</label>
                
                <!-- Dermatology -->
                <label><input type="checkbox" name="degree[]" value="DDV"> DDV</label>
                <label><input type="checkbox" name="degree[]" value="DVD"> DVD</label>
                
                <!-- Additional Specializations -->
                <label><input type="checkbox" name="degree[]" value="DLO"> DLO (ENT)</label>
                <label><input type="checkbox" name="degree[]" value="DCH"> DCH (Child Health)</label>
                <label><input type="checkbox" name="degree[]" value="DGO"> DGO (Obstetrics)</label>
                <label><input type="checkbox" name="degree[]" value="DOrtho"> DOrtho (Orthopedics)</label>
                <label><input type="checkbox" name="degree[]" value="DD"> DD (Dermatology)</label>
                <label><input type="checkbox" name="degree[]" value="DOMS"> DOMS (Ophthalmology)</label>
                
                <!-- Fellowship -->
                <label><input type="checkbox" name="degree[]" value="FACS"> FACS</label>
                <label><input type="checkbox" name="degree[]" value="FRCP"> FRCP</label>
                <label><input type="checkbox" name="degree[]" value="FICS"> FICS</label>
            </div>
            <div class="validation-error" id="degreeError">Please select at least one degree</div>
        </div>
    