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
    /* box-shadow: 0 4px 15px rgba(1, 1, 1, 1); */
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
        padding: 20px 0;
    }
    .logo {
        width: 200px;
        margin-bottom: 10px;
    }
    .footer-info {
        font-size: 11px;
    }
    .blockquote{
        margin: 5px 0;
    }
}

     </style>
</head>
<body>
    <div class='container'>
        <img src='https://bossgpt.com/boss-gpt.png' alt='BossGPT' class='logo' />
        <div class='header'>DAILY PERFORMANCE REVIEW</div>
        <div class='content'>
            <p><strong><?php echo $userName; ?></strong>,</p>
            <p>Here's your performance assessment for today:</p>
            <div class='block'>
                <p><strong>TASK EXECUTION REPORT:</strong></p>
                <blockquote class='blockquote'><?php echo $taskSummary; ?></blockquote>
            </div>
            <p>IMMEDIATE ACTION ITEMS FOR TOMORROW:</p>
            <div class='block'>
                <p><strong>PRIORITY OBJECTIVES:</strong></p>
                <blockquote class='blockquote'><?php echo $upcomingTasks; ?></blockquote>
            </div>
            <p><strong>PERFORMANCE DIRECTIVE:</strong> <?php echo $motivation; ?></p>
            <p>I expect these objectives to be met with excellence. There will be a thorough review of your progress tomorrow. Maintain peak performance.</p>
        </div>
        <div class="footer">
            <div class="footer-info">
                &copy; <?php echo date('Y'); ?> BossGPT | Excellence is not optional.
            </div>
        </div>
    </div>
</body>
</html>


