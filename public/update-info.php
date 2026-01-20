<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";

header('Content-Type: application/json');

$doctor = new Doctor($conn);

$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$chamber = $_POST['chamber'] ?? '';

$success = $doctor->updateInfo($_SESSION['user_id'], [
    'phone' => $phone,
    'address' => $address,
    'chamber' => $chamber
]);

if($success){
    echo json_encode(['success'=>true, 'message'=>'Info updated!']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Update failed']);
}
