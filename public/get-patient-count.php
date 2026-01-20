<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Booking.php";

$doctor_id = $_GET['doctor_id'] ?? null;
header('Content-Type: application/json');

if(!$doctor_id){
    echo json_encode(['count'=>0]);
    exit;
}

$booking = new Booking($conn);
$patients = $booking->forDoctor($doctor_id);

echo json_encode(['count'=>count($patients)]);
