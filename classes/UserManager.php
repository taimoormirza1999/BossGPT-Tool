<?php
require_once __DIR__ . '/../functions.php';
// Add this at the top to include functions.php
class UserManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    public function createOrAssignUser($username, $email, $projectId = null, $role = null, $BASE_URL)
    {
        try {
            $this->db->beginTransaction();

            // Check if user exists by email
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // User exists, just assign to project if needed
                $userId = $existingUser['id'];
                $existingUsername = $existingUser['username'];

                // Check if user is already assigned to this project
                if ($projectId && $role) {
                    $stmt = $this->db->prepare("SELECT 1 FROM project_users WHERE project_id = ? AND user_id = ?");
                    $stmt->execute([$projectId, $userId]);

                    if (!$stmt->fetch()) {
                        // User not yet assigned to this project
                        $stmt = $this->db->prepare(
                            "INSERT INTO project_users (project_id, user_id, role) 
                             VALUES (?, ?, ?)"
                        );
                        $stmt->execute([$projectId, $userId, $role]);

                        // Log the activity
                        $stmt = $this->db->prepare("
                            INSERT INTO activity_log (project_id, user_id, action_type, description) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $projectId,
                            $userId,
                            'user_assigned',
                            "User {$existingUsername} has been added to the project as {$role}"
                        ]);
                    }
                }

                $this->db->commit();

                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'Existing user assigned to project successfully.',
                    'is_new_user' => false
                ];
            } else {
                // Create new user
                $tempPassword = $this->generateTempPassword();

                // Create user
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
                    // Log the activity
                    $stmt = $this->db->prepare("
                        INSERT INTO activity_log (project_id, user_id, action_type, description) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $projectId,
                        $userId,
                        'user_assigned',
                        "User {$username} has been added to the project as {$role}"
                    ]);
                }
                $this->db->commit();
                // Send welcome email with credentials
                $this->sendInviteUserEmail($email, $username, $tempPassword, $BASE_URL, "testingtoken");
                // Execute email script in the background
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'New user created and assigned to project successfully. Welcome email sent.',
                    'is_new_user' => true
                ];
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    public function getProjectUsers($project_id)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, pu.role, u.fcm_token
                 FROM users u 
                 JOIN project_users pu ON u.id = pu.user_id 
                 WHERE pu.project_id = ?"
            );
            $stmt->execute([$project_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Project Users Error: " . $e->getMessage());
            throw $e;
        }
    }
    private function generateTempPassword($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    public function sendWelcomeEmail($email, $username, $BASE_URL)
    {
        $subject = "BossGPT Welcomes You! Your AI-Powered Journey Starts Now!";
        $template = 'welcome';

        // Prepare data for email template
        $emailData = [

            'email' => $email,
            'subject' => $subject,
            'template' => $template,
            'data' => [
                'welcomeLink' => $BASE_URL,
                'username' => $username,
            ]
        ];

        $emailDataJson = escapeshellarg(json_encode($emailData)); // Convert array to JSON and escape it
        $command = "php sendEmail.php $emailDataJson > /dev/null 2>&1 &";
        exec($command);
        // return sendTemplateEmail($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data']);


    }
    public function sendInviteUserEmail($email, $username, $tempPassword, $BASE_URL, $token)
    {

        $verificationLink = $BASE_URL . "/verify.php?token=" . $token;

        $subject = "Welcome to BossGPT - Your Account Details";
        $template = 'invite_user';
        // Prepare data for email template
        $emailData = [
            'email' => $email,
            'subject' => $subject,
            'template' => $template,
            'data' => [
                'username' => $username,
                'tempPassword' => $tempPassword,
                'verificationLink' => $verificationLink
            ]
        ];

        // $emailDataJson = escapeshellarg(json_encode($emailData)); // Convert array to JSON and escape it
        $command = "php sendEmail.php $$emailData > /dev/null 2>&1 &";
        exec($command);

        // return "Welcome email is being processed asynchronously.";
        //    return  sendTemplateEmail($emailData['email'], $emailData['subject'], $emailData['template'], $emailData['data']);

    }

    public function projectUsersNewUserAddedEmail($newUserUsername, $projectTilte, $newRole, $projectAllUsers)
    {
     
        $subject = "New User Added to Project " . $projectTilte;
        // $template = "new_user_added_to_project";
        foreach ($projectAllUsers as $user) {
            // $allUsers[] = $user['email'];
            $template = 'user_added_update';
            $emailData = [
                'email' => $user['email'],
                'subject' => $subject,
                'template' => $template,
                'data' => [
                    'newusername' => $newUserUsername,
                    'username' => $user['username'],
                    'role' => $newRole,
                    'date' => date('Y-m-d'),  
                    'time' => date('H:i:s')
                ]
            ];
            // sendTemplateEmail($user['email'], $subject, $template, $emailData);
            $command = "php sendEmail.php '$emailData' > /dev/null 2>&1 &";
            exec($command);
        }
        // return $newUser . " has been added to the project: " . $projectTilte . " at new Role: " . $newRole . " project All Users: " . implode(", ", $allUsers);
    }
    public function newTaskAddedEmailNotifer($taskCreator, $projectTilte, $taksTitle, $projectAllUsers)
    {
        $allUsers = [];
        $subject = "New User Added to Project " . $projectTilte;
        // $template = "new_user_added_to_project";
        foreach ($projectAllUsers as $user) {
            $allUsers[] = $user['email'];
            $template = 'welcome_email';
            $emailData = [
                'email' => $user['email'],
                'subject' => $subject,
                'template' => $template,
                'data' => [
                    'username' => "",
                    'tempPassword' => "",
                    'verificationLink' => ""
                ]
            ];
            // sendTemplateEmail($user['email'], $subject, $template, $emailData);
            $command = "php sendEmail.php '$emailData' > /dev/null 2>&1 &";
            exec($command);
        }
        return $newUser . " has been added to the project: " . $projectTilte . " at new Role: " . $newRole . " project All Users: " . implode(", ", $allUsers);
    }


    public function verifyUser($token)
    {
        try {
            // Check if the token exists and get the user ID
            $stmt = $this->db->prepare("SELECT id FROM users WHERE verification_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                return false; // Token not found
            }

            // Update user status to 'active' and clear the verification token
            $stmt = $this->db->prepare("UPDATE users SET status = 'active', verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Check if the update was successful
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Verify User Error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>