<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Email Address</h2>
        <p>Hello <?php echo $data['username']; ?>,</p>
        
        <p>Please verify your email address by clicking the button below:</p>
        
        <p>
            <a href="<?php echo $data['verificationLink']; ?>" class="button">
                Verify Email Address
            </a>
        </p>

        <p>Or copy and paste this link in your browser:</p>
        <p><?php echo $data['verificationLink']; ?></p>

        <p>If you didn't create an account, you can safely ignore this email.</p>

        <p>Best regards,<br>The BossGPT Team</p>
    </div>
</body>
</html>
