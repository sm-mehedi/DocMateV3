<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

// Only doctors can access
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor'){
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$doctorModel = new Doctor($conn);
$booking = new Booking($conn);

$doc = $doctorModel->getByUser($user_id);

// Get doctor's full info with email
$stmt = $conn->prepare("SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$profileSuccess = '';
$profileError = '';

// Handle Doctor Profile Update
if(isset($_POST['update_doctor_profile'])){
    $name = trim($_POST['name']);
    $degree = trim($_POST['degree']);
    $phone = trim($_POST['phone']);
    $bmdc = trim($_POST['bmdc']);
    $nid = trim($_POST['nid']);
    $address = trim($_POST['address']);
    $chamber = trim($_POST['chamber']);
    $available_days = trim($_POST['available_days']);
    $available_time = trim($_POST['available_time']);
    $description = trim($_POST['description']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $profileErrors = [];
    
    // Validate fields
    if(!$name) $profileErrors['name'] = "Name is required!";
    if(!$degree) $profileErrors['degree'] = "Degree is required!";
    if(!preg_match('/^\d{10,15}$/', $phone)) $profileErrors['phone'] = "Phone must be 10-15 digits!";
    if(!$bmdc) $profileErrors['bmdc'] = "BMDC is required!";
    if(!$nid || !preg_match('/^\d{10,17}$/', $nid)) $profileErrors['nid'] = "NID must be 10-17 digits!";
    if(!$address) $profileErrors['address'] = "Address is required!";
    if(!$chamber) $profileErrors['chamber'] = "Chamber is required!";
    if(!$available_days) $profileErrors['available_days'] = "Available days required!";
    if(!$available_time) $profileErrors['available_time'] = "Available time required!";
    if(!$description) $profileErrors['description'] = "Description is required!";
    
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
            // Update doctor info
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, degree = ?, phone = ?, bmdc = ?, nid = ?, address = ?, chamber = ?, available_days = ?, available_time = ?, description = ? WHERE user_id = ?");
            $stmt->execute([$name, $degree, $phone, $bmdc, $nid, $address, $chamber, $available_days, $available_time, $description, $user_id]);
            
            if(!empty($new_password)) {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $user_id]);
            }
            
            // Update local doctor info
            $doctor['name'] = $name;
            $doctor['degree'] = $degree;
            $doctor['phone'] = $phone;
            $doctor['bmdc'] = $bmdc;
            $doctor['nid'] = $nid;
            $doctor['address'] = $address;
            $doctor['chamber'] = $chamber;
            $doctor['available_days'] = $available_days;
            $doctor['available_time'] = $available_time;
            $doctor['description'] = $description;
            
            $profileSuccess = "Profile updated successfully!";
        } catch (Exception $e) {
            $profileError = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $profileError = implode(" ", array_values($profileErrors));
    }
}

$patients = $booking->forDoctor($user_id);

$today = date('D'); 

$patientCount = count($patients);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/doctor.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#welcome" class="logo">
                <span>Doctor Portal</span>
            </a>
            <div class="nav-links">
                <a href="#welcome" class="nav-link active">Dashboard</a>
                <a href="#availability" class="nav-link">Availability</a>
                <a href="#patients" class="nav-link">Patients</a>
                <a href="#profile" class="nav-link">Profile</a>
                <a href="../public/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="scroll-top" onclick="scrollToTop()">↑</div>

    <div class="container">
        <!-- Welcome Section -->
        <section id="welcome" class="section">
            <div class="welcome-message" style="background: #fdfdfd; border: 2px solid #000; padding: 20px; margin-bottom: 20px; text-align: center;">
                <h2>Welcome, Dr. <?= htmlspecialchars($doctor['name']) ?>!</h2>
                <p>Specialization: <?= htmlspecialchars($doctor['degree']) ?></p>
                <p>Email: <?= htmlspecialchars($doctor['email']) ?></p>
                <p>BMDC: <?= htmlspecialchars($doctor['bmdc'] ?? 'N/A') ?></p>
                
                <div class="doctor-quick-info" style="margin-top: 15px; display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                    <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                        <strong>Phone:</strong> <?= htmlspecialchars($doctor['phone']) ?>
                    </div>
                    <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                        <strong>Active Patients:</strong> <?= $patientCount ?>
                    </div>
                    <div style="border: 1px solid #000; padding: 10px; background: #fff;">
                        <strong>Status:</strong> 
                        <span class="availability-status <?= $doctor['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                            <?= $doctor['is_available'] ? 'Available' : 'Offline' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $patientCount ?></div>
                    <div class="stat-label">Active Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $patientCount ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <span class="availability-status <?= $doctor['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                            <?= $doctor['is_available'] ? 'Available' : 'Offline' ?>
                        </span>
                    </div>
                    <div class="stat-label">Current Status</div>
                </div>
            </div>
        </section>

        <!-- Availability Management -->
        <section id="availability" class="section">
            <h3>📅 Availability Management</h3>
            
            <div class="form-group">
                <label>Current Status:</label>
                <div>
                    <span class="availability-status <?= $doctor['is_available'] ? 'status-available' : 'status-unavailable' ?>" id="availability-status">
                        <?= $doctor['is_available'] ? '🟢 Available' : '🔴 Offline' ?>
                    </span>
                    <button id="toggle-availability" class="btn <?= $doctor['is_available'] ? 'btn-warning' : 'btn-success' ?>">
                        <?= $doctor['is_available'] ? 'Go Offline' : 'Go Online' ?>
                    </button>
                </div>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #dee2e6;">

            <h4>Update Schedule</h4>
            <form id="update-availability-form">
                <div class="form-group">
                    <label>Available Days</label>
                    <div class="checkbox-group">
                        <?php
                        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                        $selected = explode(',', $doctor['available_days']);
                        foreach ($days as $d):
                            $highlight = ($d === $today) ? 'today-checkbox' : '';
                        ?>
                            <label class="<?= $highlight ?>">
                                <input type="checkbox" name="days[]" value="<?= $d ?>"
                                    <?= in_array($d, $selected) ? 'checked' : '' ?>>
                                <?= $d ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Available Time</label>
                    <input type="text" name="time" value="<?= htmlspecialchars($doctor['available_time']) ?>" 
                           placeholder="e.g., 9:00 AM - 5:00 PM" required>
                    <div class="error" id="time-error"></div>
                </div>

                <button type="submit" class="btn">Update Schedule</button>
            </form>
        </section>

        <!-- Patients Section -->
        <section id="patients" class="section">
            <h3> My Patients 
                <span class="badge badge-success"><?= $patientCount ?> Active</span>
            </h3>

            <?php if (empty($patients)): ?>
                <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 6px;">
                    <p style="color: #6c757d;">No active appointments at the moment.</p>
                </div>
            <?php else: ?>
                <div class="cards" id="patient-cards">
                    <?php foreach ($patients as $p): ?>
                        <div class="card <?= ($p['preferred_day'] ?? '') === $today ? 'today' : '' ?>" id="patient-<?= $p['id'] ?>">
                            <h4>
                                <?= htmlspecialchars($p['name']) ?>
                                <?= ($p['preferred_day'] ?? '') === $today ? '<span class="badge badge-success">Today</span>' : '' ?>
                            </h4>
                            <p><strong>📞 Phone:</strong> <?= htmlspecialchars($p['phone']) ?></p>
                            <p><strong>🏠 Address:</strong> <?= htmlspecialchars($p['address']) ?></p>
                            <p><strong>⚕️ Health Issues:</strong> <?= htmlspecialchars($p['health_issues']) ?></p>
                            <p><strong>🚨 Emergency:</strong> <?= htmlspecialchars($p['emergency']) ?></p>
                            <p><strong>📅 Preferred Day:</strong> <?= htmlspecialchars($p['preferred_day'] ?? 'Not selected') ?></p>
                            <p><strong>🆔 Patient ID:</strong> <?= $p['id'] ?></p>

                            <div class="card-actions">
                                <button class="btn btn-seen mark-seen-btn" data-patient="<?= $p['id'] ?>">
                                    ✅ Mark as Seen 
                                </button>
                                <button class="btn btn-danger cancel-btn" data-patient="<?= $p['id'] ?>">
                                    ❌ Cancel Appointment
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Profile Update Section -->
        <section id="profile" class="section">
            <h3 style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">👤 Update Profile Information</h3>

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
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Full Name *</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" placeholder="Full Name" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Degree/Specialization *</label>
                                <input type="text" name="degree" value="<?= htmlspecialchars($doctor['degree']) ?>" placeholder="MBBS, MD, etc." required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Phone Number *</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" placeholder="10-15 digits" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">BMDC Registration *</label>
                                <input type="text" name="bmdc" value="<?= htmlspecialchars($doctor['bmdc']) ?>" placeholder="BMDC Number" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">NID *</label>
                                <input type="text" name="nid" value="<?= htmlspecialchars($doctor['nid']) ?>" placeholder="10-17 digits" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Address *</label>
                                <input type="text" name="address" value="<?= htmlspecialchars($doctor['address']) ?>" placeholder="Clinic Address" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Chamber Location *</label>
                                <input type="text" name="chamber" value="<?= htmlspecialchars($doctor['chamber']) ?>" placeholder="Chamber Details" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Available Days *</label>
                                <input type="text" name="available_days" value="<?= htmlspecialchars($doctor['available_days']) ?>" placeholder="Mon,Tue,Wed,Thu,Fri" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                                <small style="color: #666;">Comma separated days</small>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Available Time *</label>
                                <input type="text" name="available_time" value="<?= htmlspecialchars($doctor['available_time']) ?>" placeholder="9:00 AM - 5:00 PM" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Professional Description *</label>
                                <textarea name="description" placeholder="Describe your expertise, experience, etc." required 
                                          style="width: 100%; padding: 8px; border: 1px solid #000; font-family: inherit; min-height: 100px;"><?= htmlspecialchars($doctor['description']) ?></textarea>
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
                            <button type="submit" name="update_doctor_profile" 
                                    style="padding: 10px 30px; background: #fff; border: 2px solid #000; font-weight: bold; cursor: pointer; font-family: inherit;">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>© <?= date('Y') ?> DocMate - Doctor Portal</p>
            <p>Dr. <?= htmlspecialchars($doctor['name']) ?> | BMDC: <?= htmlspecialchars($doctor['bmdc'] ?? 'N/A') ?></p>
            <p style="font-size: 12px; margin-top: 10px; color: #bdc3c7;">
                Logged in as: <?= htmlspecialchars($doctor['email']) ?>
            </p>
        </div>
    </footer>

<script>
$(document).ready(function(){
    // Toggle availability
    $('#toggle-availability').click(function(){
        const btn = $(this);
        btn.prop('disabled', true);

        $.post('../public/toggle-availability.php', {}, function(data){
            if(data.success){
                $('#availability-status')
                    .text(data.is_available ? '🟢 Available' : '🔴 Offline')
                    .toggleClass('status-available status-unavailable');

                btn.text(data.is_available ? 'Go Offline' : 'Go Online')
                   .toggleClass('btn-warning btn-success');
                
                document.querySelector('.availability-status').textContent = 
                    data.is_available ? 'Available' : 'Offline';
                document.querySelector('.availability-status').className = 
                    'availability-status ' + (data.is_available ? 'status-available' : 'status-unavailable');
                
                alert('Availability status updated!');
            } else {
                alert(data.message || 'Error toggling availability');
            }
        }, 'json').always(() => btn.prop('disabled', false));
    });

    // Update availability schedule
    $('#update-availability-form').submit(function(e){
        e.preventDefault();

        const timeInput = $(this).find('input[name="time"]');
        const time = timeInput.val().trim();
        const originalTime = "<?= htmlspecialchars($doctor['available_time']) ?>";

        $('#time-error').text('');

        if(time.length < 5 || time.length > 50){
            $('#time-error').text('Time must be 5–50 characters.');
            timeInput.val(originalTime);
            return;
        }

        const formData = $(this).serialize();

        $.post('../public/update-availability.php', formData, function(data){
            if(data.success){
                $('input[name="time"]').val(data.time);
                alert('Schedule updated successfully!');
            } else {
                $('#time-error').text(data.message || 'Update failed.');
                $('input[name="time"]').val(originalTime);
            }
        }, 'json');
    });

    // Cancel appointment
    $('.cancel-btn').click(function(){
        if(!confirm('Are you sure you want to cancel this appointment?\n\nThe patient will be notified.')) return;

        const btn = $(this);
        const patient_id = btn.data('patient');
        const card = $('#patient-' + patient_id);

        btn.prop('disabled', true).text('Cancelling...');

        $.post('../public/cancel-appointment.php', { patient_id }, function(data){
            if(data.success){
                card.fadeOut(300, function() {
                    $(this).remove();
                    updatePatientCount();
                });
                alert('Appointment cancelled successfully.');
            } else {
                alert(data.message || 'Cancel failed.');
                btn.prop('disabled', false).text('❌ Cancel Appointment');
            }
        }, 'json');
    });

    // Mark as Seen (DELETE booking)
    $('.mark-seen-btn').click(function(){
        if(!confirm('Mark this patient as seen?')) return;

        const btn = $(this);
        const patient_id = btn.data('patient');
        const card = $('#patient-' + patient_id);

        btn.prop('disabled', true).text('Deleting...');

        $.post('../public/mark-seen.php', { patient_id }, function(data){
            if(data.success){
                card.fadeOut(300, function() {
                    $(this).remove();
                    updatePatientCount();
                });
                alert('Patient marked as seen and booking deleted.');
            } else {
                alert(data.message || 'Failed to mark as seen.');
                btn.prop('disabled', false).text('✅ Mark as Seen (Delete)');
            }
        }, 'json');
    });

    // Update patient count
    function updatePatientCount() {
        const count = $('#patient-cards .card').length;
        $('#patients h3 .badge').text(count + ' Active');
        $('.stat-card:first-child .stat-number').text(count);
        $('.stat-card:nth-child(2) .stat-number').text(count);
    }

    // Smooth scrolling
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if(this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Scroll to top
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Show scroll to top button
    window.addEventListener('scroll', function() {
        const scrollTopBtn = document.querySelector('.scroll-top');
        if (window.scrollY > 300) {
            scrollTopBtn.style.display = 'flex';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    });
});
</script>
</body>
</html>