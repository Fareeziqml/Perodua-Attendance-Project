<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg = "";
if(isset($_POST['register'])) {
    $employee_id = $_POST['employee_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    $department = $_POST['department'];
    $role = $_POST['role'];

    // Handle photo upload
    $photo = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
        $targetDir = "uploads/";
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $photo = $targetDir . basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo);
    }

    $stmt = $conn->prepare("INSERT INTO employee (employee_id, password, photo, name, department, role) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $employee_id, $password, $photo, $name, $department, $role);

    if($stmt->execute()) {
        $msg = "✅ Registration successful. You can now login.";
    } else {
        $msg = "❌ Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Perodua System</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f9f9f9;
            color: #333;
        }

        /* Header with video background */
        header {
            position: relative;
            height: 35vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            overflow: hidden;
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
            font-size: 34px;
            margin: 10px 0;
            font-weight: bold;
        }

        header p {
            font-size: 16px;
            margin: 0;
            font-weight: 300;
        }

        /* Form section */
        .form-section {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 50px 20px;
            min-height: 65vh;
            background: #f9f9f9;
        }

        .form-box {
            background: #fff;
            border-radius: 15px;
            padding: 35px 30px;
            width: 420px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.3);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #009739;
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
            border-color: #009739;
            outline: none;
            box-shadow: 0 0 6px rgba(0,151,57,0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: #009739;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #007a2d;
        }

        .login-btn {
            background: #9E9E9E;
        }
        .login-btn:hover {
            background: #757575;
        }

        .msg { 
            text-align: center; 
            color: #d32f2f; 
            margin: 12px 0; 
            font-size: 14px;
        }

        footer {
            background: #161616ff;
            color: white;
            text-align: center;
            padding: 12px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <video autoplay muted loop playsinline>
        <source src="Video3_Perodua.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div>
        <h1>Perodua System Registration</h1>
        <p>Create Your Employee Account</p>
    </div>
</header>

<div class="form-section">
    <div class="form-box">
        <h2>Register 👨🏻‍💼</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <select name="department" required>
                <option value="">--Select Department--</option>
                <option>Management</option>
                <option>Sales</option>
                <option>Inventory</option>
                <option>Admin</option>
                <option>Warehouse</option>
            </select>
            <select name="role" required>
                <option value="">--Select Role--</option>
                <option value="GM">GM</option>
                <option value="employee">Employee</option>
            </select>
            <input type="file" name="photo" accept="image/*" required>
            <button type="submit" name="register">Register</button>
        </form>
        <div class="msg"><?= $msg ?></div>
        <form action="LoginPerodua.php" method="get">
            <button type="submit" class="login-btn">Login Here</button>
        </form>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division
</footer>

</body>
</html>
