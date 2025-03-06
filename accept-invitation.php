<?php
require_once 'classes/UserManager.php';
session_start();

$token = $_GET['token'] ?? '';
$message = '';
$status = '';

if (empty($token)) {
    $message = 'Invalid invitation link.';
    $status = 'error';
} else {
    try {
        $userManager = new UserManager();
        $result = $userManager->acceptProjectInvitation($token);
        
        if ($result) {
            $message = 'You have successfully joined the project.';
            $status = 'success';
        } else {
            $message = 'Invalid or expired invitation link.';
            $status = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while accepting the invitation.';
        $status = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Invitation - BossGPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .invitation-container {
            max-width: 500px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="invitation-container">
        <?php if ($status === 'success'): ?>
            <h2 class="mb-4">Welcome to the Project!</h2>
            <p class="text-success"><?php echo $message; ?></p>
            <a href="dashboard.php" class="btn btn-primary mt-3">Go to Dashboard</a>
        <?php else: ?>
            <h2 class="mb-4">Invitation Error</h2>
            <p class="text-danger"><?php echo $message; ?></p>
            <a href="index.php" class="btn btn-secondary mt-3">Return to Home</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 