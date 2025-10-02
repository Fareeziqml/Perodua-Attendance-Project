<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php"; // this path must exist

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host       = "smtp.gmail.com";
$mail->SMTPAuth   = true;
$mail->Username   = "rizgaming3011@gmail.com"; // your Gmail
$mail->Password   = "wfmn nblc ohhi gyhv";    // 16-char App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587; // TLS port
$mail->isHTML(true);

return $mail;
