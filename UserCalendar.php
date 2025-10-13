<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Logout handling
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: LoginPerodua.php");
    exit;
}

// Only logged-in employees can access
if (!isset($_SESSION['employee_id'])) {
    header("Location: LoginPerodua.php");
    exit;
}

$emp_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['name'];
$role = $_SESSION['role'];

// Fetch holidays for calendar
$events = [];
$result = $conn->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => $row['title'],
        "start" => $row['holiday_date'],
        "description" => $row['description'],
        "backgroundColor" => "#ff4d4d",
        "borderColor" => "darkred"
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Holidays Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <style>
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
        body { background:#f2f2f2; color:#111; min-height:100vh; }

        /* Sidebar */
        .sidebar {
            height:100%; width:250px; position:fixed; top:0; left:-260px;
            background:#111; color:#fff; overflow-x:hidden; transition:0.4s; z-index:1200;
            padding-top:60px; border-right:3px solid #333;
        }
        .sidebar.open { left:0; }
        .sidebar a {
            padding:12px 25px; text-decoration:none; font-size:18px; color:#ddd; display:block; transition:0.3s;
        }
        .sidebar a:hover { background:#575757; color:#fff; }
        .sidebar .closebtn {
            position:absolute; top:15px; right:20px; font-size:28px; cursor:pointer; color:#fff;
        }
        .sidebar-header { padding:15px 25px; border-bottom:1px solid #444; margin-bottom:10px; }
        .sidebar-header h3 { margin:0; font-size:20px; }
        .sidebar a.active { background:#4CAF50; color:white; box-shadow:0 6px 20px rgba(0,0,0,0.3);}

        /* Overlay */
        .overlay {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.4); backdrop-filter:blur(3px); z-index:1100;
        }
        .overlay.active { display:block; }

        /* Menu button */
        .menu-btn {
            position:fixed; top:15px; left:20px;
            background:#009739; color:#fff; border:none;
            padding:10px 16px; font-size:18px;
            border-radius:8px; cursor:pointer; z-index:1300;
            transition:0.3s;
        }
        .menu-btn:hover { background:#007a2e; }

        /* Logout button */
        .logout-btn {
            width: 85%;
            margin: 15px auto;
            display: block;
            background:#c62828; color:#fff;
            border:none; padding:10px 16px;
            font-size:16px; border-radius:8px;
            cursor:pointer; transition:0.3s;
        }
        .logout-btn:hover { background:#a81f1f; }

        /* Content */
        .content { margin-left:0; padding:100px 30px 30px 30px; transition:0.3s; }
        .page-title { font-size:24px; font-weight:bold; color:#009739; margin-bottom:20px; text-align:center; }

        #calendar {
            max-width:1000px; margin:auto; background:#fff; padding:20px;
            border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);
        }
        .fc-toolbar-title { font-size: 22px !important; color: #009739; }

        /* Modal (Holiday Info) */
        .modal {
            display:none;
            position:fixed; top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,0.4); backdrop-filter: blur(5px);
            justify-content:center;align-items:center;z-index:2000;
        }
        .modal-content {
            background:#fff; padding:20px; border-radius:12px; width:400px;
            box-shadow:0 8px 24px rgba(0,0,0,0.3);
        }
        .modal-content h3 { color:#009739; margin-bottom:10px; }
        .modal-content p { margin:5px 0; }
        .modal-content button {
            margin-top:15px; background:#009739; color:#fff; border:none;
            padding:8px 16px; border-radius:8px; cursor:pointer; transition:0.3s;
        }
        .modal-content button:hover { background:#007a2e; }

        /* Logout Modal */
        #logoutModal .modal-content {
            text-align:center;
        }
        #logoutModal h3 { margin-bottom:15px; color:#c62828; }
        #logoutModal button { margin:8px; }
        #logoutModal .confirm-btn { background:#c62828; }
        #logoutModal .cancel-btn { background:#555; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
  <span class="closebtn" onclick="closeSidebar()">&times;</span>
  <div class="sidebar-header">
    <h3>Employee</h3>
    <p>ID: <?= htmlspecialchars($emp_id) ?></p>
  </div>
  <a href="UserDashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='UserDashboard.php'?'active':'' ?>"><i class="fas fa-calendar-day"></i> My Attendance</a>
  <a href="UserCalendar.php" class="<?= basename($_SERVER['PHP_SELF'])=='UserCalendar.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i> View Calendar</a>

  <button type="button" class="logout-btn" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Logout</button>
</div>

<!-- Overlay -->
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>

<!-- Menu Button -->
<button class="menu-btn" onclick="openSidebar()">☰ Menu</button>

<!-- Content -->
<div class="content">
    <div class="page-title">Public Holidays</div>
    <div id="calendar"></div>
</div>

<!-- Holiday Modal -->
<div class="modal" id="holidayModal">
    <div class="modal-content">
        <h3 id="holidayTitle"></h3>
        <p><strong>Date:</strong> <span id="holidayDate"></span></p>
        <p><strong>Description:</strong></p>
        <p id="holidayDesc"></p>
        <button onclick="closeModal()">Close</button>
    </div>
</div>

<!-- Logout Modal -->
<div class="modal" id="logoutModal">
    <div class="modal-content">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout?</p>
        <form method="POST">
            <button type="submit" name="logout" class="confirm-btn">Yes, Logout</button>
            <button type="button" class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let events = <?php echo json_encode($events); ?>;
    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: events,
        eventClick: function(info) {
            openModal(info.event);
        }
    });
    calendar.render();
});

// Sidebar functions
function openSidebar() {
    document.getElementById("sidebar").classList.add("open");
    document.getElementById("overlay").classList.add("active");
}
function closeSidebar() {
    document.getElementById("sidebar").classList.remove("open");
    document.getElementById("overlay").classList.remove("active");
}

// Holiday Modal
function openModal(event){
    document.getElementById("holidayTitle").innerText = event.title;
    document.getElementById("holidayDate").innerText = event.startStr;
    document.getElementById("holidayDesc").innerText = event.extendedProps.description || "-";
    document.getElementById("holidayModal").style.display = "flex";
}
function closeModal(){
    document.getElementById("holidayModal").style.display = "none";
}

// Logout Modal
function showLogoutModal(){
    document.getElementById("logoutModal").style.display = "flex";
}
function closeLogoutModal(){
    document.getElementById("logoutModal").style.display = "none";
}
</script>

</body>
</html>
