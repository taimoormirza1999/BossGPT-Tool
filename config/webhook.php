<?php
// webhook.php?source=telegram
// webhook.php?source=discord

require_once __DIR__ . '/../classes/Database.php';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

$source = $_GET['source'] ?? 'telegram'; // default to telegram

switch ($source) {
    case 'telegram':
        handleTelegram($data);
        break;
    case 'discord':
        // handleDiscord($data);
        break;
}
function handleTelegram($data) {
    $chat_id = $data['message']['chat']['id'];
    $text = $data['message']['text'];

    if (preg_match('/\/start connect(_(\d+))?/', $text, $matches)) {
    sendTelegramMessage($chat_id, "Please reply with your registered email to connect your Telegram. with BossGPT to connect with reminders");
} elseif (filter_var($text, FILTER_VALIDATE_EMAIL)) {
    $db = Database::getInstance()->getConnection();

    // Check if user with this email exists
    $userCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userCheck->execute([$text]);

    if ($userCheck->rowCount() > 0) {
        $stmt = $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE email = ?");
        $stmt->execute([$chat_id, $text]);

        sendTelegramMessage($chat_id, "✅ Your Telegram is now connected for reminders!");
    } else {
        sendTelegramMessage($chat_id, "❌ Email not found. Please make sure you're using your BossGPT registered email.");
    }
}
}

function sendTelegramMessage($chat_id, $text) {
    $token = '7646465598:AAHLURvH87qnQEcpmhw5HwoAS8HaA8Tm4Sg';
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($text));
}
