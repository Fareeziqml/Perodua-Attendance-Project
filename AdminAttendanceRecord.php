<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$employee_name = $_SESSION['name'];
$role = $_SESSION['role'];
$today = date("Y-m-d");

// Fetch today's attendance per employee
$sql = "
    SELECT e.employee_id, e.name, e.department, e.photo, a.status, a.sub_status, a.note, a.date
    FROM employee e
    LEFT JOIN (
        SELECT *
        FROM attendance
        WHERE date = '$today'
    ) a ON e.employee_id = a.employee_id
    ORDER BY
        CASE
            WHEN a.status IN ('MIA','MC','MIA/MC') THEN 1
            WHEN a.status = 'OutStation' THEN 2
            WHEN a.status = 'Available' THEN 3
            ELSE 4
        END,
        e.department,
        e.name
";
$result = $conn->query($sql);

// Count statistics only for today's records
$stats = ['available'=>0,'outstation'=>0,'mia'=>0,'none'=>0];
$rows = [];
while($row = $result->fetch_assoc()){
    $status = strtolower(trim($row['status'] ?? ''));
    $recordDate = $row['date'] ?? '';

    if ($recordDate === $today) {
        if ($status == "available") $stats['available']++;
        elseif ($status == "outstation") $stats['outstation']++;
        elseif ($status == "mia" || $status == "mc" || $status == "mia/mc") $stats['mia']++;
        else $stats['none']++;
    } else {
        $stats['none']++;
        $row['status'] = 'Not Submitted';
        $row['sub_status'] = '-';
        $row['note'] = '-';
    }
    $rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Dashboard - GM</title>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
*   {  
    box-sizing: border-box; 
    margin:0; 
    padding:0; 
    font-family: 'Segoe UI', Arial, sans-serif; 
}

    body {
        display: flex; 
        min-height: 100vh; 
        background: #f4f6f9; 
    }

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
         transition: all 0.3s; 
        }

    .sidebar img.logo-main {
         max-width: 160px; 
         max-height: 160px; 
         width: auto; 
         height: auto; 
         display: block; 
         margin: 0 auto 25px; 
         border-radius: 20%; 
        }

    .sidebar img.logo-main:hover { 
        transform: scale(1.05); 
    }

    .sidebar h2 {
         text-align: center; 
         font-size: 26px; 
         margin-bottom: 25px; 
         color: #fff; 
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

    .topbar h3 {
        font-weight: 500;
        font-size: 18px; 
        }

    .topbar .date-time {
        font-weight: 400;
        opacity: 0.85; 
        font-size: 15px; 
        display: flex; 
        gap: 20px; 
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
    }

    /* Stat Cards */
    .stats { 
        display: flex; 
        gap: 20px; 
        flex-wrap: wrap; 
        justify-content: center; 
        margin-bottom: 25px; 
    }

    .card {
        flex: 1; 
        min-width: 180px; 
        background: white;
        padding: 20px; 
        border-radius: 12px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        text-align: center; 
        transition: transform 0.3s; 
    }

    .card:hover { 
        transform: translateY(-5px); 
    }

    .card h3 { 
        font-size: 24px;
        color: #4CAF50; 
        margin-bottom: 8px; 
    }

    .card p { 
        font-size: 14px; 
        color: #555; 
    }

    /* Container */
    .container { 
        background: #fff; 
        padding: 25px 30px; 
        border-radius: 15px; 
        box-shadow: 0 6px 18px rgba(0,0,0,0.15); 
    }
    
    /* Headings */
    h2 { 
        text-align: center;
        color: #2e7d32; 
        margin-bottom: 15px; 
        font-size: 28px; 
    }

    h4 { 
        text-align: center; 
        color: #555; 
        margin-top: 0; 
        font-size: 16px; 
    }

    /* Table */
    table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0; 
        margin-top: 20px; 
        border-radius: 10px; 
        overflow: hidden; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    th, td { 
        padding: 12px 15px; 
        text-align: center; 
    }

    th {
        background: #4CAF50;
        color: white; 
        font-size: 16px; 
    }

    td { 
        background: #fefefe;
        font-size: 15px; 
    }

    tr:hover td { 
        background: #f1f1f1; 
        transition: 0.3s; 
    }

    img { 
        width: 70px; 
        height: 70px; 
        object-fit: cover; 
        border-radius: 50%; 
    }

        /* Row coloring */
    .status-mia td { background-color: #f8d7da !important; }   
    .status-out td { background-color: #fff3cd !important; }   
    .status-avail td { background-color: #d4edda !important; }   
    .status-none td { background-color: #ffffff !important; }    

        /* Legend */
    .legend { 
        margin: 15px 0;
         display: flex; 
         justify-content: center; 
         gap: 25px; 
        }

    .legend-item { 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-size: 14px; 
    }

    .legend-box { 
        width: 22px;
        height: 22px;
        border-radius: 4px;
        border: 1px solid #ccc; 
    }

    .legend-red { background: #f8d7da; }
    .legend-yellow { background: #fff3cd; }
    .legend-green { background: #d4edda; }
    .legend-white { background: #ffffff; }

        /* Responsive */
    @media (max-width: 1200px) {
        .main-content { margin-left: 0; margin-top: 100px; padding: 25px 15px; }
        .topbar { left: 0; }
        .sidebar { position: fixed; z-index: 1001; }
        .stats { flex-direction: column; align-items: center; }
        table th, table td { padding: 10px 8px; font-size: 14px; }
    }   

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="https://car-logos.org/wp-content/uploads/2022/08/perodua.png" alt="Perodua Logo" class="logo-main">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php"><i class="fas fa-file-alt"></i> My Attendance</a>
    <a href="Admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Report</a>
    <a href="AdminAttendanceRecord.php"><i class="fas fa-calendar-check"></i> Attendance Report</a>
    <a href="AttendanceRateTable.php"><i class="fas fa-users"></i> Attendance Rate</a>
    <a href="AdminEmployeeList.php"><i class="fas fa-users"></i> Update Employee</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= $role ?>)</h3>
        <div class="date-time">
            <span><?= date("l, d F Y") ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Stat Cards -->
    <div class="stats">
        <div class="card"><h3><?= $stats['available'] ?></h3><p>Available</p></div>
        <div class="card"><h3><?= $stats['outstation'] ?></h3><p>OutStation</p></div>
        <div class="card"><h3><?= $stats['mia'] ?></h3><p>MIA / MC</p></div>
        <div class="card"><h3><?= $stats['none'] ?></h3><p>Not Submitted</p></div>
    </div>

    <div class="container">
        <h2>Attendance Records</h2>
        <h4><?= $today ?></h4>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item"><div class="legend-box legend-red"></div> MIA / MC</div>
            <div class="legend-item"><div class="legend-box legend-yellow"></div> OutStation</div>
            <div class="legend-item"><div class="legend-box legend-green"></div> Available</div>
            <div class="legend-item"><div class="legend-box legend-white"></div> Not Submitted</div>
        </div>

        <table>
            <tr>
                <th>No</th>
                <th>Employee ID</th>
                <th>Photo</th>
                <th>Name</th>
                <th>Department</th>
                <th>Status</th>
                <th>Sub-Status</th>
                <th>Note</th>
            </tr>
            <?php 
            $no = 1;
            foreach($rows as $row): 
                $status = strtolower(trim($row['status'] ?? ''));
                $recordDate = $row['date'] ?? '';
                $class = "status-none";
                if ($recordDate === $today) {
                    if ($status == "mia" || $status == "mc" || $status == "mia/mc") $class="status-mia";
                    elseif ($status == "outstation") $class="status-out";
                    elseif ($status == "available") $class="status-avail";
                }
            ?>
            <tr class="<?= $class ?>">
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['employee_id']) ?></td>
                <td><?php if($row['photo']): ?><img src="<?= htmlspecialchars($row['photo']) ?>" alt="Photo"><?php else: ?><span>No Photo</span><?php endif; ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['status'] ?? 'Not Submitted') ?></td>
                <td><?= htmlspecialchars($row['sub_status'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function updateClock() {
    const clock = document.getElementById('clock');
    const now = new Date();
    clock.textContent = now.toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>
