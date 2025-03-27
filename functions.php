<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
function sendTemplateEmail($to, $subject, $template, $data)
{
    try {
        $mail = new PHPMailer(true);

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'taimoorhamza199@gmail.com';
        $mail->Password = 'qyiujnhjbwtmpkma';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email Headers
        $mail->setFrom('taimoorhamza199@gmail.com', 'BossGPT AI Manager');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Load template
        ob_start();
        include "./email_templates/{$template}.php";
        $body = ob_get_clean();

        $mail->Body = $body;
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return $e->getMessage();
    }     
}
function sendTaskAssignmentEmail($email, $username, $taskTitle, $projectTitle)
{
    $subject = "New Task Assignment";
    $template = 'task_assignment';
    $data = [
        'username' => $username,
        'taskTitle' => $taskTitle,
        'projectTitle' => $projectTitle
    ];

    return sendTemplateEmail($email, $subject, $template, $data);
}

/**
 * Send project update notification
 */
function sendProjectUpdateEmail($email, $username, $projectTitle, $updateMessage)
{
    $subject = "Project Update: {$projectTitle}";
    $template = 'project_update';
    $data = [
        'username' => $username,
        'projectTitle' => $projectTitle,
        'updateMessage' => $updateMessage
    ];

    return sendTemplateEmail($email, $subject, $template, $data);
}

function getLogoImage($bottomMargin = "0", $topMargin = "-1rem", $width = "15rem", $height = "auto", $positionClass = "position-absolute top-0 start-50 translate-middle", $positionStyle = "position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);", $src="https://res.cloudinary.com/da6qujoed/image/upload/v1742824680/boss-gptold_zti8gh.png")
{
    return '<img src=' . $src . ' alt="Logo"
            class="' . $positionClass . '" 
            style="margin-top: ' . $topMargin . '; margin-bottom: ' . $bottomMargin . '; width: ' . $width . '; height: ' . $height . '; position: ' . $positionStyle . '">';
}