<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Only GM can access
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != "GM") {
    header("Location: LoginPerodua.php");
    exit;
}

$employee_name = $_SESSION['name'];
$role = $_SESSION['role'];

// Handle Add/Edit/Delete requests (AJAX)
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "add") {
        $date = $_POST['holiday_date'];
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, title, description) VALUES (?,?,?)");
        $stmt->bind_param("sss", $date, $title, $desc);
        $stmt->execute();
        echo "Holiday added successfully";
    }

    if ($action == "update") {
        $id = $_POST['id'];
        $date = $_POST['holiday_date'];
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $stmt = $conn->prepare("UPDATE holidays SET holiday_date=?, title=?, description=? WHERE id=?");
        $stmt->bind_param("sssi", $date, $title, $desc, $id);
        $stmt->execute();
        echo "Holiday updated successfully";
    }

    if ($action == "delete") {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo "Holiday removed successfully";
    }

    exit; // stop output for AJAX
}

// Fetch holidays for calendar
$events = [];
$result = $conn->query("SELECT * FROM holidays");
while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => $row['title'],
        "start" => $row['holiday_date'],
        "description" => $row['description'],
        "backgroundColor" => "red",
        "borderColor" => "darkred"
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Calendar - Public Holidays</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
        body { background:#f2f2f2; color:#111; min-height:100vh; }

    /* Sidebar */
        .sidebar {
            position: fixed; left:0; top:0; bottom:0; width:260px;
            background:#111; color:white; padding:30px 0;
            box-shadow:3px 0 15px rgba(0,0,0,0.25); transition:0.3s;
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

        /* Topbar */
        .topbar {
            position: fixed; left:250px; right:0; top:0; height:70px; background:#111; color:white;
            display:flex; justify-content:space-between; align-items:center; padding:0 30px;
            box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000;
        }
        .topbar h3 { font-weight:500; }
        .topbar .date-time { font-size:14px; opacity:0.85; display:flex; flex-direction:column; }
        .logout-btn { background:#fff; color:#000; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
        .logout-btn:hover { background:#007a2e; color:#fff; }

        /* Calendar */
        .content { margin-left:250px; padding:100px 30px 30px 30px; }
        .page-title { font-size:24px; font-weight:bold; color:#009739; margin-bottom:20px; text-align:center; }
        #calendar {
            max-width: 1000px; margin: auto; background:#fff; padding:20px;
            border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);
        }
        .fc-toolbar-title { font-size: 22px !important; color: #009739; }

        /* Filter */
        .filter-bar {
            max-width:1000px;
            margin: 0 auto 20px auto;
            background:#fff;
            padding:15px;
            border-radius:8px;
            box-shadow:0 4px 12px rgba(0,0,0,0.1);
            display:flex;
            gap:10px;
            align-items:center;
        }
        .filter-bar select, .filter-bar button {
            padding:8px 12px;
            border:1px solid #ccc;
            border-radius:6px;
            font-size:14px;
        }
        .filter-bar button {
            background:#009739; color:white; cursor:pointer; transition:0.3s;
        }
        .filter-bar button:hover { background:#007a2e; }

        /* Modal */
        .modal {
            display:none;
            position:fixed; top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,0.4);
            backdrop-filter: blur(5px);
            justify-content:center;align-items:center;
            z-index:2000;
            animation: fadeInBg 0.3s ease;
        }
        .modal-content {
            background:#fff;
            padding:20px;
            border-radius:12px;
            width:400px;
            box-shadow:0 8px 24px rgba(0,0,0,0.3);
            animation: fadeIn 0.4s ease;
        }
        .modal-content input, .modal-content textarea {
            width:100%;padding:10px;margin-top:5px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;
            font-size:14px;
        }
        .modal-content button {
            padding:8px 15px;
            border:none;border-radius:6px;
            cursor:pointer;
            margin-right:5px;
            transition:0.3s;
        }
        .modal-content button[type=submit] { background:#009739; color:#fff; }
        .modal-content button[type=submit]:hover { background:#007a2e; }
        .modal-content button[type=button] { background:#ccc; }
        .modal-content button[type=button]:hover { background:#999; }
        #deleteBtn { background:red; color:white; margin-top:10px; }
        #deleteBtn:hover { background:darkred; }

        /* Popup */
        .popup {
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            display:flex; justify-content:center; align-items:center;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index:3000;
            animation: fadeInBg 0.3s ease;
        }
        .popup-content {
            background:#fff;
            padding:20px 30px;
            border-radius:12px;
            text-align:center;
            box-shadow:0 8px 24px rgba(0,0,0,0.3);
            animation: fadeIn 0.4s ease;
        }
        .popup-content h4 { margin-bottom:15px; }
        .popup-content button {
            background:#009739; color:#fff; padding:8px 16px;
            border:none; border-radius:8px; cursor:pointer; transition:0.3s;
        }
        .popup-content button:hover { background:#007a2e; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceUpdate.php'?'active':'' ?>"><i class="fas fa-calendar-day"></i><span>My Attendance</span></a>
    <a href="AdminEmployeeList.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminEmployeeList.php'?'active':'' ?>"><i class="fas fa-user-cog"></i><span>Employee Management</span></a>
    <a href="AdminDashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminDashboard.php'?'active':'' ?>"><i class="fas fa-chart-line"></i><span>Analysis Dashboard</span></a>
    <a href="AdminAttendanceRecord.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceRecord.php'?'active':'' ?>"><i class="fas fa-calendar-check"></i><span>Attendance Reports</span></a>
    <a href="AdminCalendar.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminCalendar.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i><span>Calendar Management</span></a>
    <a href="AttendanceRateTable.php" class="<?= basename($_SERVER['PHP_SELF'])=='AttendanceRateTable.php'?'active':'' ?>"><i class="fas fa-users"></i><span>Attendance Statistics</span></a>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?></h3>
        <div class="date-time">
            <span><?= date("l, d F Y") ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <button class="logout-btn" onclick="showLogoutModal()">Logout</button>
</div>

<!-- Content -->
<div class="content">
    <div class="page-title">Company Holidays</div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <label>Month:</label>
        <select id="monthFilter">
            <?php 
            for($m=1;$m<=12;$m++){
                $monthName = date("F", mktime(0,0,0,$m,1));
                echo "<option value='$m'>$monthName</option>";
            }
            ?>
        </select>
        <label>Year:</label>
        <select id="yearFilter">
            <?php 
            $currentYear = date("Y");
            for($y=$currentYear-5;$y<=$currentYear+5;$y++){
                $selected = $y == $currentYear ? "selected" : "";
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>
        <button id="goFilter">Go</button>
    </div>

    <div id="calendar"></div>
</div>

<!-- Holiday Modal -->
<div class="modal" id="holidayModal">
    <div class="modal-content">
        <h3 id="modalTitle">Add Holiday</h3>
        <form id="holidayForm">
            <input type="hidden" name="id" id="holidayId">
            <label>Date:</label>
            <input type="date" name="holiday_date" id="holidayDate" required>
            <label>Title:</label>
            <input type="text" name="title" id="holidayTitle" required>
            <label>Description:</label>
            <textarea name="description" id="holidayDesc"></textarea>
            <button type="submit">Save</button>
            <button type="button" onclick="closeModal()">Cancel</button>
        </form>
        <button id="deleteBtn" style="display:none;">Remove</button>
    </div>
</div>

<!-- Popup -->
<div class="popup" id="popupMsg" style="display:none;">
    <div class="popup-content">
        <h4 id="popupText">Action completed!</h4>
        <button id="popupBtn" onclick="closePopup()">OK</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let events = <?php echo json_encode($events); ?>;
    let calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        events: events,
        dateClick: function(info) {
            let holiday = events.find(e => e.start === info.dateStr);
            if (holiday) {
                openModal('edit', holiday);
            } else {
                openModal('add', { date: info.dateStr });
            }
        },
        eventClick: function(info) {
            openModal('edit', {
                id: info.event.id,
                date: info.event.startStr,
                title: info.event.title,
                description: info.event.extendedProps.description
            });
        }
    });
    calendar.render();

    // Update clock
    setInterval(()=>{
        let now = new Date();
        document.getElementById("clock").textContent =
            now.toLocaleTimeString("en-GB");
    },1000);

    // Filter
    document.getElementById("goFilter").addEventListener("click", function(){
        let month = document.getElementById("monthFilter").value;
        let year = document.getElementById("yearFilter").value;
        let dateStr = year + "-" + (month.toString().padStart(2,"0")) + "-01";
        calendar.gotoDate(dateStr);
    });

    // Modal functions
    window.openModal = function(mode, data){
        document.getElementById("holidayModal").style.display = "flex";
        if(mode === "add"){
            document.getElementById("modalTitle").innerText = "Add Holiday";
            document.getElementById("holidayId").value = "";
            document.getElementById("holidayDate").value = data.date || "";
            document.getElementById("holidayTitle").value = "";
            document.getElementById("holidayDesc").value = "";
            document.getElementById("deleteBtn").style.display = "none";
        } else {
            document.getElementById("modalTitle").innerText = "Edit Holiday";
            document.getElementById("holidayId").value = data.id;
            document.getElementById("holidayDate").value = data.date;
            document.getElementById("holidayTitle").value = data.title;
            document.getElementById("holidayDesc").value = data.description;
            document.getElementById("deleteBtn").style.display = "inline-block";
        }
    }
    window.closeModal = function(){
        document.getElementById("holidayModal").style.display = "none";
    }

    // Show popup with optional countdown
    function showPopup(msg, reload=false, countdown=false, callback=null) {
        document.getElementById("popupText").innerText = msg;
        document.getElementById("popupMsg").style.display = "flex";
        let btn = document.getElementById("popupBtn");

        if (countdown) {
            let timeLeft = 3;
            btn.disabled = true;
            let timer = setInterval(()=>{
                btn.innerText = `OK (${timeLeft})`;
                timeLeft--;
                if(timeLeft < 0){
                    clearInterval(timer);
                    btn.innerText = "OK";
                    btn.disabled = false;
                    if(callback) callback();
                }
            },1000);
        }

        if (reload) {
            setTimeout(()=> location.reload(), 1500);
        }
    }

    window.closePopup = function(){
        document.getElementById("popupMsg").style.display = "none";
    }

    // Save form
    document.getElementById("holidayForm").addEventListener("submit", function(e){
        e.preventDefault();
        let id = document.getElementById("holidayId").value;
        let formData = new FormData(this);
        formData.append("action", id ? "update" : "add");
        axios.post("AdminCalendar.php", formData).then(res=> {
            showPopup(res.data, true);
        });
    });

    // Delete button with countdown
    document.getElementById("deleteBtn").addEventListener("click", function(){
        let id = document.getElementById("holidayId").value;
        showPopup("Removing holiday...", false, true, ()=>{
            let formData = new FormData();
            formData.append("action","delete");
            formData.append("id",id);
            axios.post("AdminCalendar.php", formData).then(res=> {
                showPopup(res.data, true);
            });
        });
    });

    // Logout button popup
    window.showLogoutModal = function(){
        showPopup("Logging out...", false, true, ()=> {
            window.location.href = "Logout.php";
        });
    }
});
</script>
</body>
</html>
