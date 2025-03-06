<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .task-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .project-name {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Task Assignment</h2>
        <p>Hello <?php echo $data['username']; ?>,</p>
        
        <p>You have been assigned a new task in project <span class="project-name"><?php echo $data['projectTitle']; ?></span></p>
        
        <div class="task-box">
            <h3><?php echo $data['taskTitle']; ?></h3>
        </div>

        <p>Please log in to view the complete task details and start working.</p>

        <p>Best regards,<br>The BossGPT Team</p>
    </div>
</body>
</html>
