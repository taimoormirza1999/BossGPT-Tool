<?php

require './functions.php'; 
echo "php -v";
if ($argc > 1) {
    $emailData = json_decode($argv[1], true);
    
    if (!empty($emailData)) {
        sendTemplateEmail($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data']);
    }
}
