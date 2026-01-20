<?php
session_start();
require "../app/config/database.php";

// Only admin can access
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: ../public/login.php");
    exit;
}

// Get current admin info
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize messages
$errors = [];
$success = '';
$medicineError = '';
$medicineSuccess = '';
$appointmentSuccess = '';
$appointmentError = '';
$profileSuccess = '';
$profileError = '';
$bookingSuccess = '';
$bookingError = '';

// Handle Admin Profile Update
if(isset($_POST['update_admin_profile'])){
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $profileErrors = [];
    
    // Validate email
    if(!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors['email'] = "Valid email required!";
    }
    
    // Check if email already exists (excluding current admin)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$new_email, $admin_id]);
    if($stmt->rowCount() > 0){
        $profileErrors['email'] = "Email already in use by another account!";
    }
    
    // Validate passwords if provided
    if(!empty($new_password)) {
        if(strlen($new_password) < 6) {
            $profileErrors['password'] = "Password must be at least 6 characters!";
        }
        if($new_password !== $confirm_password) {
            $profileErrors['confirm_password'] = "Passwords do not match!";
        }
    }
    
    if(empty($profileErrors)){
        try {
            if(!empty($new_password)) {
                // Update with new password
                $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_email, $new_password, $admin_id]);
            } else {
                // Update only email
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $admin_id]);
            }
            
            // Update session email
            $_SESSION['email'] = $new_email;
            
            // Update local admin info
            $admin['email'] = $new_email;
            
            $profileSuccess = "Profile updated successfully!";
        } catch (Exception $e) {
            $profileError = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $profileError = implode(" ", array_values($profileErrors));
    }
}

// Handle Admin Booking Appointment
if(isset($_POST['admin_book_appointment'])){
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $preferred_day = trim($_POST['preferred_day'] ?? '');
    
    if($patient_id <= 0 || $doctor_id <= 0 || empty($preferred_day)){
        $bookingError = "All fields are required!";
    } else {
        // Check if patient and doctor exist
        $stmt = $conn->prepare("SELECT id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        if($stmt->rowCount() === 0){
            $bookingError = "Patient not found!";
        }
        
        $stmt = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        if($stmt->rowCount() === 0){
            $bookingError = "Doctor not found!";
        }
        
        if(!in_array($preferred_day, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])){
            $bookingError = "Invalid day selected!";
        }
        
        if(empty($bookingError)){
            // Check for existing booking
            $stmt = $conn->prepare("SELECT id FROM bookings WHERE doctor_id = ? AND patient_id = ? AND status = 'booked'");
            $stmt->execute([$doctor_id, $patient_id]);
            
            if($stmt->rowCount() > 0){
                $bookingError = "Patient already has an active booking with this doctor!";
            } else {
                // Check for cancelled booking to reuse
                $stmt = $conn->prepare("SELECT id FROM bookings WHERE doctor_id = ? AND patient_id = ? AND status = 'cancelled'");
                $stmt->execute([$doctor_id, $patient_id]);
                $cancelled = $stmt->fetch(PDO::FETCH_ASSOC);
                
                try {
                    if($cancelled){
                        // Update cancelled booking
                        $stmt = $conn->prepare("UPDATE bookings SET status = 'booked', doctor_cancelled = 0, preferred_day = ?, patient_unbooked = 0, is_seen = 0 WHERE id = ?");
                        $result = $stmt->execute([$preferred_day, $cancelled['id']]);
                    } else {
                        // Create new booking
                        $stmt = $conn->prepare("INSERT INTO bookings (doctor_id, patient_id, preferred_day, status, doctor_cancelled, patient_unbooked, is_seen) VALUES (?, ?, ?, 'booked', 0, 0, 0)");
                        $result = $stmt->execute([$doctor_id, $patient_id, $preferred_day]);
                    }
                    
                    if($result) {
                        $bookingSuccess = "Appointment booked successfully!";
                        
                        // Refresh appointments list
                        $appointments = $conn->query("
                            SELECT b.*, 
                                   p.name as patient_name, 
                                   p.phone as patient_phone,
                                   d.name as doctor_name,
                                   d.degree as doctor_degree
                            FROM bookings b
                            LEFT JOIN patients p ON b.patient_id = p.id
                            LEFT JOIN doctors d ON b.doctor_id = d.id
                            ORDER BY b.id DESC
                        ")->fetchAll(PDO::FETCH_ASSOC);
                        
                        $aCount = count($appointments);
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $bookingError = "Database error: " . $errorInfo[2];
                    }
                    
                } catch (Exception $e) {
                    $bookingError = "Error booking appointment!";
                }
            }
        }
    }
}

// Medicine JSON file
$medicineFile = __DIR__ . "/../public/assets/data/medicines.json";
$medicineJson = file_exists($medicineFile) ? file_get_contents($medicineFile) : "[]";
$medicines = json_decode($medicineJson, true) ?: [];

// Handle Add User
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
            $successAdd = "$role added successfully!";
        }
    }
}

// Handle Medicine Table Update
if(isset($_POST['save_medicines_table'])){
    $newMedicines = [];
    
    if(isset($_POST['medicine'])) {
        foreach($_POST['medicine'] as $medicine) {
            $name = trim($medicine['name'] ?? '');
            $type = trim($medicine['type'] ?? '');
            $for = trim($medicine['for'] ?? '');
            $brand = trim($medicine['brand'] ?? '');
            
            if(!empty($name) && !empty($type)) {
                $newMedicines[] = [
                    'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
                    'for' => htmlspecialchars($for, ENT_QUOTES, 'UTF-8'),
                    'brand' => htmlspecialchars($brand, ENT_QUOTES, 'UTF-8')
                ];
            }
        }
    }
    
    $newJson = json_encode($newMedicines, JSON_PRETTY_PRINT);
    if(json_last_error() === JSON_ERROR_NONE){
        file_put_contents($medicineFile, $newJson);
        $medicineSuccess = "Medicines updated successfully!";
        $medicines = $newMedicines;
        $medicineJson = $newJson;
    } else {
        $medicineError = "Error saving medicines!";
    }
}

// Handle Appointment Updates
if(isset($_POST['update_appointment'])){
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $new_day = trim($_POST['preferred_day'] ?? '');
    
    if($appointment_id > 0) {
        $allowed_statuses = ['booked', 'cancelled', 'completed', 'pending'];
        $allowed_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        if(in_array($new_status, $allowed_statuses) && in_array($new_day, $allowed_days)) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, preferred_day = ? WHERE id = ?");
            $stmt->execute([$new_status, $new_day, $appointment_id]);
            
            if($stmt->rowCount() > 0) {
                $appointmentSuccess = "Appointment updated successfully!";
            } else {
                $appointmentError = "No changes made or appointment not found!";
            }
        } else {
            $appointmentError = "Invalid status or day selected!";
        }
    } else {
        $appointmentError = "Invalid appointment ID!";
    }
}

// Handle Cancel Appointment
if(isset($_GET['cancel_appointment'])){
    $appointment_id = (int)$_GET['cancel_appointment'];
    
    if($appointment_id > 0) {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', doctor_cancelled = 1 WHERE id = ?");
        $stmt->execute([$appointment_id]);
        
        if($stmt->rowCount() > 0) {
            $appointmentSuccess = "Appointment cancelled successfully!";
        }
    }
}

// Handle Delete Appointment
if(isset($_GET['delete_appointment'])){
    $appointment_id = (int)$_GET['delete_appointment'];
    
    if($appointment_id > 0) {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$appointment_id]);
        
        if($stmt->rowCount() > 0) {
            $appointmentSuccess = "Appointment deleted successfully!";
        }
    }
}

// Handle Mark as Seen
if(isset($_GET['mark_seen'])){
    $appointment_id = (int)$_GET['mark_seen'];
    
    if($appointment_id > 0) {
        $stmt = $conn->prepare("UPDATE bookings SET is_seen = 1 WHERE id = ?");
        $stmt->execute([$appointment_id]);
        
        if($stmt->rowCount() > 0) {
            $appointmentSuccess = "Appointment marked as seen!";
        }
    }
}

// Handle Delete User
if(isset($_GET['delete_user'])){
    $id = (int)$_GET['delete_user'];
    $conn->prepare("DELETE FROM patients WHERE user_id=?")->execute([$id]);
    $conn->prepare("DELETE FROM doctors WHERE user_id=?")->execute([$id]);
    $conn->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    header("Location: admin.php");
    exit;
}

// Update Patient
if(isset($_POST['update_patient'])){
    $id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $health_issues = trim($_POST['health_issues']);
    $emergency = trim($_POST['emergency']);
    $nid = trim($_POST['nid']);
    $password = trim($_POST['password']);

    if(!$name) $errors['name'] = "Name is required!";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email!";
    if(!preg_match('/^\d{10,15}$/', $phone)) $errors['phone'] = "Phone must be 10-15 digits!";
    if(!$address) $errors['address'] = "Address is required!";
    if(!$health_issues) $errors['health_issues'] = "Health issues required!";
    if(!$emergency || !preg_match('/^\d{10,15}$/', $emergency)) $errors['emergency'] = "Emergency must be 10-15 digits!";
    if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $errors['nid'] = "NID must be 10-17 digits!";

    if(empty($errors)){
        if($password){
            $conn->prepare("UPDATE users SET email=?, password=? WHERE id=?")->execute([$email,$password,$id]);
        } else {
            $conn->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,$id]);
        }
        $conn->prepare("UPDATE patients SET name=?, phone=?, address=?, health_issues=?, emergency=?, nid=? WHERE user_id=?")
             ->execute([$name,$phone,$address,$health_issues,$emergency,$nid,$id]);
        $success = "Patient updated successfully!";
    }
}

// Update Doctor
if(isset($_POST['update_doctor'])){
    $id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $degree = trim($_POST['degree']);
    $bmdc = trim($_POST['bmdc']);
    $nid = trim($_POST['nid']);
    $address = trim($_POST['address']);
    $chamber = trim($_POST['chamber']);
    $available_days = trim($_POST['available_days']);
    $available_time = trim($_POST['available_time']);
    $is_available = $_POST['is_available'] ?? 0;
    $description = trim($_POST['description']);
    $password = trim($_POST['password']);

    if(!$name) $errors['name'] = "Name required!";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email!";
    if(!preg_match('/^\d{10,15}$/', $phone)) $errors['phone'] = "Phone must be 10-15 digits!";
    if(!$degree) $errors['degree'] = "Degree required!";
    if(!$bmdc) $errors['bmdc'] = "BMDC required!";
    if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $errors['nid'] = "NID must be 10-17 digits!";
    if(!$address) $errors['address'] = "Address required!";
    if(!$chamber) $errors['chamber'] = "Chamber required!";
    if(!$available_days) $errors['available_days'] = "Available days required!";
    if(!$available_time) $errors['available_time'] = "Available time required!";
    if(!$description) $errors['description'] = "Description required!";

    if(empty($errors)){
        if($password){
            $conn->prepare("UPDATE users SET email=?, password=? WHERE id=?")->execute([$email,$password,$id]);
        } else {
            $conn->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,$id]);
        }
        $conn->prepare("UPDATE doctors SET name=?, degree=?, phone=?, bmdc=?, nid=?, address=?, chamber=?, available_days=?, available_time=?, is_available=?, description=? WHERE user_id=?")
            ->execute([$name,$degree,$phone,$bmdc,$nid,$address,$chamber,$available_days,$available_time,$is_available,$description,$id]);
        $success = "Doctor updated successfully!";
    }
}

// Fetch all users after updates
$patients = $conn->query("SELECT p.*, u.email FROM patients p JOIN users u ON p.user_id=u.id")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $conn->query("SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id=u.id")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all appointments
$appointments = $conn->query("
    SELECT b.*, 
           p.name as patient_name, 
           p.phone as patient_phone,
           d.name as doctor_name,
           d.degree as doctor_degree
    FROM bookings b
    LEFT JOIN patients p ON b.patient_id = p.id
    LEFT JOIN doctors d ON b.doctor_id = d.id
    ORDER BY b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pCount = count($patients);
$dCount = count($doctors);
$aCount = count($appointments);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#dashboard" class="logo">DocMate Admin</a>
            <div class="nav-links">
                <a href="#dashboard" class="nav-link">Dashboard</a>
                <a href="#admin-profile" class="nav-link">My Profile</a>
                <a href="#view-users" class="nav-link">View Users</a>
                <a href="#update-users" class="nav-link">Update Users</a>
                <a href="#add-user" class="nav-link">Add User</a>
                <a href="#appointment-manager" class="nav-link">Appointments</a>
                <a href="#admin-book-appointment" class="nav-link">Book Appointment</a>
                <a href="#medicine-manager" class="nav-link">Medicines</a>
                <a href="../public/logout.php" class="nav-link" style="background: #dc3545;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <div class="centered-content">
                <div class="welcome-message">
                    <h1>Welcome, <?= htmlspecialchars($admin['email'] ?? 'Admin') ?>!</h1>
                    <p>You are logged in as Administrator. Use the navigation above to manage the system.</p>
                </div>
                <div class="counts">
                    <div class="count-box">Total Patients: <?= $pCount ?></div>
                    <div class="count-box">Total Doctors: <?= $dCount ?></div>
                    <div class="count-box">Total Appointments: <?= $aCount ?></div>
                </div>
            </div>
        </div>

        <!-- Admin Profile Section -->
        <div id="admin-profile" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Admin Profile Update:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>You can change your email address here</li>
                        <li>Leave password fields empty if you don't want to change password</li>
                        <li>If changing password, it must be at least 6 characters</li>
                        <li>Passwords must match in both fields</li>
                        <li>Email must be unique and valid</li>
                    </ul>
                </div>

                <h2>My Profile</h2>

                <?php if($profileSuccess): ?>
                    <div class="success"><?= $profileSuccess ?></div>
                <?php endif; ?>

                <?php if($profileError): ?>
                    <div class="error"><?= $profileError ?></div>
                <?php endif; ?>

                <div class="profile-form-container">
                    <div class="update-form-card" style="max-width: 600px; margin: 0 auto;">
                        <form method="POST">
                            <div class="form-grid">
                                <div style="grid-column: span 2;">
                                    <h3 style="text-align: center; margin-bottom: 15px;">Current Email: <?= htmlspecialchars($admin['email'] ?? '') ?></h3>
                                </div>
                                
                                <div style="grid-column: span 2;">
                                    <label style="display: block; margin-bottom: 5px;">New Email Address:</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" placeholder="New Email" required>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">New Password (Optional):</label>
                                    <input type="password" name="password" placeholder="Leave empty to keep current">
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">Confirm Password:</label>
                                    <input type="password" name="confirm_password" placeholder="Confirm password">
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="submit" name="update_admin_profile">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Users Section -->
        <div id="view-users" class="section">
            <div class="centered-content">
                <h2>View Users</h2>
                
                <?php if(!empty($success)): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>

                <div class="toggle-buttons">
                    <button id="showPatients">Patients</button>
                    <button id="showDoctors">Doctors</button>
                </div>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by name...">
                </div>

                <div id="patientsSection">
                    <div class="users-grid">
                        <?php foreach($patients as $p): ?>
                        <div class="user-card">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <p><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($p['phone'] ?? '-') ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($p['address'] ?? '-') ?></p>
                            <p><strong>Health Issues:</strong> <?= htmlspecialchars($p['health_issues'] ?? '-') ?></p>
                            <p><strong>Emergency Contact:</strong> <?= htmlspecialchars($p['emergency'] ?? '-') ?></p>
                            <p><strong>NID:</strong> <?= htmlspecialchars($p['nid'] ?? '-') ?></p>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="?delete_user=<?= $p['user_id'] ?>" class="btn-danger action-btn" onclick="return confirm('Delete this patient?')">Delete</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="doctorsSection" style="display:none;">
                    <div class="users-grid">
                        <?php foreach($doctors as $d): ?>
                        <div class="user-card">
                            <h3><?= htmlspecialchars($d['name']) ?></h3>
                            <p><strong>Email:</strong> <?= htmlspecialchars($d['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone'] ?? '-') ?></p>
                            <p><strong>Degree:</strong> <?= htmlspecialchars($d['degree'] ?? '-') ?></p>
                            <p><strong>BMDC:</strong> <?= htmlspecialchars($d['bmdc'] ?? '-') ?></p>
                            <p><strong>NID:</strong> <?= htmlspecialchars($d['nid'] ?? '-') ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($d['address'] ?? '-') ?></p>
                            <p><strong>Chamber:</strong> <?= htmlspecialchars($d['chamber'] ?? '-') ?></p>
                            <p><strong>Available Days:</strong> <?= htmlspecialchars($d['available_days'] ?? '-') ?></p>
                            <p><strong>Available Time:</strong> <?= htmlspecialchars($d['available_time'] ?? '-') ?></p>
                            <p><strong>Description:</strong> <?= htmlspecialchars($d['description'] ?? '-') ?></p>
                            <p><strong>Status:</strong> <?= $d['is_available'] ? 'Available' : 'Not Available' ?></p>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="?delete_user=<?= $d['user_id'] ?>" class="btn-danger action-btn" onclick="return confirm('Delete this doctor?')">Delete</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Users Section -->
        <div id="update-users" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Updating User Info:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
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

                <div class="toggle-buttons">
                    <button id="showPatientUsers">Patients</button>
                    <button id="showDoctorUsers">Doctors</button>
                </div>

                <div id="patientUsers">
                    <div class="update-forms-grid">
                        <?php foreach($patients as $p): ?>
                        <div class="update-form-card">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Name" required>
                                <?php if(isset($errors['name'])): ?><div style="color: red; font-size: 12px;"><?= $errors['name'] ?></div><?php endif; ?>

                                <input type="email" name="email" value="<?= htmlspecialchars($p['email']) ?>" placeholder="Email" required>
                                <?php if(isset($errors['email'])): ?><div style="color: red; font-size: 12px;"><?= $errors['email'] ?></div><?php endif; ?>

                                <input type="password" name="password" placeholder="New Password (leave empty to keep)">
                                
                                <input type="text" name="phone" value="<?= htmlspecialchars($p['phone']) ?>" placeholder="Phone" required>
                                <?php if(isset($errors['phone'])): ?><div style="color: red; font-size: 12px;"><?= $errors['phone'] ?></div><?php endif; ?>

                                <input type="text" name="address" value="<?= htmlspecialchars($p['address']) ?>" placeholder="Address" required>
                                <?php if(isset($errors['address'])): ?><div style="color: red; font-size: 12px;"><?= $errors['address'] ?></div><?php endif; ?>

                                <input type="text" name="health_issues" value="<?= htmlspecialchars($p['health_issues']) ?>" placeholder="Health Issues" required>
                                <?php if(isset($errors['health_issues'])): ?><div style="color: red; font-size: 12px;"><?= $errors['health_issues'] ?></div><?php endif; ?>

                                <input type="text" name="emergency" value="<?= htmlspecialchars($p['emergency']) ?>" placeholder="Emergency Contact" required>
                                <?php if(isset($errors['emergency'])): ?><div style="color: red; font-size: 12px;"><?= $errors['emergency'] ?></div><?php endif; ?>

                                <input type="text" name="nid" value="<?= htmlspecialchars($p['nid']) ?>" placeholder="NID" required>
                                <?php if(isset($errors['nid'])): ?><div style="color: red; font-size: 12px;"><?= $errors['nid'] ?></div><?php endif; ?>

                                <button name="update_patient" style="width: 100%; margin-top: 10px;">Update Patient</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="doctorUsers" style="display:none;">
                    <div class="update-forms-grid">
                        <?php foreach($doctors as $d): ?>
                        <div class="update-form-card">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">

                                <input type="text" name="name" value="<?= htmlspecialchars($d['name']) ?>" placeholder="Name" required>
                                <?php if(isset($errors['name'])): ?><div style="color: red; font-size: 12px;"><?= $errors['name'] ?></div><?php endif; ?>

                                <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" placeholder="Email" required>
                                <?php if(isset($errors['email'])): ?><div style="color: red; font-size: 12px;"><?= $errors['email'] ?></div><?php endif; ?>

                                <input type="password" name="password" placeholder="New Password (leave empty to keep)">

                                <input type="text" name="degree" value="<?= htmlspecialchars($d['degree']) ?>" placeholder="Degree" required>
                                <?php if(isset($errors['degree'])): ?><div style="color: red; font-size: 12px;"><?= $errors['degree'] ?></div><?php endif; ?>

                                <input type="text" name="phone" value="<?= htmlspecialchars($d['phone']) ?>" placeholder="Phone" required>
                                <?php if(isset($errors['phone'])): ?><div style="color: red; font-size: 12px;"><?= $errors['phone'] ?></div><?php endif; ?>

                                <input type="text" name="bmdc" value="<?= htmlspecialchars($d['bmdc']) ?>" placeholder="BMDC" required>
                                <?php if(isset($errors['bmdc'])): ?><div style="color: red; font-size: 12px;"><?= $errors['bmdc'] ?></div><?php endif; ?>

                                <input type="text" name="nid" value="<?= htmlspecialchars($d['nid']) ?>" placeholder="NID" required>
                                <?php if(isset($errors['nid'])): ?><div style="color: red; font-size: 12px;"><?= $errors['nid'] ?></div><?php endif; ?>

                                <input type="text" name="address" value="<?= htmlspecialchars($d['address']) ?>" placeholder="Address" required>
                                <?php if(isset($errors['address'])): ?><div style="color: red; font-size: 12px;"><?= $errors['address'] ?></div><?php endif; ?>

                                <input type="text" name="chamber" value="<?= htmlspecialchars($d['chamber']) ?>" placeholder="Chamber" required>
                                <?php if(isset($errors['chamber'])): ?><div style="color: red; font-size: 12px;"><?= $errors['chamber'] ?></div><?php endif; ?>

                                <input type="text" name="available_days" value="<?= htmlspecialchars($d['available_days']) ?>" placeholder="Available Days" required>
                                <?php if(isset($errors['available_days'])): ?><div style="color: red; font-size: 12px;"><?= $errors['available_days'] ?></div><?php endif; ?>

                                <input type="text" name="available_time" value="<?= htmlspecialchars($d['available_time']) ?>" placeholder="Available Time" required>
                                <?php if(isset($errors['available_time'])): ?><div style="color: red; font-size: 12px;"><?= $errors['available_time'] ?></div><?php endif; ?>

                                <select name="is_available" style="margin-bottom: 10px;">
                                    <option value="1" <?= $d['is_available'] ? 'selected' : '' ?>>Available</option>
                                    <option value="0" <?= !$d['is_available'] ? 'selected' : '' ?>>Not Available</option>
                                </select>

                                <input type="text" name="description" value="<?= htmlspecialchars($d['description']) ?>" placeholder="Description" required>
                                <?php if(isset($errors['description'])): ?><div style="color: red; font-size: 12px;"><?= $errors['description'] ?></div><?php endif; ?>

                                <button name="update_doctor" style="width: 100%; margin-top: 10px;">Update Doctor</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Section -->
        <div id="add-user" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Adding New User:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>Email must be valid and unique.</li>
                        <li>Password is required.</li>
                        <li>Patient fields: Name, Address, Phone, Health Issues, Emergency, NID</li>
                        <li>Doctor fields: Name, Degree, BMDC, Address, Chamber, Available Days & Time, Description</li>
                        <li>Click <strong>Add</strong> button to save the new user.</li>
                    </ul>
                </div>

                <h2>Add New User</h2>
                
                <?php if(!empty($successAdd)): ?>
                    <div class="success"><?= $successAdd ?></div>
                <?php endif; ?>

                <?php if(!empty($errorsAdd)): ?>
                    <div class="error">
                        <?php foreach($errorsAdd as $error): ?>
                            <div><?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="toggle-buttons">
                    <button id="addAdminBtn">Admin</button>
                    <button id="addPatientBtn">Patient</button>
                    <button id="addDoctorBtn">Doctor</button>
                </div>

                <!-- Admin Add Form -->
                <div id="addAdmin" class="add-user-form">
                    <h3>Add Admin</h3>
                    <form method="POST">
                        <input type="hidden" name="role" value="admin">
                        <div class="form-grid">
                            <div>
                                <input type="email" name="email" placeholder="Email" required>
                            </div>
                            <div>
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button name="add_user">Add Admin</button>
                        </div>
                    </form>
                </div>

                <!-- Patient Add Form -->
                <div id="addPatient" class="add-user-form" style="display:none;">
                    <h3>Add Patient</h3>
                    <form method="POST">
                        <input type="hidden" name="role" value="patient">
                        <div class="form-grid">
                            <div><input type="text" name="name" placeholder="Name" required></div>
                            <div><input type="email" name="email" placeholder="Email" required></div>
                            <div><input type="password" name="password" placeholder="Password" required></div>
                            <div><input type="text" name="phone" placeholder="Phone" required></div>
                            <div><input type="text" name="address" placeholder="Address" required></div>
                            <div><input type="text" name="health_issues" placeholder="Health Issues" required></div>
                            <div><input type="text" name="emergency" placeholder="Emergency Contact" required></div>
                            <div><input type="text" name="nid" placeholder="NID" required></div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button name="add_user">Add Patient</button>
                        </div>
                    </form>
                </div>

                <!-- Doctor Add Form -->
                <div id="addDoctor" class="add-user-form" style="display:none;">
                    <h3>Add Doctor</h3>
                    <form method="POST">
                        <input type="hidden" name="role" value="doctor">
                        <div class="form-grid">
                            <div><input type="text" name="name" placeholder="Name" required></div>
                            <div><input type="email" name="email" placeholder="Email" required></div>
                            <div><input type="password" name="password" placeholder="Password" required></div>
                            <div><input type="text" name="degree" placeholder="Degree" required></div>
                            <div><input type="text" name="phone" placeholder="Phone" required></div>
                            <div><input type="text" name="bmdc" placeholder="BMDC" required></div>
                            <div><input type="text" name="nid" placeholder="NID" required></div>
                            <div><input type="text" name="address" placeholder="Address" required></div>
                            <div><input type="text" name="chamber" placeholder="Chamber" required></div>
                            <div><input type="text" name="available_days" placeholder="Available Days" required></div>
                            <div><input type="text" name="available_time" placeholder="Available Time" required></div>
                            <div>
                                <select name="is_available" required>
                                    <option value="">Select Availability</option>
                                    <option value="1">Available</option>
                                    <option value="0">Not Available</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid full-width" style="margin-top: 15px;">
                            <div>
                                <input type="text" name="description" placeholder="Description" required>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button name="add_user">Add Doctor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Appointment Manager Section -->
        <div id="appointment-manager" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Appointment Management:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>View all appointments in the table below</li>
                        <li>Edit appointment status and preferred day using the form</li>
                        <li>Click Edit to load appointment details into the edit form</li>
                        <li>Click Cancel to mark appointment as cancelled</li>
                        <li>Click Delete to permanently remove appointment</li>
                        <li>Click Mark Seen to mark appointment as seen by doctor</li>
                    </ul>
                </div>

                <h2>Appointment Manager</h2>

                <?php if($appointmentSuccess): ?>
                    <div class="success"><?= $appointmentSuccess ?></div>
                <?php endif; ?>

                <?php if($appointmentError): ?>
                    <div class="error"><?= $appointmentError ?></div>
                <?php endif; ?>
                        <!-- Admin Book Appointment Section -->
        <div id="admin-book-appointment" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Admin Booking:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>Select a patient from the dropdown</li>
                        <li>Select a doctor from the dropdown</li>
                        <li>Choose the preferred day for the appointment</li>
                        <li>Click "Book Appointment" to create the booking</li>
                        <li>Note: Patient can only have one active booking per doctor</li>
                    </ul>
                </div>

                <h2>Book Appointment on Behalf of Patient</h2>

                <?php if(isset($bookingSuccess)): ?>
                    <div class="success"><?= $bookingSuccess ?></div>
                <?php endif; ?>

                <?php if(isset($bookingError)): ?>
                    <div class="error"><?= $bookingError ?></div>
                <?php endif; ?>

                <div class="booking-form-container">
                    <div style="background: white; padding: 25px; border-radius: 5px; border: 1px solid #ddd; max-width: 600px; margin: 0 auto;">
                        <form method="POST">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">Select Patient *</label>
                                    <select name="patient_id" required style="width: 100%;">
                                        <option value="">-- Choose Patient --</option>
                                        <?php foreach($patients as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['name']) ?> (ID: <?= $p['id'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">Select Doctor *</label>
                                    <select name="doctor_id" required style="width: 100%;">
                                        <option value="">-- Choose Doctor --</option>
                                        <?php foreach($doctors as $d): ?>
                                        <option value="<?= $d['id'] ?>">
                                            <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['degree']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 5px;">Preferred Day *</label>
                                <select name="preferred_day" required style="width: 100%;">
                                    <option value="">-- Select Day --</option>
                                    <option value="Mon">Monday</option>
                                    <option value="Tue">Tuesday</option>
                                    <option value="Wed">Wednesday</option>
                                    <option value="Thu">Thursday</option>
                                    <option value="Fri">Friday</option>
                                    <option value="Sat">Saturday</option>
                                    <option value="Sun">Sunday</option>
                                </select>
                            </div>
                            
                            <div style="text-align: center;">
                                <button type="submit" name="admin_book_appointment">Book Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


                <!-- Appointment Edit Form -->
                <div class="appointment-form-container">
                    <div style="background: white; padding: 25px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 30px;">
                        <h3 style="text-align: center; margin-top: 0; color: #007BFF;">Edit Appointment</h3>
                        <form method="POST">
                            <input type="hidden" name="appointment_id" id="editAppointmentId">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">Appointment Status</label>
                                    <select name="status" id="editStatus" required>
                                        <option value="booked">Booked</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px;">Preferred Day</label>
                                    <select name="preferred_day" id="editDay" required>
                                        <option value="Mon">Monday</option>
                                        <option value="Tue">Tuesday</option>
                                        <option value="Wed">Wednesday</option>
                                        <option value="Thu">Thursday</option>
                                        <option value="Fri">Friday</option>
                                        <option value="Sat">Saturday</option>
                                        <option value="Sun">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="submit" name="update_appointment">Update Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div style="width: 100%; overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Day</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $appointment): ?>
                            <tr>
                                <td><?= $appointment['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($appointment['patient_name'] ?? 'N/A') ?></strong><br>
                                    <small>ID: <?= $appointment['patient_id'] ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($appointment['doctor_name'] ?? 'N/A') ?></strong><br>
                                    <small><?= htmlspecialchars($appointment['doctor_degree'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="status status-<?= $appointment['status'] ?>">
                                        <?= ucfirst($appointment['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($appointment['preferred_day']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" onclick="loadAppointmentForEdit(<?= $appointment['id'] ?>, '<?= $appointment['status'] ?>', '<?= $appointment['preferred_day'] ?>')" class="btn-warning action-btn">
                                            Edit
                                        </button>
                                        <a href="?cancel_appointment=<?= $appointment['id'] ?>" class="btn-warning action-btn" onclick="return confirm('Cancel this appointment?')">
                                            Cancel
                                        </a>
                                        <a href="?delete_appointment=<?= $appointment['id'] ?>" class="btn-danger action-btn" onclick="return confirm('Permanently delete this appointment?')">
                                            Delete
                                        </a>
                                        <?php if(!$appointment['is_seen']): ?>
                                        <a href="?mark_seen=<?= $appointment['id'] ?>" class="action-btn" onclick="return confirm('Mark as seen by doctor?')">
                                            Mark Seen
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($appointments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    No appointments found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

               <!-- Medicine Manager Section -->
        <div id="medicine-manager" class="section">
            <div class="centered-content">
                <div class="instructions">
                    <strong>Instructions for Medicine Manager:</strong>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>Search medicines by name using the search box</li>
                        <li>Edit medicine details directly in the table</li>
                        <li>Click Add New Medicine to add new medicine</li>
                        <li>Click Remove to delete a medicine (with confirmation)</li>
                        <li>Click Save All Medicines to save all changes</li>
                        <li>Current medicine count: <strong><?= count($medicines) ?></strong></li>
                        <li>Showing <span id="showingCount"><?= min(10, count($medicines)) ?></span> of <?= count($medicines) ?> medicines</li>
                    </ul>
                </div>

                <h2>Medicine Manager</h2>

                <?php if($medicineError): ?>
                    <div class="error"><?= $medicineError ?></div>
                <?php endif; ?>

                <?php if($medicineSuccess): ?>
                    <div class="success"><?= $medicineSuccess ?></div>
                <?php endif; ?>

                <!-- Search and Add Section -->
                <div style="display: flex; gap: 20px; margin: 30px 0; align-items: center; justify-content: center;">
                    <div style="width: 400px;">
                        <input type="text" id="medicineSearch" placeholder="Search medicines by name..." style="width: 100%;">
                    </div>
                    <div>
                        <button type="button" onclick="addMedicineRow()">Add New Medicine</button>
                    </div>
                </div>

                <!-- Medicine Table -->
                <div class="medicine-form-container">
                    <form method="POST" id="medicineForm">
                        <input type="hidden" name="save_medicines_table" value="1">
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Medicine Name *</th>
                                        <th>Type *</th>
                                        <th>Used For</th>
                                        <th>Brand</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="medicineTableBody">
                                    <?php 
                                    $totalMedicines = count($medicines);
                                    $initialShow = min(10, $totalMedicines);
                                    
                                    // JSON encode all medicines for JavaScript
                                    $medicinesJson = json_encode($medicines);
                                    ?>
                                    
                                    <!-- Medicines will be loaded here by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Load More Button Container -->
                        <div id="loadMoreContainer" style="text-align: center; margin-top: 20px;">
                            <?php if($totalMedicines > 10): ?>
                            <button type="button" id="loadMoreBtn" class="btn-success" 
                                    style="padding: 8px 20px; font-weight: bold;">
                                Load All Medicines (<?= $totalMedicines - $initialShow ?> more)
                            </button>
                            <button type="button" id="showLessBtn" class="btn-warning" 
                                    style="padding: 8px 20px; font-weight: bold; display: none;">
                                Show Only First 10 Medicines
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit">Save All Medicines</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <!-- Footer -->
    <footer class="footer">
        <p>All Right Reserved © DocMate <?= date('Y') ?></p>
    </footer>

<script>
// ========== MEDICINE MANAGER WITH AJAX LOAD MORE ==========
let allMedicines = <?= json_encode($medicines) ?>;
let showingAll = <?= isset($_GET['show_all_medicines']) ? 'true' : 'false' ?>;
const INITIAL_SHOW = 10;
const medicineTableBody = document.getElementById('medicineTableBody');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const showLessBtn = document.getElementById('showLessBtn');
const showingCountSpan = document.getElementById('showingCount');

// Initialize medicine manager
function initializeMedicineManager() {
    // Render initial medicines
    renderMedicines();
    
    // Set up button event listeners
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadAllMedicines);
    }
    
    if (showLessBtn) {
        showLessBtn.addEventListener('click', showLessMedicines);
    }
    
    // Update showing count
    updateShowingCount();
}

// Render medicines to table
function renderMedicines() {
    medicineTableBody.innerHTML = '';
    const limit = showingAll ? allMedicines.length : Math.min(INITIAL_SHOW, allMedicines.length);
    
    // Slice medicines based on current view
    const medicinesToShow = allMedicines.slice(0, limit);
    
    medicinesToShow.forEach((medicine, index) => {
        const newRow = document.createElement('tr');
        newRow.className = 'medicine-row';
        newRow.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <input type="text" 
                       name="medicine[${index}][name]" 
                       value="${escapeHtml(medicine.name || '')}"
                       placeholder="Medicine name"
                       required>
            </td>
            <td>
                <input type="text" 
                       name="medicine[${index}][type]" 
                       value="${escapeHtml(medicine.type || '')}"
                       placeholder="Type"
                       required>
            </td>
            <td>
                <input type="text" 
                       name="medicine[${index}][for]" 
                       value="${escapeHtml(medicine.for || '')}"
                       placeholder="Used for">
            </td>
            <td>
                <input type="text" 
                       name="medicine[${index}][brand]" 
                       value="${escapeHtml(medicine.brand || '')}"
                       placeholder="Brand">
            </td>
            <td>
                <button type="button" onclick="removeMedicineRow(this, ${index})" class="btn-danger action-btn">
                    Remove
                </button>
            </td>
        `;
        medicineTableBody.appendChild(newRow);
    });
    
    // Add empty row for new medicine if needed
    if (allMedicines.length === 0) {
        addEmptyMedicineRow(0);
    }
    
    // Update button visibility
    updateButtonVisibility();
}

// Add new medicine row
function addMedicineRow() {
    const newIndex = allMedicines.length;
    
    // Add to array
    allMedicines.push({
        name: '',
        type: '',
        for: '',
        brand: ''
    });
    
    // If we're showing limited view and just exceeded it, show load more button
    if (!showingAll && allMedicines.length > INITIAL_SHOW && loadMoreBtn) {
        loadMoreBtn.style.display = 'inline-block';
    }
    
    // Re-render with new count
    renderMedicines();
    updateShowingCount();
}

// Add empty row for new medicine
function addEmptyMedicineRow(index) {
    const newRow = document.createElement('tr');
    newRow.className = 'medicine-row';
    newRow.innerHTML = `
        <td>${index + 1}</td>
        <td>
            <input type="text" 
                   name="medicine[${index}][name]" 
                   placeholder="Medicine name"
                   required>
        </td>
        <td>
            <input type="text" 
                   name="medicine[${index}][type]" 
                   placeholder="Type"
                   required>
        </td>
        <td>
            <input type="text" 
                   name="medicine[${index}][for]" 
                   placeholder="Used for">
        </td>
        <td>
            <input type="text" 
                   name="medicine[${index}][brand]" 
                   placeholder="Brand">
        </td>
        <td>
            <button type="button" onclick="removeMedicineRow(this, ${index})" class="btn-danger action-btn">
                Remove
            </button>
        </td>
    `;
    medicineTableBody.appendChild(newRow);
}

// Remove medicine row
function removeMedicineRow(button, index) {
    if (!confirm('Are you sure you want to remove this medicine?')) {
        return;
    }
    
    // Remove from array
    allMedicines.splice(index, 1);
    
    // Re-render
    renderMedicines();
    updateShowingCount();
}

// Load all medicines (AJAX-style - no page reload)
function loadAllMedicines() {
    showingAll = true;
    renderMedicines();
    updateShowingCount();
}

// Show only first 10 medicines
function showLessMedicines() {
    showingAll = false;
    renderMedicines();
    updateShowingCount();
}

// Update showing count display
function updateShowingCount() {
    const showingCount = showingAll ? allMedicines.length : Math.min(INITIAL_SHOW, allMedicines.length);
    showingCountSpan.textContent = showingCount;
}

// Update button visibility
function updateButtonVisibility() {
    if (!loadMoreBtn || !showLessBtn) return;
    
    if (allMedicines.length > INITIAL_SHOW) {
        if (showingAll) {
            loadMoreBtn.style.display = 'none';
            showLessBtn.style.display = 'inline-block';
        } else {
            loadMoreBtn.style.display = 'inline-block';
            showLessBtn.style.display = 'none';
            loadMoreBtn.textContent = `Load All Medicines (${allMedicines.length - INITIAL_SHOW} more)`;
        }
    } else {
        loadMoreBtn.style.display = 'none';
        showLessBtn.style.display = 'none';
    }
}

// HTML escape function
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Search functionality
const medicineSearch = document.getElementById('medicineSearch');
if (medicineSearch) {
    medicineSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.medicine-row');
        
        rows.forEach(row => {
            const nameInput = row.querySelector('input[name*="[name]"]');
            const medicineName = nameInput ? nameInput.value.toLowerCase() : '';
            
            if (searchTerm === '' || medicineName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// ========== APPOINTMENT FUNCTIONS ==========
function loadAppointmentForEdit(id, status, day) {
    document.getElementById('editAppointmentId').value = id;
    document.getElementById('editStatus').value = status;
    document.getElementById('editDay').value = day;
    
    // Scroll to the edit form
    document.querySelector('.appointment-form-container').scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}

// ========== VIEW USERS TOGGLE FUNCTIONS ==========
const patientsSection = document.getElementById('patientsSection');
const doctorsSection = document.getElementById('doctorsSection');
const showPatientsBtn = document.getElementById('showPatients');
const showDoctorsBtn = document.getElementById('showDoctors');
const searchInput = document.getElementById('searchInput');
const showPatientUsersBtn = document.getElementById('showPatientUsers');
const showDoctorUsersBtn = document.getElementById('showDoctorUsers');
const patientUsers = document.getElementById('patientUsers');
const doctorUsers = document.getElementById('doctorUsers');
const addAdminBtn = document.getElementById('addAdminBtn');
const addPatientBtn = document.getElementById('addPatientBtn');
const addDoctorBtn = document.getElementById('addDoctorBtn');
const addAdmin = document.getElementById('addAdmin');
const addPatient = document.getElementById('addPatient');
const addDoctor = document.getElementById('addDoctor');

// Toggle for View Users
if (showPatientUsersBtn && showDoctorUsersBtn) {
    showPatientUsersBtn.addEventListener('click', () => {
        patientUsers.style.display = 'block';
        doctorUsers.style.display = 'none';
    });
    
    showDoctorUsersBtn.addEventListener('click', () => {
        doctorUsers.style.display = 'block';
        patientUsers.style.display = 'none';
    });
}

// Toggle for Add User forms
if (addAdminBtn && addPatientBtn && addDoctorBtn) {
    addAdminBtn.addEventListener('click', () => {
        addAdmin.style.display = 'block';
        addPatient.style.display = 'none';
        addDoctor.style.display = 'none';
    });
    
    addPatientBtn.addEventListener('click', () => {
        addAdmin.style.display = 'none';
        addPatient.style.display = 'block';
        addDoctor.style.display = 'none';
    });
    
    addDoctorBtn.addEventListener('click', () => {
        addAdmin.style.display = 'none';
        addPatient.style.display = 'none';
        addDoctor.style.display = 'block';
    });
}

// Toggle sections for View Users
if (showPatientsBtn && showDoctorsBtn) {
    showPatientsBtn.addEventListener('click', () => {
        patientsSection.style.display = 'block';
        doctorsSection.style.display = 'none';
        if (searchInput) searchInput.value = '';
        filterCards();
    });
    
    showDoctorsBtn.addEventListener('click', () => {
        doctorsSection.style.display = 'block';
        patientsSection.style.display = 'none';
        if (searchInput) searchInput.value = '';
        filterCards();
    });
}

// Search function for user cards
if (searchInput) {
    searchInput.addEventListener('input', filterCards);
}

function filterCards() {
    const val = searchInput.value.toLowerCase();
    const activeSection = patientsSection.style.display !== 'none' ? patientsSection : doctorsSection;
    const cards = activeSection.querySelectorAll('.user-card');
    
    cards.forEach(card => {
        const nameElement = card.querySelector('h3');
        if (nameElement) {
            card.style.display = nameElement.textContent.toLowerCase().includes(val) ? 'block' : 'none';
        }
    });
}

// ========== SMOOTH SCROLLING ==========
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        if(this.getAttribute('href').startsWith('#')) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        }
    });
});

// ========== INITIALIZE ON PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', function() {
    // Initialize medicine manager
    initializeMedicineManager();
    
    // Initialize any other components here
});
</script>
</body>
</html>