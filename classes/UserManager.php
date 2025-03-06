<?php
require_once __DIR__ . '/../functions.php';  // Add this at the top to include functions.php


class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // public function createUser($username, $email, $projectId = null, $role = null) {
    //     try {
    //         $this->db->beginTransaction();

    //         // Check if user exists
    //         $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
    //         $stmt->execute([$email]);
    //         if ($stmt->fetch()) {
    //             throw new Exception("User with this email already exists");
    //         }

    //         // Generate temporary password and verification token
    //         $tempPassword = $this->generateTempPassword();
    //         $verificationToken = bin2hex(random_bytes(32));
            
    //         // Create user
    //         $stmt = $this->db->prepare(
    //             "INSERT INTO users (username, email, password, verification_token, status) 
    //              VALUES (?, ?, ?, ?, 'pending')"
    //         );
    //         $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    //         $stmt->execute([$username, $email, $hashedPassword, $verificationToken]);
            
    //         $userId = $this->db->lastInsertId();

    //         // If projectId is provided, add user to project
    //         if ($projectId && $role) {
    //             $invitationToken = bin2hex(random_bytes(32));
    //             $stmt = $this->db->prepare(
    //                 "INSERT INTO project_users (project_id, user_id, role, status, invitation_token) 
    //                  VALUES (?, ?, ?, 'pending', ?)"
    //             );
    //             $stmt->execute([$projectId, $userId, $role, $invitationToken]);
    //         }

    //         $this->db->commit();

    //         // Send welcome email with credentials
    //         $this->sendWelcomeEmail($email, $username, $tempPassword, $verificationToken);

    //         return [
    //             'success' => true,
    //             'user_id' => $userId,
    //             'message' => 'User created successfully. Welcome email sent.'
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->rollBack();
    //         throw $e;
    //     }
    // }

    public function createUser($username, $email, $projectId = null, $role = null) {
        try {
            $this->db->beginTransaction();
    
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("User with this email already exists");
            }
    
            // Generate temporary password and verification token
            $tempPassword = $this->generateTempPassword();
            $verificationToken = bin2hex(random_bytes(32));
            
            // Create user - Changed 'password' to 'password_hash' to match the database schema
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash) 
                 VALUES (?, ?, ?)"
            );
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            $stmt->execute([$username, $email, $hashedPassword]);
            
            $userId = $this->db->lastInsertId();
    
            // If projectId and role are provided, add user to project
            if ($projectId && $role) {
                $stmt = $this->db->prepare(
                    "INSERT INTO project_users (project_id, user_id, role) 
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([$projectId, $userId, $role]);
            }
    
            $this->db->commit();
    
            // Send welcome email with credentials
            $this->sendWelcomeEmail($email, $username, $tempPassword);
    
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully. Welcome email sent.'
            ];
    
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    private function generateTempPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function sendWelcomeEmail($email, $username, $tempPassword, ) {
        $verificationLink = "https://yourwebsite.com/verify.php?token=" . "dfgd";
        
        $subject = "Welcome to BossGPT - Your Account Details";
        $template = 'welcome_email';
        
        // Prepare data for email template
        $emailData = [
            'username' => $username,
            'tempPassword' => $tempPassword,
            'verificationLink' => $verificationLink
        ];
        
        return sendTemplateEmail($email, $subject, $template, $emailData);
    }
}
?>