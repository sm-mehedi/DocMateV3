<?php
require "../app/config/database.php";
require "../app/core/auth.php";
require "../app/models/Doctor.php";
require "../app/models/Booking.php";

// Get patient info
$q = $conn->prepare("SELECT id, name, phone FROM patients WHERE user_id=?");
$q->execute([$_SESSION['user_id']]);
$patient = $q->fetch(PDO::FETCH_ASSOC);

$doctorModel  = new Doctor($conn);
$bookingModel = new Booking($conn);

$doctors = $doctorModel->all();

$activeBookedDoctors = array_column(
    $bookingModel->myActiveBookings($patient['id']),
    'id'  // this is the doctor ID, because SELECT d.* returns d.id
);



?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/patient_dashboard.css">
</head>
<body>

<nav>
    <div class="nav-left">Patient View</div>
    <div class="nav-right">
        <a href="./patient.php">Dashboard</a>
        <a href="./my_bookings.php">Doctors</a>
        <a href="./medicines.php">Medicines</a>


        <div class="dropdown">
            <span><?= htmlspecialchars($patient['name']) ?> ▼</span>
            <div class="dropdown-content">
                <a href="../public/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <h2>Available Doctors</h2>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search doctors by expertise or description...">
    </div>

    <div class="cards" id="doctorCards">
        <?php foreach ($doctors as $d): ?>
        <div class="card" data-expertise="<?= htmlspecialchars(strtolower($d['degree'] . ' ' . $d['description'])) ?>">
            <h3><?= htmlspecialchars($d['name']) ?></h3>
            <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone']) ?></p>
            <p><strong>Degree/Expertise:</strong> <?= htmlspecialchars($d['degree']) ?></p>
            <p><strong>BMDC:</strong> <?= htmlspecialchars($d['bmdc'] ?? 'N/A') ?></p>
            <p><strong>Chamber:</strong> <?= htmlspecialchars($d['chamber'] ?? 'N/A') ?></p>
            <p><strong>Available Days:</strong> <?= htmlspecialchars($d['available_days']) ?></p>
            <p><strong>Available Time:</strong> <?= htmlspecialchars($d['available_time']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($d['description'] ?? '') ?></p>

            <!-- Updated buttons using only active bookings -->
    <!-- Updated buttons -->
     <?php
$days = array_map('trim', explode(',', $d['available_days']));
?>

<div class="days">
    <?php foreach($days as $day): ?>
        <label>
            <input 
                type="radio"
                name="day_<?= (int)$d['id'] ?>"
                value="<?= htmlspecialchars($day) ?>"
            >
            <?= htmlspecialchars($day) ?>
        </label>
    <?php endforeach; ?>
</div>

<button
    class="book"
    onclick="bookDoctor(<?= (int)$d['id'] ?>)"
    <?= !($d['is_available'] ?? 0) || in_array($d['id'], $activeBookedDoctors) ? 'disabled' : '' ?>
>Book</button>

<button
    class="unbook"
    onclick="unbookDoctorByDocId(<?= (int)$d['id'] ?>)"
    <?= in_array($d['id'], $activeBookedDoctors) ? '' : 'disabled' ?>
>Unbook</button>




        </div>
        <?php endforeach; ?>
    </div>
</div>
<footer class="footer">
    <div class="footer-content">
        <p>© <?= date('Y') ?> DocMate - Patient Portal</p>
        <p><?= htmlspecialchars($patient['name']) ?> | Member Since: <?= date('F Y') ?></p>
        <p style="font-size: 12px; margin-top: 10px; color: #bdc3c7;">
        </p>
    </div>
</footer>

<script>
function bookDoctor(doctorId){
    const selectedDay = document.querySelector(
        `input[name="day_${doctorId}"]:checked`
    );

    if(!selectedDay){
        alert("Please select a preferred day");
        return;
    }

    fetch("../public/book.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            doctor: doctorId,
            preferred_day: selectedDay.value
        })
    })
    .then(r => r.json())
    .then(res => {
        if(res.success){
            location.reload();
        } else {
            alert(res.error || "Booking failed");
        }
    });
}

// New function for doctor ID based unbooking
function unbookDoctorByDocId(doctorId){
    fetch("../public/unbook.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ doctor_id: doctorId })
    }).then(r => r.json()).then(res => {
        if(res.success){
            location.reload();
        } else {
            alert("Failed to unbook: " + (res.message || "Unknown error"));
        }
    });
}

// For booking ID based unbooking (used in my_bookings.php)
function unbookDoctor(bookingId){
    fetch("../public/unbook.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId })
    }).then(r => r.json()).then(res => {
        if(res.success){
            location.reload();
        } else {
            alert("Failed to unbook: " + (res.message || "Unknown error"));
        }
    });
}


// Search filter
document.getElementById('searchInput').addEventListener('input', function(){
    let val = this.value.toLowerCase();
    document.querySelectorAll('.card').forEach(card => {
        let expertise = card.dataset.expertise;
        card.style.display = expertise.includes(val) ? '' : 'none';
    });
});
</script>

</body>
</html>
