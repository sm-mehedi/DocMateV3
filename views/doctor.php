<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

$doctorModel = new Doctor($conn);
$booking = new Booking($conn);

$doc = $doctorModel->getByUser($_SESSION['user_id']);

$patients = $booking->forDoctor($_SESSION['user_id']);

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
            <h2>Welcome, Dr. <?= htmlspecialchars($doc['name']) ?>!</h2>
            <p>Specialization: <?= htmlspecialchars($doc['degree']) ?></p>
            <p>BMDC: <?= htmlspecialchars($doc['bmdc'] ?? 'N/A') ?></p>
            
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
                        <span class="availability-status <?= $doc['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                            <?= $doc['is_available'] ? 'Available' : 'Offline' ?>
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
                    <span class="availability-status <?= $doc['is_available'] ? 'status-available' : 'status-unavailable' ?>" id="availability-status">
                        <?= $doc['is_available'] ? '🟢 Available' : '🔴 Offline' ?>
                    </span>
                    <button id="toggle-availability" class="btn <?= $doc['is_available'] ? 'btn-warning' : 'btn-success' ?>">
                        <?= $doc['is_available'] ? 'Go Offline' : 'Go Online' ?>
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
                        $selected = explode(',', $doc['available_days']);
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
                    <input type="text" name="time" value="<?= htmlspecialchars($doc['available_time']) ?>" 
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
            <h3>👤 Update Profile Information</h3>
            <form id="update-info-form">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($doc['phone']) ?>" 
                           placeholder="Enter phone number" required>
                    <div class="error" id="phone-error"></div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($doc['address']) ?>" 
                           placeholder="Enter clinic address" required>
                    <div class="error" id="address-error"></div>
                </div>

                <div class="form-group">
                    <label>Chamber Location</label>
                    <input type="text" name="chamber" value="<?= htmlspecialchars($doc['chamber']) ?>" 
                           placeholder="Enter chamber location" required>
                    <div class="error" id="chamber-error"></div>
                </div>

                <button type="submit" class="btn">Update Profile</button>
            </form>
        </section>
    </div>

      <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>© <?= date('Y') ?> DocMate - Doctor Portal</p>
            <p>Dr. <?= htmlspecialchars($doc['name']) ?> | BMDC: <?= htmlspecialchars($doc['bmdc'] ?? 'N/A') ?></p>
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
        const originalTime = "<?= htmlspecialchars($doc['available_time']) ?>";

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
// Update profile info
    $('#update-info-form').submit(function(e){
        e.preventDefault();

        const phone = this.phone.value.trim();
        const address = this.address.value.trim();
        const chamber = this.chamber.value.trim();

        let valid = true;

        // Clear previous errors
        $('#phone-error, #address-error, #chamber-error').text('');

        // Validation
        if(!/^\d{7,11}$/.test(phone)){
            $('#phone-error').text('Phone must be 7–11 digits only.');
            this.phone.value = "<?= htmlspecialchars($doc['phone']) ?>";
            valid = false;
        }

        if(address.length < 5 || address.length > 100){
            $('#address-error').text('Address must be 5–100 characters.');
            this.address.value = "<?= htmlspecialchars($doc['address']) ?>";
            valid = false;
        }

        if(chamber.length < 3 || chamber.length > 50){
            $('#chamber-error').text('Chamber must be 3–50 characters.');
            this.chamber.value = "<?= htmlspecialchars($doc['chamber']) ?>";
            valid = false;
        }

        if(!valid) return;

        const formData = $(this).serialize();

        $.post('../public/update-info.php', formData, function(data){
            if(data.success){
                alert('Profile updated successfully!');
            } else {
                alert(data.message || 'Update failed');
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
