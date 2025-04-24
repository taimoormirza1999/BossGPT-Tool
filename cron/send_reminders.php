<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
$action = $_GET['type'] ?? 'all'; // ?type=fcm or ?type=telegram or ?type=all
$pdo = Database::getInstance()->getConnection();

if ($action === 'fcm' || $action === 'all') {
    sendFcmReminders($pdo);
}

if ($action === 'telegram' || $action === 'all') {
    sendTelegramReminders($pdo);
}

if ($action === 'discord' || $action === 'all') {
    sendDiscordReminders($pdo);
}
function sendFcmReminders($pdo) {
    echo "\n--- Sending FCM Reminders ---<br>";
    $factory = (new Factory)->withServiceAccount(__DIR__ .'/../'. $_ENV['FIREBASE_CREDENTIALS']);
    $messaging = $factory->createMessaging();

    $userStmt = $pdo->query("SELECT id, username, fcm_token FROM users WHERE fcm_token IS NOT NULL");
    $users = $userStmt->fetchAll();

    foreach ($users as $user) {
        $userId = $user['id'];
        $fcmToken = $user['fcm_token'];

        $taskStmt = $pdo->prepare("SELECT t.title, t.due_date FROM tasks t INNER JOIN task_assignees ta ON ta.task_id = t.id WHERE ta.user_id = :uid AND t.due_date <= CURDATE() AND t.status != 'done'");
        $taskStmt->execute(['uid' => $userId]);
        $tasks = $taskStmt->fetchAll();

        if ($tasks) {
            $taskTitles = array_column($tasks, 'title');
            $messageBody = "You have pending tasks: " . implode(", ", $taskTitles);
        } else {
            $messageBody = "You have no tasks! \ud83d\ude80 Start leveraging BossGPT to plan and assign your tasks!";
        }

        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification(Notification::create('⏰ Task Reminder', $messageBody))
            ->withData(['type' => 'reminder', 'user_id' => (string)$userId]);

        try {
            $messaging->send($message);
            echo "\u2705 Reminder sent to user: {$user['username']}<br>";
            $storeStmt = $pdo->prepare("INSERT INTO fcm_reminders_temp (fcm_token, title, description) VALUES (?, ?, ?)");
            $storeStmt->execute([$fcmToken, '\u23f0 Task Reminder', $messageBody]);
        } catch (MessagingException | FirebaseException $e) {
            echo "\u274c Failed to send to {$user['username']}: " . $e->getMessage() . "<br>";
        }
    }
}
// // ✅ Initialize Firebase Messaging
// $factory = (new Factory)->withServiceAccount(__DIR__ . $_ENV['FIREBASE_CREDENTIALS']);
// $messaging = $factory->createMessaging();

// try {
//     $pdo = Database::getInstance()->getConnection();
    
//     // Get users with FCM token
//     $userStmt = $pdo->query("SELECT id, username, fcm_token FROM users WHERE fcm_token IS NOT NULL");
//     $users = $userStmt->fetchAll();
//     // exit;

//     foreach ($users as $user) {
//         $userId = $user['id'];
//         $fcmToken = $user['fcm_token'];

//         $taskStmt = $pdo->prepare("
//             SELECT t.title, t.due_date 
//             FROM tasks t
//             INNER JOIN task_assignees ta ON ta.task_id = t.id
//             WHERE ta.user_id = :uid AND t.due_date <= CURDATE() AND t.status != 'done'
//         ");
//         $taskStmt->execute(['uid' => $userId]);
//         $tasks = $taskStmt->fetchAll();

//         if ($tasks) {
//             $taskTitles = array_column($tasks, 'title');
//             $messageBody = "You have pending tasks: " . implode(", ", $taskTitles);

            
//         } else {
//             // No tasks → send motivational push
//         $messageBody = "You have no tasks! 🚀 Start leveraging NG BossGPT to plan and assign your tasks!";
//         $notificationTitle = '📅 Plan Your Day';
//             echo "ℹ️ No due tasks for {$user['username']}<br>";
//         }
//         $message = CloudMessage::withTarget('token', $fcmToken)
//                 ->withNotification(Notification::create(
//                     '⏰ Task Reminder',
//                     $messageBody
//                 ))
//                 ->withData([
//                     'type' => 'reminder',
//                     'user_id' => (string)$userId
//                 ]);

//             try {
//                 $messaging->send($message);
//                 echo "✅ Reminder sent to user: {$user['username']}<br>";
//                 // ✅ Store in temp reminder table
//                 $storeStmt = $pdo->prepare("
//                 INSERT INTO fcm_reminders_temp (fcm_token, title, description)
//                 VALUES (?, ?, ?)
//             ");
            
//             $storeStmt->execute([$fcmToken, '⏰ Task Reminder', $messageBody]);
//             } catch (MessagingException | FirebaseException $e) {
//                 echo "❌ Failed to send to {$user['username']}: " . $e->getMessage() . "<br>";
//             }
//     }

// } catch (Exception $e) {
//     echo "❌ Error: " . $e->getMessage();
// }

function sendTelegramReminders($pdo) {
    echo "\n--- Sending Telegram Reminders ---<br>";
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];

    $stmt = $pdo->query("SELECT t.title, t.due_date, u.telegram_chat_id 
                         FROM tasks t 
                         JOIN task_assignees ta ON ta.task_id = t.id 
                         JOIN users u ON u.id = ta.user_id 
                         WHERE t.status != 'done' 
                         AND u.telegram_chat_id IS NOT NULL 
                         AND t.due_date IS NOT NULL");
    $tasks = $stmt->fetchAll();

    // Group tasks by user chat ID
    $grouped = [];
    foreach ($tasks as $task) {
        $chatId = $task['telegram_chat_id'];
        $grouped[$chatId][] = $task['title'];
    }

    foreach ($grouped as $chatId => $taskTitles) {
        $taskList = '';
        foreach ($taskTitles as $title) {
            $taskList .= "🔸 " . ucwords($title) . " - (" . $task['due_date'] . ")\n";
        }

        $text = "🚀 A gentle reminder to stay on track...\n\nPlease complete the following tasks before the due date:\n\n"
              . $taskList
              . "\n✅ Stay focused and finish strong — you've got this!\n\n"
              . "— BossGPT, your productivity partner 🤖";

        file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($text));
        echo "✅ Telegram reminder sent to chat ID: $chatId<br>";
    }
}

function sendDiscordReminders($pdo) {
    echo "\n--- Sending Discord Reminders ---<br>";

    // Query users with Discord IDs and pending tasks
    $stmt = $pdo->query("SELECT t.title, t.due_date, u.discord_id 
                         FROM tasks t 
                         JOIN task_assignees ta ON ta.task_id = t.id 
                         JOIN users u ON u.id = ta.user_id 
                         WHERE t.status != 'done' 
                         AND u.discord_id IS NOT NULL 
                         AND t.due_date IS NOT NULL");

    $tasks = $stmt->fetchAll();

    // Group tasks by Discord ID
    $grouped = [];
    foreach ($tasks as $task) {
        $discordId = $task['discord_id'];
        $grouped[$discordId][] = [
            'title' => $task['title'],
            'due_date' => $task['due_date']
        ];
    }

    foreach ($grouped as $discordId => $tasks) {
        $taskList = '';
        foreach ($tasks as $task) {
            $taskList .= "🔸 " . ucwords($task['title']) . " - (" . $task['due_date'] . ")\n";
        }

        $message = "**🚀 Gentle Reminder from BossGPT**\n\n"
                 . "You have the following tasks pending:\n\n"
                 . $taskList
                 . "\n✅ Stay focused and finish strong — you've got this!\n\n"
                 . "**— BossGPT 🤖**";

        // Send to Discord via DM (requires bot user token and user DM channel)
        sendDiscordDM($discordId, $message);
        echo "✅ Discord reminder sent to Discord ID: $discordId<br>";
    }
}

function sendDiscordDM($discordId, $message) {
    $botToken = $_ENV['DISCORD_BOT_TOKEN'];

    // Step 1: Create a DM channel
    $ch = curl_init("https://discord.com/api/v10/users/@me/channels");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot $botToken",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode(['recipient_id' => $discordId])
    ]);

    $response = curl_exec($ch);
    $channel = json_decode($response, true);
    curl_close($ch);

    if (!isset($channel['id'])) {
        echo "❌ Failed to create DM channel for $discordId<br>";
        return;
    }

    // Step 2: Send the message
    $ch = curl_init("https://discord.com/api/v10/channels/{$channel['id']}/messages");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot $botToken",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode(['content' => $message])
    ]);

    $result = curl_exec($ch);
    curl_close($ch);
}
