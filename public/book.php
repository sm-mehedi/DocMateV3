<?php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php"; 
$q = $conn->prepare("SELECT id FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(400);
    echo json_encode(['error'=>'Patient not found']);
    exit;
}

// Get doctor ID from POSTed JSON
$data = json_decode(file_get_contents("php://input"), true);
$doctor_id     = $data['doctor'] ?? null;
$preferred_day = $data['preferred_day'] ?? null;

if(!$doctor_id || !$preferred_day){
    http_response_code(400);
    echo json_encode(['error'=>'Missing booking data']);
    exit;
}


$booking = new Booking($conn);

if (!$booking->book($doctor_id, $patient['id'], $preferred_day)) {

    http_response_code(400);
    echo json_encode(['error'=>'Already booked']);
    exit;
}

echo json_encode(['success'=>true]);