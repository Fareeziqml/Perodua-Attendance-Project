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
$today = date("d");
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// === Define Malaysian Public Holidays (adjust yearly) ===
$publicHolidays = [ 
    "$year-01-01", "$year-01-29", "$year-01-30",
    "$year-03-18", "$year-03-31", "$year-04-01",
    "$year-05-01", "$year-05-12", "$year-06-02",
    "$year-10-20", "$year-11-02", "$year-12-11",
    "$year-12-25"
];

// Get employees grouped by department (include POS)
$emp_sql = "SELECT employee_id, name, POS, department, annual_leave, notes 
            FROM employee ORDER BY department, name";
$emp_res = $conn->query($emp_sql);

$employees = [];
while ($row = $emp_res->fetch_assoc()) {
    $employees[$row['department']][] = $row;
}

// Get attendance data
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

// === Define weekends and holidays ===
$weekends = [];
$holidayDays = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateString = sprintf("%04d-%02d-%02d", $year, $month, $d);
    $weekday = date("N", strtotime($dateString));
    if ($weekday >= 6) $weekends[] = $d;
    if (in_array($dateString, $publicHolidays)) $holidayDays[] = $d;
}
$nonWorkingDays = array_unique(array_merge($weekends, $holidayDays));

// === Working day counts ===
$workingDays = 0;
for ($d = 1; $d <= $daysInMonth; $d++) {
    if (!in_array($d, $nonWorkingDays)) $workingDays++;
}
$remainingWorkingDays = 0;
for ($d = $today; $d <= $daysInMonth; $d++) {
    if (!in_array($d, $nonWorkingDays)) $remainingWorkingDays++;
}

// === Status groups ===
$redStatuses = ['AL - Annual Leave','MC - Medical Leave','EL - Emergency Leave','HL - Hospitalisation Leave','ML - Maternity Leave','1/2pm - Half Day (PM)','1/2am - Half Day (AM)','MIA - Missing In Action'];
$yellowStatuses = ['TR - Training','OS - OutStation'];
$greenStatuses = ['In Office'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Rate Table</title>
    <style>
        body { margin:0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f9; color:#333; }
        .topbar { position: fixed; left:0; right:0; top:0; height:70px; background:#111; color:white; display:flex; justify-content:space-between; align-items:center; padding:0 30px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000; }
        .topbar .left { display:flex; align-items:center; gap:20px; }
        .topbar img { height:45px; }
        .topbar nav { display:flex; gap:15px; }
        .topbar nav a { color:white; text-decoration:none; font-weight:500; padding:8px 14px; border-radius:6px; transition:0.3s; }
        .topbar nav a:hover { background:#333; }
        .logout-btn { background:#fff; color:#111; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
        .logout-btn:hover { background:#e0e0e0; }

        h2 { text-align:center; margin:100px 0 10px 0; font-size:24px; color:#111; }
        .working-day-info { text-align:center; margin-bottom:20px; font-size:16px; color:#444; }
        .table-container { padding:20px; max-width:100%; max-height:80vh; overflow:auto; }
        table { border-collapse:collapse; table-layout:fixed; min-width:1500px; background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
        th, td { border:1px solid #555; padding:6px; text-align:center; font-size:13px; word-wrap:break-word; }
        th { background:#111; color:white; font-size:14px; }
        .dept-row td { background:#333; color:white; font-weight:bold; text-align:left; }
        .summary-row { background:#222; color:white; font-weight:bold; }
        input[type=text], textarea { width:95%; padding:4px; font-size:12px; border:1px solid #888; border-radius:4px; }
        textarea { resize:vertical; height:50px; }

        .weekend, .holiday { background:#dcdcdc !important; color:#111; }
        .status-red { background:#ffb3b3; color:#b30000; font-weight:bold; }      
        .status-yellow { background:#fff5b3; color:#7a7000; font-weight:bold; }    
        .status-green { background:#b3ffb3; color:#006600; font-weight:bold; }     

        .delete-btn { background:transparent; border:none; cursor:pointer; font-size:14px; color:#444; transition:0.2s; margin-left:4px; }
        .delete-btn:hover { color:#b30000; transform:scale(1.1); }

        .submit-btn { display:block; margin:20px auto; padding:10px 20px; background:#111; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; transition:background 0.3s; }
        .submit-btn:hover { background:#333; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(6px); display: none; justify-content: center; align-items: center; z-index: 2000; animation: fadeIn 0.3s ease forwards; }
        .overlay.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .popup { background: #fff; border-radius: 12px; padding: 30px 40px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); text-align: center; max-width: 400px; width: 90%; transform: translateY(-20px); animation: slideIn 0.35s ease forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .popup h3 { margin-top:0; color:#111; }
        .popup p { color:#555; margin:10px 0 25px; }
        .popup button { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; margin:0 10px; }
        .btn-confirm { background:#111; color:white; }
        .btn-cancel { background:#ccc; color:#111; }
        .btn-confirm:hover { background:#333; }
        .btn-cancel:hover { background:#bbb; }
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
        <a href="AdminAttendanceUpdate.php">My Attendance</a>
        <a href="AdminDashboard.php">Dashboard</a>
        <a href="AdminAttendanceRecord.php">Attendance Record</a>
        <a href="AttendanceRateTable.php">Attendance Rate</a>
        <a href="AdminEmployeeList.php">Employee List</a>
    </nav>
    <button type="button" class="logout-btn" onclick="openPopup('logout')">Logout</button>
</div>

<h2>Attendance Rate Table - <?= date("F Y") ?></h2>
<div class="working-day-info">
    <div>Total Working Days: <?= $workingDays ?> days</div>
    <div>Count Day Before End: <?= $remainingWorkingDays ?> working days left (from today)</div>
</div>

<div class="table-container">
<form method="post" action="save_notes.php" id="notesForm">
<table>
    <tr>
        <th>No</th>
        <th>Name</th>
        <th>POS</th>
        <th>Employee ID</th>
        <th>Total Annual Leave</th>
        <th>Notes</th>
        <?php for ($d = 1; $d <= $daysInMonth; $d++): 
            $dateString = sprintf("%04d-%02d-%02d", $year, $month, $d);
            $isWeekend = in_array($d, $weekends);
            $isHoliday = in_array($d, $holidayDays);
            $extraClass = '';
            if ($isWeekend && $isHoliday) $extraClass = 'weekend holiday';
            elseif ($isHoliday) $extraClass = 'holiday';
            elseif ($isWeekend) $extraClass = 'weekend';
        ?>
            <th class="<?= $extraClass ?>"><?= $d ?></th>
        <?php endfor; ?>
    </tr>

    <?php
    $no = 1;
    $dailyMIA = array_fill(1, $daysInMonth, 0);

    foreach ($employees as $dept => $empList):
    ?>
        <tr class="dept-row"><td colspan="<?= 6 + $daysInMonth ?>">Department: <?= htmlspecialchars($dept) ?></td></tr>

        <?php foreach ($empList as $emp): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['POS']) ?></td>
                <td><?= htmlspecialchars($emp['employee_id']) ?></td>
                <td><input type="text" name="annual_leave[<?= $emp['employee_id'] ?>]" value="<?= htmlspecialchars($emp['annual_leave']) ?>"></td>
                <td><textarea name="notes[<?= $emp['employee_id'] ?>]"><?= htmlspecialchars($emp['notes']) ?></textarea></td>

                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $sub_status = $attendance[$emp['employee_id']][$d] ?? ""; 
                    $class = '';
                    $isNonWorking = in_array($d, $nonWorkingDays);

                    if (in_array($sub_status, $redStatuses)) {
                        $class = 'status-red';
                        if (!$isNonWorking) $dailyMIA[$d]++;
                    } elseif (in_array($sub_status, $yellowStatuses)) {
                        $class = 'status-yellow';
                    } elseif (in_array($sub_status, $greenStatuses)) {
                        $class = 'status-green';
                    } else {
                        // Not submitted -> count as absent (but no background color)
                        if (!$isNonWorking) $dailyMIA[$d]++;
                    }

                    if ($isNonWorking) {
                        if (in_array($d, $holidayDays)) $class .= ' holiday';
                        else $class .= ' weekend';
                    }
                ?>
                    <td class="<?= $class ?>">
                        <?= htmlspecialchars($sub_status) ?>
                        <?php if ($sub_status != ""): ?>
                            <button type="button" class="delete-btn" onclick="openDeletePopup('<?= $emp['employee_id'] ?>','<?= $year ?>','<?= $month ?>','<?= $d ?>')">🗑️</button>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <tr class="summary-row">
        <td colspan="6">Total MC/AL/EL/HL/ML/MIA (including not submitted)</td>
        <?php for ($d = 1; $d <= $daysInMonth; $d++): 
            $count = $dailyMIA[$d] ?? 0;
            $output = in_array($d, $nonWorkingDays) ? '-' : $count;
        ?>
            <td class="<?= in_array($d, $nonWorkingDays) ? 'weekend' : '' ?>"><?= $output ?></td>
        <?php endfor; ?>
    </tr>

    <tr class="summary-row">
        <td colspan="6">Attendance Rate (%)</td>
        <?php
        $dailyRate = [];
        for ($d = 1; $d <= $daysInMonth; $d++):
            if (in_array($d, $nonWorkingDays)) {
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
        <td colspan="6">Average Attendance Rate (Month)</td>
        <?php
        $validRates = array_filter($dailyRate, fn($v) => $v !== null);
        $avgRate = round(array_sum($validRates) / count($validRates), 2);
        ?>
        <td colspan="<?= $daysInMonth ?>"><?= $avgRate ?>%</td>
    </tr>
</table>

<button type="button" class="submit-btn" onclick="openPopup('saveNotes')">💾 Save Notes</button>
</form>
</div>

<div class="overlay" id="popupOverlay">
    <div class="popup" id="popupBox">
        <h3 id="popupTitle"></h3>
        <p id="popupMessage"></p>
        <div>
            <button class="btn-confirm" id="popupConfirm">Confirm</button>
            <button class="btn-cancel" onclick="closePopup()">Cancel</button>
        </div>
    </div>
</div>

<script>
let popupType = '';

function openPopup(type) {
    popupType = type;
    const overlay = document.getElementById('popupOverlay');
    const title = document.getElementById('popupTitle');
    const msg = document.getElementById('popupMessage');
    const confirmBtn = document.getElementById('popupConfirm');

    if (type === 'logout') {
        title.textContent = "Confirm Logout";
        msg.textContent = "Are you sure you want to log out?";
        confirmBtn.onclick = () => window.location.href = 'Logout.php';
    } else if (type === 'saveNotes') {
        title.textContent = "Save Notes";
        msg.textContent = "Are you sure you want to save all notes and annual leave updates?";
        confirmBtn.onclick = () => document.getElementById('notesForm').submit();
    }

    overlay.classList.add('active');
}

function openDeletePopup(empId, year, month, day) {
    popupType = 'delete';
    const overlay = document.getElementById('popupOverlay');
    document.getElementById('popupTitle').textContent = "Delete Attendance Record";
    document.getElementById('popupMessage').textContent = `Delete attendance for Employee ${empId} on ${year}-${month}-${day}?`;
    document.getElementById('popupConfirm').onclick = () => {
        window.location.href = `delete_attendance.php?employee_id=${empId}&year=${year}&month=${month}&day=${day}`;
    };
    overlay.classList.add('active');
}

function closePopup() {
    document.getElementById('popupOverlay').classList.remove('active');
}
</script>
</body>
</html>
