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
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
        }

        /* Sticky header with video */
        header {
            position: sticky;
            top: 0;
            height: 30vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            overflow: hidden;
            z-index: 10;
        }

        header video {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        header::after {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(1, 29, 12, 0.55);
            z-index: -1;
        }

        header h1 {
            font-size: 32px;
            margin: 8px 0;
            font-weight: bold;
        }

        header p {
            font-size: 16px;
            margin: 0;
            font-weight: 300;
        }

        /* Back to Home button in header */
        .home-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: black;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            border: 2px solid white;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
        }

        .home-btn:hover {
            background: white;
            color: black;
            border: 2px solid black;
        }

        /* Login form section */
        .form-section {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 50px 20px;
            min-height: 70vh;
            background: #f9f9f9;
        }

        .form-box {
            background: #fff;
            border-radius: 15px;
            padding: 30px 25px;
            width: 350px;
            box-shadow: 0px 6px 16px rgba(0,0,0,0.2);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #009739;
            text-align: center;
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 14px;
        }

        input:focus, select:focus {
            border-color: #009739;
            outline: none;
            box-shadow: 0 0 6px rgba(0,151,57,0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            background: #009739;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #007a2d;
        }

        .register-btn { background: #9E9E9E; }
        .register-btn:hover { background: #757575; }

        .reset-btn { background: #FF9800; }
        .reset-btn:hover { background: #fc0909ff; }

        .msg { 
            text-align: center; 
            color: red; 
            margin: 10px 0; 
            font-size: 14px;
        }

        footer {
            background: #131614ff;
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <video autoplay muted loop playsinline>
        <source src="Video2_Perodua.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Back to Home button -->
    <a href="HomePage.php" class="home-btn">⬅ Back to Home</a>

    <div>
        <h1>Perodua System Login</h1>
        <p>Secure Access to Daily Attendance</p>
    </div>
</header>

<div class="form-section">
    <div class="form-box">
        <h2>Login 👨🏻‍💼</h2>
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

        <form action="forgot-password.php" method="get">
            <button type="submit" class="reset-btn"> Forgot Password</button>
        </form>

    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division
</footer>

</body>
</html>
