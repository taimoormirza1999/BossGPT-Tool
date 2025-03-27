<?php
session_start(); // Start the session at the beginning
header('Content-Type: application/json'); // Set JSON response type

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Check if requestType is received
if (isset($data['requestType']) && $data['requestType'] === 'storeFCMSession') {
    // Check if token is received
    if (isset($data['fcm_token'])) {
        $_SESSION['fcm_token'] = $data['fcm_token'];  // Store token in session
        echo json_encode(["success" => true, "message" => "FCM token stored in session"]);
        exit;
    } else {
        echo json_encode(["success" => false, "message" => "No FCM token received"]);
        exit;
    }
}

// If requestType is missing
echo json_encode(["success" => false, "message" => "No requestType received"]);
exit;
?>
