<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Load request data
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

// Extract email details
$to = $data['to'] ?? '';
$template = $data['template'] ?? 'daily_report'; // Template selection
$userName = $data['userName'] ?? 'User';
$taskSummary = $data['taskSummary'] ?? 'No updates available.';
$motivation = $data['motivation'] ?? 'Keep pushing forward!';
$encouragement = $data['encouragement'] ?? 'Your hard work will pay off!';
$deadlineNote = $data['deadlineNote'] ?? 'Be mindful of pending tasks!';
$nextSteps = $data['nextSteps'] ?? 'Focus on high-priority tasks for tomorrow.';

// Pass the variables to the template
ob_start();
include "./email_templates/{$template}.php";  // Dynamically load the chosen template
$body = ob_get_clean();  // Capture the output as the email body
var_dump($body);
$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'taimoorhamza199@gmail.com'; 
    $mail->Password = 'plrjixcrrqvvjwvn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email Headers
    $mail->setFrom('taimoorhamza199@gmail.com', 'BossGPT AI Manager');
    $mail->addAddress($to);
    
    $mail->isHTML(true);
    $mail->Subject = ($template === 'daily_report') ? 'Your Daily Work Summary' : 'Reminder: Keep Up the Work!';
    $mail->Body = $body;

    // Send Email
    $mail->send();
    echo json_encode(["status" => "success", "message" => "Email sent successfully"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Mail Error: {$mail->ErrorInfo}"]);
}
?>
