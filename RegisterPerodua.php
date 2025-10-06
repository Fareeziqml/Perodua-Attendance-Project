<?php
session_start();
$conn = new mysqli("localhost", "root", "", "spareparts");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg = "";
if(isset($_POST['register_confirm'])) { // Triggered after confirmation
    $employee_id = trim($_POST['employee_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pos = $_POST['POS']; // ✅ New POS field
    $department = $_POST['department'];
    $role = $_POST['role'];

    // Check if employee_id or email already exists
    $check = $conn->prepare("SELECT * FROM employee WHERE employee_id = ? OR email = ?");
    $check->bind_param("ss", $employee_id, $email);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0) {
        $msg = "❌ Employee ID or Email already exists. Please use a different one.";
    } else {
        // Handle photo upload
        $photo = null;
        if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
            $targetDir = "uploads/";
            if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $photo = $targetDir . time() . "_" . basename($_FILES["photo"]["name"]);
            move_uploaded_file($_FILES["photo"]["tmp_name"], $photo);
        }

        // ✅ Updated to include POS column
        $stmt = $conn->prepare("INSERT INTO employee (employee_id, password, photo, name, email, POS, department, role) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $employee_id, $password, $photo, $name, $email, $pos, $department, $role);

        if($stmt->execute()) {
            $msg = "✅ Registration successful. You can now login.";
        } else {
            $msg = "❌ Error: " . $conn->error;
        }
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
        .popup-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(6px);
            display: none;
            justify-content: center;
            align-items: center;
            animation: fadeInOverlay 0.3s ease;
            z-index: 999;
        }
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .popup-box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 6px 20px rgba(0,0,0,0.3);
            text-align: center;
            width: 360px;
            animation: fadeInPopup 0.4s ease;
        }
        @keyframes fadeInPopup {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .popup-box h3 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #333;
        }
        .popup-box .popup-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
        }
        .popup-box button {
            width: 40%;
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
        <form id="registerForm" method="POST" enctype="multipart/form-data">
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <!-- Gmail only -->
            <input type="email" name="email" placeholder="Gmail" pattern="[a-z0-9._%+-]+@gmail\.com" required>
            
            <!-- ✅ New POS field -->
            <select name="POS" required>
                <option value="">--Select Position (POS)--</option>
                <option>AA</option>
                <option>GM</option>
                <option>DGM</option>
                <option>ACT DGM</option>
                <option>ACT MGR</option>
                <option>AM</option>
                <option>CAA</option>
                <option>ENG</option>
                <option>EXEC</option>
                <option>MGR</option>
                <option>SM</option>
                <option>TA</option>
                <option>TM</option>
                <option>TR</option>
            </select>

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
            <button type="button" onclick="showPopup()">Register</button>
        </form>
        <div class="msg"><?= $msg ?></div>
        <form action="LoginPerodua.php" method="get">
            <button type="submit" class="login-btn">Login Here</button>
        </form>
    </div>
</div>

<div class="popup-overlay" id="popup">
    <div class="popup-box">
        <h3>Are you sure you want to register?</h3>
        <div class="popup-buttons">
            <button onclick="confirmRegister()">Yes</button>
            <button onclick="closePopup()" class="login-btn">Cancel</button>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division
</footer>

<script>
function showPopup(){
    document.getElementById('popup').style.display = "flex";
}
function closePopup(){
    document.getElementById('popup').style.display = "none";
}
function confirmRegister(){
    const form = document.getElementById('registerForm');
    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "register_confirm";
    hidden.value = "1";
    form.appendChild(hidden);
    form.submit();
}
</script>

</body>
</html>
