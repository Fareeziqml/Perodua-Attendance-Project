<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check session (only GM can see this)
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$emp_id = $_SESSION['employee_id'];
$emp_name = $_SESSION['name'];

// Get month/year
$month = date("m");
$year  = date("Y");
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Get employees grouped by department (also fetch annual_leave & notes)
$emp_sql = "SELECT employee_id, name, department, annual_leave, notes 
            FROM employee ORDER BY department, name";
$emp_res = $conn->query($emp_sql);

$employees = [];
while ($row = $emp_res->fetch_assoc()) {
    $employees[$row['department']][] = $row;
}

// Get attendance data (sub_status)
$att_sql = "SELECT employee_id, DAY(date) as day, sub_status
            FROM attendance 
            WHERE MONTH(date) = '$month' AND YEAR(date) = '$year'";
$att_res = $conn->query($att_sql);

$attendance = [];
while ($row = $att_res->fetch_assoc()) {
    $attendance[$row['employee_id']][$row['day']] = $row['sub_status'];
}

// Count employees
$totalEmployees = $conn->query("SELECT COUNT(*) as total FROM employee")->fetch_assoc()['total'];

// Define weekends dynamically for this month
$weekends = [];
for ($d=1; $d <= $daysInMonth; $d++) {
    $weekday = date("N", strtotime("$year-$month-$d")); // 6=Saturday, 7=Sunday
    if ($weekday >= 6) {
        $weekends[] = $d;
    }
}

// Define sub-status color mapping
$redStatuses = ['AL - Annual Leave','MC - Medical Leave','EL - Emergency Leave','HL - Hospitalisation Leave','ML - Maternity Leave','1/2pm - Half Day (PM)','1/2am - Half Day (AM)','MIA - Missing In Action'];
$yellowStatuses = ['TR - Training','OS - OutStation'];
$greenStatuses = ['In Office'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Rate Table</title>
    <style>
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background:#f4f6f9; 
            color:#333;
        }

        /* === Topbar === */
        .topbar {
            position: fixed; left:0; right:0; top:0; height:70px; background:#111; color:white;
            display:flex; justify-content:space-between; align-items:center; padding:0 30px;
            box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000;
        }
        .topbar .left { display:flex; align-items:center; gap:20px; }
        .topbar img { height:45px; }
        .topbar nav { display:flex; gap:15px; }
        .topbar nav a {
            color:white; text-decoration:none; font-weight:500; padding:8px 14px;
            border-radius:6px; transition:0.3s;
        }
        .topbar nav a:hover { background:#333; }
        .logout-btn {
            background:#fff; color:#111; border:none; padding:8px 18px;
            border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s;
        }
        .logout-btn:hover { background:#e0e0e0; }

        /* === Popup Styles === */
        .popup-overlay {
            position: fixed;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            animation: fadeIn 0.4s ease forwards;
        }
        .popup {
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            width: 320px;
            transform: scale(0.8);
            opacity: 0;
            animation: scaleIn 0.35s ease forwards;
        }
        .popup h3 {
            margin: 0 0 15px;
            font-size: 18px;
            color:#111;
        }
        .popup button {
            margin: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .popup .yes-btn { background:#111; color:#fff; }
        .popup .yes-btn:hover { background:#333; }
        .popup .cancel-btn { background:#ccc; color:#111; }
        .popup .cancel-btn:hover { background:#aaa; }

        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes scaleIn { from { transform: scale(0.8); opacity:0; } to { transform: scale(1); opacity:1; } }

        /* === Content === */
        h2 { text-align:center; margin:100px 0 20px 0; font-size:24px; color:#111; }
        .table-container { padding: 20px; max-width: 100%; max-height: 80vh; overflow:auto; }
        table { border-collapse: collapse; table-layout: fixed; min-width: 1500px; background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
        th, td { border:1px solid #555; padding:6px; text-align:center; font-size:13px; word-wrap: break-word; }
        th { background:#111; color:white; font-size:14px; }
        .dept-row td { background:#333; color:white; font-weight:bold; text-align:left; }
        .summary-row { background:#222; color:white; font-weight:bold; }
        input[type=text], textarea { width: 95%; padding:4px; font-size:12px; border:1px solid #888; border-radius:4px; }
        textarea { resize: vertical; height:50px; }
        .weekend { background:#f0f0f0; color:#111; }

        /* Softer Status Colors */
        .status-red { background:#ffb3b3; color:#b30000; font-weight:bold; }      
        .status-yellow { background:#fff5b3; color:#7a7000; font-weight:bold; }    
        .status-green { background:#b3ffb3; color:#006600; font-weight:bold; }     

        .submit-btn {
            display:block; margin:20px auto; padding:10px 20px;
            background:#111; color:white; border:none; border-radius:6px;
            font-size:14px; cursor:pointer; transition:background 0.3s;
        }
        .submit-btn:hover { background:#333; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="left">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Perodua_Logo_%282008_-_Present%29.svg/2560px-Perodua_Logo_%282008_-_Present%29.svg.png" alt="Company Logo">
        <div>
            <div>ID: <?= htmlspecialchars($emp_id) ?></div>
            <div>Name: <?= htmlspecialchars($emp_name) ?></div>
        </div>
    </div>
    <nav>
        <a href="AdminAttendanceUpdate.php">Update Attendance</a>
        <a href="AdminDashboard.php">Dashboard</a>
        <a href="AdminAttendanceRecord.php">Attendance Record</a>
        <a href="AttendanceRateTable.php">Attendance Rate</a>
        <a href="AdminEmployeeList.php">Employee List</a>
    </nav>
    <button type="button" class="logout-btn" onclick="openPopup('logoutPopup')">Logout</button>
</div>

<!-- Logout Popup -->
<div class="popup-overlay" id="logoutPopup">
    <div class="popup">
        <h3>Are you sure you want to logout?</h3>
        <form method="post" action="Logout.php" style="display:inline;">
            <button type="submit" class="yes-btn">Yes</button>
        </form>
        <button type="button" class="cancel-btn" onclick="closePopup('logoutPopup')">Cancel</button>
    </div>
</div>

<!-- Save Popup -->
<div class="popup-overlay" id="savePopup">
    <div class="popup">
        <h3>Save Notes?</h3>
        <p>Your changes will be saved.</p>
        <button type="button" class="yes-btn" onclick="submitNotes()">Save</button>
        <button type="button" class="cancel-btn" onclick="closePopup('savePopup')">Cancel</button>
    </div>
</div>

<h2>Attendance Rate Table - <?= date("F Y") ?></h2>

<div class="table-container">
<form method="post" action="save_notes.php" id="notesForm">
<table>
    <tr>
        <th>No</th>
        <th>Name</th>
        <th>Employee ID</th>
        <th>Total Annual Leave</th>
        <th>Notes</th>
        <?php for ($d=1; $d <= $daysInMonth; $d++): ?>
            <th class="<?= in_array($d, $weekends) ? 'weekend' : '' ?>"><?= $d ?></th>
        <?php endfor; ?>
    </tr>

    <?php
    $no = 1;
    $dailyMIA = array_fill(1, $daysInMonth, 0);

    foreach ($employees as $dept => $empList):
    ?>
        <tr class="dept-row"><td colspan="<?= 5 + $daysInMonth ?>">Department: <?= htmlspecialchars($dept) ?></td></tr>

        <?php foreach ($empList as $emp): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= $emp['employee_id'] ?></td>
                <td><input type="text" name="annual_leave[<?= $emp['employee_id'] ?>]" value="<?= htmlspecialchars($emp['annual_leave']) ?>" placeholder="Enter leave"></td>
                <td><textarea name="notes[<?= $emp['employee_id'] ?>]" placeholder="Enter notes"><?= htmlspecialchars($emp['notes']) ?></textarea></td>

                <?php for ($d=1; $d <= $daysInMonth; $d++):
                    $sub_status = $attendance[$emp['employee_id']][$d] ?? ""; 
                    $class = '';
                    if (in_array($sub_status, $redStatuses)) {
                        $class = 'status-red';
                        if (!in_array($d, $weekends)) $dailyMIA[$d]++;
                    } elseif (in_array($sub_status, $yellowStatuses)) {
                        $class = 'status-yellow';
                    } elseif (in_array($sub_status, $greenStatuses)) {
                        $class = 'status-green';
                    }
                    if (in_array($d, $weekends)) $class .= ' weekend';
                ?>
                    <td class="<?= $class ?>"><?= htmlspecialchars($sub_status) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <tr class="summary-row">
        <td colspan="5">Total MC/AL/EL/HL/ML/MIA</td>
        <?php for ($d=1; $d <= $daysInMonth; $d++): ?>
            <td class="<?= in_array($d, $weekends) ? 'weekend' : '' ?>">
                <?= in_array($d, $weekends) ? '-' : $dailyMIA[$d] ?>
            </td>
        <?php endfor; ?>
    </tr>

    <tr class="summary-row">
        <td colspan="5">Attendance Rate (%)</td>
        <?php
        $dailyRate = [];
        for ($d=1; $d <= $daysInMonth; $d++):
            if (in_array($d, $weekends)) {
                $dailyRate[$d] = null;
                echo "<td class='weekend'>-</td>";
            } else {
                $rate = (($totalEmployees - $dailyMIA[$d]) / $totalEmployees) * 100;
                $dailyRate[$d] = round($rate, 2);
                echo "<td>{$dailyRate[$d]}</td>";
            }
        endfor;
        ?>
    </tr>

    <tr class="summary-row">
        <td colspan="5">Average Attendance Rate</td>
        <?php
        $validRates = array_filter($dailyRate, fn($v) => $v !== null);
        $avgRate = round(array_sum($validRates) / count($validRates), 2);
        ?>
        <td colspan="<?= $daysInMonth ?>"><?= $avgRate ?>%</td>
    </tr>
</table>

<button type="button" class="submit-btn" onclick="openPopup('savePopup')">💾 Save Notes</button>
</form>
</div>

<script>
function openPopup(id) {
    document.getElementById(id).style.display = "flex";
}
function closePopup(id) {
    document.getElementById(id).style.display = "none";
}
function submitNotes() {
    document.getElementById("notesForm").submit();
}
</script>

</body>
</html>
