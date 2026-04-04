<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'kl2508019931@student.uptm.edu.my';                     // SMTP username
    $mail->Password   = 'bvoqltwiytcjpjvb';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
    $mail->Port       = 465;                                    // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    // Recipients
    $mail->setFrom('kl2508019931@student.uptm.edu.my', 'Mailer Test');
    $mail->addAddress('kl2508019931@student.uptm.edu.my');     // Add yourself as recipient

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'SMTP Test Connection';
    $mail->Body    = 'If you see this, your SMTP settings are CORRECT!';

    $mail->send();
    echo "\n\nSUCCESS: Message has been sent to kl2508019931@student.uptm.edu.my";
} catch (Exception $e) {
    echo "\n\nERROR: Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
