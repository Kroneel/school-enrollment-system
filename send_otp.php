<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

function sendOTP($userEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'din.kumar@vodafone.com.fj';
        $mail->Password   = 'Password@2601!';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender & Recipient
        $mail->setFrom('din.kumar@vodafone.com.fj', 'Koro High School');
        $mail->addAddress($userEmail);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = "Your Login OTP Code";
        $mail->Body    = "<h2>Your OTP Code is:</h2><h1>$otpCode</h1><p>This code is valid for 5 minutes.</p>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
?>
