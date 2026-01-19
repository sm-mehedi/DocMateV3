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
 <!-- Availability Management -->
        <section id="availability" class="section">
            <h3>📅 Availability Management</h3>
            
            <div class="form-group">
                <label>Current Status:</label>
                <div>
                    <span class="availability-status <?= $doc['is_available'] ? 'status-available' : 'status-unavailable' ?>" id="availability-status">
                        <?= $doc['is_available'] ? '🟢 Available' : '🔴 Offline' ?>
                    </span>
                    <button id="toggle-availability" class="btn <?= $doc['is_available'] ? 'btn-warning' : 'btn-success' ?>">
                        <?= $doc['is_available'] ? 'Go Offline' : 'Go Online' ?>
                    </button>
                </div>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #dee2e6;">

            <h4>Update Schedule</h4>
            <form id="update-availability-form">
                <div class="form-group">
                    <label>Available Days</label>
                    <div class="checkbox-group">
                        <?php
                        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                        $selected = explode(',', $doc['available_days']);
                        foreach ($days as $d):
                            $highlight = ($d === $today) ? 'today-checkbox' : '';
                        ?>
                            <label class="<?= $highlight ?>">
                                <input type="checkbox" name="days[]" value="<?= $d ?>"
                                    <?= in_array($d, $selected) ? 'checked' : '' ?>>
                                <?= $d ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

