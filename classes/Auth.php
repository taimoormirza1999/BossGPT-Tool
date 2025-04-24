<?php
class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function register($username, $email, $password, $fcm_token)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }

            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username or email already exists");
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, fcm_token,pro_plan) VALUES (?, ?, ?, ?,?)");
            $stmt->execute([$username, $email, $password_hash, $fcm_token, 0]);

            return true;
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            throw $e;
        }
    }

    public function login($email, $password)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, pro_plan as pro_member, invited_by, fcm_token, telegram_chat_id, discord_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new Exception("Invalid credentials");
            }
            // Update the last_login timestamp
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['pro_member'] = $user['pro_member'];
            $_SESSION['telegram_token'] = $user['telegram_chat_id'];
            $_SESSION['discord_token'] = $user['discord_id'];

            // Update FCM token if available in the session
            if (isset($_SESSION['fcm_token']) && $_SESSION['fcm_token'] !== '0') {
                $this->updateFcmToken($user['id'], $_SESSION['fcm_token']);
                // Clear the session token after updating
                unset($_SESSION['fcm_token']);
            }

            if ($user['pro_member'] != 1) {
                if ($user['invited_by'] === null) {
                    if (isset($_COOKIE['rewardful_referral'])) {
                        header("Location: " . $_ENV['STRIPE_PAYMENT_LINK_REFREAL']);
                    } else {
                        header("Location: " . $_ENV['STRIPE_PAYMENT_LINK']);
                    }
                    exit;
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }

    public function logout()
    {
    //     echo "<script>
    //         localStorage.removeItem('lastSelectedProject');
    // </script>";
        session_destroy();
        session_start();
        session_unset();
        session_destroy();
        header("Location: " . $_ENV['BASE_URL']);
        exit;
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, username, email, pro_plan as pro_member, invited_by FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }
    public function updateProStatus($userId)
    {
        try {
            // First check if user is already a pro member
            $stmt = $this->db->prepare("SELECT pro_plan FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && $user['pro_plan'] == 1) {
                // User is already a pro member
                return true;
            }
            
            // Update to pro if not already
            $stmt = $this->db->prepare("UPDATE users SET pro_plan = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (Exception $e) {
            error_log("Update pro status error: " . $e->getMessage());
            throw new Exception("Failed to update pro status: " . $e->getMessage());
        }
    }

    public function updateFcmToken($userId, $fcmToken) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
            $stmt->execute([$fcmToken, $userId]);
            return true;
        } catch (Exception $e) {
            error_log("FCM Token update error: " . $e->getMessage());
            throw $e;
        }
    }
}
