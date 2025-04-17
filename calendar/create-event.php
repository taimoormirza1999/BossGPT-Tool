<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    die('Please connect your Google Calendar first.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Create Google Calendar Event</h2>
        <form action="handle-event.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Event Title</label>
                <input type="text" name="summary" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Select Date</label>
                <input type="date" name="event_date" class="form-control" required>
            </div>
            <input type="hidden" name="description" value="Client appointment request">
            <button type="submit" class="btn btn-primary">Create Event</button>
        </form>
    </div>
</body>
</html>
