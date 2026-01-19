<?php
class Booking {
    private $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }
     public function hasBooking($doctor_id, $patient_id){
        $q = $this->conn->prepare(
            "SELECT * FROM bookings WHERE doctor_id=? AND patient_id=? AND status='booked'"
        );
        $q->execute([$doctor_id, $patient_id]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }