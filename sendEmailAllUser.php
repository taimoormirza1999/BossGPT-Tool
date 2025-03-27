<?php
require_once './functions.php';
require_once './config/database.php';

function sendWelcomeEmail($email, $username, $BASE_URL)
{
    $subject = "BossGPT Welcomes You! Your AI-Powered Journey Starts Now!";
    $template = 'task_assigned_update';

    // Prepare data for email template
    $emailData = [

        'email' => $email,
        'subject' => $subject,
        'template' => $template,
        'data' => [
            'welcomeLink' => $BASE_URL,
            'username' => $username,
        ]
    ];
    return sendTemplateEmail($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data']);


}

// sendWelcomeEmail('taimoorhamza1999@gmail.com', 'Taimoor', 'https://bossgpt.ai');
function sendEmailToUsers($BASE_URL)
{
    // Connect to the database and fetch users
    global $pdo; // Assuming you're using PDO for database connection
    $stmt = $pdo->query("SELECT email, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through all users and send them an email
    foreach ($users as $user) {
        $email = $user['email'];
        $username = $user['username'];
        echo $email . "<br>";
        // Send welcome email to the user
        sendWelcomeEmail($email, $username, $BASE_URL);
    }
    // echo "Email sent to all users";
}
sendEmailToUsers('https://bossgpt.ai');