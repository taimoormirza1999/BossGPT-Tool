<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
// // Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
define('TESTING_FEATURE', $_ENV['TESTING_FEATURE']);
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', 'project_manager');
// OpenAI API configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL']);


$images=[
    'default-user-image'=>'https://upload.wikimedia.org/wikipedia/commons/a/ac/Default_pfp.jpg'
];


function isPage($pageName) {
    return strpos($_SERVER['REQUEST_URI'], $pageName) !== false;
}

function isAitonePage() {
    return strpos($_SERVER['REQUEST_URI'], 'aitone') !== false;
}

function isLoginUserPage() {
    $loginPages = ['profile', 'dashboard', 'aitone', 'garden'];

    // Get current URL path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Get page parameter if exists
    $page = isset($_GET['page']) ? $_GET['page'] : null;
    // Case 1: Root domain like / or /index.php
    if ($path === '/' || $path === '/index.php') {
        return true;
    }
    // Case 2: Specific ?page=profile/dashboard/aitone/garden
    if ($page && in_array($page, $loginPages)) {
        return true;
    }

    return false;
}

?>

