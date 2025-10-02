<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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
if (isset($_POST['logout_confirm'])) {
    session_destroy();
    header("Location: LoginPerodua.php");
    exit;
}

// GM Attendance Submission
if (isset($_POST['status'], $_POST['sub_status'], $_POST['start_date'], $_POST['end_date'])) {
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

// Handle delete
if (isset($_POST['delete_confirm'])) {
    $delete_id = $_POST['delete_id'];
    $del = $conn->prepare("DELETE FROM attendance WHERE id=? AND employee_id=?");
    $del->bind_param("is", $delete_id, $emp_id);
    if ($del->execute()) {
        $msg = "🗑️ Application deleted successfully.";
    } else {
        $msg = "❌ Error deleting application.";
    }
}

// Fetch records
$today_sql = "SELECT * FROM attendance WHERE employee_id=? AND date = ?";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $emp_id, $today);
$today_stmt->execute();
$todayRecord = $today_stmt->get_result();

$future_sql = "SELECT * FROM attendance WHERE employee_id=? AND date > ? ORDER BY date ASC";
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
    * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
    body { background:#f2f2f2; color:#111; min-height:100vh; }

    /* Sidebar */
    .sidebar {
        position: fixed; left:0; top:0; bottom:0; width:250px;
        background:#111; color:white; padding:30px 0;
        box-shadow:3px 0 15px rgba(0,0,0,0.2);
    }
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
    .logout-btn { background:#fff; color:#000; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
    .logout-btn:hover { background:#333; }

    /* Main Content */
    .main-content { margin-left:250px; padding:100px 30px 30px 30px; }
    h2 { text-align:center; color:#111; margin-bottom:30px; }
    .msg { text-align:center; color:#d32f2f; font-weight:500; margin:10px 0; }

    /* Card */
    .card { background:#fff; padding:25px; border-radius:15px; box-shadow:0 8px 20px rgba(0,0,0,0.1); margin-bottom:30px; transition:0.3s; }
    .card:hover { box-shadow:0 12px 25px rgba(0,0,0,0.2); }

    /* Forms */
    form { display:flex; flex-wrap:wrap; justify-content:center; gap:15px; align-items:center; margin-bottom:20px; }
    select, input[type=text], input[type=date] { padding:10px 12px; border:1px solid #ccc; border-radius:8px; min-width:160px; }
    button { padding:8px 18px; border:none; border-radius:8px; cursor:pointer; font-weight:500; transition:0.3s; }

    button[type="submit"]:not(.delete-btn):not(.logout-btn) { background:#4CAF50; color:#000; }
    button[type="submit"]:not(.delete-btn):not(.logout-btn):hover { background:#45a049; }

    .delete-btn { background:#e60000; color:white; border-radius:8px; }
    .delete-btn:hover { background:#b30000; }

    .toggle-btn { background:#2196F3; color:white; margin:10px 0; border-radius:8px; }
    .toggle-btn:hover { background:#1976d2; }

    /* Table */
    table { width:100%; border-collapse: collapse; margin-top:15px; border-radius:8px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
    th, td { padding:12px; text-align:center; border-bottom:1px solid #eee; }
    th { background:#111; color:white; text-transform:uppercase; font-size:14px; }
    tr:hover { background:#f1f1f1; }
    .badge { padding:5px 10px; border-radius:12px; color:white; font-weight:600; display:inline-block; }
    .badge-mia { background:#e53935; }
    .badge-outstation { background:#ffb300; color:#000; }
    .badge-available { background:#43a047; }

    /* Modal */
    .modal { 
        display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background:rgba(0,0,0,0.4); 
        backdrop-filter: blur(6px); /* NEW blur effect */
        justify-content:center; align-items:center; z-index:2000;
        opacity:0; transition: opacity 0.3s ease; /* NEW fade */
    }
    .modal.show { opacity:1; } /* active state */
    .modal-content { 
        background:#fff; padding:25px; border-radius:12px; width:320px; text-align:center; 
        transform: translateY(-30px); opacity:0;
        animation: fadeSlideIn 0.4s forwards; /* NEW animation */
    }
    @keyframes fadeSlideIn {
        to { transform: translateY(0); opacity:1; }
    }
    .modal-content h3 { margin-bottom:20px; color:#111; }
    .modal-buttons { display:flex; justify-content:space-around; }
    .modal-buttons button { width:100px; border-radius:8px; }

    /* Responsive */
    @media(max-width:900px) {
        .main-content { padding:120px 15px 30px 15px; }
        form { flex-direction:column; }
        select, input[type=text], input[type=date], button { width:90%; }
    }
</style>
<script>
let deleteId = null;

function updateSubStatusOptions() {
    const status = document.getElementById('status').value;
    const subStatusSelect = document.getElementById('sub_status');
    subStatusSelect.innerHTML = '';
    let options = [];
    if (status==='MIA/MC') options=['AL - Annual Leave','MC - Medical Leave','EL - Emergency Leave','HL - Hospitalisation Leave','ML - Maternity Leave','1/2pm - Half Day (PM)','1/2am - Half Day (AM)','MIA - Missing In Action'];
    else if (status==='OutStation') options=['TR - Training','OS - OutStation'];
    else if (status==='Available') options=['In Office'];
    options.forEach(opt=>{ let o=document.createElement('option'); o.value=o.text=opt; subStatusSelect.appendChild(o); });
}

function toggleFuture() {
    const section=document.getElementById("futureSection");
    section.style.display=(section.style.display==="none"||section.style.display==="")?"block":"none";
}

function updateClock() {
    const now=new Date();
    document.getElementById("clock").textContent=now.toLocaleTimeString();
}
setInterval(updateClock,1000);

// Modal functions
function showDeleteModal(id) {
    deleteId = id;
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
    setTimeout(()=> modal.classList.add('show'), 10);
}
function confirmDelete() {
    document.getElementById('delete_id_input').value = deleteId;
    document.getElementById('deleteForm').submit();
}
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    setTimeout(()=> modal.style.display='none', 300);
}

function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'flex';
    setTimeout(()=> modal.classList.add('show'), 10);
}
function confirmLogout() {
    document.getElementById('logoutForm').submit();
}
</script>
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
            <span><?= date("l, d F Y") ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <button class="logout-btn" onclick="showLogoutModal()">Logout</button>
</div>

<div class="main-content">
    <h2>GM Attendance - Staff ID: <?= htmlspecialchars($emp_id) ?></h2>

    <div class="card">
        <form method="POST">
            <select name="status" id="status" onchange="updateSubStatusOptions()" required>
                <option value="">--Select Status--</option>
                <option value="MIA/MC">MIA/MC</option>
                <option value="OutStation">OutStation</option>
                <option value="Available">Available</option>
            </select>
            <select name="sub_status" id="sub_status" required><option value="">--Select Sub-Status--</option></select>
            <input type="text" name="note" placeholder="Add Note (optional)">
            <input type="date" name="start_date" required>
            <input type="date" name="end_date" required>
            <button type="submit">Submit</button>
        </form>
        <div class="msg"><?= $msg ?></div>
    </div>

    <div class="card">
        <h3>Today Attendance Update</h3>
        <table>
            <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th><th>Action</th></tr>
            <?php if ($todayRecord->num_rows>0): while($row=$todayRecord->fetch_assoc()): ?>
                <?php
                    $badgeClass=""; 
                    if($row['status']=="MIA/MC") $badgeClass="badge-mia";
                    elseif($row['status']=="OutStation") $badgeClass="badge-outstation";
                    elseif($row['status']=="Available") $badgeClass="badge-available";
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td><?= htmlspecialchars($row['sub_status']) ?></td>
                    <td><?= htmlspecialchars($row['note']) ?></td>
                    <td>
                        <button class="delete-btn" onclick="showDeleteModal(<?= $row['id'] ?>)">Delete</button>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">❌ No attendance submitted for today.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="card">
        <button type="button" class="toggle-btn" onclick="toggleFuture()">Show/Hide Future Applications</button>
        <div id="futureSection">
            <h3>Future Applications</h3>
            <table>
                <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th><th>Action</th></tr>
                <?php if ($futureRecords->num_rows>0): while($row=$futureRecords->fetch_assoc()): ?>
                    <?php
                        $badgeClass=""; 
                        if($row['status']=="MIA/MC") $badgeClass="badge-mia";
                        elseif($row['status']=="OutStation") $badgeClass="badge-outstation";
                        elseif($row['status']=="Available") $badgeClass="badge-available";
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><?= htmlspecialchars($row['sub_status']) ?></td>
                        <td><?= htmlspecialchars($row['note']) ?></td>
                        <td>
                            <button class="delete-btn" onclick="showDeleteModal(<?= $row['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5">No future applications found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion?</h3>
        <div class="modal-buttons">
            <form id="deleteForm" method="POST">
                <input type="hidden" name="delete_id" id="delete_id_input">
                <button type="button" onclick="confirmDelete()" style="background:#e60000; color:white;">Yes</button>
                <button type="button" onclick="closeModal('deleteModal')" style="background:#ccc;">No</button>
                <input type="hidden" name="delete_confirm" value="1">
            </form>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Logout?</h3>
        <div class="modal-buttons">
            <form id="logoutForm" method="POST">
                <button type="button" onclick="confirmLogout()" style="background:#000; color:white;">Yes</button>
                <button type="button" onclick="closeModal('logoutModal')" style="background:#ccc;">No</button>
                <input type="hidden" name="logout_confirm" value="1">
            </form>
        </div>
    </div>
</div>

</body>
</html>
