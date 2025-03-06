<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .update-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .project-title {
            color: #2c5282;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Project Update</h2>
        <p>Hello <?php echo $data['username']; ?>,</p>
        
        <p>There has been an update in project <span class="project-title"><?php echo $data['projectTitle']; ?></span></p>
        
        <div class="update-box">
            <?php echo $data['updateMessage']; ?>
        </div>

        <p>Log in to your account to view more details and take action if needed.</p>

        <p>Best regards,<br>The BossGPT Team</p>
    </div>
</body>
</html>
