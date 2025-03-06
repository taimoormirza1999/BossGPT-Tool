<!-- email_templates/reminder.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Reminder</title>
    <style>
    
    body { 
        font-family: Arial, sans-serif; 
        background-color: #000; 
        color: #fff; 
        margin: 0; 
        padding: 20px;
        font-family: 'Segoe UI','Georgia', 'Times New Roman', serif,'Helvetica Neue'; 
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
        background: #333; 
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
            padding: 20px;
        }
        .logo {
            width: 200px;
        }
        .footer-info {
            font-size: 11px;
        }
    }
    
         </style>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <img src="https://bossgpt.com/boss-gpt.png" alt="BossGPT" class="logo">
        
        <!-- Header -->
        <div class="header">
            Important Work Reminder
        </div>

        <!-- Content Section -->
        <div class="content">
            <p>Dear <strong><?php echo $userName; ?></strong>,</p>
            <p>We hope you're doing well! Here's your work summary and some reminders to stay on track:</p>

            <!-- Encouragement Block -->
            <div class="block">
                <p><strong>Motivational Message:</strong></p>
                <blockquote class="blockquote"><?php echo $encouragement; ?></blockquote>
            </div>

            <!-- Task Summary Section -->
            <p><strong>Task Summary:</strong> <?php echo $taskSummary; ?></p>
            
            <!-- Deadline Reminder -->
            <p><strong>Deadline Notice:</strong> <?php echo $deadlineNote; ?></p>

            <!-- Important Notes -->
            <div class="important-note">
                Next Steps:
            </div>
            <p><strong>Ensure you focus on:</strong> Making sure that all critical tasks are completed before the deadlines to avoid any last-minute stress.</p>

            <!-- Closing Remarks -->
            <p>We understand that tasks can pile up quickly, but staying organized and focused will help you meet your objectives. Let's keep pushing forward and complete all the goals!</p>
            <p>If you need assistance or have any questions, feel free to reach out.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-info">
                &copy; <?php echo date('Y'); ?> BossGPT | Stay productive, and keep achieving!
            </div>
        </div>
    </div>
</body>
</html>
