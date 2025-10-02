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
        /* ===== Reset & Fonts ===== */
        body, html { margin:0; padding:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        a { color: inherit; text-decoration: none; }

        /* ===== Container ===== */
        .container { max-width: 1100px; margin: 40px auto; padding: 0 15px; }
        .card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); margin-bottom: 30px; }

        h2 { color: #111; text-align: center; margin-bottom: 20px; font-size: 28px; }

        /* ===== Forms ===== */
        form { margin: 20px 0; text-align: center; display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
        select, input[type=text], input[type=date] { padding: 10px; border-radius: 8px; border: 1px solid #ccc; background: #fafafa; color: #111; min-width: 160px; }
        input[type=text] { width: 200px; }
        button { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }

        /* Button colors */
        button[type="submit"] { background: #4CAF50; color: #fff; }
        button[type="submit"]:hover { background: #45a049; }

        .logout-btn { background: #f44336; color: #fff; }
        .logout-btn:hover { background: #d32f2f; }
        .toggle-btn { background: #2196F3; color: #fff; width: 220px; }
        .toggle-btn:hover { background: #1976d2; }
        .delete-btn { background: #ff1100ff; color: #fff; }
        .delete-btn:hover { background: #fb8c00; }  

        /* ===== Messages ===== */
        .msg { text-align:center; margin: 10px 0; color: #d32f2f; font-weight: bold; }

        /* ===== Tables ===== */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 10px; text-align: center; border: 1px solid #ddd; }
        th { background: #f7f7f7; font-size: 16px; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }

        /* Status highlights */
        .status-mia { background-color: #fd1212ff; font-weight: bold; color: #b71c1c; }
        .status-outstation { background-color: #fadb6dff; font-weight: bold; color: #ff6f00; }
        .status-available { background-color: #b7fcb7ff; font-weight: bold; color: #2e7d32; }

        /* Responsive */
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

            options.forEach(function(opt){
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
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>My Attendance - Staff ID: <?= htmlspecialchars($emp_id) ?></h2>

            <!-- Logout -->
            <form method="POST" style="justify-content:flex-end;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>

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
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Sub-Status</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
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
                                <form method="POST" onsubmit="return confirm('Delete this application?');">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
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
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Sub-Status</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
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
                                    <form method="POST" onsubmit="return confirm('Delete this application?');">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
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
