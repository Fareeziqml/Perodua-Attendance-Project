<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg = "";

if(isset($_POST['reset'])) {
    $employee_id = $_POST['employee_id'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password !== $confirm_password) {
        $msg = "Passwords do not match.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id=? AND role=?");
        $stmt->bind_param("ss", $employee_id, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE employee SET password=? WHERE employee_id=? AND role=?");
            $update->bind_param("sss", $hashed_password, $employee_id, $role);
            if($update->execute()) {
                $msg = "Password successfully reset. You can now <a href='LoginPerodua.php'>login</a>.";
            } else {
                $msg = "Failed to reset password. Please try again.";
            }
        } else {
            $msg = "No user found with this Employee ID and Role.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Perodua System</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #4CAF50, #9E9E9E);
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .form-box {
            background: #fff;
            border-radius: 15px;
            padding: 40px 30px;
            width: 380px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.3);
            animation: fadeIn 0.8s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 25px;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: 0.3s;
        }
        input:focus, select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 6px rgba(76,175,80,0.5);
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: #FF9800;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #F57C00;
        }
        .back-btn {
            background: #2196F3;
            margin-top: 10px;
        }
        .back-btn:hover {
            background: #1976D2;
        }
        .msg { 
            text-align: center; 
            color: red; 
            margin: 12px 0; 
            font-size: 14px;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <select name="role" required>
                <option value="">--Select Role--</option>
                <option value="GM">GM</option>
                <option value="employee">Employee</option>
            </select>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit" name="reset">Reset Password</button>
        </form>

        <div class="msg <?= strpos($msg,'successfully') !== false ? 'success' : '' ?>"><?= $msg ?></div>

        <form action="LoginPerodua.php" method="get">
            <button type="submit" class="back-btn">⬅ Back to Login</button>
        </form>
    </div>
</body>
</html>
