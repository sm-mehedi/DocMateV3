<?php
// public/remove_booking.php
require "../app/config/database.php";
require "../app/models/Booking.php";
require "../app/core/auth.php";

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;

if(!$booking_id) {
    echo json_encode(['success'=>false, 'message'=>'Invalid booking ID']);
    exit;
}