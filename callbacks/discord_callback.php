<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/constants.php'; // for DISCORD_CLIENT_ID & SECRET

error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_id() === '') {
    session_start();
  }

// 1. Get authorization code
if (!isset($_GET['code'])) {
    error_log('Discord OAuth callback missing code');
    header("Location: " . $_ENV['BASE_URL'] . "/");
    exit();
}

$code = $_GET['code'];

// 2. Exchange code for access_token
$token_url = 'https://discord.com/api/oauth2/token';
$data = [
    'client_id' => $_ENV['DISCORD_CLIENT_ID'],
    'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'],
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'],
  'scope' => 'identify bot'
];

$options = [
    CURLOPT_URL => $token_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
];

$ch = curl_init();
curl_setopt_array($ch, $options);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    exit('Error: Could not get access token.');
}

$access_token = $token_data['access_token'];

// 3. Fetch Discord user info
$user_info_url = 'https://discord.com/api/users/@me';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $user_info_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $access_token
    ]
]);

$user_response = curl_exec($ch);
curl_close($ch);

$discord_user = json_decode($user_response, true);

if (!isset($discord_user['id'])) {
    var_dump($access_token);
var_dump($http_response_header);

    exit('Error: Could not fetch user data.');
}

$discord_id = $discord_user['id'];

// 4. Save to database
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET discord_id = ? WHERE id = ?");
    $stmt->execute([$discord_id, $_SESSION['user_id']]);
    $_SESSION['discord_token'] = $discord_id;
    echo "✅ Your Discord is now connected successfully!";
    header("Location: " . 'https://discord.gg/zCjmGfF7');
} catch (PDOException $e) {
    header("Location: " . $_ENV['BASE_URL'] . "/");
    exit("❌ DB Error: " . $e->getMessage());
}
