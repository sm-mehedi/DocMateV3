<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Booking.php";


// Only patients can access
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient'){
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get patient info
$q = $conn->prepare("SELECT p.*, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$q->execute([$user_id]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

// Initialize messages
$profileSuccess = '';
$profileError = '';

// Handle Patient Profile Update
if(isset($_POST['update_patient_profile'])){
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $health_issues = trim($_POST['health_issues']);
    $emergency = trim($_POST['emergency']);
    $nid = trim($_POST['nid']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $profileErrors = [];
    
    // Validate fields
    if(!$name) $profileErrors['name'] = "Name is required!";
    if(!preg_match('/^\d{10,15}$/', $phone)) $profileErrors['phone'] = "Phone must be 10-15 digits!";
    if(!$address) $profileErrors['address'] = "Address is required!";
    if(!$health_issues) $profileErrors['health_issues'] = "Health issues required!";
    if(!$emergency || !preg_match('/^\d{10,15}$/', $emergency)) $profileErrors['emergency'] = "Emergency must be 10-15 digits!";
    if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $profileErrors['nid'] = "NID must be 10-17 digits!";
    
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
            // Update patient info
            $stmt = $conn->prepare("UPDATE patients SET name = ?, phone = ?, address = ?, health_issues = ?, emergency = ?, nid = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $address, $health_issues, $emergency, $nid, $user_id]);
            
            // Update password if provided
            if(!empty($new_password)) {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $user_id]);
            }
            
            // Update local patient info
            $patient['name'] = $name;
            $patient['phone'] = $phone;
            $patient['address'] = $address;
            $patient['health_issues'] = $health_issues;
            $patient['emergency'] = $emergency;
            $patient['nid'] = $nid;
            
            $profileSuccess = "Profile updated successfully!";
        } catch (Exception $e) {
            $profileError = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $profileError = implode(" ", array_values($profileErrors));
    }
}

$bookingModel = new Booking($conn);

// Get all bookings including cancelled ones with cancellation info
$allBookings = $bookingModel->myAllBookingsWithCancelInfo($patient['id']);

// Get only active bookings for button logic
$activeBookings = array_column(
    array_filter($allBookings, fn($b) => $b['status'] === 'booked'),
    'booking_id'
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link rel="stylesheet" href="../public/assets/css/patient_dashboard.css">
</head>
<body>
<nav>
    <div class="nav-left">Patient Portal</div>
    <div class="nav-right">
        <a href="#my-bookings">Dashboard</a>
        <a href="./my_bookings.php">Doctors</a>
        <a href="./medicines.php">Medicines</a>
        <a href="#patient-profile">My Profile</a>

        <div class="dropdown">
            <?= htmlspecialchars($patient['name']) ?>
            <div class="dropdown-content">
                <a href="../public/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Welcome Section -->
    <div class="welcome-section" style="background: #fdfdfd; border: 2px solid #000; padding: 20px; margin-bottom: 30px; text-align: center;">
        <h1>Welcome, <?= htmlspecialchars($patient['name']) ?>!</h1>
        <p>You are logged in as a patient. Use the navigation above to manage your appointments and profile.</p>
        <div class="patient-quick-info" style="margin-top: 15px; display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
            <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                <strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?>
            </div>
            <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                <strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?>
            </div>
            <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                <strong>Active Bookings:</strong> <?= count($activeBookings) ?>
            </div>
        </div>
    </div>

    <!-- Patient Profile Section -->
    <div id="patient-profile" class="section" style="margin-bottom: 40px;">
        <h2 style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">My Profile</h2>

        <?php if($profileSuccess): ?>
            <div style="background: #d4edda; color: #155724; border: 2px solid #000; padding: 10px; margin: 15px auto; max-width: 600px; text-align: center;">
                <?= $profileSuccess ?>
            </div>
        <?php endif; ?>

        <?php if($profileError): ?>
            <div style="background: #f8d7da; color: #721c24; border: 2px solid #000; padding: 10px; margin: 15px auto; max-width: 600px; text-align: center;">
                <?= $profileError ?>
            </div>
        <?php endif; ?>

        <div class="profile-form-container" style="max-width: 800px; margin: 0 auto;">
            <div style="background: #fdfdfd; border: 2px solid #000; padding: 25px; border-radius: 0;">
                <h3 style="text-align: center; margin-top: 0; margin-bottom: 20px;">Update Your Information</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Full Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" placeholder="Full Name" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Phone *</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>" placeholder="Phone Number" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Address *</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($patient['address']) ?>" placeholder="Address" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Health Issues *</label>
                            <input type="text" name="health_issues" value="<?= htmlspecialchars($patient['health_issues']) ?>" placeholder="Health Issues" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Emergency Contact *</label>
                            <input type="text" name="emergency" value="<?= htmlspecialchars($patient['emergency']) ?>" placeholder="Emergency Contact" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">NID *</label>
                            <input type="text" name="nid" value="<?= htmlspecialchars($patient['nid']) ?>" placeholder="National ID" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">New Password (Optional)</label>
                            <input type="password" name="password" placeholder="Leave empty to keep current" 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm new password" 
                                   style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="update_patient_profile" 
                                style="padding: 10px 30px; background: #fff; border: 2px solid #000; font-weight: bold; cursor: pointer; font-family: inherit;">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- My Bookings Section -->
    <div id="my-bookings" class="section">
        <h2 style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">My Booked Doctors</h2>

        <?php if(empty($allBookings)): ?>
            <p style="text-align: center; padding: 20px; background: #fdfdfd; border: 2px solid #000;">You have no booked doctors yet.</p>
        <?php else: ?>
            <div class="cards">
               <?php foreach($allBookings as $d): 
                    $isCancelled = ($d['status'] === 'cancelled');
                    $doctorCancelled = ($d['doctor_cancelled'] ?? 0) == 1;
                    $patientUnbooked = ($d['patient_unbooked'] ?? 0) == 1;
               ?>
            <div class="card" id="booking-card-<?= $d['booking_id'] ?>">
                <h3><?= htmlspecialchars($d['name']) ?></h3>
                <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone']) ?></p>
                <p><strong>Degree:</strong> <?= htmlspecialchars($d['degree']) ?></p>
                <p><strong>BMDC:</strong> <?= htmlspecialchars($d['bmdc'] ?? 'N/A') ?></p>
                <p><strong>Chamber:</strong> <?= htmlspecialchars($d['chamber'] ?? 'N/A') ?></p>
                <p><strong>Available Days:</strong> <?= htmlspecialchars($d['available_days']) ?></p>
                <p><strong>Available Time:</strong> <?= htmlspecialchars($d['available_time']) ?></p>
                <p><strong>Preferred Day:</strong> <?= htmlspecialchars($d['preferred_day'] ?? 'N/A') ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($d['description'] ?? '') ?></p>
                <p><strong>Status:</strong> 
                    <span style="color: <?= $d['status'] === 'booked' ? 'green' : ($d['status'] === 'cancelled' ? 'red' : 'orange') ?>;">
                        <?= ucfirst($d['status']) ?>
                    </span>
                </p>

               <?php if($isCancelled): ?>
                    <div class="cancelled-info <?= $doctorCancelled ? 'doctor-cancelled' : ($patientUnbooked ? 'patient-cancelled' : '') ?>">
                        <?php if($doctorCancelled): ?>
                            <strong>⚠️ Cancelled by Doctor</strong>
                            <p>This appointment was cancelled by the doctor.</p>
                        <?php elseif($patientUnbooked): ?>
                            <strong>ℹ️ Cancelled by You</strong>
                            <p>You cancelled this appointment.</p>
                        <?php else: ?>
                            <strong>⚠️ Appointment Cancelled</strong>
                            <p>This appointment has been cancelled.</p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" onclick="removeBookingCard(<?= $d['booking_id'] ?>)" class="remove-btn">
                        Remove from View
                    </button>
                <?php elseif($d['status'] === 'booked'): ?>
                    <button type="button" onclick="unbookDoctor(<?= $d['booking_id'] ?>)" class="unbook-btn">
                        Cancel Appointment
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <div class="footer-content">
        <p>© <?= date('Y') ?> DocMate - Patient Portal</p>
        <p><?= htmlspecialchars($patient['name']) ?> | Member Since: <?= date('F Y') ?></p>
        <p style="font-size: 12px; margin-top: 10px; color: #bdc3c7;">
            Logged in as: <?= htmlspecialchars($patient['email']) ?>
        </p>
    </div>
</footer>

<script>
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        if(this.getAttribute('href') !== '#') {
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

function unbookDoctor(bookingId){
    if(!confirm("Are you sure you want to cancel this appointment?")) {
        return;
    }
    
    fetch("../public/unbook.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId })
    }).then(r => r.json()).then(res => {
        if(res.success){
            alert("Appointment cancelled successfully!");
            location.reload();
        } else {
            alert("Failed to cancel appointment: " + (res.message ?? "Unknown error"));
        }
    });
}

function removeBookingCard(bookingId) {
    if(!confirm("Are you sure you want to permanently delete this cancelled appointment? This action cannot be undone.")) {
        return;
    }
    
    // Hide the card immediately
    const card = document.getElementById('booking-card-' + bookingId);
    if(card) {
        card.style.display = 'none';
    }
    
    // Permanently delete from database
    fetch("../public/remove_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId })
    }).then(r => r.json()).then(res => {
        if(res.success){
            // Optionally show a success message
            console.log("Booking deleted successfully");
            // If you want to remove the card completely from DOM:
            if(card) {
                card.remove();
            }
        } else {
            // Show error and restore the card
            alert("Failed to delete booking: " + (res.message ?? "Unknown error"));
            if(card) {
                card.style.display = 'block';
            }
        }
    });
}

function removeBookingCard(bookingId) {
    if(!confirm("Are you sure you want to remove this cancelled appointment from your view?")) {
        return;
    }
    
    // Hide the card immediately
    const card = document.getElementById('booking-card-' + bookingId);
    if(card) {
        card.style.display = 'none';
    }
    
    // Optional: Send request to mark as hidden in database
    fetch("../public/remove_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId })
    }).then(r => r.json()).then(res => {
        if(!res.success) {
            console.error("Failed to update database:", res.message);
        }
    });
}
</script>
</body>
</html>