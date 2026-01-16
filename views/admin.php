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
    if(empty($errorsAdd)){
        // Insert into users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->execute([$email]);
    if($stmt->rowCount() > 0){
        $errorsAdd['email'] = "Email already exists!";
    } else {
        $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)")->execute([$email, $password, $role]);
        $user_id = $conn->lastInsertId();

        if($role === 'patient'){
            $conn->prepare("INSERT INTO patients (user_id, name, phone, address, health_issues, emergency, nid) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$user_id, $name, $phone, $address, $health_issues, $emergency, $nid]);
        } elseif($role === 'doctor'){
            $conn->prepare("INSERT INTO doctors (user_id, name, degree, phone, bmdc, nid, address, chamber, available_days, available_time, is_available, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$user_id, $name, $degree, $phone, $bmdc, $nid, $address, $chamber, $available_days, $available_time, $is_available, $description]);
        }
        $successAdd = "✅ $role added successfully!";
    }
}
}

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
<div class="container">
    <h1>Admin Dashboard</h1>
    <div class="counts">
        <div class="count-box">Total Patients: <?= $pCount ?></div>
        <div class="count-box">Total Doctors: <?= $dCount ?></div>
    </div>

    <div class="toggle-buttons">
        <button id="showPatients">Patients</button>
        <button id="showDoctors">Doctors</button>
    </div>

    <span>Search</span>
<br>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>


div class="search-bar">
<input type="text" id="searchInput" placeholder="Search by name...">
</div>

<div id="patientsSection" class="cards-section">
<?php foreach($patients as $p): ?>
<div class="card">
    <h3><?= htmlspecialchars($p['name']) ?></h3>
    <p><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($p['phone'] ?? '-') ?></p>
    <p><strong>Health Issues:</strong> <?= htmlspecialchars($p['health_issues'] ?? '-') ?></p>
    <p><strong>Emergency Contact:</strong> <?= htmlspecialchars($p['emergency'] ?? '-') ?></p>
    <p><strong>NID:</strong> <?= htmlspecialchars($p['nid'] ?? '-') ?></p>
    <a href="?delete_user=<?= $p['user_id'] ?>" class="delete-btn" onclick="return confirm('Delete this patient?')">Delete</a>
</div>
<?php endforeach; ?>
</div>
<div id="doctorsSection" class="cards-section" style="display:none;">
        <?php foreach($doctors as $d): ?>
        <div class="card">
            <h3><?= htmlspecialchars($d['name']) ?></h3>
            <p><strong>Email:</strong> <?= htmlspecialchars($d['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone'] ?? '-') ?></p>
            <p><strong>Degree:</strong> <?= htmlspecialchars($d['degree'] ?? '-') ?></p>
            <p><strong>BMDC:</strong> <?= htmlspecialchars($d['bmdc'] ?? '-') ?></p>
            <p><strong>NID:</strong> <?= htmlspecialchars($d['nid'] ?? '-') ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($d['address'] ?? '-') ?></p>
            <p><strong>Chamber:</strong> <?= htmlspecialchars($d['chamber'] ?? '-') ?></p>
            <p><strong>Available Days:</strong> <?= htmlspecialchars($d['available_days'] ?? '-') ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($d['description'] ?? '-') ?></p>
            <a href="?delete_user=<?= $d['user_id'] ?>" class="delete-btn" onclick="return confirm('Delete this doctor?')">Delete</a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="instructions" style="background:#f0f8ff; padding:10px; border-left:4px solid #007BFF; margin-bottom:10px;">
    <strong>Instructions for Updating User Info:</strong>
    <ul>
        <li>Fill in all fields you want to update.</li>
        <li>Email must be valid and unique.</li>
        <li>Phone & Emergency must be 10-15 digits.</li>
        <li>NID must be 10-17 digits.</li>
        <li>Leave password empty if you don't want to change it.</li>
        <li>All fields marked required must not be empty.</li>
        <li>Click the <strong>Update</strong> button to save changes.</li>
    </ul>
</div>
    
<h2>Update User Info</h2>
<?php if(!empty($success)): ?>
    <p style="color:green;font-weight:bold;"><?= $success ?></p>
<?php endif; ?>

<div class="toggle-buttons">
    <button id="showPatientUsers">Patients</button>
    <button id="showDoctorUsers">Doctors</button>
</div>

<div id="patientUsers" class="cards-section">
    <?php foreach($patients as $p): ?>
    <div class="card">
        <form method="POST" >
            <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Name">
            <small style="color:red"><?= $errors['name'] ?? '' ?></small>

            <input type="email" name="email" value="<?= htmlspecialchars($p['email']) ?>" placeholder="Email">
            <small style="color:red"><?= $errors['email'] ?? '' ?></small>

            <input type="password" name="password" placeholder="New Password (leave empty to keep)">
            
            <input type="text" name="phone" value="<?= htmlspecialchars($p['phone']) ?>" placeholder="Phone">
            <small style="color:red"><?= $errors['phone'] ?? '' ?></small>

            <input type="text" name="address" value="<?= htmlspecialchars($p['address']) ?>" placeholder="Address">
            <small style="color:red"><?= $errors['address'] ?? '' ?></small>

            <input type="text" name="health_issues" value="<?= htmlspecialchars($p['health_issues']) ?>" placeholder="Health Issues">
            <small style="color:red"><?= $errors['health_issues'] ?? '' ?></small>

            <input type="text" name="emergency" value="<?= htmlspecialchars($p['emergency']) ?>" placeholder="Emergency Contact">
            <small style="color:red"><?= $errors['emergency'] ?? '' ?></small>

            <input type="text" name="nid" value="<?= htmlspecialchars($p['nid']) ?>" placeholder="NID">
            <small style="color:red"><?= $errors['nid'] ?? '' ?></small>

            <button name="update_patient">Update Patient</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<div id="doctorUsers" class="cards-section" style="display:none;">
    <?php foreach($doctors as $d): ?>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">

            <input type="text" name="name" value="<?= htmlspecialchars($d['name']) ?>" placeholder="Name">
            <small style="color:red"><?= $errors['name'] ?? '' ?></small>

            <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" placeholder="Email">
            <small style="color:red"><?= $errors['email'] ?? '' ?></small>

            <input type="password" name="password" placeholder="New Password (leave empty to keep)">

            <input type="text" name="degree" value="<?= htmlspecialchars($d['degree']) ?>" placeholder="Degree">
            <small style="color:red"><?= $errors['degree'] ?? '' ?></small>

            <input type="text" name="phone" value="<?= htmlspecialchars($d['phone']) ?>" placeholder="Phone">
            <small style="color:red"><?= $errors['phone'] ?? '' ?></small>

            <input type="text" name="bmdc" value="<?= htmlspecialchars($d['bmdc']) ?>" placeholder="BMDC">
            <small style="color:red"><?= $errors['bmdc'] ?? '' ?></small>

            <input type="text" name="nid" value="<?= htmlspecialchars($d['nid']) ?>" placeholder="NID">
            <small style="color:red"><?= $errors['nid'] ?? '' ?></small>

            <input type="text" name="address" value="<?= htmlspecialchars($d['address']) ?>" placeholder="Address">
            <small style="color:red"><?= $errors['address'] ?? '' ?></small>

            <input type="text" name="chamber" value="<?= htmlspecialchars($d['chamber']) ?>" placeholder="Chamber">
            <small style="color:red"><?= $errors['chamber'] ?? '' ?></small>

            <input type="text" name="available_days" value="<?= htmlspecialchars($d['available_days']) ?>" placeholder="Available Days">
            <small style="color:red"><?= $errors['available_days'] ?? '' ?></small>

            <input type="text" name="available_time" value="<?= htmlspecialchars($d['available_time']) ?>" placeholder="Available Time">
            <small style="color:red"><?= $errors['available_time'] ?? '' ?></small>

            <select name="is_available">
                <option value="1" <?= $d['is_available'] ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= !$d['is_available'] ? 'selected' : '' ?>>No</option>
            </select>

            <input type="text" name="description" value="<?= htmlspecialchars($d['description']) ?>" placeholder="Description">
            <small style="color:red"><?= $errors['description'] ?? '' ?></small>

            <button name="update_doctor">Update Doctor</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<hr>
<hr>
<div class="instructions" style="background:#f9f9e6; padding:10px; border-left:4px solid #ffc107; margin-bottom:10px;">
    <strong>Instructions for Adding New User:</strong>
    <ul>
        <li>Email must be valid and unique.</li>
        <li>Password is required.</li>
        <li>Patient fields:</li>
        <ul>
            <li>Name and Address are required.</li>
            <li>Phone & Emergency must be 10-15 digits.</li>
            <li>NID must be 10-17 digits.</li>
            <li>Health Issues description is required.</li>
        </ul>
        <li>Doctor fields:</li>
        <ul>
            <li>Name, Degree, BMDC, Address, Chamber, Available Days & Time, and Description are required.</li>
            <li>Phone must be 10-15 digits.</li>
     <div id="doctorUsers" class="cards-section" style="display:none;">
    <?php foreach($doctors as $d): ?>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">

            <input type="text" name="name" value="<?= htmlspecialchars($d['name']) ?>" placeholder="Name">
            <small style="color:red"><?= $errors['name'] ?? '' ?></small>

            <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" placeholder="Email">
            <small style="color:red"><?= $errors['email'] ?? '' ?></small>

            <input type="password" name="password" placeholder="New Password (leave empty to keep)">

            <input type="text" name="degree" value="<?= htmlspecialchars($d['degree']) ?>" placeholder="Degree">
            <small style="color:red"><?= $errors['degree'] ?? '' ?></small>

            <input type="text" name="phone" value="<?= htmlspecialchars($d['phone']) ?>" placeholder="Phone">
            <small style="color:red"><?= $errors['phone'] ?? '' ?></small>

            <input type="text" name="bmdc" value="<?= htmlspecialchars($d['bmdc']) ?>" placeholder="BMDC">
            <small style="color:red"><?= $errors['bmdc'] ?? '' ?></small>

            <input type="text" name="nid" value="<?= htmlspecialchars($d['nid']) ?>" placeholder="NID">
            <small style="color:red"><?= $errors['nid'] ?? '' ?></small>

            <input type="text" name="address" value="<?= htmlspecialchars($d['address']) ?>" placeholder="Address">
            <small style="color:red"><?= $errors['address'] ?? '' ?></small>

            <input type="text" name="chamber" value="<?= htmlspecialchars($d['chamber']) ?>" placeholder="Chamber">
            <small style="color:red"><?= $errors['chamber'] ?? '' ?></small>

            <input type="text" name="available_days" value="<?= htmlspecialchars($d['available_days']) ?>" placeholder="Available Days">
            <small style="color:red"><?= $errors['available_days'] ?? '' ?></small>

            <input type="text" name="available_time" value="<?= htmlspecialchars($d['available_time']) ?>" placeholder="Available Time">
            <small style="color:red"><?= $errors['available_time'] ?? '' ?></small>

            <select name="is_available">
                <option value="1" <?= $d['is_available'] ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= !$d['is_available'] ? 'selected' : '' ?>>No</option>
            </select>

            <input type="text" name="description" value="<?= htmlspecialchars($d['description']) ?>" placeholder="Description">
            <small style="color:red"><?= $errors['description'] ?? '' ?></small>

            <button name="update_doctor">Update Doctor</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
       <li>NID must be 10-17 digits.</li>
            <li>Is Available must be Yes or No.</li>
        </ul>
        <li>Click <strong>Add</strong> button to save the new user.</li>
    </ul>
</div>


<h2>Add New User</h2>
<?php if(!empty($successAdd)): ?>
    <p style="color:green;font-weight:bold;"><?= $successAdd ?></p>
<?php endif; ?>

<div class="toggle-buttons">
    <button id="addAdminBtn">Admin</button>
    <button id="addPatientBtn">Patient</button>
    <button id="addDoctorBtn">Doctor</button>
</div>
<div id="addAdmin" class="cards-section">
    <form method="POST">
        <input type="hidden" name="role" value="admin">
        <input type="email" name="email" placeholder="Email">
        <small style="color:red"><?= $errorsAdd['email'] ?? '' ?></small>
        <input type="password" name="password" placeholder="Password">
        <small style="color:red"><?= $errorsAdd['password'] ?? '' ?></small>
        <button name="add_user">Add Admin</button>
    </form>
</div>
