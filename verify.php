<?php
// session_start();
$userManager = dirname(__DIR__) . './classes/UserManager.php';
$token = $_GET['token'] ?? '';
$message = '';
// exit();
$status = '';
if (empty($token)) {

    $message = 'Invalid verification link.';
    $status = 'error';
} else {
    try {
        // require_once __DIR__ . '/classes/UserManager.php';
        // var_dump($_GET['token']); // Debugging line
        // exit();
        //    $userManager = new UserManager();
        // exit();

        // $result = $userManager->verifyUser($token);
        if ($token == 'testingtoken') {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" sizes="32x32" href="faviconbossgpt.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', 'Georgia', 'Times New Roman', serif, 'Helvetica Neue';
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mbtn-white {
            background: rgba(90, 90, 90, 0.4);
            border: 1.6px solid rgba(151, 151, 151, 0.2);
            padding: 10px 2rem !important;
            border-radius: 16px !important;
        }

        .verification-container {
            max-width: 90%;
            padding: 2rem 8rem;
            border-radius: 16px;
            border: 2px solid rgba(51, 51, 51, 0.2);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.9);
            text-align: center;
        }
h2{
    font-size: 1.8rem;
margin-top: 0.6rem;
}
        .verification-success {
            background: rgba(40, 167, 69, 0.15);
            border-color: rgba(40, 167, 69, 0.3);
        }

        .verification-error {
            background: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.3);
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
    <div
        class="verification-container <?php echo ($status === 'success') ? 'verification-success' : 'verification-error'; ?>">
        <?php if ($status === 'success'): ?>
            <i class="bi bi-check-circle-fill success-icon"></i>
            <h2 class="mb-4">Email Verified!</h2>
            <p class="text-success"><?php echo $message; ?></p>
            <!-- <a href="login.php" class="btn btn-primary mt-3">Go to Login</a> -->
            <a href="?page=login" class="btn mbtn-white mt-3 text-white">Go to Login</a>

        <?php else: ?>
            <i class="bi bi-x-circle-fill error-icon"></i>
            <h2 class="mb-4">Verification Failed</h2>
            <p class="text-danger"><?php echo $message; ?></p>
            <a href="/" class="btn mbtn-white mt-3 text-white">Return to Home</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>