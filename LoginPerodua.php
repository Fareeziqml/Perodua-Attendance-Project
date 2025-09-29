<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg = "";
if(isset($_POST['login'])) {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id=? AND role=?");
    $stmt->bind_param("ss", $employee_id, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])) {
            $_SESSION['employee_id'] = $row['employee_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['name'];

            // Redirect based on role
            if($row['role'] == "GM") {
                header("Location: AdminAttendanceUpdate.php");
            } else {
                header("Location: UserDashboard.php");
            }
            exit;
        } else {
            $msg = "Invalid password.";
        }
    } else {
        $msg = "No user found with this Employee ID and Role.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Perodua System</title>
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
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #388E3C;
        }
        .register-btn {
            background: #9E9E9E;
        }
        .register-btn:hover {
            background: #757575;
        }
        .msg { 
            text-align: center; 
            color: red; 
            margin: 12px 0; 
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Perodua System Login</h2>
        <form method="POST">
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">--Select Role--</option>
                <option value="GM">GM</option>
                <option value="employee">Employee</option>
            </select>
            <button type="submit" name="login">Login</button>
        </form>
        <div class="msg"><?= $msg ?></div>
        <form action="RegisterPerodua.php" method="get">
            <button type="submit" class="register-btn">Register Here</button>
        </form>
    </div>
</body>
</html>
