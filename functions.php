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

function getLogoImage($bottomMargin = "0", $topMargin = "-1rem", $width = "15rem", $height = "auto", $positionClass = "position-absolute top-0 start-50 translate-middle", $positionStyle = "position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);", $src="https://res.cloudinary.com/da6qujoed/image/upload/v1742651528/bossgpt-transparent_n4axv7.png")
    {
        return '<img src=' . $src . ' alt="Logo"
                class="' . $positionClass . '" 
                style="margin-top: ' . $topMargin . '; margin-bottom: ' . $bottomMargin . '; width: ' . $width . '; height: ' . $height . '; position: ' . $positionStyle . '">';
    }
    function getIconImage($bottomMargin = "0", $topMargin = "0", $width = "3.4rem", $height = "auto", $src="https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png")
    {
        return '<img src=' . $src . ' alt="Logo"
                class="logo-icon"
                style=" filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3)); margin-top: ' . $topMargin . '; margin-bottom: ' . $bottomMargin . '; width: ' . $width . '; height: ' . $height . '">';
    }
    function getCalendarIcon($color = 'rgba(160, 160, 160, 1)') {
        $svg = file_get_contents('assets/icons/calendar.svg');
    
        // Target only path stroke attributes
        $svg = preg_replace('/(<path[^>]*?)stroke="[^"]*"/i', "$1stroke=\"$color\"", $svg);
    
        // If stroke doesn't exist at all, inject it
        $svg = preg_replace_callback('/<path([^>]*)>/i', function($matches) use ($color) {
            return (strpos($matches[1], 'stroke=') === false)
                ? '<path stroke="' . $color . '"' . $matches[1] . '>'
                : '<path' . $matches[1] . '>';
        }, $svg);
    
        // Optional: add an ID to the SVG root
        $svg = preg_replace('/<svg /', '<svg id="calendar-icon" ', $svg, 1);
    
        return $svg;
    }
    
    function getMenuIcon()
    {
        return file_get_contents('assets/icons/category.svg');
    }
    function getCloseSquareIcon()
    {
        return file_get_contents('assets/icons/close-square.svg');
    }
    function getTreeIcon()
    {
        return file_get_contents('assets/icons/tree.svg');
    }
    function getLogoutIcon()
    {
        return file_get_contents('assets/icons/logout.svg');
    }
    function getFolderIcon()
    {
        return file_get_contents('assets/icons/folder.svg');
    }
    function getProfileIcon()
    {
        return file_get_contents('assets/icons/profile.svg');
    }
    function getProfileDeleteIcon()
    {
        return file_get_contents('assets/icons/profile-delete.svg');
    }
    function getclipboardIcon()
    {
        return file_get_contents('assets/icons/clipboard.svg');
    }
    function getPaperclipIcon()
    {
        return file_get_contents('assets/icons/paperclip.svg');
    }

    function getClockIcon()
    {
        return file_get_contents('assets/icons/clock.svg');
    }
    function getAddUserIcon()
    {
        return file_get_contents('assets/icons/user-add.svg');
    }
    function getFileIcon()
    {
        return file_get_contents('assets/icons/file.svg');
    }
    function getTrashIcon()
    {
        return file_get_contents('assets/icons/trash.svg');
    }
    function getAddSquareIcon()
    {
        return file_get_contents('assets/icons/add-square.svg');
    }
    function getPlantBall()
    {
        return '<img src="assets/images/garden/plant-ball.png" alt="Plant Ball" class="plant-ball">';
    }
    function getPopupAlert($title, $description, $reminder_id)

    {
        return '<div class="popup-alert" data-reminder-id="'.$reminder_id.'">
        <div class="bell-icon">
          <img
            src="https://res.cloudinary.com/da6qujoed/image/upload/v1743687520/belliconImage_vnxkhi.png"
            alt="Bell icon"
          />
        </div>
        <div class="content">
          <h2 class="title">'.$title.'</h2>
          <p class="description">
            '.$description.'
          </p>
        </div>
        <button class="close-button" onclick="closePopup(this)">×</button>
      </div>';
    }

    