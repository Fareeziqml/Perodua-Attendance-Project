<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$emp_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['name'];
$role = $_SESSION['role'];
$today = date("Y-m-d");
$msg = "";

if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $note = $_POST['note'];
    $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id=? AND date=?");
    $check->bind_param("ss", $emp_id, $today);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) $msg = "⚠️ You already submitted attendance for today.";
    else {
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, status, date, note) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $emp_id, $status, $today, $note);
        $msg = $stmt->execute() ? "✅ Attendance submitted successfully." : "❌ Error: ".$conn->error;
    }
}

if (isset($_POST['reset_attendance'])) {
    $dateToReset = $_POST['reset_attendance'];
    $del = $conn->prepare("DELETE FROM attendance WHERE employee_id=? AND date=?");
    $del->bind_param("ss", $emp_id, $dateToReset);
    $msg = $del->execute() ? "🔄 Attendance for $dateToReset has been reset." : "❌ Error resetting record.";
}

$history_sql = "
    SELECT * FROM attendance 
    WHERE employee_id=? 
    ORDER BY 
        CASE status
            WHEN 'MIA/MC' THEN 1
            WHEN 'OutStation' THEN 2
            WHEN 'Available' THEN 3
        END,
        date DESC
";
$history = $conn->prepare($history_sql);
$history->bind_param("s", $emp_id);
$history->execute();
$records = $history->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GM Attendance Update - Perodua</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Arial, sans-serif; }
body { background: #f5f5f5; display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #1b5e20, #388e3c);
    color: white;
    position: fixed;
    top: 0; bottom: 0; left: 0;
    padding-top: 30px;
    box-shadow: 3px 0 15px rgba(0,0,0,0.2);
    transition: all 0.3s;
}
.sidebar img.logo-main { display: block; margin: 0 auto 25px; width: 160px; border-radius: 20%; }
.sidebar img.logo-main:hover { transform: scale(1.05); }
.sidebar h2 { text-align: center; font-size: 26px; margin-bottom: 25px; color: White;}
.sidebar a { display: flex; align-items: center; gap: 12px; padding: 15px 25px; margin: 5px 10px; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
.sidebar a:hover { background: rgba(255,255,255,0.15); transform: translateX(10px); }
.sidebar a i { font-size: 18px; }

/* Topbar */
.topbar {
    position: fixed;
    top: 0; left: 260px; right: 0;
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
.topbar h3 { font-weight: 500; font-size: 18px; }
.topbar .date-time { font-weight: 400; opacity: 0.85; font-size: 15px; display: flex; gap: 20px; }
.logout-btn { background: #c62828; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
.logout-btn:hover { background: #b71c1c; transform: scale(1.05); }

/* Main content */
.main-content { margin-left: 260px; padding: 100px 30px 30px 30px; width: 100%; }
h2 { color: #2e7d32; text-align: center; margin-bottom: 20px; }
form { margin: 20px 0; text-align: center; }
select, input[type=text] { padding: 10px; margin: 8px; border: 1px solid #ccc; border-radius: 6px; min-width: 200px; }
button { background: #4CAF50; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: 0.3s; font-weight: 500; }
button:hover { background: #388E3C; }
.reset-btn { background: #f57c00; }
.reset-btn:hover { background: #ef6c00; }
.msg { text-align: center; margin: 10px 0; color: #d32f2f; font-weight: 500; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow:0 5px 15px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
th { background: #2e7d32; color: white; text-transform: uppercase; }
.status-mia { background-color: #ffcccc; font-weight: bold; }
.status-outstation { background-color: #fff4cc; font-weight: bold; }
.status-available { background-color: #ccffcc; font-weight: bold; }

/* Responsive */
@media (max-width: 1000px) { 
    .main-content { margin-left: 0; padding-top: 80px; } 
    .topbar { left: 0; } 
    .sidebar { width: 60px; padding-top: 20px; }
    .sidebar h2, .sidebar a span { display: none; }
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
    <h2>Update My Attendance (GM)</h2>

    <!-- Attendance submission -->
    <form method="POST">
        <label>Status:</label>
        <select name="status" required>
            <option value="MIA/MC">MIA/MC</option>
            <option value="OutStation">OutStation</option>
            <option value="Available">Available</option>
        </select>
        <input type="text" name="note" placeholder="Add Note (optional)">
        <button type="submit">Submit</button>
    </form>

    <div class="msg"><?= $msg ?></div>

    <h3>Attendance History</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Status</th>
            <th>Note</th>
            <th>Action</th>
        </tr>
        <?php while($row = $records->fetch_assoc()): ?>
            <?php
                $statusClass = $row['status']=="MIA/MC" ? "status-mia" : ($row['status']=="OutStation" ? "status-outstation" : "status-available");
            ?>
            <tr class="<?= $statusClass ?>">
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['note']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="reset_attendance" value="<?= htmlspecialchars($row['date']) ?>" class="reset-btn">Reset</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
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
