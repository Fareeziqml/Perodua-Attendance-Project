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
    "MC/MIA" => "#E53935",       // Red
    "OutStation" => "#FFEB3B",   // Yellow
    "Available" => "#4CAF50"     // Green
];

$total_present = 0;
$total_absent  = 0;

while ($row = $status_res->fetch_assoc()) {
    $statuses[] = $row['status'];
    $status_counts[] = (int)$row['total'];
    $colors[] = $status_color_map[$row['status']] ?? "#64B5F6"; // Default Blue

    if ($row['status'] === "Available" || $row['status'] === "OutStation") {
        $total_present += (int)$row['total'];
    } else {
        $total_absent += (int)$row['total'];
    }
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
        * { box-sizing: border-box; margin:0; padding:0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f7f8fa; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1b5e20, #388e3c);
            color: white;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding-top: 30px;
            box-shadow: 3px 0 15px rgba(0,0,0,0.2);
        }

        .sidebar img.logo-main { 
            display: block; 
            margin: 0 auto 25px; 
            width: 160px; 
            border-radius: 20%; 
        }

        .sidebar h2 {
            text-align: center; 
            font-size: 26px; 
            margin-bottom: 25px; 
        }

        .sidebar a {
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 15px 25px; 
            margin: 5px 10px; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: 500;
            transition: all 0.3s; 
        }

        .sidebar a:hover { 
            background: rgba(255,255,255,0.15); 
            transform: translateX(10px); 
        }

        .sidebar a i {
            font-size: 18px; 
        }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 70px;
            background: #2e7d32;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .topbar .date-time { 
            font-size: 14px; 
            margin-top: 5px; 
        }

        .logout-btn { 
            background: #c62828; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold; 
            transition: 0.3s; 
            color:white; 
        }

        .logout-btn:hover { 
            background: #b71c1c;
            transform: scale(1.05); 
        }

        /* Main content */
        .main-content {
            margin-left: 260px;
            padding: 100px 30px 30px 30px;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            background: #f7f8fa;
        }

        /* Dashboard grid layout */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            width: 100%;
        }

        /* Card */
        .card {
            background: #e8f5e9;
            border-radius: 15px;
            padding: 40px 50px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h1 { font-size: 60px; color: #2e7d32; margin-top: 10px; }
        .card h3 { font-size: 24px; color: #1b5e20; }

        /* Chart container */
        .chart-container {
            background: #f1f8f1;
            border-radius: 15px;
            padding: 30px;
        }

        @media (max-width: 1000px) {
            .dashboard { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <img src="https://car-logos.org/wp-content/uploads/2022/08/perodua.png" alt="Perodua Logo" class="logo-main">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php"><i class="fas fa-file-alt"></i> My Attendance</a>
    <a href="Admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Report</a>
    <a href="AdminAttendanceRecord.php"><i class="fas fa-calendar-check"></i> Attendance Report</a>
    <a href="AttendanceRateTable.php"><i class="fas fa-users"></i> Attendance Rate</a>
    <a href="AdminEmployeeList.php"><i class="fas fa-users"></i> Update Employee</a>
</div>

<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= $role ?>)</h3>
        <div class="date-time">
            <span><?= $date_today ?></span> | 
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

<div class="main-content">
    <div class="dashboard">
        <!-- Total Employees Card -->
        <div class="card">
            <h3>Total Employees</h3>
            <h1 id="employeeCount">0</h1>
        </div>

        <!-- Daily Attendance Rate Card -->
        <div class="card">
            <h3>Daily Attendance Rate</h3>
            <h1><?= $daily_attendance_rate ?>%</h1>
        </div>

        <!-- Pie Chart -->
        <div class="chart-container">
            <h3 style="text-align:center; margin-bottom:15px;">Employees by Department</h3>
            <canvas id="deptChart" height="400"></canvas>
        </div>

        <!-- Bar Chart -->
        <div class="chart-container">
            <h3 style="text-align:center; margin-bottom:15px;">Employees by Status (Today)</h3>
            <canvas id="statusChart" height="400"></canvas>
        </div>
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
        const now = new Date();
        clock.textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Pie Chart for Employees by Department
    new Chart(document.getElementById('deptChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($departments) ?>,
            datasets: [{
                data: <?= json_encode($dept_counts) ?>,
                backgroundColor: ['#e26014ff','#fc0505ff','#caf702ff','#06f81bff','#d104f5ff']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#0c0b0bff',
                    font: { align: 'center', weight: 'bold', size: 14 },
                    formatter: (value, ctx) => {
                        let label = ctx.chart.data.labels[ctx.dataIndex];
                        return label + ": " + value;
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Bar Chart for Employees by Status (Today)
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($statuses) ?>,
            datasets: [{
                label: 'Total Employees',
                data: <?= json_encode($status_counts) ?>,
                backgroundColor: <?= json_encode($colors) ?>
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#333',
                    font: { weight: 'bold', size: 13 },
                    formatter: (value) => value
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Employees' }
                },
                x: {
                    title: { display: true, text: 'Status' }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
</script>

</body>
</html>
