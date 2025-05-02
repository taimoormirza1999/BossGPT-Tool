<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_id() === '') {
    session_start();
  }
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constant.php';
// Load environment variables
loadEnv(__DIR__ . '/../.env');

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_CALENDAR_REDIRECT_URI']);
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline');
$client->setPrompt('consent');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Store token in session
    $_SESSION['access_token'] = $token;
    header('Location: ../');

    exit;
} else {
    echo "Failed to authenticate with Google Calendar.";
}
