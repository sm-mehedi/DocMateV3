<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$doctor = new Doctor($conn);

$days = $_POST['days'] ?? [];
$time = trim($_POST['time'] ?? '');

// Validate days
$valid_days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$days = array_intersect($days, $valid_days);

$result = $doctor->updateAvailability($_SESSION['user_id'], $days, $time);

if ($result) {
    echo json_encode(['success' => true, 'days' => $days, 'time' => $time]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
exit;