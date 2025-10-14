<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$employee_name = $_SESSION['employee_name'] ?? $_SESSION['name'];
$role = $_SESSION['role'];
$today = date("Y-m-d");

// Fetch today's attendance with POS included
$sql = "
    SELECT e.employee_id, e.name, e.POS, e.department, e.photo, a.status, a.sub_status, a.note, a.date
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

// Count statistics
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
body { background:#f2f2f2; color:#111; min-height:100vh; overflow-x:hidden; }

/* Sidebar */
.sidebar {
    position: fixed; left:0; top:0; bottom:0; width:260px;
    background:#111; color:white; padding:30px 0;
    box-shadow:3px 0 15px rgba(0,0,0,0.25); transition:0.3s;
    z-index:1100;
}
.sidebar h2 { text-align:center; margin-bottom:30px; font-size:28px; color:#fff; letter-spacing:1px; }
.sidebar a {
    display:flex; align-items:center; gap:15px; padding:14px 25px; margin:5px 15px;
    border-radius:12px; font-weight:500; color:white; text-decoration:none;
    transition:0.3s, box-shadow 0.3s;
}
.sidebar a i { font-size:18px; width:25px; text-align:center; }
.sidebar a span { flex:1; }
.sidebar a:hover { background:#222; box-shadow:0 4px 15px rgba(0,0,0,0.3); transform:translateX(5px); }
.sidebar a.active { background:#4CAF50; color:white; box-shadow:0 6px 20px rgba(0,0,0,0.3); }

/* Overlay for mobile */
.overlay {
    display:none;
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5);
    z-index:1000;
    transition:0.3s;
}

/* Hamburger toggle */
.sidebar-toggle {
    display:none;
    position: fixed;
    top:15px;
    left:15px;
    background:#4CAF50;
    border:none;
    padding:10px 12px;
    border-radius:8px;
    color:white;
    cursor:pointer;
    z-index:1200;
}

/* Topbar */
.topbar {
    position: fixed; left:260px; right:0; top:0; height:70px; background:#111; color:white;
    display:flex; justify-content:space-between; align-items:center; padding:0 30px;
    box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000;
}
.topbar h3 { font-weight:500; }
.topbar .date-time { font-size:14px; opacity:0.85; display:flex; gap:15px; }
.logout-btn { background:#fff; color:#111; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
.logout-btn:hover { background:#e0e0e0; }

/* Main content */
.main-content { margin-left: 260px; padding: 100px 30px 30px 30px; flex:1; }
h2 { text-align:center; color:#222; margin-bottom:15px; font-size:28px; }
h4 { text-align:center; color:#555; margin-top:0; font-size:16px; }

/* Stat Cards */
.stats { display:flex; gap:20px; flex-wrap:wrap; justify-content:center; margin-bottom:25px; }
.card { flex:1; min-width:160px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.07); text-align:center; transition: transform 0.3s; }
.card:hover { transform: translateY(-5px); }
.card h3 { font-size:24px; color:#222; margin-bottom:8px; }
.card p { font-size:14px; color:#555; }

/* Container */
.container { background:#fff; padding:25px 30px; border-radius:15px; box-shadow:0 6px 18px rgba(0,0,0,0.1); }

/* Table wrapper for scroll */
.table-wrapper {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 65vh;
    margin-top:20px;
    border-radius:10px;
    border:1px solid #ddd;
}

/* Table */
table { width:100%; border-collapse: collapse; min-width:700px; }
th, td { padding:12px 15px; text-align:center; border-bottom:1px solid #eee; }
th { background:#111; color:white; font-size:14px; text-transform:uppercase; }
td { background:#fefefe; font-size:15px; }
tr:hover td { background:#f1f1f1; transition:0.3s; }
img { width:70px; height:70px; object-fit:cover; border-radius:50%; }

/* Row coloring */
.status-mia td { background-color:#f8d7da !important; }
.status-out td { background-color:#fff3cd !important; }
.status-avail td { background-color:#d4edda !important; }
.status-none td { background-color:#ffffff !important; }

/* Legend */
.legend { margin:15px 0; display:flex; justify-content:center; gap:25px; flex-wrap:wrap; }
.legend-item { display:flex; align-items:center; gap:10px; font-size:14px; }
.legend-box { width:22px; height:22px; border-radius:4px; border:1px solid #ccc; }
.legend-red { background:#f8d7da; }
.legend-yellow { background:#fff3cd; }
.legend-green { background:#d4edda; }
.legend-white { background:#ffffff; }

/* Modal */
.modal {
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    justify-content:center; align-items:center; z-index:2000;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(6px);
    opacity: 0;
    transition: opacity 0.4s ease;
}
.modal.show { display:flex; opacity:1; }
.modal-content {
    background:#fff; padding:25px; border-radius:12px; width:320px; text-align:center;
    transform: translateY(-20px);
    opacity: 0;
    animation: fadeInUp 0.4s forwards;
}
.modal-content h3 { margin-bottom:20px; color:#111; }
.modal-buttons { display:flex; justify-content:space-around; gap:15px; }
.modal-buttons button { flex:1; padding:10px 18px; border:none; border-radius:8px; cursor:pointer; font-weight:500; }

@keyframes fadeInUp {
    from { opacity:0; transform: translateY(-20px); }
    to { opacity:1; transform: translateY(0); }
}

/* Responsive */
@media(max-width:900px) {
    .main-content { padding:120px 15px 30px 15px; margin-left:0; }
    .topbar { left:0; padding:0 15px; }
    .sidebar { width:260px; left:-260px; top:0; padding-top:20px; transition:0.3s; }
    .sidebar.show { left:0; }
    .sidebar-toggle { display:block; }
    .overlay.show { display:block; }
    .table-wrapper { max-height: 60vh; }
    table { min-width:600px; font-size:13px; }
    th, td { padding:8px 10px; font-size:12px; }
}

/* Buttons hover effect */
button:hover { opacity:0.85; transition:0.3s; }

</style>
<script>
// Clock
function updateClock() {
    document.getElementById("clock").textContent=new Date().toLocaleTimeString();
}
setInterval(updateClock,1000);
updateClock();

// Modals
function showLogoutModal() { document.getElementById('logoutModal').classList.add('show'); }
function closeModal(id){ document.getElementById(id).classList.remove('show'); }
function confirmLogout() { document.getElementById('logoutForm').submit(); }

// Mobile sidebar
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.overlay').classList.toggle('show');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('show');
    document.querySelector('.overlay').classList.remove('show');
}
</script>
</head>
<body>

<!-- Overlay -->
<div class="overlay" onclick="closeSidebar()"></div>

<!-- Hamburger -->
<button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceUpdate.php'?'active':'' ?>"><span>My Attendance</span></a>
    <a href="AdminEmployeeList.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminEmployeeList.php'?'active':'' ?>"><span>Employee Management</span></a>
    <a href="AdminDashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminDashboard.php'?'active':'' ?>"></i><span>Analysis Dashboard</span></a>
    <a href="AdminAttendanceList.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceList.php'?'active':'' ?>"><span>Attendance List</span></a>
    <a href="AdminCalendar.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminCalendar.php'?'active':'' ?>"><span>Calendar Management</span></a>
    <a href="AdminAttendanceTable.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceTable.php'?'active':'' ?>"><span>Attendance Table</span></a>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> </h3>
        <div class="date-time">
            <span><?= date("l, d F Y") ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <button class="logout-btn" onclick="showLogoutModal()">Logout</button>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Stat Cards -->
    <div class="stats">
        <div class="card"><h3><?= $stats['available'] ?></h3><p>Available</p></div>
        <div class="card"><h3><?= $stats['outstation'] ?></h3><p>OutStation</p></div>
        <div class="card"><h3><?= $stats['mia'] ?></h3><p>MC</p></div>
        <div class="card"><h3><?= $stats['none'] ?></h3><p>Not Submitted</p></div>
    </div>

    <div class="container">
        <h2>Attendance List</h2>
        <h4><?= $today ?></h4>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item"><div class="legend-box legend-red"></div>MC</div>
            <div class="legend-item"><div class="legend-box legend-yellow"></div> OutStation</div>
            <div class="legend-item"><div class="legend-box legend-green"></div> Available</div>
            <div class="legend-item"><div class="legend-box legend-white"></div> Not Submitted</div>
        </div>

        <!-- Scrollable Table -->
        <div class="table-wrapper">
        <table>
            <tr>
                <th>No</th>
                <th>Employee ID</th>
                <th>Photo</th>
                <th>Name</th>
                <th>POS</th>
                <th>Section</th>
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
                <td><?= htmlspecialchars($row['POS'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['status'] ?? 'Not Submitted') ?></td>
                <td><?= htmlspecialchars($row['sub_status'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Logout?</h3>
        <div class="modal-buttons">
            <form id="logoutForm" method="POST" action="LoginPerodua.php">
                <input type="hidden" name="logout_confirm" value="1">
                <button type="button" onclick="confirmLogout()" style="background:#4CAF50; color:white;">Yes</button>
                <button type="button" onclick="closeModal('logoutModal')" style="background:#ccc;">No</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
