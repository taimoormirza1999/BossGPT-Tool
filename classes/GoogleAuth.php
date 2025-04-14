<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../env.php';
loadEnv();
// Make sure the Database class is available
if (!class_exists('Database')) {
    require_once __DIR__ . '/../index.php';
}

class GoogleAuth 
{
    private $db;
    private $auth;

    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = new Auth();
    }

    /**
     * Register a new user with Google or log in existing user
     * 
     * @param string $email The user's email from Google
     * @param string $name The user's name from Google
     * @return array Response with user info and status
     */
    public function registerWithGoogle($email, $name) 
    {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id, username, pro_plan FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // User exists, just log them in
                $fcmToken = isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : null;
                $this->auth->updateUserSession($existingUser['id'], $existingUser['username'], $existingUser['pro_plan'], $fcmToken);
                
                // Clear FCM token from session after using it
                if (isset($_SESSION['fcm_token'])) {
                    unset($_SESSION['fcm_token']);
                }
                
                return [
                    'success' => true,
                    'user_id' => $existingUser['id'],
                    'message' => 'User logged in successfully',
                    'is_new_user' => false,
                    'is_pro_member' => $existingUser['pro_plan']
                ];
            } else {
                // Create new user with unique username
                $username = $this->generateUniqueUsername($email, $name);
                
                // Insert the new user
                $stmt = $this->db->prepare(
                    "INSERT INTO users (username, email, password_hash, google_id, created_at) 
                     VALUES (?, ?, ?, ?, NOW())"
                );
                
                // Generate a random password for the account (user won't need it with Google login)
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
                
                // Get Google ID if available
                $googleId = isset($google_account_info->id) ? $google_account_info->id : null;
                
                $stmt->execute([$username, $email, $hashedPassword, $googleId]);
                $userId = $this->db->lastInsertId();
                
                // Set up session via the common method
                $fcmToken = isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : null;
                $this->auth->updateUserSession($userId, $username, 0, $fcmToken);
                
                // Clear FCM token from session after using it
                if (isset($_SESSION['fcm_token'])) {
                    unset($_SESSION['fcm_token']);
                }
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'New user created successfully',
                    'is_new_user' => true,
                    'is_pro_member' => 0
                ];
            }
        } catch (Exception $e) {
            error_log("Google Registration Error: " . $e->getMessage());
            throw new Exception("Failed to register with Google: " . $e->getMessage());
        }
    }
    
    /**
     * Generate a unique username based on email and name
     * 
     * @param string $email User's email address
     * @param string $name User's name (optional)
     * @return string Unique username
     */
    private function generateUniqueUsername($email, $name = '') 
    {
        // First try with email (part before @)
        $emailParts = explode('@', $email);
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $emailParts[0]);
        
        // If we have a name and email-based username is short, use name
        if (strlen($baseUsername) < 3 && !empty($name)) {
            $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
            $baseUsername = str_replace(' ', '', $baseUsername);
        }
        
        // Ensure username is not too short
        if (strlen($baseUsername) < 3) {
            $baseUsername = 'user' . $baseUsername;
        }
        
        // Check if username exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$baseUsername]);
        
        // If username is unique, return it
        if ($stmt->rowCount() == 0) {
            return $baseUsername;
        }
        
        // Otherwise add random digits until we get a unique username
        $counter = 1;
        $maxAttempts = 10;
        
        while ($counter <= $maxAttempts) {
            // Add 6 random digits
            $uniqueUsername = $baseUsername . rand(100000, 999999);
            
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$uniqueUsername]);
            
            if ($stmt->rowCount() == 0) {
                return $uniqueUsername;
            }
            
            $counter++;
        }
        
        // If all attempts fail, use timestamp as last resort
        return $baseUsername . time();
    }

    public function handleGoogleAuth($googleUser)
    {
        try {
            $email = $googleUser['email'];
            $name = $googleUser['name'];
            $googleId = $googleUser['sub'];

            // Check if user exists
            $stmt = $this->db->prepare("SELECT id, username, pro_plan as pro_member FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // User exists, log them in
                $fcmToken = isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : null;
                $this->auth->updateUserSession($user['id'], $user['username'], $user['pro_member'], $fcmToken);
                
                // Clear the session token after updating
                if (isset($_SESSION['fcm_token'])) {
                    unset($_SESSION['fcm_token']);
                }
                
                return true;
            } else {
                // Register new user
                $username = $this->generateUsername($name);
                $password = bin2hex(random_bytes(8)); // Generate random password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, google_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $passwordHash, $googleId]);
                
                $userId = $this->db->lastInsertId();
                
                // Use the new method to update session and FCM token
                $fcmToken = isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : null;
                $this->auth->updateUserSession($userId, $username, false, $fcmToken);
                
                // Clear the session token after updating
                if (isset($_SESSION['fcm_token'])) {
                    unset($_SESSION['fcm_token']);
                }
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Google auth error: " . $e->getMessage());
            throw $e;
        }
    }
} 