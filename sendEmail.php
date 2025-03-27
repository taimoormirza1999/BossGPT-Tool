<?php

require './functions.php'; 

if ($argc > 1 && isset($argv[1])) {
    $emailData = json_decode($argv[1], true);

    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON received in sendEmail.php: " . json_last_error_msg());
        exit(1); // Exit with error code
    }

    // Validate required fields
    if (!empty($emailData) && isset($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data'])) {
        sendTemplateEmail($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data']);
    } else {
        error_log("Missing required email fields in sendEmail.php");
    }
}
?>
