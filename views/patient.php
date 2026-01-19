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
<html lang="en">
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
    
</body>
</html>


