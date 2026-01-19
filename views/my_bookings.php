<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

// Get patient info
$q = $conn->prepare("SELECT id, name, phone FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

$doctorModel  = new Doctor($conn);
$bookingModel = new Booking($conn);

$doctors = $doctorModel->all();

$activeBookedDoctors = array_column(
    $bookingModel->myActiveBookings($patient['id']),
    'id'  // this is the doctor ID, because SELECT d.* returns d.id
);



?>