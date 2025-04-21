<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';


$mail = new PHPMailer(true);

try {
     // Enable verbose debug output
     $mail->SMTPDebug = 2;                // 0 = off (default), 1 = client messages, 2 = client + server messages
     $mail->Debugoutput = 'html';          // Pretty output in HTML format

    $mail->isSMTP();
    $mail->Host       = 'smtp.mandrillapp.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'Mirza Software Solution';
    // $mail->Password   = 'md-UlLHlao5ZMXpAOUJL2fZgQ';
    $mail->Password   = 'md-eP9ygk6AxG6Y3diwGFgk8A';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
   

    $mail->setFrom('contact@bossgpt.com', 'BossGPT');
    $mail->addAddress('taimoorhamza1999@gmail.com');
    $mail->Subject = 'Test Email from PHP';
    $mail->Body    = 'This is a test email sent via SMTP using PHPMailer in core PHP.';

    
    $mail->send();
    echo 'Email has been sent successfully.';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}