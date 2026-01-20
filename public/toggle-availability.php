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
$result = $doctor->toggleAvailability($_SESSION['user_id']);

if ($result) {
    $doc = $doctor->getByUser($_SESSION['user_id']);
    echo json_encode([
        'success' => true,
        'is_available' => $doc['is_available']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Toggle failed']);
}
exit;