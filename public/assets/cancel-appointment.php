<?php
// public/cancel-appointment.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$booking = new Booking($conn);
$patient_id = $_POST['patient_id'] ?? null;

if(!$patient_id) exit(json_encode(['success'=>false,'message'=>'Invalid patient.']));