<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
            height: 100vh;
        }
        .form-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            min-height: 100vh;
            background: #f9f9f9;
        }
        .form-box {
            background: #fff;
            border-radius: 25px;
            padding: 80px 60px;
            width: 600px;
            max-width: 95%;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.25);
            animation: fadeIn 0.8s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            color: #009739;
            text-align: center;
            margin-bottom: 40px;
            font-size: 32px;
        }
        input, select {
            width: 100%;
            padding: 18px;
            margin: 14px 0;
            border: 1px solid #ccc;
            border-radius: 12px;
            transition: 0.3s;
            font-size: 18px;
        }
        input:focus, select:focus {
            border-color: #009739;
            outline: none;
            box-shadow: 0 0 12px rgba(0,151,57,0.5);
        }
        button {
            width: 100%;
            padding: 18px;
            margin-top: 20px;
            background: #009739;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #007a2d;
        }
        .nav-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: #131614;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .nav-btn:hover {
            background: #009739;
        }
        .msg { 
            text-align: center; 
            color: red; 
            margin: 12px 0; 
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="form-section">
    <div class="form-box">
        <a href="LoginPerodua.php" class="nav-btn">⬅ Back to Login</a>
        <h2>Forgot Password 🔑</h2>

        <?php if (isset($_GET['error'])): ?>
            <p class="msg"><?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <p style="text-align:center; color:green;"><?= htmlspecialchars($_GET['success']) ?></p>
        <?php endif; ?>

        <form method="post" action="send-password-reset.php">
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</div>

</body>
</html>
