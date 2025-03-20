<?php

// Start the session
session_start();
// Include required files
require_once 'config.php';
require_once 'env.php';
loadEnv();
// Make sure Database class is available (index.php contains the Database class)
if (!class_exists('Database')) {
    require_once __DIR__ . '/index.php';
}

// Include the GoogleAuth class
require_once 'classes/GoogleAuth.php';

// Enable error reporting for debugging
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
session_start();
try {
    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);
        $_SESSION['access_token'] = $token;
        // Get user info from Google
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // Initialize our Google Auth handler
        $googleAuth = new GoogleAuth();
  
        // Register or login user
        $result = $googleAuth->registerWithGoogle($email, $name);
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = $name;
        $_SESSION['result11']=$result;
        $_SESSION['result'] = $result;
        // Set a welcome or return message based on whether this is a new user
        if ($result['is_new_user'] && $result['is_pro_member'] == 0) {
            $_SESSION['welcome_message'] = "Welcome to BossGPT! Your account has been created.";
            header("Location: ".$_ENV['STRIPE_PAYMENT_LINK']);
        } 
           
            if($result['is_pro_member'] != 1){
                $_SESSION['welcome_message'] = "Welcome back!";
                header("Location: ".$_ENV['STRIPE_PAYMENT_LINK']);
            }
        
    
        // Redirect to dashboard
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        // No code received, redirect to login
        $_SESSION['error_message'] = "Google authentication failed. No authorization code received.";
        header('Location: index.php?page=login&error=no_code');
        exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Google Auth Error: " . $e->getMessage());
    
    // Handle errors
    $_SESSION['error_message'] = "Authentication error: " . $e->getMessage();
    header('Location: index.php?page=login&error=google_auth');
    exit;
}