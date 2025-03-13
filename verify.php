<?php
require_once 'classes/UserManager.php';
session_start();

$token = $_GET['token'] ?? '';
$message = '';
$status = '';

if (empty($token)) {
    $message = 'Invalid verification link.';
    $status = 'error';
} else {
    try {
        $userManager = new UserManager();
        $result = $userManager->verifyUser($token);
        
        if ($result) {
            $message = 'Your email has been verified successfully. You can now login.';
            $status = 'success';
        } else {
            $message = 'Invalid or expired verification link.';
            $status = 'error';
        }
    } catch (Exception $e) {
        $message = 'An error occurred during verification.';
        $status = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - BossGPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif; 
        background-color: #000; 
        color: #fff; 
        margin: 0; 
        padding: 20px;
        font-family: 'Segoe UI','Georgia', 'Times New Roman', serif,'Helvetica Neue'; 
            /* background-color: #f8f9fa; */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mbtn-white{
            background: rgba(90, 90, 90, 0.4); 
            border: 1.6px solid rgba(151, 151, 151, 0.2);
            padding: 10px 2rem!important;
            border-radius: 16px !important;
        }
        .verification-container {
            max-width: 90%;
            padding: 4rem 10rem;
            background: rgba(51, 51, 51, 0.4); 
            border-radius: 16px;
            border: 2px solid rgba(51, 51, 51, 0.2);
            box-shadow: 0 0 20px rgba(0,0,0,0.9);
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .error-icon {
            color: #dc3545;
            font-size: 48px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .verification-container {
                padding: 2rem 4rem;
            }
        }

    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($status === 'success'): ?>
            <i class="bi bi-check-circle-fill success-icon"></i>
            <h2 class="mb-4">Email Verified!</h2>
            <p class="text-success"><?php echo $message; ?></p>
            <a href="login.php" class="btn btn-primary mt-3">Go to Login</a>
        <?php else: ?>
            <i class="bi bi-x-circle-fill error-icon"></i>
            <h2 class="mb-4">Verification Failed</h2>
            <p class="text-danger"><?php echo $message; ?></p>
            <a href="index.php" class="btn mbtn-white mt-3 text-white">Return to Home</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>