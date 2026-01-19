<?php
class Booking {
    private $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }