<?php
require './classes/NotificationManager.php';
require './classes/UserManager.php';
// Notification::send('project_48', 'user_added', ['message' => 'New User Added successfully']);
// Notification::send('project_48', 'task_created', ['message' => 'New Task Created successfully']);
// Notification::send('project_48', 'task_updated', ['message' => 'Task Updated successfully']);
// echo "<script>alert('Notification Sent');</script>";
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
echo "<pre>";
print_r(value: $_SESSION);
echo "</pre>";


require_once __DIR__ . '/classes/GoogleCalendarManager.php';

$calendar = new GoogleCalendarManager();

if (!$calendar->isAuthenticated()) {
    $calendar->authenticate(); // Redirects to Google OAuth
}

// Use form values
$summary     = $_POST['summary'] ?? 'Booking';
$description = $_POST['description'] ?? 'Client Appointment';
$eventDate   = $_POST['event_date'] ?? date('Y-m-d');

$calendar->createFixedEvent($summary, $description, $eventDate);
// $userManager = new UserManager();
// $notificationManager = new NotificationManager($userManager);
// $notificationManager->sendProjectNotification(42, "New User Added", "New User Added successfully");
?>


