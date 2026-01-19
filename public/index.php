<?php
require "../app/config/database.php";
require "../app/core/auth.php";

if($_SESSION['role']=='admin') header("Location: ../views/admin.php");
if($_SESSION['role']=='doctor') header("Location: ../views/doctor.php");
if($_SESSION['role']=='patient') header("Location: ../views/patient.php");
