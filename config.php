<?php
require_once 'config/constants.php';

require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['BASE_URL'].'/callback.php');
$client->addScope("email");
$client->addScope("profile");
