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
    $department = $_POST['department'];
    $role_field = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = "uploads/" . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    $stmt = $conn->prepare("INSERT INTO employee (employee_id, name, department, role, photo, password) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $emp_id, $name, $department, $role_field, $photo, $password);
    $msg = $stmt->execute() ? "✅ Employee added successfully" : "❌ Error: " . $conn->error;
}

/* --- EDIT Employee --- */
if (isset($_POST['edit'])) {
    $emp_id = $_POST['employee_id'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $role_field = $_POST['role'];

    $sql = "UPDATE employee SET name=?, department=?, role=?";
    $params = [$name, $department, $role_field];
    $types = "sss";

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

$result = $conn->query("SELECT * FROM employee ORDER BY department, name");
$date_today = date("l, d F Y");

$gm_result = $conn->query("SELECT COUNT(*) AS total_gm FROM employee WHERE role='GM'");
$total_gm = $gm_result->fetch_assoc()['total_gm'];

$emp_result = $conn->query("SELECT COUNT(*) AS total_emp FROM employee WHERE role='Employee'");
$total_employee = $emp_result->fetch_assoc()['total_emp'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee List - GM</title>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Reset & Base */
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Arial, sans-serif; }
body { background: #f4f6f8; display: flex; min-height: 100vh; color:#333; }

/* Sidebar */
.sidebar {
    width: 220px;
    background: linear-gradient(180deg, #1b5e20, #43a047);
    color: white;
    position: fixed;
    top: 0; bottom: 0; left: 0;
    padding-top: 30px;
    box-shadow: 3px 0 15px rgba(0,0,0,0.15);
    transition: width 0.3s;
}
.sidebar img.logo-main {
    width: 140px;
    height: 140px;
    display: block;
    margin: 0 auto 20px;
    border-radius: 15%;
    object-fit: contain;
}
.sidebar h2 { 
    text-align: center; 
    font-size: 24px; 
    margin-bottom: 25px; 
    font-weight: 600; 
    color:white;
}

.sidebar a { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 12px 20px; 
    margin: 5px 10px; 
    color: white; 
    text-decoration: none; 
    border-radius: 8px; 
    font-weight: 500; 
    transition: all 0.3s; 
    color:white;
}

.sidebar a:hover { 
    background: rgba(255,255,255,0.15); 
    transform: translateX(5px); 
}

.sidebar i {
    font-size: 18px; 
}

/* Topbar */
.topbar {
    position: fixed;
    top: 0; left: 220px; right: 0;
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

.topbar h3 { 
    font-weight: 500; 
    font-size: 18px; 
}

.topbar .date-time { 
    font-weight: 400; 
    opacity: 0.85; 
    font-size: 15px; 
    display: flex; 
    gap: 20px; 
}

.logout-btn { 
    background: #c62828; 
    border: none; 
    padding: 10px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: bold; 
    transition: 0.3s; 
    color:white; 
}

.logout-btn:hover { 
    background: #b71c1c; 
    transform: scale(1.05); 
}

/* Main content */
.main-content { 
    margin-left: 220px; 
    padding: 100px 30px 30px 30px; 
    flex: 1; 
    transition: margin-left 0.3s; 
}

/* Cards */
.card-container { 
    display:flex; 
    gap:20px; 
    justify-content:center; 
    margin-bottom:30px; 
    flex-wrap:wrap; 
}

.card { 
    width:180px; 
    padding:20px; 
    border-radius:15px; 
    color:white; 
    text-align:center; 
    box-shadow:0 8px 20px rgba(0,0,0,0.08); 
    transition:0.3s; 
}

.card:hover { 
    transform: translateY(-3px); 
}

.card h3 { 
    font-size:16px; 
    margin-bottom:8px; 
    color:white;
}

.card h2 { 
    font-size:26px; 
    margin-bottom:10px; 
    color:white; 
}

/* Container/Table */
.container { 
    background: #fff; 
    padding: 30px; 
    border-radius: 15px; 
    box-shadow: 0 12px 25px rgba(0,0,0,0.1); 
    transition:0.3s; 
}

.container:hover { 
    box-shadow: 0 15px 30px rgba(0,0,0,0.12); 
}

h2 { 
    text-align: center; 
    color: #2e7d32; 
    margin-bottom: 25px; 
    font-size:30px; 
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
    border-radius: 10px; 
    overflow: hidden;
    box-shadow:0 5px 15px rgba(0,0,0,0.05); 
}

th, td { 
    padding: 14px; 
    text-align: center; 
}

th { 
    background: #2e7d32; 
    color: white; 
    text-transform: uppercase; 
    font-size:14px; 
}

td { 
    background: #fefefe; 
    transition:0.3s; 
}

tr:hover td { 
    background: #f1f1f1; 
}

/* Buttons */
button { 
    cursor: pointer; 
    font-weight: 600; 
}

.btn { 
    padding: 7px 14px; 
    border: none; 
    border-radius: 8px; 
    margin:2px; 
    font-size:14px; 
    transition:0.3s; 
}

.btn-add { 
    background: #4CAF50; 
    color: white;
}

.btn-add:hover 
{
    background:#388E3C; 
    transform:scale(1.05); 
}

.btn-edit { 
    background: #2196F3; 
    color: white; 
}

.btn-edit:hover {
    background:#1976D2; 
    transform:scale(1.05); 
}

.btn-del {
    background: #d32f2f; 
    color: white; 
}

.btn-del:hover { 
    background:#b71c1c; 
    transform:scale(1.05); 
}

/* Images */
img { 
    width: 60px; 
    height: 60px; 
    object-fit: cover; 
    border-radius: 50%; 
}

/* Forms */
.form-popup { 
    display: none; 
    background: #fff; 
    padding: 30px; 
    border-radius: 15px; 
    box-shadow: 0 12px 35px rgba(0,0,0,0.2); 
    position: fixed; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%); 
    z-index: 1000; 
    width: 380px; 
}

.overlay { 
    display: none; 
    position: fixed; 
    top:0; 
    left:0; 
    width:100%; 
    height:100%; 
    background: rgba(0,0,0,0.5); 
    z-index: 999; 
}

input, select { 
    width: 100%; 
    padding: 12px 14px; 
    margin-bottom: 15px; 
    border-radius: 8px; 
    border: 1px solid #ccc;
    transition:0.3s; 
}

input:focus, select:focus { 
    border-color: #2e7d32; 
    outline: none; 
}

/* Responsive */
@media(max-width: 900px){
    .sidebar { width: 60px; padding-top:10px;}
    .sidebar h2, .sidebar a span { display:none;}
    .topbar { left:60px; padding:0 20px; }
    .main-content { margin-left:60px; padding:20px; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="https://car-logos.org/wp-content/uploads/2022/08/perodua.png" alt="Perodua Logo" class="logo-main">
    <h2>Perodua</h2>
        <a href="AdminAttendanceUpdate.php"><i class="fas fa-file-alt"></i> My Attendance</a>
        <a href="Admindashboard.php"> <i class="fas fa-tachometer-alt"></i> Dashboard Report</a>
        <a href="AdminAttendanceRecord.php"> <i class="fas fa-calendar-check"></i> Attendance Report</a>
        <a href="AttendanceRateTable.php"><i class="fas fa-users"></i> Attendance Rate</a>
        <a href="AdminEmployeeList.php"> <i class="fas fa-users"></i> Update Employee</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h3>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= $role ?>)</h3>
        <div class="date-time">
            <span><?= $date_today ?></span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

<!-- Main Content -->
<div class="main-content">
<div class="card-container">
    <div class="card" style="background:#43a047;">
        <h3>Total GM</h3>
        <h2><?= $total_gm ?></h2>
        <canvas id="gmChart" height="80"></canvas>
    </div>
    <div class="card" style="background:#2e7d32;">
        <h3>Total Employee</h3>
        <h2><?= $total_employee ?></h2>
        <canvas id="empChart" height="80"></canvas>
    </div>
</div>

<div class="container">
    <h2>Employee List</h2>
    <button class="btn btn-add" onclick="openForm('addForm')">➕ Add New Employee</button>
    <table>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Department</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['employee_id']) ?></td>
            <td><?= $row['photo'] ? "<img src='".htmlspecialchars($row['photo'])."'>" : "No Photo" ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td>
                <button class="btn btn-edit" onclick="openEditForm('<?= $row['employee_id'] ?>','<?= htmlspecialchars($row['name']) ?>',
                                                                    '<?= htmlspecialchars($row['department']) ?>','<?= htmlspecialchars($row['role']) ?>')">Edit Profile</button>
                <button class="btn btn-del" onclick="confirmDelete('<?= $row['employee_id'] ?>')">🗑</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</div>

<!-- Overlay & Forms -->
<div class="overlay" id="overlay"></div>

<!-- Add Form -->
<div class="form-popup" id="addForm">
    <h3>Add Employee</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="employee_id" placeholder="Employee ID" required>
        <input type="text" name="name" placeholder="Name" required>
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
        <input type="text" name="name" id="edit_name" placeholder="Insert Your Name" required>
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

<script>
function openForm(id){ document.getElementById(id).style.display="block"; document.getElementById("overlay").style.display="block"; }
function openEditForm(id,name,dept,role){ document.getElementById("edit_id").value=id; document.getElementById("edit_name").value=name; document.getElementById("edit_dept").value=dept; document.getElementById("edit_role").value=role; openForm("editForm"); }
function closeForm(){ document.querySelectorAll(".form-popup").forEach(f=>f.style.display="none"); document.getElementById("overlay").style.display="none"; }
function confirmDelete(emp_id){ document.getElementById("delete_id").value=emp_id; openForm("deleteForm"); }

// Clock
function updateClock(){ const clock=document.getElementById('clock'); const now=new Date(); clock.textContent=now.toLocaleTimeString(); }
setInterval(updateClock,1000); updateClock();

// Charts
const gmChart = new Chart(document.getElementById('gmChart'), { type:'doughnut', data:{ labels:['GM'], datasets:[{ data:[<?= $total_gm ?>], backgroundColor:['#fdd835'] }] }, options:{ responsive:true, plugins:{legend:{display:false}}, cutout:'70%' } });
const empChart = new Chart(document.getElementById('empChart'), { type:'doughnut', data:{ labels:['Employee'], datasets:[{ data:[<?= $total_employee ?>], backgroundColor:['#90caf9'] }] }, options:{ responsive:true, plugins:{legend:{display:false}}, cutout:'70%' } });
</script>

</body>
</html>
