<?php
// public/mark-seen.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$patient_id = $_POST['patient_id'] ?? null;

if(!$patient_id) {
    echo json_encode(['success'=>false, 'message'=>'Invalid patient ID']);
    exit;
}

$bookingModel = new Booking($conn);

// DELETE the booking
$res = $bookingModel->deleteByDoctor($patient_id, $_SESSION['user_id']);

echo json_encode([
    'success' => $res,
    'message' => $res ? 'Patient marked as seen and booking deleted' : 'Failed to delete booking'
]);