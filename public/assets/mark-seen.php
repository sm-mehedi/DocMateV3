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