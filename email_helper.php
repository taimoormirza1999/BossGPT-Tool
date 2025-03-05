<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require 'vendor/autoload.php'; // If using Composer

function send_email($to, $subject, $body, $from = 'taimoor@herogram.app', $fromName = 'Taimoor Herogram', $attachment = null) {
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        // Enable debugging to see errors
        $mail->SMTPDebug = 2; // 2 for errors only, 3 for full details
        $mail->Debugoutput = 'html'; // Makes debugging output readable

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change for different provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'taimoorhamza199@gmail.com'; // Use your email
        $mail->Password   = 'plrjixcrrqvvjwvn'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Secure encryption (TLS)
        $mail->Port       = 587; // SMTP Port (587 for TLS, 465 for SSL)

        // Sender & Recipient
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Fallback for plain text

        // Attachment (if provided)
        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        // Send Email
        if ($mail->send()) {
            return "✅ Email sent successfully!";
        } else {
            return "❌ Email failed to send.";
        }
    } catch (Exception $e) {
        return "❌ Email error: " . $mail->ErrorInfo;
    }
}

// Example Usage
echo send_email(
    'taimoorhamza1999@gmail.com',
    'Test Email from PHP Mailer',
    '<h2>Hello, this is a test email!</h2>',
    'taimoorhamza199@gmail.com',
    'Taimoor Herogram'
);
?>
