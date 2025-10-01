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
            // check duplicate for each day
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

// Fetch today attendance (ONLY today)
$today_sql = "SELECT * FROM attendance WHERE employee_id=? AND date = ?";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $emp_id, $today);
$today_stmt->execute();
$todayRecord = $today_stmt->get_result();

// Fetch future applications (strictly > today)
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
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        h2 { color: #4CAF50; text-align: center; }
        form { margin: 20px 0; text-align: center; }
        select, input[type=text], input[type=date] { padding: 8px; margin: 8px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        button:hover { background: #388E3C; }
        .logout-btn { background: #d32f2f; margin-left: 15px; }
        .logout-btn:hover { background: #b71c1c; }
        .toggle-btn { background: #1976d2; display:block; margin:15px auto; }
        .toggle-btn:hover { background: #0d47a1; }
        .delete-btn { background: #e53935; padding: 5px 10px; border-radius: 4px; }
        .delete-btn:hover { background: #c62828; }
        .msg { text-align: center; margin: 10px 0; color: #d32f2f; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #4CAF50; color: white; }

        /* Highlight colors */
        .status-mia { background-color: #ffcccc; font-weight: bold; }
        .status-outstation { background-color: #fff4cc; font-weight: bold; }
        .status-available { background-color: #ccffcc; font-weight: bold; }
        #futureSection { display: none; }
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
        <h2>My Attendance - Staff ID: <?= htmlspecialchars($emp_id) ?></h2>

        <!-- Logout button -->
        <form method="POST" style="text-align:right;">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>

        <!-- Attendance submission -->
        <form method="POST">
            <label>Status:</label>
            <select name="status" id="status" onchange="updateSubStatusOptions()" required>
                <option value="">--Select--</option>
                <option value="MIA/MC">MIA/MC</option>
                <option value="OutStation">OutStation</option>
                <option value="Available">Available</option>
            </select>

            <label>Sub-Status:</label>
            <select name="sub_status" id="sub_status" required>
                <option value="">--Select--</option>
            </select>

            <input type="text" name="note" placeholder="Add Note (optional)">
            <br>
            <label>From:</label>
            <input type="date" name="start_date" required>
            <label>To:</label>
            <input type="date" name="end_date" required>
            <button type="submit">Submit</button>
        </form>

        <div class="msg"><?= $msg ?></div>

        <h3>Today Attendance Update</h3>
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

        <!-- Show/Hide Button -->
        <button type="button" class="toggle-btn" onclick="toggleFuture()">Show/Hide Future Applications</button>

        <div id="futureSection">
            <h3>Future Applications (Tomorrow and Beyond)</h3>
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
</body>
</html>
