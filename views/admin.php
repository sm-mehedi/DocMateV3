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
require "../app/config/database.php";

if(isset($_POST['add_user'])){
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $errorsAdd = [];

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errorsAdd['email'] = "Valid email required!";
    if(!$password) $errorsAdd['password'] = "Password required!";

    if($role === 'patient'){
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $health_issues = trim($_POST['health_issues']);
        $emergency = trim($_POST['emergency']);
        $nid = trim($_POST['nid']);

        if(!$name) $errorsAdd['name'] = "Name required!";
        if(!preg_match('/^\d{10,15}$/', $phone)) $errorsAdd['phone'] = "Phone must be 10-15 digits!";
        if(!$address) $errorsAdd['address'] = "Address required!";
        if(!$health_issues) $errorsAdd['health_issues'] = "Health issues required!";
        if(!$emergency || !preg_match('/^\d{10,15}$/', $emergency)) $errorsAdd['emergency'] = "Emergency must be 10-15 digits!";
        if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $errorsAdd['nid'] = "NID must be 10-17 digits!";
    }


    if($role === 'doctor'){
        $name = trim($_POST['name']);
        $degree = trim($_POST['degree']);
        $phone = trim($_POST['phone']);
        $bmdc = trim($_POST['bmdc']);
        $nid = trim($_POST['nid']);
        $address = trim($_POST['address']);
        $chamber = trim($_POST['chamber']);
        $available_days = trim($_POST['available_days']);
        $available_time = trim($_POST['available_time']);
        $is_available = $_POST['is_available'] ?? 0;
        $description = trim($_POST['description']);


        if(!$name) $errorsAdd['name'] = "Name required!";
        if(!$degree) $errorsAdd['degree'] = "Degree required!";
        if(!preg_match('/^\d{10,15}$/', $phone)) $errorsAdd['phone'] = "Phone must be 10-15 digits!";
        if(!$bmdc) $errorsAdd['bmdc'] = "BMDC required!";
        if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $errorsAdd['nid'] = "NID must be 10-17 digits!";
        if(!$address) $errorsAdd['address'] = "Address required!";
        if(!$chamber) $errorsAdd['chamber'] = "Chamber required!";
        if(!$available_days) $errorsAdd['available_days'] = "Available days required!";
        if(!$available_time) $errorsAdd['available_time'] = "Available time required!";
        if(!$description) $errorsAdd['description'] = "Description required!";
    }