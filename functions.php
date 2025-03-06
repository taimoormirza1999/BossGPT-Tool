<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
function sendTemplateEmail($to, $subject, $template, $data) {
    try {
        $mail = new PHPMailer(true);

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
        return false;
    }
    /**
 * Send verification email to new users
 */
function sendVerificationEmail($email, $username, $token) {
    $verificationLink = "http://localhost/bossgpt-tool/verify.php?token=" . $token; // Update with your actual domain
    
    $subject = "Verify Your Account";
    $template = 'verification';
    $data = [
        'username' => $username,
        'verificationLink' => $verificationLink
    ];

    return sendTemplateEmail($email, $subject, $template, $data);
}
}
// **
//  * Send project invitation email
//  */
function sendProjectInvitation($email, $username, $projectId, $role, $token) {
    $invitationLink = "http://localhost/bossgpt-tool/accept-invitation.php?token=" . $token; // Update with your actual domain
    
    $subject = "Project Invitation";
    $template = 'project_invitation';
    $data = [
        'username' => $username,
        'role' => $role,
        'invitationLink' => $invitationLink
    ];

    return sendTemplateEmail($email, $subject, $template, $data);
}

/**
 * Send task assignment notification
 */
function sendTaskAssignmentEmail($email, $username, $taskTitle, $projectTitle) {
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
function sendProjectUpdateEmail($email, $username, $projectTitle, $updateMessage) {
    $subject = "Project Update: {$projectTitle}";
    $template = 'project_update';
    $data = [
        'username' => $username,
        'projectTitle' => $projectTitle,
        'updateMessage' => $updateMessage
    ];

    return sendTemplateEmail($email, $subject, $template, $data);
}