<?php
// public/unbook.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;
$doctor_id = $data['doctor_id'] ?? null;

// Get patient
$q = $conn->prepare("SELECT id FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

$bookingModel = new Booking($conn);

if($booking_id) {
    // Verify the booking belongs to this patient
    $check = $conn->prepare("SELECT id FROM bookings WHERE id=? AND patient_id=? AND status='booked'");
    $check->execute([$booking_id, $patient['id']]);
    
    if($check->rowCount() === 0) {
        echo json_encode(['success'=>false, 'message'=>'Booking not found or already cancelled']);
        exit;
    }
    
    // Update booking to mark as cancelled by patient
    $stmt = $conn->prepare(
        "UPDATE bookings 
         SET status='cancelled', patient_unbooked=1, cancelled_at=NOW() 
         WHERE id=?"
    );
    $res = $stmt->execute([$booking_id]);
    
} elseif($doctor_id && $patient) {
    // Use doctor ID method (existing logic)
    $res = $bookingModel->unbook($doctor_id, $patient['id']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Invalid request']);
    exit;
}

echo json_encode(['success' => $res]);