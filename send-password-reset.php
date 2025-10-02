<?php
// send-password-reset.php
if (!isset($_POST['email'])) {
    die("Email is required.");
}

$email = $_POST['email'];

$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 1800); // 30 minutes

$mysqli = require __DIR__ . "/database.php"; // Make sure this returns a mysqli connection

// Update employee table with token
$sql = "UPDATE employee
        SET reset_token_hash = ?, reset_token_expires_at = ?
        WHERE email = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sss", $token_hash, $expiry, $email);
$stmt->execute();

if ($stmt->affected_rows) {
    // Load PHPMailer
    $mail = require __DIR__ . "/mailer.php";

    $resetLink = "http://localhost/PERODUA_PROJECT/reset-password.php?token=$token";    


    try {
        $mail->setFrom("your-email@gmail.com", "Perodua System");
        $mail->addAddress($email);
        $mail->Subject = "Password Reset Request";
        $mail->Body = "Click <a href='$resetLink'>here</a> to reset your password. This link expires in 30 minutes.";

        $mail->send();
        echo "✅ Message sent! Check your inbox.";
    } catch (Exception $e) {
        echo "❌ Mailer Error: " . $mail->ErrorInfo;
    }
} else {
    echo "❌ No account found with that email.";
}
