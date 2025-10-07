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
$msg = "";

/* --- ADD Employee --- */
if (isset($_POST['add'])) {
    $emp_id = $_POST['employee_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pos = $_POST['pos'];
    $department = $_POST['department'];
    $role_field = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = "uploads/" . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    $stmt = $conn->prepare("INSERT INTO employee (employee_id, name, email, POS, department, role, photo, password) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $emp_id, $name, $email, $pos, $department, $role_field, $photo, $password);
    $msg = $stmt->execute() ? "✅ Employee added successfully" : "❌ Error: " . $conn->error;
}

/* --- EDIT Employee --- */
if (isset($_POST['edit'])) {
    $emp_id = $_POST['employee_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pos = $_POST['pos'];
    $department = $_POST['department'];
    $role_field = $_POST['role'];

    $sql = "UPDATE employee SET name=?, email=?, POS=?, department=?, role=?";
    $params = [$name, $email, $pos, $department, $role_field];
    $types = "sssss";

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $password;
        $types .= "s";
    }

    if (!empty($_FILES['photo']['name'])) {
        $photo = "uploads/" . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        $sql .= ", photo=?";
        $params[] = $photo;
        $types .= "s";
    }

    $sql .= " WHERE employee_id=?";
    $params[] = $emp_id;
    $types .= "s";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $msg = $stmt->execute() ? "✅ Employee updated successfully" : "❌ Error: " . $conn->error;
}

/* --- DELETE Employee --- */
if (isset($_POST['delete_confirm'])) {
    $emp_id = $_POST['employee_id'];
    $stmt = $conn->prepare("DELETE FROM employee WHERE employee_id=?");
    $stmt->bind_param("s", $emp_id);
    $msg = $stmt->execute() ? "🗑 Employee removed successfully" : "❌ Error: " . $conn->error;
}

/* --- Get Employee List --- */
$result = $conn->query("SELECT * FROM employee ORDER BY department, name");
$date_today = date("l, d F Y");

$gm_result = $conn->query("SELECT COUNT(*) AS total_gm FROM employee WHERE role='GM'");
$total_gm = $gm_result->fetch_assoc()['total_gm'];

$emp_result = $conn->query("SELECT COUNT(*) AS total_emp FROM employee WHERE role='Employee'");
$total_employee = $emp_result->fetch_assoc()['total_emp'];

/* --- Group employees by department --- */
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[$row['department']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee List - GM</title>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
.topbar { position: fixed; left:250px; right:0; top:0; height:70px; background:#111; color:white;
display:flex; justify-content:space-between; align-items:center; padding:0 30px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1000; }
.topbar h3 { font-weight:500; font-size:18px; }
.topbar .date-time { font-size:14px; opacity:0.85; display:flex; gap:15px; }
.logout-btn { background:#fff; color:#111; border:none; padding:8px 18px; border-radius:10px; cursor:pointer; font-weight:600; transition:0.3s; }
.logout-btn:hover { background:#e0e0e0; }

/* Main Content */
.main-content { margin-left: 250px; padding: 100px 30px 30px 30px; flex: 1; transition: margin-left 0.3s; }

.card-container { display:flex; gap:20px; justify-content:center; margin-bottom:30px; flex-wrap:wrap; }
.card { width:180px; padding:20px; border-radius:15px; color:white; text-align:center; box-shadow:0 8px 20px rgba(0,0,0,0.08); transition:0.3s; }
.card:hover { transform: translateY(-3px); }
.card h3 { font-size:16px; margin-bottom:8px; color:white; }
.card h2 { font-size:26px; margin-bottom:10px; color:white; }
.card-gm, .card-emp { background: #fff; color: #111; }
.card-gm h3, .card-gm h2, .card-emp h3, .card-emp h2 { color: #111; }

.container { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 12px 25px rgba(0,0,0,0.1); margin-bottom:40px; }
h2 { text-align: center; color: #131613ff; margin-bottom: 25px; font-size:30px; }
h3.dept-title { margin-top:30px; background:#111; color:white; padding:10px 15px; border-radius:8px; font-size:20px; }

table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 10px; overflow: hidden; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
th, td { padding: 14px; text-align: center; }
th { background: #262926ff; color: white; text-transform: uppercase; font-size:14px; }
td { background: #fefefe; transition:0.3s; }
tr:hover td { background: #f1f1f1; }

button { cursor: pointer; font-weight: 600; }
.btn { padding: 7px 14px; border: none; border-radius: 8px; margin:2px; font-size:14px; transition:0.3s; }
.btn-add { background: #4CAF50; color: white; }
.btn-add:hover { background:#388E3C; transform:scale(1.05); }
.btn-edit { background: #2196F3; color: white; }
.btn-edit:hover { background:#1976D2; transform:scale(1.05); }
.btn-del { background: #d32f2f; color: white; }
.btn-del:hover { background:#b71c1c; transform:scale(1.05); }

img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; }

.overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(5px); z-index:999; opacity:0; transition:opacity 0.3s; }
.overlay.active { display:block; opacity:1; }

.form-popup { display:none; background:#fff; padding:30px; border-radius:15px; box-shadow:0 12px 35px rgba(0,0,0,0.2); position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) scale(0.9); z-index:1000; width:380px; text-align:center; opacity:0; transition:all 0.3s ease; }
.form-popup.active { display:block; opacity:1; transform:translate(-50%,-50%) scale(1); }

input, select { width:100%; padding:12px 14px; margin-bottom:15px; border-radius:8px; border:1px solid #ccc; transition:0.3s; }
input:focus, select:focus { border-color:#2e7d32; outline:none; }

    @media(max-width: 900px){
    .sidebar { position: fixed; left: -250px; width: 250px; transition: left 0.3s; }
    .sidebar.active { left: 0; }
    .main-content { margin-left: 0; padding: 100px 15px; }
    table, th, td { font-size: 12px; }
    .stats { flex-direction: column; }
    }

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Perodua</h2>
<div class="sidebar">
    <h2>Perodua</h2>
    <a href="AdminAttendanceUpdate.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceUpdate.php'?'active':'' ?>"><i class="fas fa-calendar-day"></i><span>My Attendance</span></a>
    <a href="AdminEmployeeList.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminEmployeeList.php'?'active':'' ?>"><i class="fas fa-user-cog"></i><span>Employee Management</span></a>
    <a href="AdminDashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminDashboard.php'?'active':'' ?>"><i class="fas fa-chart-line"></i><span>Analysis Dashboard</span></a>
    <a href="AdminAttendanceRecord.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminAttendanceRecord.php'?'active':'' ?>"><i class="fas fa-calendar-check"></i><span>Attendance Reports</span></a>
    <a href="AdminCalendar.php" class="<?= basename($_SERVER['PHP_SELF'])=='AdminCalendar.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i><span>Calendar Management</span></a>
    <a href="AttendanceRateTable.php" class="<?= basename($_SERVER['PHP_SELF'])=='AttendanceRateTable.php'?'active':'' ?>"><i class="fas fa-users"></i><span>Attendance Statistics</span></a>
</div>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?></h3>
        <div class="date-time">
            <span><?= $date_today ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <button type="button" class="logout-btn" onclick="openForm('logoutForm')">Logout</button>
</div>

<!-- Main Content -->
<div class="main-content">
<div class="card-container">
    <div class="card card-gm">
        <h3>Total GM</h3>
        <h2><?= $total_gm ?></h2>
        <canvas id="gmChart" height="80"></canvas>
    </div>
    <div class="card card-emp">
        <h3>Total Employee</h3>
        <h2><?= $total_employee ?></h2>
        <canvas id="empChart" height="80"></canvas>
    </div>
</div>

<div class="container">
    <h2>Employee Management</h2>
    <button class="btn btn-add" onclick="openForm('addForm')">➕ Add New Employee</button>

    <?php foreach ($departments as $dept => $employees): ?>
        <h3 class="dept-title"><?= htmlspecialchars($dept) ?> Department</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Name</th>
                <th>Email</th>
                <th>POS</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($employees as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['employee_id']) ?></td>
                <td><?= $row['photo'] ? "<img src='".htmlspecialchars($row['photo'])."'>" : "No Photo" ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['POS']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td>
                    <button class="btn btn-edit" onclick="openEditForm('<?= $row['employee_id'] ?>','<?= htmlspecialchars($row['name']) ?>',
                                                                        '<?= htmlspecialchars($row['email']) ?>','<?= htmlspecialchars($row['POS']) ?>',
                                                                        '<?= htmlspecialchars($row['department']) ?>','<?= htmlspecialchars($row['role']) ?>')">Edit Profile</button>
                    <button class="btn btn-del" onclick="confirmDelete('<?= $row['employee_id'] ?>')">🗑</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
</div>
</div>

<!-- Forms (Add/Edit/Delete/Logout same as before) -->
<div class="overlay" id="overlay"></div>

<!-- Add Form -->
<div class="form-popup" id="addForm">
    <h3>Add Employee</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="employee_id" placeholder="Employee ID" required>
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" >
        <select name="pos" required>
            <option value="">Select POS</option>
            <option value="GM">GM</option>
            <option value="AA">AA</option>
            <option value="DGM">DGM</option>
            <option value="ACT DGM">ACT DGM</option>
            <option value="ACT MGR">ACT MGR</option>
            <option value="AM">AM</option>
            <option value="CAA">CAA</option>
            <option value="ENG">ENG</option>
            <option value="EXEC">EXEC</option>
            <option value="MGR">MGR</option>
            <option value="SM">SM</option>
            <option value="TA">TA</option>
            <option value="TM">TM</option>
            <option value="TR">TR</option>
        </select>
        <select name="department" required>
            <option value="Sales">Sales</option>
            <option value="Admin">Admin</option>
            <option value="Warehouse">Warehouse</option>
            <option value="Inventory">Inventory</option>
            <option value="Management">Management</option>
        </select>
        <select name="role" required>
            <option value="Employee">Employee</option>
            <option value="GM">General Manager</option>
        </select>
        <input type="password" name="password" placeholder="Set Password" required>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="add" class="btn btn-add">Add</button>
        <button type="button" class="btn btn-del" onclick="closeForm()">Cancel</button>
    </form>
</div>

<!-- Edit Form -->
<div class="form-popup" id="editForm">
    <h3>Edit Employee</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="employee_id" id="edit_id">
        <input type="text" name="name" id="edit_name" required>
        <input type="email" name="email" id="edit_email" >
        <select name="pos" id="edit_pos" required>
            <option value="">Select POS</option>
            <option value="GM">GM</option>
            <option value="AA">AA</option>
            <option value="DGM">DGM</option>
            <option value="ACT DGM">ACT DGM</option>
            <option value="ACT MGR">ACT MGR</option>
            <option value="AM">AM</option>
            <option value="CAA">CAA</option>
            <option value="ENG">ENG</option>
            <option value="EXEC">EXEC</option>
            <option value="MGR">MGR</option>
            <option value="SM">SM</option>
            <option value="TA">TA</option>
            <option value="TM">TM</option>
            <option value="TR">TR</option>
        </select>
        <select name="department" id="edit_dept" required>
            <option value="Sales">Sales</option>
            <option value="Admin">Admin</option>
            <option value="Warehouse">Warehouse</option>
            <option value="Inventory">Inventory</option>
            <option value="Management">Management</option>
        </select>
        <select name="role" id="edit_role" required>
            <option value="Employee">Employee</option>
            <option value="GM">General Manager</option>
        </select>
        <input type="password" name="password" placeholder="Set New Password (optional)">
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="edit" class="btn btn-edit">Update</button>
        <button type="button" class="btn btn-del" onclick="closeForm()">Cancel</button>
    </form>
</div>

<!-- Delete Form -->
<div class="form-popup" id="deleteForm">
    <h3>Confirm Delete</h3>
    <p>Are you sure you want to delete this employee?</p>
    <form method="POST">
        <input type="hidden" name="employee_id" id="delete_id">
        <button type="submit" name="delete_confirm" class="btn btn-del">Yes, Delete</button>
        <button type="button" class="btn btn-edit" onclick="closeForm()">Cancel</button>
    </form>
</div>

<!-- Logout Confirmation -->
<div class="form-popup" id="logoutForm">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to log out?</p>
    <form action="logout.php" method="post">
        <button type="submit" class="btn btn-del">Yes, Logout</button>
        <button type="button" class="btn btn-edit" onclick="closeForm()">Cancel</button>
    </form>
</div>

<script>
function openForm(id){ 
    document.getElementById("overlay").classList.add("active");
    document.getElementById(id).classList.add("active");
}
function openEditForm(id,name,email,pos,dept,role){ 
    document.getElementById("edit_id").value=id; 
    document.getElementById("edit_name").value=name; 
    document.getElementById("edit_email").value=email; 
    document.getElementById("edit_pos").value=pos; 
    document.getElementById("edit_dept").value=dept; 
    document.getElementById("edit_role").value=role; 
    openForm("editForm"); 
}
function closeForm(){ 
    document.querySelectorAll(".form-popup").forEach(f=>f.classList.remove("active")); 
    document.getElementById("overlay").classList.remove("active"); 
}
function confirmDelete(emp_id){ 
    document.getElementById("delete_id").value=emp_id; 
    openForm("deleteForm"); 
}
function updateClock(){ 
    const clock=document.getElementById('clock'); 
    const now=new Date(); 
    clock.textContent=now.toLocaleTimeString(); 
}
setInterval(updateClock,1000); updateClock();

const gmChart = new Chart(document.getElementById('gmChart'), { 
    type:'doughnut', 
    data:{ labels:['GM'], datasets:[{ data:[<?= $total_gm ?>], backgroundColor:['#fc0909ff'] }] }, 
    options:{ responsive:true, plugins:{legend:{display:false}}, cutout:'70%' } 
});
const empChart = new Chart(document.getElementById('empChart'), { 
    type:'doughnut', 
    data:{ labels:['Employee'], datasets:[{ data:[<?= $total_employee ?>], backgroundColor:['#09f11dff'] }] }, 
    options:{ responsive:true, plugins:{legend:{display:false}}, cutout:'70%' } 
});
</script>
</body>
</html>
