<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=docmate", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("Database connection failed: " . $e->getMessage());
}

// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
