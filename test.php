<?php
// require './classes/Notification.php';
// Notification::send('project_48', 'user_added', ['message' => 'New User Added successfully']);
// Notification::send('project_48', 'task_created', ['message' => 'New Task Created successfully']);
// Notification::send('project_48', 'task_updated', ['message' => 'Task Updated successfully']);
// echo "<script>alert('Notification Sent');</script>";
session_start();
echo "<pre>";
print_r(value: $_SESSION['result']);
echo "</pre>";
?>


