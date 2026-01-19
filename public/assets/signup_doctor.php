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
     <!-- Hidden field for formatted time -->
        <input type="hidden" name="available_time" id="available_time">

        <!-- Professional Description -->
        <div class="form-group">
            <label>Professional Description</label>
            <textarea name="desc" id="desc" placeholder="Brief description of your expertise, specialization, and experience..."></textarea>
        </div>

        <!-- Security -->
        <div class="form-row">
            <div class="form-group">
                <label class="required">Password</label>
                <input type="password" name="password" id="password" required minlength="6">
                <div class="validation-error" id="passwordError">Password must be at least 6 characters</div>
            </div>
            <div class="form-group">
                <label class="required">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <div class="validation-error" id="confirmPasswordError">Passwords do not match</div>
            </div>
        </div>

        <button type="submit">Register as Doctor</button>
    </form>

    <div class="back-link">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

<script>
function validateForm() {
    let isValid = true;
    
    // Clear previous errors
    document.querySelectorAll('.validation-error').forEach(el => el.style.display = 'none');
    
    // Required fields validation
    const requiredFields = ['name', 'email', 'phone', 'bmdc', 'nid', 'address', 'chamber', 'password', 'confirm_password'];
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            document.getElementById(field + 'Error').style.display = 'block';
            isValid = false;
        }
    });
    
    // Email validation
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
        document.getElementById('emailError').style.display = 'block';
        isValid = false;
    }
 // Phone validation (Bangladeshi format)
    const phone = document.getElementById('phone');
    const phoneRegex = /^01[3-9]\d{8}$/;
    if (!phoneRegex.test(phone.value)) {
        document.getElementById('phoneError').textContent = 'Please enter a valid Bangladeshi phone number (01XXXXXXXXX)';
        document.getElementById('phoneError').style.display = 'block';
        isValid = false;
    }
    
    // Degree validation
    const degrees = document.querySelectorAll('input[name="degree[]"]:checked');
    if (degrees.length === 0) {
        document.getElementById('degreeError').style.display = 'block';
        isValid = false;
    }
    
    // Days validation
    const days = document.querySelectorAll('input[name="days[]"]:checked');
    if (days.length === 0) {
        document.getElementById('daysError').style.display = 'block';
        isValid = false;
    }
   // Time validation
    const timeFrom = document.getElementById('time_from').value;
    const timeTo = document.getElementById('time_to').value;
    if (!timeFrom || !timeTo) {
        if (!timeFrom) document.getElementById('timeFromError').style.display = 'block';
        if (!timeTo) document.getElementById('timeToError').style.display = 'block';
        isValid = false;
    } else if (timeFrom >= timeTo) {
        document.getElementById('timeToError').textContent = 'End time must be after start time';
        document.getElementById('timeToError').style.display = 'block';
        isValid = false;
    }
    
    // Format time for database
    if (timeFrom && timeTo) {
        const formatTime = (timeStr) => {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        };
        document.getElementById('available_time').value = 
            `${formatTime(timeFrom)} - ${formatTime(timeTo)}`;
    }
    
    // Password validation
    const password = document.getElementById('password');
    if (password.value.length < 6) {
        document.getElementById('passwordError').style.display = 'block';
        isValid = false;
    }
    
    // Confirm password
    const confirmPassword = document.getElementById('confirm_password');
    if (password.value !== confirmPassword.value) {
        document.getElementById('confirmPasswordError').style.display = 'block';
        isValid = false;
    }
    
    return isValid;
}
// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0 && !value.startsWith('01')) {
            value = '01' + value.substring(2);
        }
        if (value.length > 11) value = value.substring(0, 11);
        e.target.value = value;
    });
    
    // Time formatting
    const timeInputs = document.querySelectorAll('input[type="time"]');
    timeInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fromTime = document.getElementById('time_from').value;
            const toTime = document.getElementById('time_to').value;
            if (fromTime && toTime && fromTime >= toTime) {
                document.getElementById('timeToError').textContent = 'End time must be after start time';
                document.getElementById('timeToError').style.display = 'block';
            } else {
                document.getElementById('timeToError').style.display = 'none';
            }
        });
    });
    
