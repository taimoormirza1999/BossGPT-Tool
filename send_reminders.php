<?php
require_once 'env.php';
loadEnv();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'classes/Database.php';
require_once 'config/constants.php';
require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;



// ✅ Initialize Firebase Messaging
$factory = (new Factory)->withServiceAccount(__DIR__ . $_ENV['FIREBASE_CREDENTIALS']);


$messaging = $factory->createMessaging();

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get users with FCM token
    $userStmt = $pdo->query("SELECT id, username, fcm_token FROM users WHERE fcm_token IS NOT NULL");
    $users = $userStmt->fetchAll();
    // exit;

    foreach ($users as $user) {
        $userId = $user['id'];
        $fcmToken = $user['fcm_token'];

        $taskStmt = $pdo->prepare("
            SELECT t.title, t.due_date 
            FROM tasks t
            INNER JOIN task_assignees ta ON ta.task_id = t.id
            WHERE ta.user_id = :uid AND t.due_date <= CURDATE() AND t.status != 'done'
        ");
        $taskStmt->execute(['uid' => $userId]);
        $tasks = $taskStmt->fetchAll();

        if ($tasks) {
            $taskTitles = array_column($tasks, 'title');
            $messageBody = "You have pending tasks: " . implode(", ", $taskTitles);

            
        } else {
            // No tasks → send motivational push
        $messageBody = "You have no tasks! 🚀 Start leveraging NG BossGPT to plan and assign your tasks!";
        $notificationTitle = '📅 Plan Your Day';
            echo "ℹ️ No due tasks for {$user['username']}<br>";
        }
        $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create(
                    '⏰ Task Reminder',
                    $messageBody
                ))
                ->withData([
                    'type' => 'reminder',
                    'user_id' => (string)$userId
                ]);

            try {
                $messaging->send($message);
                echo "✅ Reminder sent to user: {$user['username']}<br>";
                // ✅ Store in temp reminder table
                $storeStmt = $pdo->prepare("
                INSERT INTO fcm_reminders_temp (fcm_token, title, description)
                VALUES (?, ?, ?)
            ");
            
            $storeStmt->execute([$fcmToken, '⏰ Task Reminder', $messageBody]);
            } catch (MessagingException | FirebaseException $e) {
                echo "❌ Failed to send to {$user['username']}: " . $e->getMessage() . "<br>";
            }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
