<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/constant.php';

if (!isset($_SESSION['access_token'])) {
    die("Not authorized with Google Calendar.");
}

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_CALENDAR_REDIRECT_URI']);
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$client->setAccessToken($_SESSION['access_token']);

// Check if token has expired and refresh if necessary
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $_SESSION['access_token'] = $client->getAccessToken();
    } else {
        // No refresh token, redirect to re-authenticate
        header('Location: connect-calendar.php');
        exit;
    }
}

$service = new Google_Service_Calendar($client);

// Date selected by the user (format: YYYY-MM-DD)
$eventSummary = $_POST['summary']; // e.g., 2025-04-18
$eventDate = $_POST['event_date']; // e.g., 2025-04-18

// Set a fixed time slot (3:00 PM to 4:00 PM Dubai time)
$startTime = $eventDate . ' 15:00:00'; // 3:00 PM
$endTime   = $eventDate . ' 16:00:00'; // 4:00 PM

$event = new Google_Service_Calendar_Event([
    'summary' => $eventSummary,
    'description' => $_POST['description'] ?? 'Appointment via form.',
    'start' => [
        'dateTime' => date('c', strtotime($startTime)),
        'timeZone' => 'Asia/Dubai',
    ],
    'end' => [
        'dateTime' => date('c', strtotime($endTime)),
        'timeZone' => 'Asia/Dubai',
    ],
]);

$calendarId = 'primary';

try {
    $created = $service->events->insert($calendarId, $event);
    echo "<h3>Appointment Created:</h3>";
    echo "<p><a href='" . $created->htmlLink . "' target='_blank'>" . $created->getSummary() . "</a></p>";
} catch (Exception $e) {
    echo "Error creating appointment: " . $e->getMessage();
}
