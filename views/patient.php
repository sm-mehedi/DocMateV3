<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Booking.php";

$q = $conn->prepare("SELECT id, name FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

$bookingModel = new Booking($conn);

// Get all bookings including cancelled ones with cancellation info
$allBookings = $bookingModel->myAllBookingsWithCancelInfo($patient['id']);

// Get only active bookings for button logic
$activeBookings = array_column(
    array_filter($allBookings, fn($b) => $b['status'] === 'booked'),
    'booking_id'
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link rel="stylesheet" href="../public/assets/css/patient_dashboard.css">
    
</head>
<body>
<nav>
    <div class="nav-left">Patient View</div>
    <div class="nav-right">
        <a href="./patient.php">My Bookings</a>
        <a href="./my_bookings.php">Doctors</a>
        <a href="./medicines.php">Medicines</a>

        <div class="dropdown">
            <?= htmlspecialchars($patient['name']) ?>
            <div class="dropdown-content">
                <a href="../public/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
<h2>My Booked Doctors</h2>

<?php if(empty($allBookings)): ?>
    <p>You have no booked doctors yet.</p>
<?php else: ?>
    <div class="cards">
       <?php foreach($allBookings as $d): 
            $isCancelled = ($d['status'] === 'cancelled');
            $doctorCancelled = ($d['doctor_cancelled'] ?? 0) == 1;
            $patientUnbooked = ($d['patient_unbooked'] ?? 0) == 1;
       ?>
    <div class="card" id="booking-card-<?= $d['booking_id'] ?>">
        <h3><?= htmlspecialchars($d['name']) ?></h3>
        <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone']) ?></p>
        <p><strong>Degree:</strong> <?= htmlspecialchars($d['degree']) ?></p>
        <p><strong>BMDC:</strong> <?= htmlspecialchars($d['bmdc'] ?? 'N/A') ?></p>
        <p><strong>Chamber:</strong> <?= htmlspecialchars($d['chamber'] ?? 'N/A') ?></p>
        <p><strong>Available Days:</strong> <?= htmlspecialchars($d['available_days']) ?></p>
        <p><strong>Available Time:</strong> <?= htmlspecialchars($d['available_time']) ?></p>
        <p><strong>Preferred Day:</strong> <?= htmlspecialchars($d['preferred_day'] ?? 'N/A') ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($d['description'] ?? '') ?></p>
        <p><strong>Status:</strong> 
            <span style="color: <?= $d['status'] === 'booked' ? 'green' : ($d['status'] === 'cancelled' ? 'red' : 'orange') ?>;">
                <?= ucfirst($d['status']) ?>
            </span>
        </p>

       <?php if($isCancelled): ?>
            <div class="cancelled-info <?= $doctorCancelled ? 'doctor-cancelled' : ($patientUnbooked ? 'patient-cancelled' : '') ?>">
                <?php if($doctorCancelled): ?>
                    <strong>⚠️ Cancelled by Doctor</strong>
                    <p>This appointment was cancelled by the doctor.</p>
                <?php elseif($patientUnbooked): ?>
                    <strong>ℹ️ Cancelled by You</strong>
                    <p>You cancelled this appointment.</p>
                <?php else: ?>
                    <strong>⚠️ Appointment Cancelled</strong>
                    <p>This appointment has been cancelled.</p>
                <?php endif; ?>
            </div>
            
            <button type="button" onclick="removeBookingCard(<?= $d['booking_id'] ?>)" class="remove-btn">
                Remove from View
            </button>
        <?php elseif($d['status'] === 'booked'): ?>
            <button type="button" onclick="unbookDoctor(<?= $d['booking_id'] ?>)" class="unbook-btn">
                Cancel Appointment
            </button>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
</div>


<footer class="footer">
    <div class="footer-content">
        <p>© <?= date('Y') ?> DocMate - Patient Portal</p>
        <p><?= htmlspecialchars($patient['name']) ?> | Member Since: <?= date('F Y') ?></p>
        <p style="font-size: 12px; margin-top: 10px; color: #bdc3c7;">
        </p>
    </div>
</footer>