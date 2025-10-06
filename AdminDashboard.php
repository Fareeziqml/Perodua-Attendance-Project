<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$employee_id   = $_SESSION['employee_id'];
$employee_name = $_SESSION['name'];
$role          = $_SESSION['role'];

/* --- Total Employees --- */
$total_employees = $conn->query("SELECT COUNT(*) as total FROM employee")->fetch_assoc()['total'];

/* --- Employees by Department (Pie Chart) --- */
$dept_sql = "SELECT department, COUNT(*) as total FROM employee GROUP BY department";
$dept_res = $conn->query($dept_sql);
$departments = [];
$dept_counts = [];
while ($row = $dept_res->fetch_assoc()) {
    $departments[] = $row['department'];
    $dept_counts[] = (int)$row['total'];
}

/* --- Employees by Status (Bar Chart for Today Only) --- */
$status_sql = "SELECT status, COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE() GROUP BY status";
$status_res = $conn->query($status_sql);
$statuses = [];
$status_counts = [];
$colors = [];

// Map each status to a fixed color
$status_color_map = [
    "MC/MIA"     => "#E53935",   // Red
    "OutStation" => "#FFEB3B",   // Yellow
    "Available"  => "#4CAF50"    // Green
];

$total_present = 0;
$total_absent  = 0;
$total_submitted = 0;

while ($row = $status_res->fetch_assoc()) {
    $statuses[] = $row['status'];
    $status_counts[] = (int)$row['total'];
    $colors[] = $status_color_map[$row['status']] ?? "#64B5F6"; // Default Blue

    $total_submitted += (int)$row['total'];

    if ($row['status'] === "Available" || $row['status'] === "OutStation") {
        $total_present += (int)$row['total'];
    } else {
        $total_absent += (int)$row['total'];
    }
}

/* --- Add Not Submitted --- */
$not_submitted = max(0, $total_employees - $total_submitted);
if ($not_submitted > 0) {
    $statuses[] = "Not Submitted";
    $status_counts[] = $not_submitted;
    $colors[] = "#9E9E9E"; // Grey
}

/* --- Daily Attendance Rate (%) --- */
$daily_attendance_rate = 0;
if ($total_employees > 0) {
    $daily_attendance_rate = round(($total_present / $total_employees) * 100, 2);
}

$date_today = date("l, d F Y"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Perodua GM Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
    * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
    body { background: #f4f4f4; display: flex; min-height: 100vh; color:#222; }

     /* Sidebar */
    .sidebar {
        position: fixed; left:0; top:0; bottom:0; width:250px;
        background:#111; color:white; padding:30px 0;
        box-shadow:3px 0 15px rgba(0,0,0,0.2);
    }
    .sidebar img.logo-main { width: 140px; display:block; margin:0 auto 20px; border-radius:20%; }
    .sidebar h2 { text-align:center; margin-bottom:25px; font-size:26px; color:white; }
    .sidebar a { display:flex; align-items:center; gap:12px; padding:12px 25px; margin:5px 15px; border-radius:8px; font-weight:500; transition:0.3s; color:white; }
    .sidebar a:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
    .sidebar i { font-size:18px; }

    /* Topbar */
    .topbar {
        position: fixed; left:250px; right:0; top:0; height:70px; background:#111; color:white;
        display:flex; justify-content:space-between; align-items:center; padding:0 30px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000;
    }
    .topbar h3 { font-weight:500; }
    .topbar .date-time { font-size:14px; opacity:0.85; display:flex; gap:15px; }
    .logout-btn { background:#fff; color:#111; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
    .logout-btn:hover { background:#e0e0e0; }

    /* Main content */
    .main-content {
        margin-left: 260px; padding: 100px 30px 30px 30px; flex: 1;
        display: flex; justify-content: center; align-items: flex-start; min-height: 100vh;
    }

    /* Dashboard grid layout */
    .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; width: 100%; }

    /* Card */
    .card {
        background: #fff; border-radius: 15px; padding: 40px 30px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.07); text-align: center; transition: transform 0.3s;
    }
    .card:hover { transform: translateY(-5px); }
    .card h1 { font-size: 60px; color: #222; margin-top: 10px; }
    .card h3 { font-size: 22px; color: #444; }

    /* Chart container */
    .chart-container {
        background: #fff; border-radius: 15px; padding: 30px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.05); transition: transform 0.3s;
    }
    .chart-container:hover { transform: translateY(-3px); }

    /* Modal */
    .modal { 
        display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background: rgba(0,0,0,0.4); backdrop-filter: blur(6px); 
        justify-content:center; align-items:center; z-index:2000; 
        opacity:0; transition: opacity 0.4s ease;
    }
    .modal.show { display:flex; opacity:1; }

    .modal-content { 
        background:#fff; padding:30px; border-radius:12px; text-align:center; 
        width:400px; max-width:90%; box-shadow:0 8px 20px rgba(0,0,0,0.2); 
        transform: scale(0.9); transition: transform 0.3s ease;
    }
    .modal.show .modal-content { transform: scale(1); }

    .modal-content h2 { margin-bottom:20px; color:#222; }
    .modal-content button { padding:10px 20px; margin: 0 10px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; }
    .confirm-btn { background:#222; color:#fff; }
    .cancel-btn { background:#eee; color:#222; }
    .modal-content button:hover { opacity:0.9; }

    @media (max-width: 1000px) { .dashboard { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php"><i class="fas fa-file-alt"></i> My Attendance</a>
    <a href="AdminDashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Report</a>
    <a href="AdminAttendanceRecord.php"><i class="fas fa-calendar-check"></i> Attendance Report</a>
    <a href="AttendanceRateTable.php"><i class="fas fa-users"></i> Attendance Rate</a>
    <a href="AdminEmployeeList.php"><i class="fas fa-user-cog"></i> Update Employee</a>
</div>

<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= $role ?>)</h3>
        <div class="date-time">
            <span><?= $date_today ?></span> | <span id="clock">--:--:--</span>
        </div>
    </div>
    <button class="logout-btn" onclick="showModal('logoutModal')">Logout</button>
</div>

<div class="main-content">
    <div class="dashboard">
        <div class="card">
            <h3>Total Employees</h3>
            <h1 id="employeeCount">0</h1>
        </div>

        <div class="card">
            <h3>Daily Attendance Rate</h3>
            <h1><?= $daily_attendance_rate ?>%</h1>
        </div>

        <div class="chart-container">
            <h3 style="text-align:center; margin-bottom:15px;">Employees by Department</h3>
            <canvas id="deptChart" height="400"></canvas>
        </div>

        <div class="chart-container">
            <h3 style="text-align:center; margin-bottom:15px;">Employees by Status (Today)</h3>
            <canvas id="statusChart" height="400"></canvas>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div class="modal" id="logoutModal">
    <div class="modal-content">
        <h2>Confirm Logout?</h2>
        <button class="confirm-btn" onclick="logout()">Yes, Logout</button>
        <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
    </div>
</div>

<script>
    // Animate Employee Count
    const countEl = document.getElementById('employeeCount');
    let count = 0;
    const target = <?= $total_employees ?>;
    const increment = Math.ceil(target / 100);
    function updateCount() {
        if(count < target) {
            count += increment;
            countEl.textContent = count > target ? target : count;
            requestAnimationFrame(updateCount);
        }
    }
    updateCount();

    // Clock
    function updateClock() {
        const clock = document.getElementById('clock');
        clock.textContent = new Date().toLocaleTimeString();
    }
    setInterval(updateClock,1000);
    updateClock();

    // Pie Chart
    new Chart(document.getElementById('deptChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($departments) ?>,
            datasets: [{ data: <?= json_encode($dept_counts) ?>, backgroundColor: ['#e47c40','#01e1ff','#caf702','#06f81b','#d35fe7'] }]
        },
        options: { responsive:true, plugins:{ legend:{ position:'bottom' } } },
        plugins: [ChartDataLabels]
    });

    // Bar Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($statuses) ?>,
            datasets: [{ label:'Total Employees', data: <?= json_encode($status_counts) ?>, backgroundColor: <?= json_encode($colors) ?> }]
        },
        options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, title:{ display:true, text:'Number of Employees'} }, x:{ title:{ display:true, text:'Status'}} } },
        plugins:[ChartDataLabels]
    });

    // Modal functions with fade & blur
    function showModal(id){
        const modal = document.getElementById(id);
        modal.classList.add("show");
    }
    function closeModal(id){
        const modal = document.getElementById(id);
        modal.classList.remove("show");
        setTimeout(()=>{ modal.style.display="none"; },300);
    }
    function logout(){ window.location.href='logout.php'; }
</script>

</body>
</html>
