<?php
// public/remove_booking.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;

if(!$booking_id) {
    echo json_encode(['success'=>false, 'message'=>'Invalid booking ID']);
    exit;
}

// Get patient
$q = $conn->prepare("SELECT id FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

if(!$patient) {
    echo json_encode(['success'=>false, 'message'=>'Patient not found']);
    exit;
}

// DELETE the cancelled booking
$bookingModel = new Booking($conn);
$res = $bookingModel->deleteBooking($booking_id, $patient['id']);

echo json_encode([
    'success' => $res,
    'message' => $res ? 'Booking permanently deleted' : 'Failed to delete booking'
]);