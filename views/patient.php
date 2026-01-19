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