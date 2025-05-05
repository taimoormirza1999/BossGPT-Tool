<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/constant.php';

// Load environment variables
loadEnv(__DIR__ . '/../.env');


// Initialize Google client
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_CALENDAR_REDIRECT_URI']);
// $client->addScope(Google_Service_Calendar::CALENDAR);
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS); // Add events scope explicitly
$client->setAccessType('offline'); // Get refresh token
$client->setPrompt('consent'); // Always ask permission

$authUrl = $client->createAuthUrl();
// echo $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;