<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../env.php';
loadEnv();
$GOOGLE_CLIENT_ID='949298386531-pbk4td6p6ga18e6diee9rifskto0ou0v.apps.googleusercontent.com';
$GOOGLE_CLIENT_SECRET='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';
$GOOGLE_REDIRECT_URI='http://localhost/bossgpt-tool/calendar/calendar-callback.php';


$client = new Google_Client();
$client->setClientId($GOOGLE_CLIENT_ID);
$client->setClientSecret($GOOGLE_CLIENT_SECRET);
$client->setRedirectUri($GOOGLE_REDIRECT_URI);
$client->addScope(Google_Service_Calendar::CALENDAR);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // ✅ Store token in session or DB (for now, using session)
    $_SESSION['access_token'] = $token;

    // Redirect to event creation form
    header('Location: create-event.php');
    exit;
} else {
    echo "Failed to authenticate with Google Calendar.";
}
