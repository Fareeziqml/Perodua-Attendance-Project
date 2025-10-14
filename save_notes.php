<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['notes']) && isset($_POST['annual_leave'])) {
        foreach ($_POST['notes'] as $emp_id => $note) {
            $annual = isset($_POST['annual_leave'][$emp_id]) ? trim($_POST['annual_leave'][$emp_id]) : 0;
            $note   = trim($note);

            // Force integer for annual_leave (if column is INT in DB)
            if ($annual === '' || !is_numeric($annual)) {
                $annual = 0;
            }

            $stmt = $conn->prepare("UPDATE employee SET annual_leave = ?, notes = ? WHERE employee_id = ?");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            // Assuming: annual_leave = INT, notes = TEXT/VARCHAR, employee_id = VARCHAR
            $stmt->bind_param("iss", $annual, $note, $emp_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo "<script>alert('Notes and Annual Leave saved successfully!'); window.location.href='AdminAttendanceTable.php';</script>";
}
?>
