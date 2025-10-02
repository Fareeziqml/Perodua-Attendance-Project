<?php
// process-reset-password.php

// Include database connection
$mysqli = require __DIR__ . "/database.php";

// Token must come from form submission
$token = $_POST["token"] ?? '';

if (empty($token)) {
    die("❌ Invalid request. Token is missing.");
}

$token_hash = hash("sha256", $token);

// Check token in DB
$sql = "SELECT * FROM employee WHERE reset_token_hash = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("❌ Invalid or expired token.");
}

// Check expiry
if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("❌ Token has expired. Please request a new password reset.");
}

// Get password inputs
$password = $_POST["password"] ?? '';
$password_confirmation = $_POST["password_confirmation"] ?? '';

// Validation
if (empty($password) || empty($password_confirmation)) {
    die("❌ Password fields cannot be empty.");
}

if ($password !== $password_confirmation) {
    die("❌ Passwords do not match.");
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Update DB and clear token
$sql = "UPDATE employee
        SET password = ?,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("si", $password_hash, $user["id"]);
$stmt->execute();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Updated</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
        body {
            text-align: center;
            margin-top: 100px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        h1 {
            color: #009739;
        }
        p {
            margin: 20px 0;
        }
        button {
            padding: 12px 20px;
            background: #009739;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #007a2d;
        }
    </style>
</head>
<body>
    <h1>✅ Password Updated Successfully</h1>
    <p>Your password has been changed. You can now login with your new credentials.</p>
    <form action="LoginPerodua.php" method="get">
        <button type="submit">Go to Login</button>
    </form>
</body>
</html>
