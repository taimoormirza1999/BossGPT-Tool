<?php
$taskSummary = $taskSummary ?? "No tasks were completed today. We recommend setting achievable goals for tomorrow.";
$motivation = $motivation ?? "Stay focused, and remember that progress is progress, no matter how small.";
$upcomingTasks = $upcomingTasks ?? "Prepare for tomorrow's meeting, and finalize the report due next week.";
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Daily Work Summary</title>
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
    <div class='container'>
        <img src='https://bossgpt.com/boss-gpt.png' alt='BossGPT' class='logo' />
        <div class='header'>Your Daily Work Summary</div>
        <div class='content'>
            <p>Dear <strong><?php echo $userName; ?></strong>,</p>
            <p>Here's a brief overview of your progress today:</p>
            <div class='block'>
                <p><strong>Tasks Completed Today:</strong></p>
                <blockquote class='blockquote'><?php echo $taskSummary; ?></blockquote>
            </div>
            <p>Additionally, here are some things to keep in mind for tomorrow:</p>
            <div class='block'>
                <p><strong>Upcoming Tasks:</strong></p>
                <blockquote class='blockquote'><?php echo $upcomingTasks; ?></blockquote>
            </div>
            <p><strong>Motivation for Tomorrow:</strong> <?php echo $motivation; ?></p>
            <p>Remember, consistency is key to reaching your goals. You’ve made significant progress today, and tomorrow is another opportunity to advance further. Keep it up!</p>
        </div>
        <div class="footer">
            <div class="footer-info">
                &copy; <?php echo date('Y'); ?> BossGPT | Stay ahead, stay productive!
            </div>
        </div>
    </div>
</body>
</html>


