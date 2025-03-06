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
        .role-badge {
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Project Invitation</h2>
        <p>Hello <?php echo $data['username']; ?>,</p>
        
        <p>You have been invited to join a project as a <span class="role-badge"><?php echo $data['role']; ?></span></p>
        
        <p>Click the button below to accept the invitation:</p>
        
        <p>
            <a href="<?php echo $data['invitationLink']; ?>" class="button">
                Accept Invitation
            </a>
        </p>

        <p>Or copy and paste this link in your browser:</p>
        <p><?php echo $data['invitationLink']; ?></p>

        <p>This invitation will expire in 7 days.</p>

        <p>Best regards,<br>The BossGPT Team</p>
    </div>
</body>
</html>
