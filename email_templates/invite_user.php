<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
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
            color: #fff !important;
        }

        .ii a[href] {
            color: #000 !important;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #000;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            width: 100%;
        }

        .logo {
            width: 250px;
            margin-bottom: 20px;
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
            margin: 15px 0;
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
    <div class='container'>
        <img src='https://bossgpt.com/boss-gpt.png' alt='BossGPT' class='logo' />
        <div class='header'>Welcome to BossGPT!</div>
        <div class='content'>
            <p>Dear <strong><?php echo $data['username']; ?></strong>,</p>
            <p>You have been invited to join the project <strong><?php echo $data['projectname']; ?></strong>.</p>
            <?php if(isset($data['tempPassword']) && $data['tempPassword']): ?>     
            <p>Your account has been successfully created. Below are your login credentials:</p>

            <div class='block'>
                <p><strong>Email:</strong> <?php echo $to; ?></p>
                <p><strong>Temporary Password:</strong> <?php echo $data['tempPassword'] ?? ''; ?></p>
            </div>
            <?php endif; ?>
            <p><strong>Important:</strong> Please follow these steps to get started:</p>
            <?php if(isset($data['tempPassword']) && $data['tempPassword']): ?>
            <div class='block'>
                <ol>
                    <li>Click the verification link below to verify your email:</li>
                    <p><a href="<?php echo $data['verificationLink'] ?? ''; ?>" class="button">Verify Email</a></p>
                    <li>Log in using your email and temporary password.</li>
                    <li>Change your password immediately after logging in for security.</li>
                </ol>
            </div>
            <?php endif; ?>
            <?php if(!isset($data['tempPassword']) || !$data['tempPassword']): ?>
                <p>Please click the button below to join the project:</p>
                <p><a href="<?php echo $data['BASE_URL']."?page=dashboard&switchproject=".$data['projectId']."&title=".$data['projectname']."" ?? ''; ?>" class="button">Join Project</a></p>
            <?php endif; ?>
            <p>For security reasons, please change your password after your first login.</p>
            <p>If you didn’t request this account, please ignore this email.</p>
        </div>

        <div class="footer">
            <div class="footer-info">
                &copy; <?php echo date('Y'); ?> BossGPT | Stay ahead, stay productive!
            </div>
        </div>
    </div>
</body>

</html>