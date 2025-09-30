<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Redirect if not logged in or not GM
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$emp_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? "GM"; 
$role = $_SESSION['role'] ?? "GM";
$today = date("Y-m-d");
$msg = "";

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: LoginPerodua.php");
    exit;
}

// GM can submit/update attendance for staff (or themselves if needed)
if (isset($_POST['status']) && isset($_POST['sub_status']) && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $status = $_POST['status'];
    $sub_status = $_POST['sub_status'];
    $note = $_POST['note'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($start_date > $end_date) {
        $msg = "⚠️ Invalid date range.";
    } else {
        $cur_date = $start_date;
        while ($cur_date <= $end_date) {
            $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id=? AND date=?");
            $check->bind_param("ss", $emp_id, $cur_date);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO attendance (employee_id, status, sub_status, date, note) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $emp_id, $status, $sub_status, $cur_date, $note);
                $stmt->execute();
            }
            $cur_date = date("Y-m-d", strtotime($cur_date . " +1 day"));
        }
        $msg = "✅ Attendance submitted for selected dates.";
    }
}

// Reset future applications
if (isset($_POST['reset_future'])) {
    $delFuture = $conn->prepare("DELETE FROM attendance WHERE employee_id=? AND date >= ?");
    $delFuture->bind_param("ss", $emp_id, $today);
    if ($delFuture->execute()) {
        $msg = "🔄 All applications from today and future have been reset.";
    } else {
        $msg = "❌ Error resetting applications.";
    }
}

// Fetch today
$today_sql = "SELECT * FROM attendance WHERE employee_id=? AND date = ?";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $emp_id, $today);
$today_stmt->execute();
$todayRecord = $today_stmt->get_result();

// Fetch history
$history_sql = "
    SELECT * FROM attendance 
    WHERE employee_id=? AND date < ?
    ORDER BY date DESC
";
$history = $conn->prepare($history_sql);
$history->bind_param("ss", $emp_id, $today);
$history->execute();
$records = $history->get_result();

// Fetch future
$future_sql = "
    SELECT * FROM attendance 
    WHERE employee_id=? AND date >= ?
    ORDER BY date ASC
";
$future = $conn->prepare($future_sql);
$future->bind_param("ss", $emp_id, $today);
$future->execute();
$futureRecords = $future->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GM Attendance - Perodua</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f5f5f5; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #1b5e20, #388e3c); color: white; position: fixed; top: 0; bottom: 0; left: 0; padding-top: 30px; box-shadow: 3px 0 15px rgba(0,0,0,0.2); }
        .sidebar img.logo-main { display: block; margin: 0 auto 25px; width: 160px; border-radius: 20%; }
        .sidebar h2 { text-align: center; font-size: 26px; margin-bottom: 25px; color:white;}
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 15px 25px; margin: 5px 10px; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.15); transform: translateX(10px); }
        .sidebar a i { font-size: 18px; }
        .topbar { position: fixed; top: 0; left: 260px; right: 0; height: 70px; background: #2e7d32; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; }
        .topbar h3 { font-weight: 500; font-size: 18px; }
        .topbar .date-time { font-weight: 400; opacity: 0.85; font-size: 15px; display: flex; gap: 20px; }
        .logout-btn { background: #c62828; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .logout-btn:hover { background: #b71c1c; transform: scale(1.05); }
        .main-content { margin-left: 260px; padding: 100px 30px 30px 30px; width: 100%; }
        h2 { color: #2e7d32; text-align: center; margin-bottom: 20px; }
        form { margin: 20px 0; text-align: center; }
        select, input[type=text], input[type=date] { padding: 10px; margin: 8px; border: 1px solid #ccc; border-radius: 6px; min-width: 200px; }
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
        #futureSection { display:none; }
    </style>
    <script>
        function updateSubStatusOptions() {
            const status = document.getElementById('status').value;
            const subStatusSelect = document.getElementById('sub_status');
            subStatusSelect.innerHTML = '';
            let options = [];
            if (status === 'MIA/MC') {
                options = ['AL - Annual Leave','MC - Medical Leave','EL - Emergency Leave','HL - Hospitalisation Leave','ML - Maternity Leave','1/2pm - Half Day (PM)','1/2am - Half Day (AM)','MIA - Missing In Action'];
            } else if (status === 'OutStation') {
                options = ['TR - Training','OS - OutStation'];
            } else if (status === 'Available') {
                options = ['-'];
            }
            options.forEach(function(opt){
                let option = document.createElement('option');
                option.value = opt;
                option.text = opt;
                subStatusSelect.appendChild(option);
            });
        }
        function toggleFuture() {
            const section = document.getElementById("futureSection");
            section.style.display = (section.style.display === "none" || section.style.display === "") ? "block" : "none";
        }
        function updateClock() {
            const now = new Date();
            document.getElementById("clock").textContent = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
    </script>
</head>
<body>
<div class="sidebar">
    <img src="https://car-logos.org/wp-content/uploads/2022/08/perodua.png" alt="Perodua Logo" class="logo-main">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php"><i class="fas fa-file-alt"></i> My Attendance</a>
    <a href="Admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Report</a>
    <a href="AdminAttendanceRecord.php"><i class="fas fa-calendar-check"></i> Attendance Report</a>
    <a href="AdminEmployeeList.php"><i class="fas fa-users"></i> Update Employee</a>
</div>

<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= $role ?>)</h3>
        <div class="date-time">
            <span><?= date("l, d F Y") ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <form method="POST">
        <button type="submit" name="logout" class="logout-btn">Logout</button>
    </form>
</div>


<div class="main-content">
    <h2>GM Attendance - Staff ID: <?= htmlspecialchars($emp_id) ?></h2>

    <!-- Attendance submission -->
    <form method="POST">
        <label>Status:</label>
        <select name="status" id="status" onchange="updateSubStatusOptions()" required>
            <option value="">--Select--</option>
            <option value="MIA/MC">MIA/MC</option>
            <option value="OutStation">OutStation</option>
            <option value="Available">Available</option>
        </select>
        <label>Sub-Status:</label>
        <select name="sub_status" id="sub_status" required>
            <option value="">--Select--</option>
        </select>
        <input type="text" name="note" placeholder="Add Note (optional)">
        <br>
        <label>From:</label>
        <input type="date" name="start_date" required>
        <label>To:</label>
        <input type="date" name="end_date" required>
        <button type="submit">Submit</button>
    </form>

    <div class="msg"><?= $msg ?></div>

    <h3>Today Attendance Update</h3>
    <table>
        <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th></tr>
        <?php if ($todayRecord->num_rows > 0): ?>
            <?php while($row = $todayRecord->fetch_assoc()): ?>
                <?php
                    $statusClass = "";
                    if ($row['status'] == "MIA/MC") $statusClass = "status-mia";
                    elseif ($row['status'] == "OutStation") $statusClass = "status-outstation";
                    elseif ($row['status'] == "Available") $statusClass = "status-available";
                ?>
                <tr class="<?= $statusClass ?>">
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['sub_status']) ?></td>
                    <td><?= htmlspecialchars($row['note']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">❌ No attendance submitted for today.</td></tr>
        <?php endif; ?>
    </table>

    <button type="button" class="toggle-btn" onclick="toggleFuture()">Show/Hide Future Applications</button>
    <form method="POST" style="text-align:center; margin-top:15px;">
        <button type="submit" name="reset_future" class="reset-btn" onclick="return confirm('Delete ALL future applications (including today)?')">Reset All Applications</button>
    </form>

    <div id="futureSection">
        <h3>Future Applications (Today and Beyond)</h3>
        <table>
            <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th></tr>
            <?php if ($futureRecords->num_rows > 0): ?>
                <?php while($row = $futureRecords->fetch_assoc()): ?>
                    <?php
                        $statusClass = "";
                        if ($row['status'] == "MIA/MC") $statusClass = "status-mia";
                        elseif ($row['status'] == "OutStation") $statusClass = "status-outstation";
                        elseif ($row['status'] == "Available") $statusClass = "status-available";
                    ?>
                    <tr class="<?= $statusClass ?>">
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['sub_status']) ?></td>
                        <td><?= htmlspecialchars($row['note']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No future applications found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
