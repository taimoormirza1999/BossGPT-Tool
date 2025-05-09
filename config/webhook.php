<?php
// webhook.php?source=telegram
// webhook.php?source=discord
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/constants.php';

$body   = file_get_contents('php://input');
$data   = json_decode($body, true);
$source = $_GET['source'] ?? 'telegram'; // default to telegram

switch ($source) {
    case 'telegram':
        handleTelegram($data);
        break;
    case 'discord':
        handleDiscord($data);
        break;
}

function handleDiscord($data) {
    // Just log the event for now or test message back
    file_put_contents(__DIR__ . '/discord_log.txt', json_encode($data, JSON_PRETTY_PRINT));
}

function handleTelegram($data) {
    // Guard: ignore non-message updates
    if (!isset($data['message']['chat']['id'], $data['message']['text'])) {
        return;
    }

    $chat_id = $data['message']['chat']['id'];
    $text    = $data['message']['text'];

    if (preg_match('/\/start connect(_(\d+))?/', $text, $matches)) {
        sendTelegramMessage(
            $chat_id,
            "To ensure you never miss a deadline, please provide your registered email. We'll link your Telegram to your BossGPT account for timely updates."
        );

    } elseif (filter_var($text, FILTER_VALIDATE_EMAIL)) {
        $db = Database::getInstance()->getConnection();

        // Check if user with this email exists
        $userCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
        $userCheck->execute([$text]);

        if ($userCheck->rowCount() > 0) {
            $stmt = $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE email = ?");
            $stmt->execute([$chat_id, $text]);

            // Build an inline-button
            $button = [
                [
                    [
                        'text' => 'Go to BossGPT',
                        'url'  => rtrim($_ENV['BASE_URL'], '/') . '/tool'
                    ]
                ]
            ];

            sendTelegramMessage(
                $chat_id,
                "✅ Your Telegram is now connected for reminders! You can now receive timely updates and reminders directly to your Telegram.",
                $button
            );

            $_SESSION['telegram_token'] = $chat_id;
        } else {
            sendTelegramMessage(
                $chat_id,
                "❌ Email not found. Please make sure you're using your BossGPT registered email."
            );
        }
    }
}

/**
 * Sends a Telegram message, optionally with inline keyboard buttons.
 *
 * @param int           $chat_id
 * @param string        $text
 * @param array|null    $buttons  Inline keyboard rows (see Telegram Bot API)
 */
function sendTelegramMessage($chat_id, $text, array $buttons = null) {
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];
    $url   = "https://api.telegram.org/bot{$token}/sendMessage";

    $payload = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];

    if ($buttons !== null) {
        $payload['reply_markup'] = json_encode([
            'inline_keyboard' => $buttons
        ]);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
