<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

$doctorModel = new Doctor($conn);
$booking = new Booking($conn);

$doc = $doctorModel->getByUser($_SESSION['user_id']);

$patients = $booking->forDoctor($_SESSION['user_id']);

$today = date('D'); 

$patientCount = count($patients);
?>