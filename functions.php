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
        $mail->Host = 'email-smtp.us-east-1.amazonaws.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'AKIAZBQNTRAPZVUXHAV4';
        $mail->Password = 'BMuaIhdcAnWFN7zTywJAROyVNdyoBNy+vp1JcFw6dVVd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        // Email Headers
        $mail->setFrom('contact@bossgpt.com', 'BossGPT.com');
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
function sendEmailAWS($to, $subject)
{
    try {
        $mail = new PHPMailer(true);

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'email-smtp.us-east-1.amazonaws.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'AKIAZBQNTRAPZVUXHAV4';
        $mail->Password = 'BMuaIhdcAnWFN7zTywJAROyVNdyoBNy+vp1JcFw6dVVd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        

        // Email Headers
        $mail->setFrom('contact@bossgpt.com', 'BossGPT AI Manager');
        $mail->addAddress($to);
        // $mail->isHTML(true);
        $mail->Subject = $subject;
        // Message
        $mail->Subject = 'SES SMTP PHP Test';
        $mail->Body    = "Hello!\nThis is a test email sent via SES SMTP and PHPMailer.";

        // Send it
        $mail->send();
        // Load template
        // ob_start();
        // include "./email_templates/{$template}.php";
        // $body = ob_get_clean();

        // $mail->Body = $body;
        // $mail->send();
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

function getLogoImage($bottomMargin = "0", $topMargin = "-1rem", $width = "15rem", $height = "auto", $positionClass = "lg:position-absolute top-0 start-50 translate-middle non-login-page-logo", $positionStyle = "", $src="https://res.cloudinary.com/da6qujoed/image/upload/v1742651528/bossgpt-transparent_n4axv7.png")
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
        $svg = preg_replace('/<svg /', '<svg class="calendar-icons" id="calendar-icon" ', $svg, 1);
    
        return $svg;
    }
    
    function getGoogleIcon()
    {
        return file_get_contents('assets/icons/googleIcon.svg');
    }
    function getMenuIcon()
    {
        return file_get_contents('assets/icons/category.svg');
    }
    function getThemeIcon()
    {
        return file_get_contents('assets/icons/brush.svg');
    }
    function getCloseSquareIcon()
    {
        return file_get_contents('assets/icons/close-square.svg');
    }
    function getErrorIcon()
    {
        return file_get_contents('assets/icons/error.svg');
    }
    function getSuccessIcon()
    {
        return file_get_contents('assets/icons/tick.svg');
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

    function getEditIcon()
    {
        return file_get_contents('assets/icons/edit.svg');
    }
    function getClockIcon()
    {
        return file_get_contents('assets/icons/clock.svg');
    }
    function getTimerIcon()
    {
        return file_get_contents('assets/icons/timer.svg');
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
    function getAddIcon()
    {
        return file_get_contents('assets/icons/add_normal.svg');
    }
    function getPlantBall()
    {
        return '<img src="assets/images/garden/plant-ball.png" alt="Plant Ball" class="plant-ball">';
    }
    function getPopupAlert($title, $description, $reminder_id, $button = false, $container_class = '', $icon = 'https://res.cloudinary.com/da6qujoed/image/upload/v1743687520/belliconImage_vnxkhi.png', $type = 'telegram')
    {
        return '<div class="popup-alert '.$container_class.'" data-reminder-id="'.$reminder_id.'" id="'.$reminder_id.'">
        <div class="bell-icon">
          <img
            src="'.$icon.'"
            alt="Bell icon"
          />
        </div>
        <div class="content font-secondaryBold">
          <h2 class="title">'.$title.'</h2>
          <p class="description">
            '.$description.'
          </p>
          '.$button.'
        </div>
        <button class="close-button" onclick="closePopup(this, \''.$type.'\')">×</button>
      </div>';
    }
    function renderAIToneOptions($tones = [
        [
            'value' => 'friendly',
            'image' => 'https://res.cloudinary.com/da6qujoed/image/upload/v1744649255/friendlyai_hy04oz.svg',
            'label' => 'Friendly',
            'active' => true
        ],
        [
            'value' => 'funny',
            'image' => 'https://res.cloudinary.com/da6qujoed/image/upload/v1744650101/funny_ql6wcm.svg',
            'label' => 'Funny'
        ],
        [
            'value' => 'angry',
            'image' => 'https://res.cloudinary.com/da6qujoed/image/upload/v1744650100/angry_sye97x.svg',
            'label' => 'Angry'
        ],
        [
            'value' => 'geeky',
            'image' => 'https://res.cloudinary.com/da6qujoed/image/upload/v1744650100/geeky_cm1bmy.svg',
            'label' => 'Geeky'
        ],
        [
            'value' => 'caring',
            'image' => 'https://res.cloudinary.com/da6qujoed/image/upload/v1744650100/caring_b4yp8e.svg',
            'label' => 'Caring'
        ]
    ]) {
        $html = '<div class="ai-tone-options row">';
        foreach ($tones as $tone) {
            $html .= '
            <div class="col-4 mb-4">
                <div class="ai-tone-option" data-tone="' . $tone['value'] . '">
                    <img src="' . $tone['image'] . '" alt="' . $tone['label'] . '">
                    <div>' . $tone['label'] . '</div>
                    <div class="tone-indicator' . (isset($tone['active']) && $tone['active'] ? ' active' : '') . '"></div>
                </div>
            </div>';
        }
        $html .= '</div>';
        return $html;
    } 
    function renderAIErrorMessage($messageTitle="Sorry! I encountered an error 
while scheduling your event",$messageDescription="Please connect your calendar to schedule the event and get calendar notifications.
", $link = "/calendar/connect-calendar.php") {
        return '<div class="error-message-block">
        <div class="d-flex align-items-start ">'.getErrorIcon().' <p class="error-message-title mb-0">'.$messageTitle.'</p></div>
        <p class="d-flex align-items-start justify-content-between mb-1"  style="
    font-size: 0.89rem;
">'.$messageDescription.'</p>
        <div class="d-flex align-items-center justify-content-center gap-3">
        <button class="btn btn-chat btn-error" onclick="window.location.href=\''.$_ENV['BASE_URL'].$link.'\'">Connect</button>
        </div>
        </div>';
    }
    function renderAICalendarSuccessMessage($messageHeader="Event Scheduled successfully", $title="Meeting with Taimoor",$date="Saturday, May 3, 2025", $time="10:00AM - 11:00AM(Dubai Time)",$description="Event scheduled via BossGpt AI Assistant.", $link = "/calendar/connect-calendar.php") {
        return '<div class="success-message-block">
        <div class="d-flex align-items-start ">'.getSuccessIcon().' <p class="success-message-title mb-0">'.$messageHeader.'</p></div>
        <p class="d-flex align-items-start justify-content-between mb-1"  style="
    font-size: 0.89rem;
    ">'.$title.'</p>
        <strong class="d-flex align-items-start justify-content-between mb-1"  style="
    font-size: 0.89rem;
">Details:</strong>
 <div class="">
       <p class="mb-0"><strong>Date:</strong> '.$date.'</p>
       <p class="mb-0"><strong>Time:</strong> '.$time.'</p>
       <p class="mb-0"><strong>Description:</strong> '.$description.'</p>
        <div class="d-flex align-items-center justify-content-center gap-3">
        <button class="btn btn-chat btn-error mt-1" onclick="openLink(\''.$link.'\',true)">Open&nbsp;in&nbsp;Calendar</button>
        </div>
        </div>';
    }
    function renderCustomModal($modalId, $headerText, $bodyContent, $footerButtons = '') {
    return '
    <h2>'.$modalId.'</h2>
        <div class="modal fade" id="'.$modalId.'" tabindex="-1" aria-labelledby="'.$modalId.'Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-white border-0 rounded-t-lg">
                        <h5 class="modal-title" id="'.$modalId.'Label">
                            '.$headerText.'
                        </h5>
                        <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal" aria-label="Close">
                            '.getCloseSquareIcon().'
                        </button>
                    </div>
    
                    <div class="modal-body">
                        '.$bodyContent.'
                    </div>
    
                    '.$footerButtons.'
                </div>
            </div>
        </div>';
    }
    
    