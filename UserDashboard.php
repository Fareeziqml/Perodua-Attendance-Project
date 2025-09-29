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

// Handle attendance submission
if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $note = $_POST['note'];

    // Check if already marked today
    $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id=? AND date=?");
    $check->bind_param("ss", $emp_id, $today);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $msg = "⚠️ You already submitted attendance for today.";
    } else {
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, status, date, note) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $emp_id, $status, $today, $note);
        if($stmt->execute()) {
            $msg = "✅ Attendance submitted successfully.";
        } else {
            $msg = "❌ Error: " . $conn->error;
        }
    }
}

// Handle reset (delete) attendance record
if (isset($_POST['reset_attendance'])) {
    $dateToReset = $_POST['reset_attendance'];
    $del = $conn->prepare("DELETE FROM attendance WHERE employee_id=? AND date=?");
    $del->bind_param("ss", $emp_id, $dateToReset);
    if($del->execute()) {
        $msg = "🔄 Attendance for $dateToReset has been reset. You can resubmit.";
    } else {
        $msg = "❌ Error resetting record.";
    }
}

// Fetch attendance history (sorted by custom order: MIA/MC → OutStation → Available)
$history_sql = "
    SELECT * FROM attendance 
    WHERE employee_id=? 
    ORDER BY 
        CASE status
            WHEN 'MIA/MC' THEN 1
            WHEN 'OutStation' THEN 2
            WHEN 'Available' THEN 3
        END,
        date DESC
";
$history = $conn->prepare($history_sql);
$history->bind_param("s", $emp_id);
$history->execute();
$records = $history->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance - Perodua</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #f5f5f5; }
        .container { max-width: 950px; margin: 50px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        h2 { color: #4CAF50; text-align: center; }
        form { margin: 20px 0; text-align: center; }
        select, input[type=text] { padding: 8px; margin: 8px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        button:hover { background: #388E3C; }
        .logout-btn { background: #d32f2f; margin-left: 15px; }
        .logout-btn:hover { background: #b71c1c; }
        .reset-btn { background: #f57c00; }
        .reset-btn:hover { background: #ef6c00; }
        .msg { text-align: center; margin: 10px 0; color: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #4CAF50; color: white; }

        /* Highlight colors */
        .status-mia { background-color: #ffcccc; font-weight: bold; }
        .status-outstation { background-color: #fff4cc; font-weight: bold; }
        .status-available { background-color: #ccffcc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Attendance</h2>

        <!-- Logout button -->
        <form method="POST" style="text-align:right;">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>

        <!-- Attendance submission -->
        <form method="POST">
            <label>Status:</label>
            <select name="status" required>
                <option value="MIA/MC">MIA/MC</option>
                <option value="OutStation">OutStation</option>
                <option value="Available">Available</option>
            </select>
            <input type="text" name="note" placeholder="Add Note (optional)">
            <button type="submit">Submit</button>
        </form>

        <div class="msg"><?= $msg ?></div>

        <h3>Attendance History</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Note</th>
                <th>Action</th>
            </tr>
            <?php while($row = $records->fetch_assoc()): ?>
                <?php
                    $statusClass = "";
                    if ($row['status'] == "MIA/MC") $statusClass = "status-mia";
                    elseif ($row['status'] == "OutStation") $statusClass = "status-outstation";
                    elseif ($row['status'] == "Available") $statusClass = "status-available";
                ?>
                <tr class="<?= $statusClass ?>">
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['note']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="reset_attendance" value="<?= htmlspecialchars($row['date']) ?>" class="reset-btn">Reset</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
