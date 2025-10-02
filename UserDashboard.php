<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Redirect if not logged in or role not employee
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != "employee") {
    header("Location: LoginPerodua.php");
    exit;
}

$emp_id = $_SESSION['employee_id'];
$today = date("Y-m-d");
$msg = "";

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: LoginPerodua.php");
    exit;
}

// Handle attendance submission for a date range
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

// Handle delete single application
if (isset($_POST['delete_id'])) {
    $del_id = $_POST['delete_id'];
    $del = $conn->prepare("DELETE FROM attendance WHERE id=? AND employee_id=?");
    $del->bind_param("is", $del_id, $emp_id);
    if ($del->execute()) {
        $msg = "🗑️ Application deleted successfully.";
    } else {
        $msg = "❌ Error deleting application.";
    }
}

// Fetch today attendance
$today_sql = "SELECT * FROM attendance WHERE employee_id=? AND date = ?";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $emp_id, $today);
$today_stmt->execute();
$todayRecord = $today_stmt->get_result();

// Fetch future applications
$future_sql = "
    SELECT * FROM attendance 
    WHERE employee_id=? AND date > ?
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
    <title>My Attendance - Perodua</title>
    <style>
        body, html { margin:0; padding:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 15px; }
        .card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); margin-bottom: 30px; }
        h2 { text-align: center; margin-bottom: 20px; font-size: 28px; }

        form { margin: 20px 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
        select, input[type=text], input[type=date] { padding: 10px; border-radius: 8px; border: 1px solid #ccc; background: #fafafa; min-width: 160px; }
        input[type=text] { width: 200px; }
        button { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }

        /* Submit button */
        button[type="submit"]:not(.logout-btn):not(.delete-btn) { background: #4CAF50; color: #000; }
        button[type="submit"]:not(.logout-btn):not(.delete-btn):hover { background: #45a049; }

        /* Logout button */
        .logout-btn { background: #000; color: #fff; }
        .logout-btn:hover { background: #333; }

        /* Delete button */
        .delete-btn { background: #e60000; color: #fff; }
        .delete-btn:hover { background: #b30000; }

        /* Toggle button */
        .toggle-btn { background: #007BFF; color: #fff; width: 220px; }
        .toggle-btn:hover { background: #0056b3; }

        .msg { text-align:center; margin: 10px 0; color: #d32f2f; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 10px; text-align: center; border: 1px solid #ddd; }
        th { background: #0e0d0dff; color:white;}
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }

        /* Status row colors */
        .status-mia { background-color: #fd1212 !important; color: #fff; }
        .status-outstation { background-color: #fadb6d !important; color: #000; }
        .status-available { background-color: #b7fcb7 !important; color: #000; }

        /* Modal styles with blur + fade-in */
        .modal { 
            display:none; 
            position:fixed; 
            z-index:1000; 
            left:0; 
            top:0; 
            width:100%; 
            height:100%; 
            background:rgba(0,0,0,0.4); 
            backdrop-filter: blur(5px); 
            animation: fadeIn 0.3s ease-in-out;
        }
        .modal-content { 
            background:#fff; 
            margin:15% auto; 
            padding:20px; 
            border-radius:10px; 
            width:350px; 
            text-align:center; 
            animation: slideDown 0.3s ease;
        }
        .modal h3 { margin-bottom:15px; }
        .modal-buttons { display:flex; justify-content:center; gap:15px; }
        .confirm-btn { background:#e60000; color:#fff; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; }
        .cancel-btn { background:#777; color:#fff; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; }
        .confirm-btn:hover { background:#b30000; }
        .cancel-btn:hover { background:#555; }

        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes slideDown { from { transform: translateY(-30px); opacity:0; } to { transform: translateY(0); opacity:1; } }

        @media(max-width: 900px) {
            form { flex-direction: column; gap: 10px; }
            input[type=text], select { width: 90%; }
        }
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
                options = ['In Office']; 
            }
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.text = opt;
                subStatusSelect.appendChild(option);
            });
        }
        function toggleFuture() {
            const section = document.getElementById("futureSection");
            section.style.display = (section.style.display === "none" || section.style.display === "") ? "block" : "none";
        }
        function showLogoutModal() { document.getElementById("logoutModal").style.display = "block"; }
        function closeLogoutModal() { document.getElementById("logoutModal").style.display = "none"; }

        // Show Delete Modal
        function showDeleteModal(id) {
            document.getElementById("deleteModal").style.display = "block";
            document.getElementById("delete_id_input").value = id;
        }
        function closeDeleteModal() {
            document.getElementById("deleteModal").style.display = "none";
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>My Attendance - Staff ID: <?= htmlspecialchars($emp_id) ?></h2>

            <!-- Logout -->
            <button type="button" class="logout-btn" onclick="showLogoutModal()">Logout</button>

            <!-- Logout Modal -->
            <div id="logoutModal" class="modal">
                <div class="modal-content">
                    <h3>Confirm Logout</h3>
                    <p>Are you sure you want to log out?</p>
                    <div class="modal-buttons">
                        <form method="POST">
                            <button type="submit" name="logout" class="confirm-btn">Yes, Logout</button>
                        </form>
                        <button type="button" class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <h3>Confirm Delete</h3>
                    <p>Are you sure you want to delete this application?</p>
                    <div class="modal-buttons">
                        <form method="POST">
                            <input type="hidden" name="delete_id" id="delete_id_input">
                            <button type="submit" class="confirm-btn">Yes, Delete</button>
                        </form>
                        <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Attendance Submission -->
            <form method="POST">
                <select name="status" id="status" onchange="updateSubStatusOptions()" required>
                    <option value="">--Select Status--</option>
                    <option value="MIA/MC">MIA/MC</option>
                    <option value="OutStation">OutStation</option>
                    <option value="Available">Available</option>
                </select>
                <select name="sub_status" id="sub_status" required>
                    <option value="">--Select Sub-Status--</option>
                </select>
                <input type="text" name="note" placeholder="Add Note (optional)">
                <input type="date" name="start_date" required>
                <input type="date" name="end_date" required>
                <button type="submit">Submit</button>
            </form>

            <div class="msg"><?= $msg ?></div>
        </div>

        <!-- Today's Attendance -->
        <div class="card">
            <h3>Today Attendance</h3>
            <table>
                <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th><th>Action</th></tr>
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
                            <td>
                                <button type="button" class="delete-btn" onclick="showDeleteModal(<?= $row['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">❌ No attendance submitted for today.</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Future Applications -->
        <div class="card">
            <button type="button" class="toggle-btn" onclick="toggleFuture()">Show/Hide Future Applications</button>
            <div id="futureSection" style="display:none;">
                <h3>Future Applications</h3>
                <table>
                    <tr><th>Date</th><th>Status</th><th>Sub-Status</th><th>Note</th><th>Action</th></tr>
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
                                <td>
                                    <button type="button" class="delete-btn" onclick="showDeleteModal(<?= $row['id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No future applications found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
