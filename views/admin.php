<?php
session_start();
require "../app/config/database.php";

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: ../public/login.php");
    exit;
}
$errors = [];
$success = '';
$medicineError = '';
$medicineSuccess = '';

// Medicine JSON file
$medicineFile = __DIR__ . "/../public/assets/data/medicines.json";
$medicineJson = file_exists($medicineFile) ? file_get_contents($medicineFile) : "[]";
