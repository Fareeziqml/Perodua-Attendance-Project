<?php
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$emp_id = $_GET['employee_id'];
$year = $_GET['year'];
$month = $_GET['month'];
$day = $_GET['day'];

$date = sprintf("%04d-%02d-%02d", $year, $month, $day);

// Delete attendance for that date
$sql = "DELETE FROM attendance WHERE employee_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $emp_id, $date);
$stmt->execute();

header("Location: AttendanceRateTable.php");
exit;
?>
    
