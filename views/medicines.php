<!DOCTYPE html>
<html>
<head>
  <title>Medicine List</title>
  <link rel="stylesheet" href="../public/assets/css/medicine.css">
</head>
<body>
<nav>
    <div class="nav-left">Patient View</div>
    <div class="nav-right">
        <a href="./patient.php">Doctors</a>
        <a href="./my_bookings.php">My Bookings</a>
        <a href="./medicines.php">Medicines</a>

       
                <a href="../public/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
  <h2>Search Medicines</h2>
  <input type="text" id="searchInput" placeholder="Search medicine...">

  <div class="cards" id="medicineCards"></div>
</div>

<script>
fetch('../public/assets/data/medicines.json')
  .then(res => res.json())
  .then(data => {
    window.meds = data;
    displayMeds(data);
  });

function displayMeds(list){
  let container = document.getElementById('medicineCards');
  container.innerHTML = "";
  list.forEach(m => {
    container.innerHTML += `
      <div class="card">
        <h3>${m.name}</h3>
        <p><strong>Type:</strong> ${m.type}</p>
        <p><strong>Uses:</strong> ${m.for}</p>
        <p><strong>Brand:</strong> ${m.brand}</p>
      </div>`;
  });
}

document.getElementById('searchInput').addEventListener('input', function(){
  let val = this.value.toLowerCase();
  let filtered = window.meds.filter(m =>
    m.name.toLowerCase().includes(val) ||
    m.for.toLowerCase().includes(val) ||
    m.brand.toLowerCase().includes(val)
  );
  displayMeds(filtered);
});
</script>

</body>
</html>
