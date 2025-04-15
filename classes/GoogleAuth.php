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

    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
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
                $_SESSION['user_id'] = $existingUser['id'];
                $_SESSION['username'] = $existingUser['username'];
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
                    "INSERT INTO users (username, email, password_hash, fcm_token, pro_plan) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                
                // Generate a random password for the account (user won't need it with Google login)
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
                
                $stmt->execute([$username, $email, $hashedPassword, $_SESSION['fcm_token'], 0]);
                $userId = $this->db->lastInsertId();
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                
                // Send welcome email (if you have this functionality)
                // if (function_exists('sendWelcomeEmail')) {
                //     $user = new UserManager();
                //     $user->sendWelcomeEmail($email, $username, $_ENV['BASE_URL'] ?? 'https://bossgpt.com');
                // }
                
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
} 