<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to BossGPT!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', 'Georgia', 'Times New Roman', serif, 'Helvetica Neue';
        }
        span.im {
    color: #fff!important;
}
.ii a[href]{
    color: #000!important;
}
        .container {
            max-width: 600px;
            margin: auto;
            background: #000;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(1, 1, 1, 1);
            width: 100%;
        }

        .logo {
            width: 15rem;
            margin-bottom: 30px;
        }

        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #eee;
        }

        .content {
            font-size: 14px;
            line-height: 1.6;
            text-align: left;
            padding: 20px 30px;
            background: rgba(90, 90, 90, 0.3);
            border: 1.6px solid rgba(151, 151, 151, 0.2);
            color: #eee !important;
            border-radius: 8px;
        }

        .footer {
            font-size: 12px;
            color: #aaa;
            margin-top: 30px;
        }

        .block {
            background: #444;
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .blockquote {
            background: #222;
            padding: 10px;
            border-left: 5px solid #eee;
            color: #ddd;
        }

        .important-note {
            font-size: 18px;
            font-weight: bold;
            color: #eee;
            margin-top: 25px;
        }

        .footer-info {
            margin-top: 20px;
            font-size: 13px;

        }

        .button {
            background-color: #eee;
            color: #000;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 25px 0;
        }


        /* Mobile Responsiveness */
        @media (max-width: 600px) {
            .header {
                font-size: 20px;
            }

            .content {
                padding: 15px;
                font-size: 12px;
            }

            .container {
                padding: 20px 0;
            }

            .logo {
                width: 200px;
                margin-bottom: 10px;
            }

            .footer-info {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="https://bossgpt.com/boss-gpt.png" alt="BossGPT" class="logo" />
        <div class="header">Welcome to BossGPT!</div>
        <div class="content">
            <p>Hi <strong><?php echo $data['username']; ?></strong>,</p>
            <p><strong><?php echo $data['assignee_username']; ?></strong> has assigned you a new task! 📝</p>
            <p>We wanted to inform you that there’s an update in your project. Please check the details of the newly
                assigned task:</p>
            <p><strong>Project Name:</strong> <?php echo $data['project_name']; ?></p>
            <p><strong>Task Details:</strong> <?php echo $data['task_details']; ?></p>
            <p>To view and manage the task, click the button below:</p>
            <p><a href="<?php echo $data['projectLink']; ?>" class="button">Check Project Update</a></p>
            <p>If you have any questions or need further assistance, feel free to reach out. We're here to help!</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> BossGPT | Stay ahead, stay productive!</p>
        </div>
    </div>
</body>

</html>