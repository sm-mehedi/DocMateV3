<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

$doctorModel = new Doctor($conn);
$booking = new Booking($conn);

$doc = $doctorModel->getByUser($_SESSION['user_id']);

$patients = $booking->forDoctor($_SESSION['user_id']);

$today = date('D'); 

$patientCount = count($patients);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/doctor.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#welcome" class="logo">
                <span>Doctor Portal</span>
            </a>
            <div class="nav-links">
                <a href="#welcome" class="nav-link active">Dashboard</a>
                <a href="#availability" class="nav-link">Availability</a>
                <a href="#patients" class="nav-link">Patients</a>
                <a href="#profile" class="nav-link">Profile</a>
                <a href="../public/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

<div class="scroll-top" onclick="scrollToTop()">↑</div>

    <div class="container">
        <!-- Welcome Section -->
        <section id="welcome" class="section">
            <h2>Welcome, Dr. <?= htmlspecialchars($doc['name']) ?>!</h2>
            <p>Specialization: <?= htmlspecialchars($doc['degree']) ?></p>
            <p>BMDC: <?= htmlspecialchars($doc['bmdc'] ?? 'N/A') ?></p>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $patientCount ?></div>
                    <div class="stat-label">Active Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $patientCount ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <span class="availability-status <?= $doc['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                            <?= $doc['is_available'] ? 'Available' : 'Offline' ?>
                        </span>
                    </div>
                    <div class="stat-label">Current Status</div>
                </div>
            </div>
        </section>

