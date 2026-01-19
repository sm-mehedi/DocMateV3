<?php
// public/cancel-appointment.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$booking = new Booking($conn);
$patient_id = $_POST['patient_id'] ?? null;

if(!$patient_id) exit(json_encode(['success'=>false,'message'=>'Invalid patient.']));

// Cancel the appointment (doctor cancels)
$q = $conn->prepare(
    "UPDATE bookings 
     SET status='cancelled', doctor_cancelled=1, cancelled_at=NOW() 
     WHERE patient_id=? AND doctor_id=(SELECT id FROM doctors WHERE user_id=?) AND status='booked'"
);

$res = $q->execute([$patient_id, $_SESSION['user_id']]);

echo json_encode(['success'=>$res, 'message' => $res ? 'Appointment cancelled successfully' : 'Failed to cancel appointment']);