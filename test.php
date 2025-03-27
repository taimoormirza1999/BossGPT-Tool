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
// $userManager = new UserManager();
// $notificationManager = new NotificationManager($userManager);
// $notificationManager->sendProjectNotification(42, "New User Added", "New User Added successfully");
?>


