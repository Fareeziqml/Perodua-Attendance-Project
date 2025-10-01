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
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding:15px 30px;
            background: #388e3c;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        header img {
            height:50px;
        }
        .header-left {
            display:flex;
            align-items:center;
            gap:20px;
        }
        .user-info {
            font-size:14px;
            line-height:1.4;
        }
        .clock {
            font-weight:bold;
            font-size:14px;
        }
        header nav {
            display:flex;
            gap:15px;
        }
        header nav a {
            color:white;
            text-decoration:none;
            font-weight:500;
            padding:8px 14px;
            border-radius:5px;
            transition: background 0.3s;
        }
        header nav a:hover {
            background:#2e7d32;
        }
        header .logout {
            background:#d32f2f;
            padding:8px 14px;
            border-radius:5px;
        }
        header .logout:hover {
            background:#b71c1c;
        }

        h2 {
            text-align:center; 
            margin:20px 0; 
            font-size:24px; 
            color:#2e7d32;
        }

.table-container {
    padding: 20px;
    max-width: 100%;
    max-height: 80vh;
    overflow-x: auto;
    overflow-y: auto;
}

table { 
    border-collapse: collapse; 
    table-layout: fixed;
    min-width: 1500px;
    background:white;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
th, td { 
    border:1px solid #ddd; 
    padding:6px; 
    text-align:center; 
    font-size:13px; 
    word-wrap: break-word;
}

        th { 
            background:#388e3c; 
            color:white; 
            font-size:14px;
        }
        .dept-row td { 
            background:#e8f5e9; 
            font-weight:bold; 
            text-align:left; 
        }
        .summary-row { 
            background:#c8e6c9; 
            font-weight:bold; 
        }
        input[type=text], textarea { 
            width: 95%; 
            padding:4px; 
            font-size:12px; 
            border:1px solid #ccc; 
            border-radius:4px; 
        }
        textarea {
            resize: vertical;
            height:50px;
        }

        .weekend { background:#eeeeee; }
        .status-red { background:#f44336; color:white; font-weight:bold; }      
        .status-yellow { background:#ffeb3b; color:black; font-weight:bold; }    
        .status-green { background:#4caf50; color:white; font-weight:bold; }     

        .submit-btn {
            display:block;
            margin:20px auto;
            padding:10px 20px;
            background:#388e3c;
            color:white;
            border:none;
            border-radius:6px;
            font-size:14px;
            cursor:pointer;
            transition:background 0.3s;
        }
        .submit-btn:hover {
            background:#2e7d32;
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <div class="logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Perodua_Logo_%282008_-_Present%29.svg/2560px-Perodua_Logo_%282008_-_Present%29.svg.png" alt="Company Logo">
        </div>
        <div class="user-info">
            <div>ID: <?= htmlspecialchars($emp_id) ?></div>
            <div>Name: <?= htmlspecialchars($emp_name) ?></div>
            <div class="clock" id="clock"></div>
        </div>
    </div>
    <nav>
        <a href="AdminAttendanceUpdate.php">Update Attendance</a>
        <a href="AdminDashboard.php">Dashboard</a>
        <a href="AdminAttendanceRecord.php">Attendance Record</a>
        <a href="AttendanceRateTable.php"><i class="fas fa-users"></i> Attendance Rate</a>
        <a href="AdminEmployeeList.php">Employee List</a>
        <a href="Logout.php" class="logout">Logout</a>
    </nav>
</header>

<h2>Attendance Rate Table - <?= date("F Y") ?></h2>

<div class="table-container">
<form method="post" action="save_notes.php">
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

                    // Determine color class
                    $class = '';
                    if (in_array($sub_status, $redStatuses)) {
                        $class = 'status-red';
                        if (!in_array($d, $weekends)) {
                            $dailyMIA[$d]++;
                        }
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

<button type="submit" class="submit-btn">💾 Save Notes</button>
</form>
</div>

<script>
function updateClock() {
    const now = new Date();
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    const dateStr = now.toLocaleDateString('en-GB', options);
    const timeStr = now.toLocaleTimeString('en-GB');
    document.getElementById("clock").innerText = dateStr + " | " + timeStr;
}
setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>
