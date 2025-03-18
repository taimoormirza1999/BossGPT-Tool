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
            color: #eee;
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
            <p>Dear <?php echo $data['username']; ?>,</p>
            <p>We wanted to inform you that a new user has been added to the project. Below are the details of the new
                user:</p>
            <ul>
                <li><strong>Username:</strong> <?php echo $data['newusername']; ?></li>
                <li><strong>Role:</strong> <?php echo $data['role']; ?></li>
                <li><strong>Date Added:</strong> <?php echo $data['date']; ?></li>
                <li><strong>Time Added:</strong> <?php echo $data['time']; ?></li>
            </ul>
            <p>The new user is now part of the project, and we look forward to collaborating with them!</p>
            <p>If you have any questions or need further information, feel free to reach out.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> BossGPT | Stay ahead, stay productive!</p>
        </div>
    </div>
</body>

</html>