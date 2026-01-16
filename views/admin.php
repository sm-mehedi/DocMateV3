<?php
session_start();
require "../app/config/database.php";

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: ../public/login.php");
    exit;
}
require "../app/config/database.php";
