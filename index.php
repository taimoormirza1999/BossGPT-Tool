<?php
require './vendor/autoload.php';
//Added to load the environment variables
// require_once 'env.php';
require_once './classes/UserManager.php';
require_once './classes/Notification.php';
use Dotenv\Dotenv;

// // Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);

// Added to persist the login cookie for one year
session_set_cookie_params(60 * 60 * 24 * 365);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 365);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['DISPLAY_ERRORS']);
ini_set('log_errors', 0);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);
define('TESTING_FEATURE', $_ENV['TESTING_FEATURE']);
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', 'project_manager');

// OpenAI API configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);
ob_start();
// Database Class
class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Unable to connect to database. Please try again later.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function initializeTables()
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS projects (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                status VARCHAR(20) DEFAULT 'planning',
                created_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )",

            "CREATE TABLE IF NOT EXISTS tasks (
                id INT PRIMARY KEY AUTO_INCREMENT,
                project_id INT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                picture VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'todo',
                due_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id)
            )",

            "CREATE TABLE IF NOT EXISTS chat_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                project_id INT,
                message TEXT,
                sender VARCHAR(50),
                function_call JSON NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id)
            )",
            // New table for project user assignments
            "CREATE TABLE IF NOT EXISTS project_users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                project_id INT,
                user_id INT,
                role VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            "CREATE TABLE IF NOT EXISTS task_assignees (
                id INT PRIMARY KEY AUTO_INCREMENT,
                task_id INT,
                user_id INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (task_id) REFERENCES tasks(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            "CREATE TABLE IF NOT EXISTS subtasks (
                id INT PRIMARY KEY AUTO_INCREMENT,
                task_id INT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                status VARCHAR(20) DEFAULT 'todo',
                due_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
            )",
            // Add this new query for the activity log table
            "CREATE TABLE IF NOT EXISTS activity_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                project_id INT,
                user_id INT,
                action_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )"
        ];

        try {
            foreach ($queries as $query) {
                $this->conn->exec($query);
            }
        } catch (PDOException $e) {
            error_log("Table creation failed: " . $e->getMessage());
            throw new Exception("Database setup failed. Please contact administrator.");
        }
    }
}

// Auth Class
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
            $stmt = $this->db->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new Exception("Invalid credentials");
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            return true;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }

    public function logout()
    {
        session_destroy();
        session_start();
        session_unset();
        session_destroy();
        return true;
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
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }
    public function updateProStatus($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET pro_plan = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }
}

// Project Manager Class
class ProjectManager
{
    private $db;
    private $lastActions = [];
    private $debounceTime = 2; // 2 seconds debounce

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function shouldLogAction($action_type, $description, $project_id)
    {
        $key = $project_id . '_' . $action_type . '_' . $description;
        $currentTime = time();

        if (isset($this->lastActions[$key])) {
            if ($currentTime - $this->lastActions[$key] < $this->debounceTime) {
                return false;
            }
        }

        $this->lastActions[$key] = $currentTime;
        return true;
    }

    private function logActivity($project_id, $action_type, $description)
    {
        try {
            // Check if we should log this action
            if (!$this->shouldLogAction($action_type, $description, $project_id)) {
                return;
            }

            $stmt = $this->db->prepare("
                INSERT INTO activity_log (project_id, user_id, action_type, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $project_id,
                $_SESSION['user_id'],
                $action_type,
                $description
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }

    public function createProject($title, $description, $user_id)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO projects (title, description, created_by) 
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$title, $description, $user_id]);
            $project_id = $this->db->lastInsertId();
            $this->assignUserToProject($project_id, $user_id, "Creator");

            // Log the activity
            $this->logActivity(
                $project_id,
                'project_created',
                "Created new project: $title"
            );

            return $project_id;
        } catch (Exception $e) {
            error_log("Project Creation Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getProjects($user_id)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT p.* 
                FROM projects p 
                LEFT JOIN project_users pu ON p.id = pu.project_id 
                WHERE p.created_by = ? OR pu.user_id = ? 
                ORDER BY p.created_at DESC"
            );
            $stmt->execute([$user_id, $user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Projects Error: " . $e->getMessage());
            throw $e;
        }
    }
    public function getProjectUsers($project_id)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, pu.role 
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

    public function getTasks($project_id)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    t.id, 
                    t.project_id, 
                    t.title, 
                    t.description, 
                    t.picture, 
                    t.status, 
                    t.due_date, 
                    t.created_at, 
                    t.updated_at, 
                    COALESCE(GROUP_CONCAT(DISTINCT u.username SEPARATOR ', '), '') AS assigned_usernames,
                    COALESCE(GROUP_CONCAT(DISTINCT u.id SEPARATOR ', '), '') AS assigned_user_ids,
                    COALESCE(
                        (
                            SELECT CONCAT('[', 
                                GROUP_CONCAT(
                                    CONCAT(
                                        '{\"id\":', s.id, 
                                        ',\"title\":\"', s.title, 
                                        '\",\"description\":\"', COALESCE(s.description, ''), 
                                        '\",\"status\":\"', s.status, 
                                        '\",\"due_date\":\"', COALESCE(s.due_date, ''), '\"}'
                                    ) 
                                    SEPARATOR ','
                                ), 
                            ']') 
                            FROM subtasks s 
                            WHERE s.task_id = t.id
                        ), '[]'
                    ) AS subtasks
                FROM tasks t
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN users u ON ta.user_id = u.id
                WHERE t.project_id = ? 
                AND t.status != 'deleted'
                GROUP BY t.id, t.project_id, t.title, t.description, t.picture, t.status, t.due_date, t.created_at, t.updated_at
                ORDER BY t.created_at DESC"
            );

            $stmt->execute([$project_id]);
            $tasks = $stmt->fetchAll();

            // Process the concatenated strings into arrays
            foreach ($tasks as &$task) {
                $task['assigned_users'] = $task['assigned_usernames'] ?
                    array_combine(
                        explode(',', $task['assigned_user_ids']),
                        explode(',', $task['assigned_usernames'])
                    ) : [];
                unset($task['assigned_usernames']);
                unset($task['assigned_user_ids']);

                // Parse subtasks JSON
                $task['subtasks'] = json_decode($task['subtasks'] ?? '[]', true) ?? [];
            }
            unset($task);

            return $tasks;
        } catch (Exception $e) {
            error_log("Get Tasks Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateTaskStatus($task_id, $status)
    {
        try {
            // Get task info first
            $stmt = $this->db->prepare("
                SELECT t.title, t.project_id 
                FROM tasks t 
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();

            $stmt = $this->db->prepare(
                "UPDATE tasks SET status = ? WHERE id = ?"
            );
            $stmt->execute([$status, $task_id]);

            // Log the activity
            $this->logActivity(
                $task['project_id'],
                'task_status_updated',
                "Updated status of task '{$task['title']}' to $status"
            );

            return true;
        } catch (Exception $e) {
            error_log("Update Task Status Error: " . $e->getMessage());
            throw $e;
        }
    }

    // For task creation (in AIAssistant class where tasks are created)
    private function createSingleTask($project_id, $task)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO tasks (project_id, title, description, picture, status, due_date) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $project_id,
                $task['title'],
                $task['description'],
                isset($task['picture']) ? $task['picture'] : null,
                $task['status'] ?? 'todo',
                $task['due_date'] ?? null
            ]);

            $task_id = $this->db->lastInsertId();

            // Ensure assignees are provided
            if (!isset($task['assignees']) || !is_array($task['assignees']) || count($task['assignees']) === 0) {
                throw new Exception("At least one assignee is required for the task '{$task['title']}'");
            }

            // Insert task assignees
            $assigneeStmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            foreach ($task['assignees'] as $user_id) {
                $assigneeStmt->execute([$task_id, $user_id]);
            }

            // Log task creation with assignees
            $assigneeNames = $this->getAssigneeNames($task['assignees']);
            $this->logActivity(
                $project_id,
                'task_created',
                "Created task '{$task['title']}' and assigned to: " . implode(', ', $assigneeNames)
            );

            return $task_id;
        } catch (Exception $e) {
            error_log("Error creating task: " . $e->getMessage());
            throw $e;
        }
    }

    // Helper function to get assignee names
    private function getAssigneeNames($userIds)
    {
        if (empty($userIds)) {
            return []; // Return an empty array if there are no user IDs.
        }
        // Create a comma-separated string of placeholders, e.g., "?,?,?" if there are 3 IDs.
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // For task updates
    public function updateTask($task_id, $updates)
    {
        try {
            // Get original task data for logging
            $stmt = $this->db->prepare("SELECT title, project_id FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $originalTask = $stmt->fetch();

            $changes = [];
            if (isset($updates['title'])) {
                $changes[] = "title updated to '{$updates['title']}'";
            }
            if (isset($updates['description'])) {
                $changes[] = "description updated";
            }
            if (isset($updates['due_date'])) {
                $changes[] = "due date set to {$updates['due_date']}";
            }
            if (isset($updates['assignees'])) {
                $newAssigneeNames = $this->getAssigneeNames($updates['assignees']);
                $changes[] = "assignees updated to: " . implode(', ', $newAssigneeNames);
            }
            if (isset($updates['picture'])) {
                $changes[] = "picture updated";
            }
            if (!$originalTask) {
                throw new Exception("Task not found");
            }

            // Update task
            $updateFields = [];
            $params = [];
            foreach (['title', 'description', 'due_date', 'picture'] as $field) {
                if (isset($updates[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $updates[$field];
                }
            }

            if (!empty($updateFields)) {
                $params[] = $task_id;
                $stmt = $this->db->prepare("UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = ?");
                $stmt->execute($params);
            }

            // Update assignees if provided
            if (isset($updates['assignees'])) {
                $stmt = $this->db->prepare("DELETE FROM task_assignees WHERE task_id = ?");
                $stmt->execute([$task_id]);

                $stmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                foreach ($updates['assignees'] as $user_id) {
                    $stmt->execute([$task_id, $user_id]);
                }
            }

            // Log the changes
            if (!empty($changes)) {
                $this->logActivity(
                    $originalTask['project_id'],
                    'task_updated',
                    "Updated task '{$originalTask['title']}': " . implode(', ', $changes)
                );
            }

            return true;
        } catch (Exception $e) {
            error_log("Error updating task: " . $e->getMessage());
            throw $e;
        }
    }

    // For subtask creation
    public function createSubtask($task_id, $title, $description, $due_date)
    {
        try {
            // Get project ID for logging
            $stmt = $this->db->prepare("SELECT project_id, title as task_title FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();

            $stmt = $this->db->prepare(
                "INSERT INTO subtasks (task_id, title, description, due_date) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$task_id, $title, $description, $due_date]);

            // Log subtask creation
            $this->logActivity(
                $task['project_id'],
                'subtask_created',
                "Created subtask '{$title}' for task '{$task['task_title']}'"
            );

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating subtask: " . $e->getMessage());
            throw $e;
        }
    }

    // For subtask status updates
    public function updateSubtaskStatus($subtask_id, $status)
    {
        try {
            // Get task and project info for logging
            $stmt = $this->db->prepare("
                SELECT s.title as subtask_title, t.project_id, t.title as task_title 
                FROM subtasks s
                JOIN tasks t ON s.task_id = t.id
                WHERE s.id = ?
            ");
            $stmt->execute([$subtask_id]);
            $info = $stmt->fetch();

            $stmt = $this->db->prepare("UPDATE subtasks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $subtask_id]);

            // Log subtask status update
            $this->logActivity(
                $info['project_id'],
                'subtask_status_updated',
                "Updated status of subtask '{$info['subtask_title']}' in task '{$info['task_title']}' to {$status}"
            );

            return true;
        } catch (Exception $e) {
            error_log("Error updating subtask status: " . $e->getMessage());
            throw $e;
        }
    }

    // For user assignment to project
    public function assignUserToProject($project_id, $user_id, $role)
    {
        try {
            // Get user and project info for logging
            $stmt = $this->db->prepare("
                SELECT p.title as project_title, u.username 
                FROM projects p, users u 
                WHERE p.id = ? AND u.id = ?
            ");
            $stmt->execute([$project_id, $user_id]);
            $info = $stmt->fetch();

            $stmt = $this->db->prepare("
                INSERT INTO project_users (project_id, user_id, role) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE role = ?
            ");
            $stmt->execute([$project_id, $user_id, $role, $role]);

            // Log user assignment
            $this->logActivity(
                $project_id,
                'user_assigned',
                "Assigned user '{$info['username']}' to project '{$info['project_title']}' as {$role}"
            );

            return true;
        } catch (Exception $e) {
            error_log("Error assigning user to project: " . $e->getMessage());
            throw $e;
        }
    }

    // For deleting tasks
    public function deleteTask($task_id)
    {
        try {
            // Get task info for logging
            $stmt = $this->db->prepare("SELECT title, project_id FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();

            $stmt = $this->db->prepare("UPDATE tasks SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$task_id]);

            // Log task deletion
            $this->logActivity(
                $task['project_id'],
                'task_deleted',
                "Deleted task '{$task['title']}'"
            );

            return true;
        } catch (Exception $e) {
            error_log("Error deleting task: " . $e->getMessage());
            throw $e;
        }
    }

    // For deleting subtasks
    public function deleteSubtask($subtask_id)
    {
        try {
            // Get subtask info for logging
            $stmt = $this->db->prepare("
                SELECT s.title as subtask_title, t.project_id, t.title as task_title 
                FROM subtasks s
                JOIN tasks t ON s.task_id = t.id
                WHERE s.id = ?
            ");
            $stmt->execute([$subtask_id]);
            $info = $stmt->fetch();

            $stmt = $this->db->prepare("DELETE FROM subtasks WHERE id = ?");
            $stmt->execute([$subtask_id]);

            // Log subtask deletion
            $this->logActivity(
                $info['project_id'],
                'subtask_deleted',
                "Deleted subtask '{$info['subtask_title']}' from task '{$info['task_title']}'"
            );

            return true;
        } catch (Exception $e) {
            error_log("Error deleting subtask: " . $e->getMessage());
            throw $e;
        }
    }

    // For creating a task from a suggestion (public method)
    public function createTaskFromSuggestion($project_id, $title, $description, $due_date = null, $picture = null, $assignees = [])
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO tasks (project_id, title, description, picture, status, due_date) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $project_id,
                $title,
                $description,
                $picture,
                'todo',
                $due_date
            ]);
            $task_id = $this->db->lastInsertId();

            // Assign the current user as default assignee
            $assigneeStmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            foreach ($assignees as $user_id) {
                $assigneeStmt->execute([$task_id, $user_id]);
            }

            // Log activity for task creation
            $this->logActivity($project_id, 'task_created', "Created suggested task '$title' assigned to current user");

            return $task_id;
        } catch (Exception $e) {
            error_log("Error creating task from suggestion: " . $e->getMessage());
            throw $e;
        }
    }

    // <<-- New method for removing a task picture
    public function removeTaskPicture($task_id)
    {
        try {
            // Fetch current task information
            $stmt = $this->db->prepare("SELECT title, project_id, picture FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            if (!$task) {
                throw new Exception("Task not found");
            }
            $oldPicture = $task['picture'];

            // Remove the picture link from the database
            $stmt = $this->db->prepare("UPDATE tasks SET picture = '' WHERE id = ?");
            $stmt->execute([$task_id]);

            // Optionally delete the file from disk if it exists
            if ($oldPicture && file_exists($oldPicture)) {
                unlink($oldPicture);
            }

            // Log that the picture was removed
            $this->logActivity(
                $task['project_id'],
                'task_picture_removed',
                "Removed picture from task '{$task['title']}'"
            );

            return true;
        } catch (Exception $e) {
            error_log("Error removing task picture: " . $e->getMessage());
            throw $e;
        }
    }
    public function getProjectName($project_id)
    {
        $stmt = $this->db->prepare("SELECT title FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetch()['title'];
    }
}

// AI Assistant Class
class AIAssistant
{
    private $api_key;
    private $db;

    public function __construct()
    {
        $this->api_key = OPENAI_API_KEY;
        $this->db = Database::getInstance()->getConnection();
    }

    private function getProjectContext($project_id)
    {
        try {
            // Get project details
            $stmt = $this->db->prepare("
                SELECT * FROM projects WHERE id = ?
            ");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();

            // Get all tasks
            $stmt = $this->db->prepare("
                SELECT * FROM tasks 
                WHERE project_id = ? 
                ORDER BY created_at
            ");
            $stmt->execute([$project_id]);
            $tasks = $stmt->fetchAll();

            // Get conversation history
            $stmt = $this->db->prepare("
                SELECT message, sender, timestamp 
                FROM chat_history 
                WHERE project_id = ? 
                ORDER BY timestamp
                LIMIT 20
            ");
            $stmt->execute([$project_id]);
            $chat_history = $stmt->fetchAll();

            // Get project members (assigned users and their roles)
            $stmt = $this->db->prepare("
                SELECT pu.role, u.username, u.email 
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ?
            ");
            $stmt->execute([$project_id]);
            $members = $stmt->fetchAll();

            return [
                'project' => $project,
                'tasks' => $tasks,
                'chat_history' => $chat_history,
                'members' => $members
            ];
        } catch (Exception $e) {
            error_log("Error getting project context: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatContextForAI($context)
    {
        $formatted = "Project Context:\n";
        $formatted .= "Current Date: " . date('Y-m-d') . "\n\n";

        // Emphasize project details at the top
        $formatted .= "PROJECT OVERVIEW\n";
        $formatted .= "================\n";
        $formatted .= "Title: " . $context['project']['title'] . "\n";
        $formatted .= "Description: " . $context['project']['description'] . "\n";
        $formatted .= "Status: " . $context['project']['status'] . "\n\n";

        // Add project members to the context
        $formatted .= "PROJECT TEAM\n";
        $formatted .= "============\n";
        if (!empty($context['members'])) {
            foreach ($context['members'] as $member) {
                $formatted .= "- {$member['username']} (Role: {$member['role']})\n";
            }
        } else {
            $formatted .= "No members assigned.\n";
        }
        $formatted .= "\n";

        // Add current tasks with more structure
        $formatted .= "CURRENT TASKS\n";
        $formatted .= "=============\n";
        foreach ($context['tasks'] as $task) {
            $formatted .= "• {$task['title']}\n";
            $formatted .= "  Status: {$task['status']}\n";
            if (!empty($task['description'])) {
                $formatted .= "  Description: {$task['description']}\n";
            }
            if ($task['due_date']) {
                $formatted .= "  Due: {$task['due_date']}\n";
            }
            $formatted .= "\n";
        }

        return $formatted;
    }

    public function processMessage($message, $project_id)
    {
        try {
            $context = $this->getProjectContext($project_id);
            $formatted_context = $this->formatContextForAI($context);

            $messages = [
                [
                    'role' => 'system',
                    'content' => "You are a demanding and results-driven executive manager. Your communication style is direct, authoritative, and focused on performance and deadlines. Use phrases like 'I expect', 'You need to', 'This must be done', and emphasize urgency and accountability. Be stern but fair, always pushing for excellence.\n\nWhen responding:\n1. Be direct and concise\n2. Set clear expectations and deadlines\n3. Show zero tolerance for excuses\n4. Emphasize accountability\n5. Push for high performance\n6. Use authoritative language\n7. Focus on results and metrics\n8. Give direct feedback\n\nProject Context:\n" . $formatted_context
                ]
            ];

            // Append previous chat history...

            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            // Existing functions for tasks and updates
            $functions = [
                [
                    'name' => 'create_multiple_tasks',
                    'description' => 'Create multiple tasks for the project at once',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tasks' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => [
                                            'type' => 'string',
                                            'description' => 'Title of the task'
                                        ],
                                        'description' => [
                                            'type' => 'string',
                                            'description' => 'Detailed description of the task'
                                        ],
                                        'due_date' => [
                                            'type' => 'string',
                                            'description' => 'Due date in YYYY-MM-DD format'
                                        ],
                                        'status' => [
                                            'type' => 'string',
                                            'enum' => ['todo', 'in_progress', 'done'],
                                            'description' => 'Current status of the task'
                                        ],
                                        'assignees' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'integer',
                                                'description' => 'ID of the user to be assigned to the task'
                                            ],
                                            'minItems' => 1,
                                            'description' => 'User IDs assigned to the task (at least one required)'
                                        ]
                                    ],
                                    'required' => ['title', 'description', 'due_date', 'status', 'assignees']
                                ]
                            ]
                        ],
                        'required' => ['tasks']
                    ]
                ],
                [
                    'name' => 'update_task',
                    'description' => 'Update an existing task',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_id' => [
                                'type' => 'integer',
                                'description' => 'ID of the task to update'
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'New title of the task'
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'New description of the task'
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['todo', 'in_progress', 'done'],
                                'description' => 'New status of the task'
                            ],
                            'due_date' => [
                                'type' => 'string',
                                'description' => 'New due date in YYYY-MM-DD format'
                            ]
                        ],
                        'required' => ['task_id']
                    ]
                ],
                // <<-- New function definition for creating multiple subtasks using AI
                [
                    'name' => 'create_multiple_subtasks',
                    'description' => 'Create multiple subtasks for a given task using AI generation',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_id' => [
                                'type' => 'integer',
                                'description' => 'ID of the task to add subtasks for'
                            ],
                            'subtasks' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => [
                                            'type' => 'string',
                                            'description' => 'Title of the subtask'
                                        ],
                                        'description' => [
                                            'type' => 'string',
                                            'description' => 'Detailed description of the subtask'
                                        ],
                                        'due_date' => [
                                            'type' => 'string',
                                            'description' => 'Due date in YYYY-MM-DD format'
                                        ]
                                    ],
                                    'required' => ['title', 'description', 'due_date']
                                ]
                            ]
                        ],
                        'required' => ['task_id', 'subtasks']
                    ]
                ],
                // <<-- New function definition for suggesting new tasks and features
                [
                    'name' => 'suggest_new_tasks',
                    'description' => 'Suggest new tasks and features for the project based on its context. Provide an array of suggested tasks, each including title, description, and optionally due_date.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'suggestions' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => [
                                            'type' => 'string',
                                            'description' => 'Title of the suggested task'
                                        ],
                                        'description' => [
                                            'type' => 'string',
                                            'description' => 'Detailed description of the suggested task'
                                        ],
                                        'due_date' => [
                                            'type' => 'string',
                                            'description' => 'Optional due date in YYYY-MM-DD format'
                                        ]
                                    ],
                                    'required' => ['title', 'description']
                                ]
                            ]
                        ],
                        'required' => ['suggestions']
                    ]
                ]
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'gpt-4o',
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => 'auto'
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->api_key,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                throw new Exception("cURL Error: " . $err);
            }

            $result = json_decode($response, true);

            if (isset($result['error'])) {
                throw new Exception("API Error: " . $result['error']['message']);
            }

            // Save user message
            $stmt = $this->db->prepare(
                "INSERT INTO chat_history (project_id, message, sender, function_call) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $project_id,
                $message,
                'user',
                null
            ]);

            // Process function calls if any
            if (isset($result['choices'][0]['message']['function_call'])) {
                $function_call = $result['choices'][0]['message']['function_call'];
                $this->executeFunctionCall($function_call, $project_id);
            }

            // Save AI response
            $ai_message = $result['choices'][0]['message']['content'] ?? 'Tasks have been created successfully.';
            $stmt->execute([
                $project_id,
                $ai_message,
                'ai',
                isset($result['choices'][0]['message']['function_call'])
                ? json_encode($result['choices'][0]['message']['function_call'])
                : null
            ]);

            return [
                'message' => $ai_message,
                'function_call' => $result['choices'][0]['message']['function_call'] ?? null,
                'context' => $context
            ];

        } catch (Exception $e) {
            error_log("AI Processing Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function executeFunctionCall($function_call, $project_id)
    {
        $name = $function_call['name'];
        $arguments = json_decode($function_call['arguments'], true);

        switch ($name) {
            case 'create_multiple_tasks':
                if (isset($arguments['tasks']) && is_array($arguments['tasks'])) {
                    foreach ($arguments['tasks'] as $task) {
                        $this->createSingleTask($project_id, $task);
                    }
                }
                break;

            case 'update_task':
                $updates = [];
                $params = [];

                if (isset($arguments['title'])) {
                    $updates[] = "title = ?";
                    $params[] = $arguments['title'];
                }
                if (isset($arguments['description'])) {
                    $updates[] = "description = ?";
                    $params[] = $arguments['description'];
                }
                if (isset($arguments['status'])) {
                    $updates[] = "status = ?";
                    $params[] = $arguments['status'];
                }
                if (isset($arguments['due_date'])) {
                    $updates[] = "due_date = ?";
                    $params[] = $arguments['due_date'];
                }

                if (!empty($updates)) {
                    $params[] = $arguments['task_id'];
                    $sql = "UPDATE tasks SET " . implode(", ", $updates) . " WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                }

                // Update task assignees if provided
                if (isset($arguments['assignees'])) {
                    // Delete current assignees for this task
                    $stmt = $this->db->prepare("DELETE FROM task_assignees WHERE task_id = ?");
                    $stmt->execute([$arguments['task_id']]);

                    // Insert new assignees into task_assignees
                    $assigneeStmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                    foreach ($arguments['assignees'] as $user_id) {
                        $assigneeStmt->execute([$arguments['task_id'], $user_id]);
                    }
                }
                break;
            // <<-- New case for AI generated subtasks
            case 'create_multiple_subtasks':
                if (isset($arguments['task_id']) && isset($arguments['subtasks']) && is_array($arguments['subtasks'])) {
                    $project_manager = new ProjectManager();
                    foreach ($arguments['subtasks'] as $subtask) {
                        $project_manager->createSubtask(
                            $arguments['task_id'],
                            $subtask['title'],
                            $subtask['description'],
                            $subtask['due_date']
                        );
                    }
                }
                break;
        }
    }

    private function createSingleTask($project_id, $task)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO tasks (project_id, title, description, picture, status, due_date) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $project_id,
                $task['title'],
                $task['description'],
                isset($task['picture']) ? $task['picture'] : null,
                $task['status'] ?? 'todo',
                $task['due_date'] ?? null
            ]);

            $task_id = $this->db->lastInsertId();

            // Ensure assignees are provided
            if (!isset($task['assignees']) || !is_array($task['assignees']) || count($task['assignees']) === 0) {
                throw new Exception("At least one assignee is required for the task '{$task['title']}'");
            }

            // Insert task assignees
            $assigneeStmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            foreach ($task['assignees'] as $user_id) {
                $assigneeStmt->execute([$task_id, $user_id]);
            }

            // Log task creation with assignees
            $assigneeNames = $this->getAssigneeNames($task['assignees']);
            $this->logActivity(
                $project_id,
                'task_created',
                "Created task '{$task['title']}' and assigned to: " . implode(', ', $assigneeNames)
            );

            return $task_id;
        } catch (Exception $e) {
            error_log("Error creating task: " . $e->getMessage());
            throw $e;
        }
    }

    // Helper function to get assignee names
    private function getAssigneeNames($userIds)
    {
        if (empty($userIds)) {
            return []; // Return an empty array if there are no user IDs.
        }
        // Create a comma-separated string of placeholders, e.g., "?,?,?" if there are 3 IDs.
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Initialize database and handle API requests
$database = Database::getInstance();
$database->initializeTables();

// Move the POST handling code here, after all classes are defined
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth = new Auth();

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'register':
                    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                        throw new Exception('All fields are required');
                    }

                    $auth->register(
                        $_POST['username'],
                        $_POST['email'],
                        $_POST['password'],
                        $_POST['fcm_token']
                    );

                    $user = new UserManager();
                    // After successful registration, log the user in
                    $user->sendWelcomeEmail($_POST['email'], $_POST['username'], $_ENV['BASE_URL']);                    // After successful registration, log the user in
                    $auth->login($_POST['email'], $_POST['password']);

                    $paymentLink = $_ENV['STRIPE_PAYMENT_LINK'];
                    header("Location: $paymentLink");
                    exit;

                case 'login':
                    if (empty($_POST['email']) || empty($_POST['password'])) {
                        throw new Exception('Email and password are required');
                    }

                    $auth->login($_POST['email'], $_POST['password']);
                    header('Location: ?page=dashboard');
                    exit;

                case 'logout':
                    $auth->logout();
                    session_start();
                    session_unset();
                    session_destroy();
                    header('Location: ?page=login');
                    exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// API Endpoint Handler
if (isset($_GET['api'])) {
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];

    try {
        $auth = new Auth();
        if (!$auth->isLoggedIn()) {
            throw new Exception('Unauthorized');
        }

        $db = Database::getInstance()->getConnection();
        $project_manager = new ProjectManager();
        $ai_assistant = new AIAssistant();

        switch ($_GET['api']) {

            case 'update_pro_status':
                if (!isset($_SESSION['user_id'])) {
                    return json_encode(['success' => false, 'message' => 'User not logged in']);
                    exit;
                }

                try {
                    $auth = new Auth();
                    $result = $auth->updateProStatus($_SESSION['user_id']);
                    return json_encode(['success' => true, 'message' => 'Pro status updated successfully']);
                    exit;
                } catch (Exception $e) {
                    return json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                exit;
            case 'get_chat_history':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['project_id'])) {
                    throw new Exception('Project ID is required');
                }

                $stmt = $db->prepare("
                    SELECT message, sender, timestamp 
                    FROM chat_history 
                    WHERE project_id = ? 
                    ORDER BY timestamp
                    LIMIT 20
                ");
                $stmt->execute([$data['project_id']]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = [
                    'success' => true,
                    'history' => $history ?? []
                ];
                break;

            case 'get_project_users':
                $data = json_decode(file_get_contents('php://input'), true);
                header('Content-Type: application/json');
                if (!isset($data['project_id'])) {
                    throw new Exception('Project ID is required');
                }

                $users = $project_manager->getProjectUsers($data['project_id']);
                $response = ['success' => true, 'users' => $users];
                break;
            case 'get_all_project_users':
                header('Content-Type: application/json');
                $users = $project_manager->getProjectUsers($_GET['project_id']);
                echo json_encode([
                    'success' => true,
                    'users' => $users
                ]);
                exit;

            case 'send_message':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['message']) || !isset($data['project_id'])) {
                    throw new Exception('Message and project ID are required');
                }

                $result = $ai_assistant->processMessage(
                    $data['message'],
                    $data['project_id']
                );
                $response = ['success' => true] + $result;
                break;

            case 'create_project':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['title'])) {
                    throw new Exception('Project title is required');
                }

                $project_id = $project_manager->createProject(
                    $data['title'],
                    $data['description'] ?? '',
                    $_SESSION['user_id']
                );
                $response = ['success' => true, 'project_id' => $project_id];
                break;

            case 'get_projects':
                $projects = $project_manager->getProjects($_SESSION['user_id']);
                $response = ['success' => true, 'projects' => $projects];
                break;

            case 'get_tasks':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['project_id'])) {
                    throw new Exception('Project ID is required');
                }

                $tasks = $project_manager->getTasks($data['project_id']);
                $response = ['success' => true, 'tasks' => $tasks];
                break;

            case 'update_task_status':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id']) || !isset($data['status'])) {
                    throw new Exception('Task ID and status are required');
                }

                $project_manager->updateTaskStatus(
                    $data['task_id'],
                    $data['status']
                );
                $response = ['success' => true];
                break;

            case 'update_task':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id'])) {
                    throw new Exception('Task ID is required');
                }
                if (isset($data['picture']) && !empty($data['picture']) && strpos($data['picture'], 'data:image') === 0) {
                    $imgData = $data['picture'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                        $imgData = substr($imgData, strpos($imgData, ',') + 1);
                        $type = strtolower($type[1]);
                        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                            throw new Exception('Invalid image type');
                        }
                        $imgData = base64_decode($imgData);
                        if ($imgData === false) {
                            throw new Exception('Base64 decode failed');
                        }
                    } else {
                        throw new Exception('Invalid image data');
                    }
                    $uploadDir = 'uploads';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '.' . $type;
                    $filePath = $uploadDir . '/' . $fileName;
                    file_put_contents($filePath, $imgData);
                    $data['picture'] = $filePath;
                }
                $project_manager->updateTask($data['task_id'], $data);
                $response = ['success' => true];
                break;

            case 'assign_user_to_project':

                $data = json_decode(
                    file_get_contents('php://input'),
                    true
                );
                if (!isset($data['project_id']) || !isset($data['user_id']) || !isset($data['role'])) {
                    throw new Exception('Project ID, user ID, and role are required');
                }
                $project_manager->assignUserToProject($data['project_id'], $data['user_id'], $data['role']);
                $response = ['success' => true];
                break;
            case 'create_or_assign_user':
                header('Content-Type: application/json');
                try {
                    // error_log("Received create user request");
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!$data) {
                        throw new Exception('Invalid request data');
                    }

                    $project_manager = new ProjectManager();
                    $projectTilte = $project_manager->getProjectName($data['project_id']);
                    $projectAllUsers = $project_manager->getProjectUsers($data['project_id']);
                    // $projectAllUsers = [
                    //     [
                    //         "id" => 34,
                    //         "username" => "taimoorhamza1999",
                    //         "email" => "taimoorhamza1999@gmail.com",
                    //         "role" => "Creator"
                    //     ],
                    //     [
                    //         "id" => 35, // Changed to unique ID
                    //         "username" => "taimoorhamza199",
                    //         "email" => "taimoorhamza199@gmail.com",
                    //         "role" => "Full Stack Developer"
                    //     ],
                    // ];
                    $userManager = new UserManager();
                    $result = $userManager->createOrAssignUser(
                        $data['username'],
                        $data['email'],
                        $data['project_id'] ?? null,
                        $data['role'] ?? null,
                        $_ENV['BASE_URL']
                    );


                    // Send Notification
                    $result = Notification::send('project_' . $data['project_id'], 'user_assigned', ['message' => 'New User ' . $data['username'] . ' joined as the ' . $data['role'] . 'in the project']);
                    // Sending Email & Notification
                    try {
                        $emailSent = $userManager->projectUsersNewUserAddedEmail($data['username'], $projectTilte, $data['role'], $projectAllUsers, );
                        if ($emailSent) {
                            echo json_encode($response = [
                                'success' => $emailSent,
                                'message' => "An invite has been sent along with login credentials."
                            ]);
                            exit;
                        } else {
                            $response = [
                                'success' => false,
                                'message' => "Failed to send the invite."
                            ];
                        }
                    } catch (Exception $e) {
                        $response = [
                            'success' => false,
                            'message' => "Error: " . $e->getMessage()
                        ];
                    }


                    echo json_encode([
                        'success' => true,
                        'message' => 'User created successfully',
                        'data' => $result
                    ]);

                } catch (Exception $e) {
                    error_log("Error in create_user: " . $e->getMessage());
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                exit;
            case 'get_users':
                $stmt = $db->query("SELECT id, username, email FROM users ORDER BY username ASC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['success' => true, 'users' => $users];
                break;
            case 'delete_user':
                if (!isset($_GET['user_id']) || !isset($_GET['project_id']) || !isset($_GET['user_name'])) {
                    $response = ['success' => false, 'message' => 'User ID, Project ID, and User Name are required'];
                    break;
                }


                $user_id = $_GET['user_id'];
                $project_id = $_GET['project_id'];
                $user_name = $_GET['user_name'];
                // Check if user exists
                // $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                // $stmt->execute([$user_id]);
                // $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // if (!$user) {
                //     $response = ['success' => false, 'message' => 'User not found'];
                //     break;
                // }

                // Delete user
                $stmt = $db->prepare("DELETE FROM project_users WHERE user_id = ?");
                $success = $stmt->execute([$user_id]);



                if ($success) {
                    $stmt = $db->prepare("
            INSERT INTO activity_log (project_id, user_id, action_type, description) 
            VALUES (?, ?, ?, ?)
        ");
                    $stmt->execute([
                        $project_id,
                        $user_id,
                        'user_removed',
                        "User {$user_name} has been removed from project {$project_id}"
                    ]);
                    $response = ['success' => true, 'message' => `$user_name removed successfully`];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to remove user'];
                }
                break;
            case 'get_task_assignees':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id'])) {
                    throw new Exception('Task ID is required');
                }
                $stmt = $db->prepare("SELECT user_id FROM task_assignees WHERE task_id = ?");
                $stmt->execute([$data['task_id']]);
                $assignees = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $response = ['success' => true, 'assignees' => $assignees];
                break;

            case 'create_subtask':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id']) || !isset($data['title'])) {
                    throw new Exception('Task ID and title are required');
                }
                $subtask_id = $project_manager->createSubtask(
                    $data['task_id'],
                    $data['title'],
                    $data['description'] ?? null,
                    $data['due_date'] ?? null
                );
                $response = ['success' => true, 'subtask_id' => $subtask_id];
                break;

            case 'update_subtask_status':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['subtask_id']) || !isset($data['status'])) {
                    throw new Exception('Subtask ID and status are required');
                }
                $project_manager->updateSubtaskStatus($data['subtask_id'], $data['status']);
                $response = ['success' => true];
                break;

            case 'delete_subtask':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['subtask_id'])) {
                    throw new Exception('Subtask ID is required');
                }
                $project_manager->deleteSubtask($data['subtask_id']);
                $response = ['success' => true];
                break;

            case 'get_activity_log':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['project_id'])) {
                    throw new Exception('Project ID is required');
                }

                $stmt = $db->prepare("
                    SELECT 
                        al.action_type,
                        al.description,
                        al.created_at,
                        u.username
                    FROM activity_log al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE al.project_id = ?
                    ORDER BY al.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute([$data['project_id']]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = ['success' => true, 'logs' => $logs];
                break;
            case 'get_unreadnotifications':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($_GET['project_id'])) {
                    throw new Exception('Project ID is required');
                }

                $stmt = $db->prepare("
                    SELECT 
                        al.action_type,
                        al.description,
                        al.created_at,
                        u.username,
                        al.status as notification_status
                    FROM activity_log al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE al.project_id = ? AND al.status = 'unread'
                    ORDER BY al.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute([$_GET['project_id']]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = ['success' => true, 'logs' => $logs];
                break;

            case 'get_task_activity_log':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id'])) {
                    throw new Exception('Task ID is required');
                }

                $stmt = $db->prepare("
                    SELECT 
                        al.action_type,
                        al.description,
                        al.created_at,
                        u.username
                    FROM activity_log al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE al.description LIKE CONCAT('%task ''%', (SELECT title FROM tasks WHERE id = ?), '%''%')
                    ORDER BY al.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([$data['task_id']]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = ['success' => true, 'logs' => $logs];
                break;

            case 'create_task':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['project_id']) || !isset($data['title'])) {
                    throw new Exception('Project ID and task title are required');
                }
                $picture = null;
                if (isset($data['picture']) && !empty($data['picture'])) {
                    $imgData = $data['picture'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                        $imgData = substr($imgData, strpos($imgData, ',') + 1);
                        $type = strtolower($type[1]);
                        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                            throw new Exception('Invalid image type');
                        }
                        $imgData = base64_decode($imgData);
                        if ($imgData === false) {
                            throw new Exception('Base64 decode failed');
                        }
                    } else {
                        throw new Exception('Invalid image data');
                    }
                    $uploadDir = 'uploads';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '.' . $type;
                    $filePath = $uploadDir . '/' . $fileName;

                    // Debug information to check directory permissions and file writing
                    error_log("Attempting to save image to: " . realpath($uploadDir) . "/$fileName");
                    error_log("Directory exists: " . (file_exists($uploadDir) ? 'Yes' : 'No'));
                    error_log("Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

                    $result = file_put_contents($filePath, $imgData);
                    if ($result === false) {
                        error_log("Failed to write image file. Error: " . error_get_last()['message']);
                        throw new Exception('Failed to save image file. Check server permissions.');
                    } else {
                        error_log("Successfully wrote $result bytes to $filePath");
                    }

                    $picture = $filePath;
                }
                $assignees = isset($data['assignees']) ? $data['assignees'] : [];
                $task_id = $project_manager->createTaskFromSuggestion(
                    $data['project_id'],
                    $data['title'],
                    $data['description'] ?? '',
                    $data['due_date'] ?? null,
                    $picture,
                    $assignees
                );

                // Send Email and Notifications
                $allAssignees = [];
                $userManager = new UserManager();
                foreach ($assignees as $assignee_id) {
                    $userDetails = $userManager->getUserDetails($assignee_id);
                    if ($userDetails) {
                        $allAssignees[] = $userDetails;
                    }
                }
                // $allAssignees = [
                //         [
                //             "id" => 34,
                //             "username" => "taimoorhamza1999",
                //             "email" => "taimoorhamza1999@gmail.com",
                //             "role" => "Creator"
                //         ],
                //         [
                //             "id" => 35, // Changed to unique ID
                //             "username" => "taimoorhamza199",
                //             "email" => "taimoorhamza199@gmail.com",
                //             "role" => "Full Stack Developer"
                //         ],
                //     ];
                // Send Notification
                $result = Notification::send('project_' . $data['project_id'], 'task_created', ['message' => 'New Task created ' . $data['title'] . ' and assigned ']);

                // Sending Email
                $Auth = new Auth();
                $logedinUser = $Auth->getCurrentUser();
                $projectTilte = $project_manager->getProjectName($data['project_id']);
                try {
                    $emailSent = $userManager->projectUsersTaskAssignedEmail($logedinUser['username'], $projectTilte, $data['title'], $allAssignees, );
                    if ($emailSent) {
                        echo json_encode($response = [
                            'success' => $emailSent,
                            'message' => "An invite has been sent to assignee"
                        ]);
                        exit;
                    } else {
                        $response = [
                            'success' => false,
                            'message' => "Failed to send the invite."
                        ];
                    }
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => "Error: " . $e->getMessage()
                    ];
                }

                $response = ['success' => true, 'task_id' => $task_id];
                break;

            case 'remove_task_picture':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['task_id'])) {
                    throw new Exception('Task ID is required');
                }
                $project_manager->removeTaskPicture($data['task_id']);
                $response = ['success' => true];
                break;

            case 'update_subtask':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['subtask_id']) || !isset($data['due_date'])) {
                    throw new Exception('Subtask ID and due date are required');
                }

                // First get the parent task's due date
                $stmt = $db->prepare("
                    SELECT t.due_date as parent_due_date 
                    FROM subtasks s
                    JOIN tasks t ON s.task_id = t.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$data['subtask_id']]);
                $parentTask = $stmt->fetch();

                // Validate the new date against parent task's due date
                if ($parentTask && $parentTask['parent_due_date']) {
                    $parentDueDate = new DateTime($parentTask['parent_due_date']);
                    $newSubtaskDate = new DateTime($data['due_date']);

                    if ($newSubtaskDate > $parentDueDate) {
                        throw new Exception('Subtask due date cannot be later than parent task due date');
                    }
                }

                // Update the subtask with the new date
                $stmt = $db->prepare("
                    UPDATE subtasks 
                    SET due_date = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$data['due_date'], $data['subtask_id']]);
                $response = ['success' => true];
                break;
                // Email
                // Enable error reporting for debugging
                ini_set('display_errors', 1);
                error_reporting(E_ALL);

            // The case for sending welcome email
            case 'send_welcome_email':
                $data = json_decode(file_get_contents('php://input'), true);
                $BASE_URL = $_ENV['BASE_URL'];

                // Validate input
                if (!isset($data['email']) || !isset($data['username']) || !isset($data['tempPassword'])) {
                    $response = [
                        'success' => false,
                        'message' => "Error: Missing required fields (email, username, tempPassword)."
                    ];
                    // Set the Content-Type header to application/json
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit;
                }

                // Simulate getting project users (Example users)
                $project_manager = new ProjectManager();
                $projectTilte = $project_manager->getProjectName($data['projectId']);
                $projectAllUsers = $project_manager->getProjectUsers($data['projectId']);

                // $projectAllUsers = [
                //     [
                //         "id" => 34,
                //         "username" => "taimoorhamza1999",
                //         "email" => "taimoorhamza1999@gmail.com",
                //         "role" => "Creator"
                //     ],
                //     [
                //         "id" => 35, // Changed to unique ID
                //         "username" => "taimoorhamza199",
                //         "email" => "taimoorhamza199@gmail.com",
                //         "role" => "Full Stack Developer"
                //     ],
                // ];

                $userManager = new UserManager();
                try {
                    $emailSent = $userManager->projectUsersNewUserAddedEmail("taimoorhamza1999", "Temp", "Developer", $projectAllUsers, );
                    if ($emailSent) {
                        $response = [
                            'success' => true,
                            'message' => "An invite has been sent along with login credentials."
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => "Failed to send the invite."
                        ];
                    }
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => "Error: " . $e->getMessage()
                    ];
                }

                // Set the Content-Type header to application/json
                header('Content-Type: application/json');
                echo json_encode($response);  // Ensure JSON is properly returned
                exit;



            // Notification
            case 'send_notification_project':
                $data = json_decode(file_get_contents('php://input'), true);
                // if (!isset($data['task_id'])) {
                //     throw new Exception('Task ID is required');
                // }
                $userManager = new UserManager();
                if (!isset($db)) {
                    $response = [
                        'success' => false,
                        'message' => "Error: Database connection (\$db) is not set."
                    ];

                }

                if (!isset($userManager)) {

                    $response = [
                        'success' => false,
                        'message' => "Error: UserManager (\$userManager) is not set."
                    ];
                }
                // try {
                //     $notificationManager = new NotificationManager($db, $userManager);
                //     $response_data = $notificationManager->sendProjectNotification($data['project_id'], $data['title'], $data['body']);
                //     $response = [
                //         'success' => true,
                //         'message' => $response_data
                //     ];
                // } catch (Exception $e) {
                //     // error_log("API Error: " . $e->getMessage());
                //     $response = [
                //         'success' => false,
                //         'message' => "Error: " . $e->getMessage()
                //     ];
                //     // http_response_code(500);
                // }
                // Print the response for debugging
                // $response_data=$notificationManager->sendProjectNotificatio($data['project_id'], $data['title'], $data['body']);
                // $response = ['success' => true, 'message' => "$response_data"];
                break;
            default:
                throw new Exception('Invalid API endpoint');
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        http_response_code(500);
    }

    echo json_encode($response);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-signin-client_id"
        content="949298386531-pbk4td6p6ga18e6diee9rifskto0ou0v.apps.googleusercontent.com.apps.googleusercontent.com">

    <title>Project Manager AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <!-- iziToast CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
    <!-- Tailwind CSS -->
    <!-- <script src="https://unpkg.com/@tailwindcss/browser@4"></script> -->
    <!-- Custom js -->
    <script src="./assets/js/custom.js"></script>
    <!-- Favicon links -->
    <!-- <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="/assets/favicon/site.webmanifest"> -->

    <link rel="icon" type="image/png" sizes="32x32" href="faviconbossgpt.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <!-- Custom css -->
    <link rel="stylesheet" href="./assets/css/custom.css">


    <style>
        .suggestion-item {
            border-radius: 16px !important;
            border-color: gray !important;
        }

        .chat-container {
            height: calc(100vh - 230px);
            /* height: 80vh; */
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
            /* max-height: 60vh; */
            max-height: calc(100vh - 300px);
        }

        .chat-input {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background: white;
        }

        .task-column {
            /* min-height: 60vh; */
            min-height: calc(100vh - 300px);
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            overflow-y: auto;
        }

        .task-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
            backface-visibility: hidden;
            will-change: transform, box-shadow;
        }

        .task-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-color: rgba(13, 110, 253, 0.3);
            z-index: 1;
        }

        .task-card:hover::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px;
            background: radial-gradient(800px circle at var(--mouse-x) var(--mouse-y),
                    rgba(13, 110, 253, 0.06),
                    transparent 40%);
            z-index: 1;
            pointer-events: none;
        }

        .task-card:active {
            transform: translateY(-2px) scale(1.01);
            transition-duration: 0.1s;
        }

        .task-card>* {
            position: relative;
            z-index: 2;
        }

        /* Update these styles */
        .task-description {
            display: block;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            background: #fff;
            padding: 0;
            margin: 0;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
            transition: all 0.3s ease 0.3s;
            /* Added 0.3s delay */
        }

        .task-card:hover .task-description {
            opacity: 1;
            max-height: 500px;
            /* Adjust based on your needs */
            padding: 0.75rem;
            margin: 0.5rem 0;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa;
        }

        .task-card.dragging {
            opacity: 0.5;
        }

        .projects-list {
            height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .message {
            margin-bottom: 1rem;
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            max-width: 90%;
            word-wrap: break-word;
        }

        .message.user {
            background: #007bff;
            color: white;
            margin-left: auto;
        }

        .message.ai {
            background: #f8f9fa;
            margin-right: auto;
        }

        .project-item {
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }

        .project-item:hover {
            background-color: #f8f9fa;
        }

        .project-item.active {
            background-color: #e9ecef;
        }

        .card {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #editTaskAssignees {
            width: 100%;
            min-height: 100px;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-selection--multiple {
            min-height: 100px !important;
        }

        .task-assignees {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .task-assignee {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            background-color: #e9ecef;
            border-radius: 20px;
            color: #495057;
            display: inline-flex;
            align-items: center;
        }

        .task-assignee i {
            font-size: 0.7rem;
            margin-right: 0.25rem;
        }

        .task-meta {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #6c757d;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .delete-task-btn {
            padding: 0.15rem 0.4rem;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.2s;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }

        .delete-task-btn:hover {
            opacity: 1;
        }

        .task-card:hover .delete-task-btn {
            opacity: 0.7;
        }

        .due-date {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.25rem 0.75rem;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: help;
        }

        .due-date.overdue {
            color: #dc3545;
            background-color: #f8d7da;
        }

        .due-date i {
            font-size: 0.9rem;
        }

        /* Add these styles to the existing <style> section */
        .subtask-item {
            padding: 0.5rem;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.02);
            margin-bottom: 0.25rem;
        }

        .subtask-item:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .subtask-title {
            font-size: 0.9em;
            margin-bottom: 0.1rem;
        }

        .delete-subtask-btn {
            padding: 0;
            color: #dc3545;
            opacity: 0.5;
        }

        .delete-subtask-btn:hover {
            opacity: 1;
            color: #dc3545;
        }

        /* Updated CSS for the Add Subtask button */
        .add-subtask-btn {
            font-size: 0.9em;
            color: #0d6efd;
            padding: 0;
            opacity: 0;
            /* Hidden by default */
            transition: opacity 0.3s;
            /* Smooth fade-in effect */
        }

        .add-subtask-btn:hover {
            color: #0a58ca;
        }

        .task-card:hover .add-subtask-btn {
            opacity: 1;
            /* Show the button when hovering over the task */
        }

        /* Add these styles to your existing CSS */
        .task-card.dragging {
            opacity: 0.5;
            transform: scale(0.95);
            cursor: grabbing;
        }

        .task-column.drag-over {
            background-color: rgba(0, 123, 255, 0.1);
            border: 2px dashed #007bff;
        }

        .task-card {
            cursor: grab;
            transition: all 0.2s ease;
        }

        /* Add some CSS styles in the <style> section */
        .activity-log-item {
            padding: 12px;
            border-radius: 12px;
            background-color: #f8f9fa;
        }

        .activity-log-item:hover {
            background-color: #e9ecef;
        }

        /* Updated CSS for the Add Subtask button */
        .add-subtask-btn {
            font-size: 0.9em;
            color: #0d6efd;
            padding: 0;
            opacity: 0;
            /* Hidden by default */
            transition: opacity 0.3s;
            /* Smooth fade-in effect */
        }

        .task-card:hover .add-subtask-btn {
            opacity: 1;
            /* Show the button when hovering over the task */
        }

        /* New styles added for AI Generate Subtasks button */
        .ai-add-subtask-btn {
            font-size: 0.9em;
            color: #0d6efd;
            padding: 0;
            opacity: 0;
            /* Hidden by default */
            transition: opacity 0.3s;
        }

        .task-card:hover .ai-add-subtask-btn {
            opacity: 1;
        }

        /* Update these styles */
        .add-subtask-btn,
        .ai-add-subtask-btn {
            font-size: 0.9em;
            color: #0d6efd;
            padding: 0;
            display: none;
            transition: opacity 0.3s;
        }

        .task-card:hover .add-subtask-btn,
        .task-card:hover .ai-add-subtask-btn {
            display: inline-block;
        }

        /* Add this new code to handle image clicks (add it where other event listeners are defined) */
        .enlarge-image {
            transition: transform 0.2s;
        }

        .enlarge-image:hover {
            transform: scale(1.05);
        }

        .modal-dialog.modal-lg {
            max-width: 90vw;
        }

        #enlargedImage {
            object-fit: contain;
        }

        /* Add these styles to the existing <style> section */
        .hover-show-subtasks {
            display: block;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease 0.3s;
            /* Added 0.3s delay */
        }

        .task-card:hover .hover-show-subtasks {
            opacity: 1;
            max-height: 1000px;
            /* Adjust based on your needs */
        }

        /* Add a small indicator for tasks that have subtasks */
        .task-card {
            position: relative;
        }

        .task-card[data-has-subtasks="true"]::after {
            content: "•••";
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            color: #6c757d;
            font-size: 12px;
            opacity: 0.7;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1b1e;
            color: #e4e6eb;
        }

        body.dark-mode .card {
            background-color: #242526;
            border-color: #2f3031;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .navbar {
            /* background-color: #242526 !important; */
            border-bottom: 1px solid #2f3031;
        }

        body.dark-mode .task-column {
            background-color: #18191c;
            border: 1px solid #2f3031;
            border-radius: 12px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode .project-item {
            border-color: #2f3031;
            transition: all 0.2s ease;
        }

        body.dark-mode .project-item:hover {
            background-color: #3a3b3c;
        }

        body.dark-mode .project-item.active {
            background-color: #3a3b3c;
            border-left: 4px solid #2374e1;
        }

        body.dark-mode .task-card {
            background-color: #242526;
            border: 1px solid #2f3031;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
            background-color: #2c2d2e;
        }

        body.dark-mode .task-description {
            background-color: #18191c;
            border: 1px solid #2f3031;
            border-radius: 12px;
        }

        body.dark-mode .task-assignee {
            background-color: #3a3b3c;
            color: #e4e6eb;
            border-radius: 20px;
        }

        body.dark-mode .message {
            border: 1px solid #2f3031;
            border-radius: 16px;
        }

        body.dark-mode .message.ai {
            background-color: #242526;
            color: #e4e6eb;
        }

        body.dark-mode .chat-messages {
            background-color: #18191c;
            border: 1px solid #2f3031;
        }

        body.dark-mode .chat-input {
            background-color: #242526;
            border-top: 1px solid #2f3031;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode textarea {
            background-color: #3a3b3c;
            border-color: #2f3031;
            color: #e4e6eb;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus,
        body.dark-mode textarea:focus {
            background-color: #3a3b3c;
            border-color: #2374e1;
            color: #e4e6eb;
            box-shadow: 0 0 0 2px rgba(35, 116, 225, 0.2);
        }

        body.dark-mode .modal-content {
            background-color: #242526;
            border: 1px solid #2f3031;
        }

        body.dark-mode .modal-header,
        body.dark-mode .modal-footer {
            border-color: #2f3031;
        }

        body.dark-mode .table {
            color: #e4e6eb;
        }

        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: #18191c;
        }

        body.dark-mode .activity-log-item {
            background-color: #18191c;
            border: 1px solid #2f3031;
            transition: all 0.2s ease;
        }

        body.dark-mode .activity-log-item:hover {
            background-color: #242526;
        }

        body.dark-mode .subtask-item {
            background-color: #18191c;
            border: 1px solid #2f3031;
        }

        body.dark-mode .subtask-item:hover {
            background-color: #242526;
        }

        body.dark-mode .text-muted {
            color: #b0b3b8 !important;
        }

        /* Select2 Dark Mode */
        body.dark-mode .select2-container--default .select2-selection--multiple {
            background-color: #3a3b3c;
            border-color: #2f3031;
        }

        body.dark-mode .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #242526;
            border-color: #2f3031;
            color: #e4e6eb;
        }

        body.dark-mode .select2-dropdown {
            background-color: #242526;
            border-color: #2f3031;
        }

        body.dark-mode .select2-search__field {
            background-color: #3a3b3c;
            color: #e4e6eb;
        }

        body.dark-mode .select2-results__option {
            color: #e4e6eb;
        }

        body.dark-mode .select2-results__option[aria-selected=true] {
            background-color: #3a3b3c;
        }

        body.dark-mode .select2-results__option--highlighted[aria-selected] {
            background-color: #2374e1;
        }

        body.dark-mode .loading {
            background: rgba(0, 0, 0, 0.8);
        }

        /* Buttons in Dark Mode */
        body.dark-mode .btn-link {
            color: #2374e1;
        }

        body.dark-mode .btn-link:hover {
            color: #4080ff;
        }

        /* Due Date styling */
        body.dark-mode .due-date {
            background-color: #18191c;
            border: 1px solid #2f3031;
        }

        body.dark-mode .due-date.overdue {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        /* Subtask controls */
        body.dark-mode .subtask-status {
            background-color: #3a3b3c;
            border-color: #2f3031;
        }

        /* Task card hover controls */
        body.dark-mode .task-card:hover .add-subtask-btn,
        body.dark-mode .task-card:hover .ai-add-subtask-btn {
            opacity: 1;
            color: #2374e1;
        }

        /* Card headers */
        body.dark-mode .card-header {
            background-color: #242526;
            border-bottom: 1px solid #2f3031;
        }

        /* Add or update these styles in your <style> section */

        /* Tab Navigation Styles */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            margin-left: 0;
            /* Reset margin */
            margin-right: 0;
            /* Reset margin */
            width: 100%;
            /* Use 100% instead of calc */
        }

        .nav-tabs .nav-item {
            margin-bottom: -2px;
            /* Align with bottom border */
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 0.75rem 1.25rem;
            margin-right: 0.25rem;
            border-radius: 12px;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            background-color: #e9ecef;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-bottom: 2px solid #0d6efd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* New Project Button Styles */
        .nav-tabs .btn-primary {
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            /* background-color: #0d6efd; */
            border: none;
            transition: all 0.2s ease;
        }

        .nav-tabs .btn-primary:hover {
            /* background-color: #0b5ed7; */
            transform: translateY(-1px);
            /* box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2); */
        }

        .nav-tabs .btn-primary i {
            font-size: 1.1em;
        }

        /* Dark mode styles */
        body.dark-mode .nav-tabs {
            border-bottom-color: #2f3031;
            background-color: #242526;
        }

        body.dark-mode .nav-tabs .nav-link {
            color: #b0b3b8;
            /* font-weight:700; */
        }

        body.dark-mode .nav-tabs .nav-link:hover:not(.active) {
            background-color: #3a3b3c;
            color: #e4e6eb;
        }

        body.dark-mode .nav-tabs .nav-link.active {
            /* color: #2374e1; */
            background-color: #18191a;
            /* border-bottom-color: #2374e1; */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .nav-tabs {
                padding: 0.5rem;
            }

            .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* In the <style> section, update these classes: */

        .task-meta {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #6c757d;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .task-assignees {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            border-top: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .task-assignee {
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            background-color: #e9ecef;
            border-radius: 12px;
            color: #495057;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        /* Update dark mode styles for the task meta section */
        body.dark-mode .task-meta {
            color: #b0b3b8;
        }

        body.dark-mode .task-assignee {
            background-color: #3a3b3c;
            color: #e4e6eb;
        }

        /* Update the margin-top of the container-fluid */
        .container-fluid {
            margin-top: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            max-width: 100vw;
            /* Ensure container doesn't exceed viewport width */
            overflow-x: hidden;

            /* Prevent horizontal scrolling */
        }

        /* Add padding to the main content area to maintain spacing */
        .container-fluid>.row {
            padding-left: 1rem;
            padding-right: 1rem;
            margin-left: 0;
            /* Reset margin */
            margin-right: 0;
            /* Reset margin */
        }

        /* Find the existing .task-card style and update/add these styles */


        .task-card h6 {
            font-size: 1.1em;
            /* Make title size relative to base font size */
            margin-bottom: 0.5em;
            font-weight: 600;
        }

        /* Update other text elements to use relative units */
        .task-description {
            font-size: 1em;
            /* Make description text relative to base font size */
        }

        .task-meta {
            font-size: 0.875em;
            /* Make meta text relative to base font size */
        }

        .task-assignee {
            font-size: 0.875em;
            /* Make assignee text relative to base font size */
        }

        .subtask-title {
            font-size: 0.95em;
            /* Make subtask titles relative to base font size */
        }

        .due-date {
            font-size: 0.875em;
            /* Make due date text relative to base font size */
        }

        /* Update button text sizes */
        .add-subtask-btn,
        .ai-add-subtask-btn {
            font-size: 0.9em;
            /* Make button text relative to base font size */
        }

        /* Scrollable Navigation Bar */
        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            -ms-overflow-style: -ms-autohiding-scrollbar;
            white-space: nowrap;
            scrollbar-width: thin;
            padding-bottom: 5px;
            /* Prevent scrollbar from covering content */
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .nav-tabs::-webkit-scrollbar {
            height: 4px;
        }

        /* Handle for Chrome, Safari and Opera */
        .nav-tabs::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        /* Handle on hover */
        .nav-tabs::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        /* Track */
        .nav-tabs::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        /* Dark mode scrollbar */
        body.dark-mode .nav-tabs::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .nav-tabs::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Prevent tab items from wrapping */
        .nav-tabs .nav-item {
            float: none;
            display: inline-block;
            margin-bottom: 0;
        }

        /* Container adjustments */
        #projectTabs {
            position: relative;
            padding-bottom: 0.5rem;
        }
    </style>
    <style>
        /* Updated border radius styles */
        .task-column {
            border-radius: 12px;
        }

        .task-card {
            border-radius: 12px;
        }

        .message {
            border-radius: 16px;
        }

        .card {
            border-radius: 16px;
        }

        .card-header {
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
        }

        .nav-tabs .nav-link {
            border-radius: 12px;
        }

        .task-assignee {
            border-radius: 20px;
        }

        .due-date {
            border-radius: 20px;
        }

        .form-control,
        .form-select,
        .btn {
            border-radius: 10px;
        }

        .modal-content {
            border-radius: 16px;
        }

        .task-description {
            border-radius: 12px;
        }

        .subtask-item {
            border-radius: 10px;
        }

        .activity-log-item {
            border-radius: 12px;
        }

        .select2-container--default .select2-selection--multiple {
            border-radius: 10px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            border-radius: 20px;
        }

        .select2-dropdown {
            border-radius: 10px;
        }

        /* Unified Button Size and Corner Radius */
        .btn,
        .btn-sm {
            padding: 0.5rem 1rem !important;
            font-size: 1rem !important;
            border-radius: 10px !important;
        }

        .required-asterisk {
            color: #dc3545;
            margin-left: 3px;
            font-weight: bold;
        }
    </style>
    <style>
        /* Nav container with position relative for absolute positioning of buttons */
        .nav-container {
            position: relative;
            margin-bottom: 1rem;
        }

        /* Scrollable Navigation Bar */
        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            -ms-overflow-style: -ms-autohiding-scrollbar;
            white-space: nowrap;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
            position: relative;
            padding: 0 40px;
            /* Make room for scroll buttons */
            padding-top: 0.5rem;
        }

        /* Hide scrollbar completely */
        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        /* Scroll Buttons */
        .nav-scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #dee2e6;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .nav-scroll-btn:hover {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .nav-scroll-btn.left {
            left: 0;
        }

        .nav-scroll-btn.right {
            right: 0;
        }

        /* Dark mode styles for scroll buttons */
        body.dark-mode .nav-scroll-btn {
            background: rgba(54, 55, 56, 0.9);
            border-color: #2f3031;
            color: #e4e6eb;
        }

        body.dark-mode .nav-scroll-btn:hover {
            background: #3a3b3c;
        }

        /* Show buttons when nav container is hovered and has overflow */
        .nav-container:hover .nav-scroll-btn.show {
            display: flex;
        }
    </style>
</head>
<!-- Reuseable Stuff -->

<?php
function required_field()
{
    return '<span class="required-asterisk">*</span>';
}
function displayGoogleLoginBtn()
{
    // If the user is NOT logged in (no access token in session):
    if (!isset($_SESSION['access_token'])) {
        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri(  $_ENV['BASE_URL'] . '/callback.php');
        $client->addScope("email");
        $client->addScope("profile");

        $authUrl = $client->createAuthUrl();
        // Show the "Sign in with Google" button
        echo "
         <div class='text-center mt-2'>
                                    <p class='text-muted mb-1'>OR".$_ENV['BASE_URL'] . '/callback.php'."</p>
                                </div>
        <a href='$authUrl' class='btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center text-white' style='gap: 8px;'>
                <svg width='18' height='19' viewBox='0 0 16 17' fill='none' xmlns='http://www.w3.org/2000/svg'>
                  <path d='M13.8824 7.41113H8.11768V9.72516H11.4231C11.3564 10.0945 11.2134 10.4465 11.0029 10.7595C10.7925 11.0726 10.5191 11.34 10.1995 11.5454V13.051H12.1665C12.7703 12.4802 13.2452 11.7912 13.5605 11.0286C14.0327 9.88636 14.0878 8.62111 13.8824 7.41113Z' fill='#4285F4'></path>
                  <path d='M8.11765 14.4996C9.76488 14.4996 11.1599 13.9718 12.1665 13.0506L10.1995 11.545C9.58012 11.9375 8.85452 12.1373 8.11765 12.1181C7.35471 12.1088 6.61379 11.8656 5.99848 11.4224C5.38317 10.9793 4.92424 10.3584 4.68585 9.64648H2.65015V11.1856C3.15915 12.1814 3.94 13.0187 4.9055 13.604C5.87099 14.1892 6.98311 14.4992 8.11765 14.4996Z' fill='#34A853'></path>
                  <path d='M4.68589 9.64706C4.42873 8.90009 4.42873 8.09081 4.68589 7.34384V5.79395H2.65019C2.22264 6.63065 2 7.55387 2 8.49004C2 9.42621 2.22264 10.3494 2.65019 11.1861L4.68589 9.64706Z' fill='#FBBC04'></path>
                  <path d='M8.11765 4.87211C8.98898 4.85751 9.83116 5.18027 10.4621 5.77064L12.2126 4.05185C11.5147 3.43218 10.6808 2.9789 9.77551 2.72723C8.87026 2.47556 7.91812 2.43227 6.99307 2.60073C6.06803 2.76919 5.19498 3.14487 4.44177 3.69857C3.68856 4.25226 3.07548 4.96907 2.65015 5.7933L4.68585 7.34371C4.92424 6.63182 5.38317 6.0109 5.99848 5.56776C6.61379 5.12461 7.35471 4.8814 8.11765 4.87211Z' fill='#EA4335'></path>
                </svg>
                Sign in with Google
              </a>";
    }
    // Otherwise, the user IS logged in:
    else {
        echo "<a href='logout.php'>Logout</a>";
    }
}
?>

<body>
    <?php
    $auth = new Auth();
    
    // Check if the page parameter is set in the URL
    if (isset($_GET['page']) && $_GET['page'] === 'register') {
        $page = 'register';
    } else {
        $page = $_GET['page'] ?? ($auth->isLoggedIn() ? 'dashboard' : 'login');
    }

    if (!$auth->isLoggedIn() && !in_array($page, ['login', 'register'])) {
        header('Location: ?page=login');
        exit;
    }

    // Show loading indicator
    echo '<div class="loading"><div class="spinner-border text-primary" role="status"></div></div>';

    // Navigation for logged-in users
    if ($auth->isLoggedIn()):
        ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid" style="width: 98%; overflow: visible;">
                <a class="navbar-brand" href="?page=dashboard">
                    <img src="assets/images/bossgptlogo.svg" alt="Logo" style="width: 150px; height: 60px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>"
                                href="?page=dashboard">Dashboard</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <span class="navbar-text me-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>

                        <div class="d-flex align-items-center me-4">
                            <label for="fontSizeRange" class="text-light me-2 mb-0">Font Size:</label>
                            <input type="range" class="form-range" id="fontSizeRange" min="12" max="24" step="1"
                                style="width: 100px;">
                            <span id="fontSizeValue" class="text-light ms-2" style="min-width: 45px;">16px</span>
                        </div>

                        <!-- Notification Icon with Red Badge -->
                        <?php
                        $unreadNotifications = 0;
                        $notifications = [];
                        ?>
                        <div class="dropdown">
                            <input type="hidden" id="myselectedcurrentProject" value="0">
                            <button class="btn btn-outline-light position-relative" id="notificationDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                <span
                                    class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"
                                    id="notificationBadge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" id="notificationDropdownMenu"
                                aria-labelledby="notificationDropdown"
                                style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                <div class="dropdown-header p-2 pb-3 pl-5">
                                    <strong class="mb-5">Notifications</strong>
                                </div>
                                <div class="notification-list">
                                    <div class="dropdown-item text-center">Loading notifications...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle Button -->
                        <button id="toggleDarkModeBtn" class="btn btn-outline-light mx-2">Dark Mode</button>

                        <!-- Logout Form -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-outline-light">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container-fluid mt-4">
        <?php
        switch ($page) {
            case 'login':
                include_login_page();
                break;
            case 'register':
                include_register_page();
                break;
            case 'dashboard':
                include_dashboard();
                break;
            default:
                echo "<h1>404 - Page Not Found</h1>";
        }

        function include_login_page()
        {
            global $error_message;
            ?>
            <div class="d-flex justify-content-center align-items-center min-vh-100 login-page ">
                <div class="row justify-content-center w-100 position-relative">
                    <img src="assets/images/bossgptlogo.svg" alt="Logo"
                        class="position-absolute top-0 start-50 translate-middle "
                        style="margin-top: -100px; width: 15rem; height: 10rem;position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
                    <div class="col-md-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Login</h2>
                                <?php if (isset($error_message) && $_GET['page'] == 'login'): ?>
                                    <script>
                                        Toast("error", "Error", "<?php echo htmlspecialchars($error_message); ?>");
                                    </script>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                                <?php
                                displayGoogleLoginBtn();
                                ?>
                                <p class="text-center mt-3">
                                    <a href="?page=register">Need an account? Register</a>
                                </p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }

        function include_register_page()
        {
            global $error_message; // Add this line to access the error message
            ?>
            <div class="d-flex justify-content-center align-items-center min-vh-100 register-page">
                <div class="row justify-content-center w-100 position-relative">
                    <img src="assets/images/bossgptlogo.svg" alt="Logo"
                        class="position-absolute top-0 start-50 translate-middle "
                        style="margin-top: -1rem; width: 15rem; height: 10rem;position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
                    <div class="col-md-6 col-lg-4 mt-5">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Register</h2>
                                <?php if (isset($error_message) && $_GET['page'] == 'register'): ?>
                                    <script>
                                        Toast("error", "Error", "<?php echo htmlspecialchars($error_message); ?>");
                                    </script>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="fcm_token" value="0" id="fcm_token">
                                    <div class="mb-3">
                                        <label for="username" class="form-label" autocomplete="off">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label"  autocomplete="off">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label"  autocomplete="off">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Register</button>
                                </form>
                               
                                <?php
                                displayGoogleLoginBtn();
                                ?>
                                <p class="text-center mt-3">
                                    <a href="?page=login">Already have an account? Login</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }

        function include_dashboard()
        {
            ?>
            <?php $projectManager = new ProjectManager();
            $projects = $projectManager->getProjects($_SESSION['user_id']);

            // Display welcome message if set
            if (isset($_SESSION['welcome_message'])) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Toast('success', 'Welcome', '" . htmlspecialchars($_SESSION['welcome_message']) . "');
                    });
                </script>";
                unset($_SESSION['welcome_message']); // Clear the message after displaying
            }
            ?>
            <!-- Replace the existing row div with this new layout -->
            <div class="container-fluid">
                <!-- New Tab Navigation -->
                <div class="nav-container">
                    <ul class="nav nav-tabs mb-3" id="projectTabs">
                        <li class="nav-item">
                            <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal"
                                data-bs-target="#newProjectModal">
                                <i class="bi bi-plus"></i> New Project
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Main Content Area -->
                <div class="row">
                    <!-- Tasks Panel (Board) - now spans 9 columns -->
                    <div class="col-md-9">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Task Board</h5>
                                <div>
                                    <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal"
                                        data-bs-target="#activityLogModal">
                                        <i class="bi bi-clock-history"></i> Activity Log
                                    </button>

                                    <?php if (TESTING_FEATURE == 1): ?>
                                        <button type="button" class="btn btn-sm btn-info me-2" onclick='sendWelcomeEmailTest()'>
                                            <i class="bi bi-clock-history"></i> Testing Feature Button
                                        </button>
                                    <?php endif; ?>
                                    <!-- <button type="button" class="btn btn-sm btn-info me-2" onclick='sendEmailBtn()'>
                                        <i class="bi bi-clock-history"></i> Testing Feature Button
                                    </button> -->
                                    <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal"
                                        data-bs-target="#newTaskModal">
                                        <i class="bi bi-plus"></i> New Task
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                        data-bs-target="#assignUserModal">
                                        <i class="bi bi-person-plus"></i> Invite User
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="text-center">To Do</h6>
                                        <div class="task-column" id="todoTasks" data-status="todo">
                                            <!-- Todo tasks will be loaded here -->
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-center">In Progress</h6>
                                        <div class="task-column" id="inProgressTasks" data-status="in_progress">
                                            <!-- In progress tasks will be loaded here -->
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-center">Done</h6>
                                        <div class="task-column" id="doneTasks" data-status="done">
                                            <!-- Completed tasks will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Panel - now spans 3 columns -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">BossGPT Assistant <svg stroke="currentColor" fill="currentColor"
                                        stroke-width="0" viewBox="0 0 640 512" class="text-5xl" height="1.6em" width="1.6em"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M32,224H64V416H32A31.96166,31.96166,0,0,1,0,384V256A31.96166,31.96166,0,0,1,32,224Zm512-48V448a64.06328,64.06328,0,0,1-64,64H160a64.06328,64.06328,0,0,1-64-64V176a79.974,79.974,0,0,1,80-80H288V32a32,32,0,0,1,64,0V96H464A79.974,79.974,0,0,1,544,176ZM264,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,264,256Zm-8,128H192v32h64Zm96,0H288v32h64ZM456,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,456,256Zm-8,128H384v32h64ZM640,256V384a31.96166,31.96166,0,0,1-32,32H576V224h32A31.96166,31.96166,0,0,1,640,256Z">
                                        </path>
                                    </svg></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="chat-container">
                                    <div class="chat-messages" id="chatMessages">
                                        <?php if (empty($projects)): ?>
                                            <div class="welcome-guide">
                                                <div class="message-thread" id="welcomeThread">
                                                    <!-- Messages will be inserted here by JavaScript -->
                                                </div>
                                            </div>

                                            <script>
                                                // Immediately invoke function to initialize welcome messages
                                                (function initializeWelcomeMessages() {
                                                    console.log('Initializing welcome messages...'); // Debug log

                                                    const welcomeThread = document.getElementById('welcomeThread');
                                                    const chatMessages = document.getElementById('chatMessages');

                                                    if (!welcomeThread || !chatMessages) {
                                                        console.error('Required elements not found!');
                                                        return;
                                                    }

                                                    const welcomeMessages = [
                                                        {
                                                            delay: 0,
                                                            title: '👋 Welcome to BossGPT!',
                                                            content: "I'm your AI Project Manager, ready to help you organize and manage your projects efficiently."
                                                        },
                                                        {
                                                            delay: 2000,
                                                            title: '🚀 Let\'s Get Started!',
                                                            content: {
                                                                text: "To begin your journey, click the \"Create New Project\" button above. Here's what I can help you with:",
                                                                list: [
                                                                    '✨ Project planning and organization',
                                                                    '📋 Task management and tracking',
                                                                    '👥 Team collaboration',
                                                                    '📊 Progress monitoring'
                                                                ]
                                                            }
                                                        },
                                                        {
                                                            delay: 4000,
                                                            title: '💡 How I Can Help',
                                                            content: {
                                                                text: 'Once you create a project, I can:',
                                                                list: [
                                                                    '🤖 Generate task suggestions based on your project needs',
                                                                    '📅 Help manage deadlines and priorities',
                                                                    '🔍 Provide insights and recommendations',
                                                                    '💬 Answer questions about your project anytime'
                                                                ]
                                                            }
                                                        },
                                                        {
                                                            delay: 6000,
                                                            title: '🎯 Next Steps',
                                                            content: {
                                                                text: 'To get the most out of BossGPT:',
                                                                list: [
                                                                    'Click "Create New Project" and give your project a name',
                                                                    'Describe your project goals and requirements',
                                                                    'I\'ll help you break it down into manageable tasks',
                                                                    'Invite team members to collaborate'
                                                                ],
                                                                isOrdered: true
                                                            }
                                                        },
                                                        {
                                                            delay: 8000,
                                                            title: '🌟 Ready to Begin?',
                                                            content: {
                                                                text: 'Create your first project and let\'s make something amazing together!',
                                                                cta: true
                                                            }
                                                        }
                                                    ];

                                                    async function showMessage(message) {
                                                        console.log('Showing message:', message.title); // Debug log

                                                        // Show loading animation first
                                                        showChatLoading();

                                                        // Wait for loading animation
                                                        await new Promise(resolve => setTimeout(resolve, 1500));

                                                        // Hide loading animation
                                                        hideChatLoading();

                                                        // Create the message div
                                                        const messageDiv = document.createElement('div');
                                                        messageDiv.className = 'ai-message';
                                                        messageDiv.style.opacity = "0";  // Start invisible
                                                        messageDiv.style.transition = "opacity 0.5s ease-in-out"; // Smooth transition

                                                        let content = `
        <div class="ai-avatar">
            <div class="chat-loading-avatar">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 640 512" height="1.5em" width="1.5em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32,224H64V416H32A31.96166,31.96166,0,0,1,0,384V256A31.96166,31.96166,0,0,1,32,224Zm512-48V448a64.06328,64.06328,0,0,1-64,64H160a64.06328,64.06328,0,0,1-64-64V176a79.974,79.974,0,0,1,80-80H288V32a32,32,0,0,1,64,0V96H464A79.974,79.974,0,0,1,544,176ZM264,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,264,256Zm-8,128H192v32h64Zm96,0H288v32h64ZM456,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,456,256Zm-8,128H384v32h64ZM640,256V384a31.96166,31.96166,0,0,1-32,32H576V224h32A31.96166,31.96166,0,0,1,640,256Z"></path>
                </svg>
            </div>
        </div>
        <div class="message ai text-center mt-3">
            <h5>${message.title}</h5>`;

                                                        if (typeof message.content === 'string') {
                                                            content += `<p>${message.content}</p>`;
                                                        } else {
                                                            content += `<p>${message.content.text}</p>`;
                                                            if (message.content.list) {
                                                                const listType = message.content.isOrdered ? 'ol' : 'ul';
                                                                content += `<${listType}>`;
                                                                message.content.list.forEach(item => {
                                                                    content += `<li>${item}</li>`;
                                                                });
                                                                content += `</${listType}>`;
                                                            }
                                                            if (message.content.cta) {
                                                                content += `
                <div class="cta-message">
                    <button class="btn btn-primary mt-3" onclick="openNewProjectModal()">
                        <i class="fas fa-plus-circle"></i> Create New Project
                    </button>
                </div>`;
                                                            }
                                                        }

                                                        content += '</div>';
                                                        messageDiv.innerHTML = content;
                                                        welcomeThread.appendChild(messageDiv);

                                                        // Apply fade-in effect
                                                        setTimeout(() => {
                                                            messageDiv.style.opacity = "1";
                                                        }, 100);

                                                        // Scroll to bottom smoothly
                                                        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: "smooth" });
                                                    }


                                                    async function displayMessages() {
                                                        // console.log('Starting to display messages...'); // Debug log
                                                        for (const message of welcomeMessages) {
                                                            await new Promise(resolve => setTimeout(resolve, message.delay));
                                                            await showMessage(message);
                                                        }
                                                    }

                                                    // Start displaying messages
                                                    displayMessages().catch(error => console.error('Error displaying messages:', error));
                                                })();
                                            </script>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chat-input">
                                        <form id="chatForm" class="d-flex">
                                            <input type="text" class="form-control me-2" id="messageInput"
                                                placeholder="Type your message...">
                                            <button type="submit" class="btn btn-primary">Send</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Project Modal -->
            <div class="modal fade" id="newProjectModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Create New Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>
                        <div class="modal-body">
                            <form id="newProjectForm">
                                <div class="mb-3">
                                    <label for="projectTitle" class="form-label">Project
                                        Title<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="projectTitle"
                                        placeholder="Enter project title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="projectDescription"
                                        class="form-label">Description<?php echo required_field(); ?></label>
                                    <textarea class="form-control" id="projectDescription" rows="8"
                                        placeholder="Define your project in few lines."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="createProjectBtn">Create Project</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Task Modal -->
            <div class="modal fade" id="editTaskModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editTaskForm">
                                <input type="hidden" id="editTaskId">
                                <div class="mb-3">
                                    <label for="editTaskTitle" class="form-label">Task Title</label>
                                    <input type="text" class="form-control" id="editTaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editTaskDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="editTaskDueDate">
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskAssignees" class="form-label">Assigned Users</label>
                                    <select class="form-select select2-multiple" id="editTaskAssignees" multiple required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">You can select multiple users. Click to
                                        select/deselect.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskPicture" class="form-label">Task Picture</label>
                                    <input type="file" class="form-control" id="editTaskPicture" accept="image/*">
                                </div>
                                <!-- New: Remove Picture Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-danger" id="removeTaskPictureBtn">Remove
                                        Picture</button>
                                </div>
                            </form>

                            <!-- Add this new section for subtasks -->
                            <div class="mt-4">
                                <h6 class="border-bottom pb-2 d-flex justify-content-between align-items-center">
                                    Subtasks
                                    <button type="button" class="btn btn-sm btn-primary" id="addSubtaskInModalBtn">
                                        <i class="bi bi-plus"></i> Add Subtask
                                    </button>
                                </h6>
                                <div id="subtasksList" class="mt-3">
                                </div>
                            </div>

                            <!-- Existing activity log section -->
                            <div class="mt-4">
                                <h6 class="border-bottom pb-2">Task Activity Log</h6>
                                <div class="task-activity-log" style="max-height: 200px; overflow-y: auto;">
                                    <div id="taskActivityLog" class="small">
                                        <!-- Activity logs will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTaskBtn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assign User Modal -->
            <!-- <div class="modal fade" id="assignUserModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign User to Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignUserForm">
                                <div class="mb-3">
                                    <label for="userSelect" class="form-label">Select User</label>
                                    <select class="form-select" id="userSelect" required>
                                        <option value="">Select a user</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="userRole" class="form-label">Role in Project</label>
                                    <input type="text" class="form-control" id="userRole" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="assignUserBtn">Assign User</button>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="assignUserModalLabel">Invite User to Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>
                        <div class="modal-body position-relative">
                            <button class="btn btn-primary position-absolute top-5 " style="right: 10px;" id="addUserBtn">
                                <i class="bi bi-person-plus"></i> Add New User
                            </button>
                            <div id="userListContainer" class="mt-5">
                                <!-- Dynamically populated users will appear here -->
                            </div>

                            <!-- No Users Message -->
                            <div id="noUsersMessage" class="text-center py-4 d-none">
                                <p class="text-muted">No users assigned yet.</p>
                                <button class="btn btn-primary" id="addUserBtn">
                                    <i class="bi bi-person-plus"></i> Add New User
                                </button>
                            </div>
                        </div>
                        <!-- <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="inviteUserBtn">Assign User</button>
                        </div> -->
                    </div>
                </div>
            </div>
            <!-- <div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="assignUserModalLabel">Assign User to Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignUserForm">
                                <div class="mb-3">
                                    <label for="userSelect" class="form-label">Select
                                        User<?php echo required_field(); ?></label>
                                    <select class="form-select" id="userSelect" required>
                                        <option value="">Select a user</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="userRole" class="form-label">Role in
                                        Project<?php echo required_field(); ?></label>
                                    <input type="text" placeholder="Enter role (e.g., Developer, Designer, Manager)"
                                        class="form-control" id="userRole" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="assignUserBtn">Assign User</button>
                        </div>
                    </div>
                </div>
            </div> -->



            <!-- New User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addUserForm">
                                <div class="mb-3">
                                    <label for="newUserName" class="form-label">User
                                        Name<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control text-lowercase" id="newUserName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newUserEmail"
                                        class="form-label">Email<?php echo required_field(); ?></label>
                                    <input type="email" class="form-control text-lowercase" id="newUserEmail" required>
                                </div>

                                <div class="mb-3">
                                    <label for="newUserRole" class="form-label">Role<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="newUserRole"
                                        placeholder="Enter role (e.g., Developer, Designer, Manager)" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="addNewUserBtn">Save User</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- New Task Modal -->
            <div class="modal fade" id="newTaskModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Create New Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="newTaskForm">
                                <div class="mb-3">
                                    <label for="newTaskTitle" class="form-label">Task
                                        Title<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="newTaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskDescription"
                                        class="form-label">Description<?php echo required_field(); ?></label>
                                    <textarea class="form-control" id="newTaskDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskDueDate" class="form-label">Due
                                        Date<?php echo required_field(); ?></label>
                                    <input type="date" class="form-control" id="newTaskDueDate">
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskAssignees" class="form-label">Assigned
                                        Users<?php echo required_field(); ?></label>
                                    <select class="form-select select2-multiple" id="newTaskAssignees" multiple required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">You can select multiple users. Click to
                                        select/deselect.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskPicture" class="form-label">Task
                                        Picture<?php echo required_field(); ?></label>
                                    <input type="file" class="form-control" id="newTaskPicture" accept="image/*">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="createTaskBtn">Create Task</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this after the other modals -->
            <div class="modal fade" id="addSubtaskModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Subtask</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addSubtaskForm">
                                <input type="hidden" id="parentTaskId">
                                <div class="mb-3">
                                    <label for="subtaskTitle" class="form-label">Subtask Title</label>
                                    <input type="text" class="form-control" id="subtaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subtaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="subtaskDescription" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="subtaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="subtaskDueDate">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveSubtaskBtn">Add Subtask</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log Modal -->
            <div class="modal fade" id="activityLogModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered ">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Project Activity Log</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activityLogTable">
                                        <!-- Activity logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this new modal for enlarged images after your other modals -->
            <div class="modal fade" id="imageModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Task Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="enlargedImage" src="" alt="Enlarged task image"
                                style="max-width: 100%; max-height: 80vh;">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>


        document.addEventListener('DOMContentLoaded', function () {

            const currentProject = $('#myselectedcurrentProject').val();
            // console.log(currentProject)
            // alert(currentProject);
            // initPusher(currentProject);
            // First check if we're on the dashboard page
            const isDashboard = document.querySelector('.chat-container') !== null;

            if (isDashboard) {

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('pro-member') && urlParams.get('pro-member') === 'true') {
                    // Call API to update pro status
                    // alert('pro-member');
                    // return;
                    fetch('?api=update_pro_status')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Pro status updated successfully');
                                Toast('success', 'Upgrade Complete', 'Your account has been upgraded to Pro!');
                                // Remove the parameter from URL without page reload
                                const newUrl = window.location.pathname + window.location.search.replace(/[?&]pro-member=true/, '');
                                window.history.replaceState({}, document.title, newUrl);
                            } else {
                                console.error('Failed to update pro status:', data.message);
                            }
                        })
                        .catch(error => console.error('Error updating pro status:', error));
                }
                // Add debounce function at the start
                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };


                }

                // Create the debounced update function
                const debouncedUpdateTaskStatus = debounce((taskId, newStatus) => {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating task status:', error))
                        .finally(hideLoading);
                }, 500); // 500ms debounce time

                let currentProject = null;
                // Load saved project from localStorage if available
                const savedProject = localStorage.getItem('lastSelectedProject');
                if (savedProject && savedProject !== 'null') {
                    currentProject = parseInt(savedProject);
                    $('#myselectedcurrentProject').val(currentProject);
                }

                const projectsList = document.getElementById('projectsList');
                const chatMessages = document.getElementById('chatMessages');
                const chatForm = document.getElementById('chatForm');
                const messageInput = document.getElementById('messageInput');
                const createProjectBtn = document.getElementById('createProjectBtn');
                const loadingIndicator = document.querySelector('.loading');

                // Show/hide loading indicator
                function showLoading() {
                    loadingIndicator.style.display = 'flex';
                }

                function hideLoading() {
                    loadingIndicator.style.display = 'none';
                }

                // Load projects
                function loadProjects() {
                    showLoading();
                    fetch('?api=get_projects')
                        .then(response => response.json())
                        .then(data => {
                            console.log("Loaded projects: ", data.projects);
                            if (data.success) {
                                const projectTabs = document.getElementById('projectTabs');
                                // Keep the "New Project" button as the first item
                                const newProjectBtn = projectTabs.firstElementChild;
                                projectTabs.innerHTML = '';
                                projectTabs.appendChild(newProjectBtn);

                                if (!data.projects || data.projects.length === 0) {
                                    // If no projects exist, display a placeholder tab
                                    const placeholder = document.createElement('li');
                                    placeholder.className = 'nav-item';
                                    placeholder.innerHTML = '<a class="nav-link" href="#">No projects found</a>';
                                    projectTabs.appendChild(placeholder);
                                } else {
                                    data.projects.forEach(project => {
                                        const li = document.createElement('li');
                                        li.className = 'nav-item';
                                        li.innerHTML = `
                                            <a class="nav-link ${project.id === currentProject ? 'active' : ''}" 
                                               href="#" 
                                               data-id="${project.id}"
                                               title="${escapeHtml(project.title)}">
                                                <i class="bi bi-kanban"></i>
                                                ${escapeHtml(project.title)}
                                            </a>
                                        `;
                                        projectTabs.appendChild(li);
                                    });
                                }
                                // Add click handlers for the project tabs
                                document.querySelectorAll('.nav-link[data-id]').forEach(tab => {
                                    tab.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                                        tab.classList.add('active');
                                        selectProject(tab.dataset.id);
                                    });
                                });
                                return 2;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading projects:', error);
                            const projectTabs = document.getElementById('projectTabs');
                            projectTabs.innerHTML = `
                                <li class="nav-item">
                                    <div class="alert alert-danger">
                                        Unable to load projects. Please try again later.
                                    </div>
                                </li>
                            `;
                        })
                        .finally(hideLoading);
                }

                // Select project
                function selectProject(projectId, selectedProjectTitle = "") {
                    // currentProject = projectId;
                    projectId = parseInt(projectId);
                    currentProject = parseInt(projectId);
                    $('#myselectedcurrentProject').val(currentProject);

                    // Save current project to localStorage for persistence
                    localStorage.setItem('lastSelectedProject', currentProject);

                    // call to fetch notifications
                    fetchNotificationsAndOpen(false);
                    if (isNaN(projectId)) {
                        console.error('Invalid project ID:', projectId);
                        return;
                    }
                    // console.log('Selecting project:', projectId);
                    currentProject = projectId;

                    document.querySelectorAll('.project-item').forEach(item => {
                        const itemId = parseInt(item.dataset.id);
                        item.classList.toggle('active', itemId === projectId);
                    });
                    loadTasks(projectId);
                    loadChatHistory(projectId);
                    initPusher(projectId);
                }

                // Load chat history
                function loadChatHistory(projectId) {
                    showLoading();
                    fetch('?api=get_chat_history', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ project_id: projectId })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                chatMessages.innerHTML = '';
                                if (Array.isArray(data.history)) {
                                    data.history.forEach(msg => {
                                        appendMessage(msg.message, msg.sender);
                                    });
                                }
                                // console.log("object "+data.history.length)
                                const count = data.history.length;

                                if (count === 0 && $('#chatMessages').is(':empty')) {
                                    setTimeout(() => {
                                        if ($('#chatMessages').is(':empty')) {
                                            displayProjectCreationWelcomeMessages("title");
                                        }
                                    }, 2000);
                                }
                            } else {
                                throw new Error(data.message || 'Failed to load chat history');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chat history:', error);
                            chatMessages.innerHTML = `
                            <div class="alert alert-danger">
                                Failed to load chat history. Please try again later.
                            </div>
                        `;
                        })
                        .finally(hideLoading);
                }

                // Load tasks
                function loadTasks(projectId) {
                    showLoading();
                    fetch('?api=get_tasks', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ project_id: projectId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateTasksBoard(data.tasks);
                            }
                        })
                        .catch(error => console.error('Error loading tasks:', error))
                        .finally(hideLoading);
                }

                // Update tasks board
                function updateTasksBoard(tasks) {
                    const todoTasks = document.getElementById('todoTasks');
                    const inProgressTasks = document.getElementById('inProgressTasks');
                    const doneTasks = document.getElementById('doneTasks');

                    todoTasks.innerHTML = '';
                    inProgressTasks.innerHTML = '';
                    doneTasks.innerHTML = '';

                    tasks.forEach(task => {
                        const taskElement = createTaskElement(task);
                        switch (task.status) {
                            case 'todo':
                                todoTasks.appendChild(taskElement);
                                break;
                            case 'in_progress':
                                inProgressTasks.appendChild(taskElement);
                                break;
                            case 'done':
                                doneTasks.appendChild(taskElement);
                                break;
                        }
                    });

                    // Initialize drag and drop
                    initializeDragAndDrop();
                }

                // Create task element
                function createTaskElement(task) {
                    const div = document.createElement('div');
                    div.className = 'task-card';
                    div.draggable = true;
                    div.dataset.id = task.id;

                    // Add click event listener for editing
                    div.addEventListener('click', (e) => {
                        // Don't open edit modal if clicking delete button, subtask buttons, or subtask elements
                        if (!e.target.closest('.delete-task-btn') &&
                            !e.target.closest('.add-subtask-btn') &&
                            !e.target.closest('.ai-add-subtask-btn') &&
                            !e.target.closest('.subtask-item')) {
                            openEditTaskModal(task);
                        }
                    });

                    // NEW: Generate HTML for due date if it exists
                    let dueDateHtml = '';
                    if (task.due_date) {
                        const dueDateObj = new Date(task.due_date);
                        const now = new Date();
                        const overdueClass = (dueDateObj < now ? 'overdue' : '');
                        const formattedDueDate = dueDateObj.toLocaleDateString(); // you can customize the format if needed
                        dueDateHtml = `<span class="due-date ${overdueClass}"><i class="bi bi-calendar-event"></i> ${formattedDueDate}</span>`;
                    }

                    // Build subtasks section with both manual and AI add buttons
                    const subtasksHtml = (function () {
                        let html = '';
                        if (task.subtasks && task.subtasks.length > 0) {
                            // Add a class to control subtasks visibility based on task status
                            html += `<div class="subtasks mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : ''}">
                                        <div class="subtasks-list">
                                            ${task.subtasks.map(subtask => {
                                const subtaskDueDate = subtask.due_date ? new Date(subtask.due_date) : null;
                                const isOverdue = subtaskDueDate && subtaskDueDate < new Date();
                                return `
                                                    <div class="subtask-item d-flex align-items-center mb-1" data-id="${subtask.id}">
                                                        <div class="form-check me-2">
                                                            <input class="form-check-input subtask-status" type="checkbox" 
                                                                   ${subtask.status === 'done' ? 'checked' : ''}>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="subtask-title ${subtask.status === 'done' ? 'text-decoration-line-through' : ''}">${escapeHtml(subtask.title)}</div>
                                                            ${subtask.due_date ? `
                                                                <small class="text-muted due-date ${isOverdue ? 'overdue' : ''}">
                                                                    <i class="bi bi-calendar-event"></i>
                                                                    ${subtask.due_date}
                                                                </small>
                                                            ` : ''}
                                                        </div>
                                                        <button class="btn btn-sm btn-link delete-subtask-btn" data-id="${subtask.id}">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                `;
                            }).join('')}
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-plus-circle"></i> Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-robot"></i> Generate AI Subtasks
                                            </button>
                                            <button class="btn btn-sm btn-link ai-update-dates-btn" data-task-id="${task.id}">
                                                <i class="bi bi-calendar-check"></i> AI Update Dates
                                            </button>
                                        </div>
                                    </div>`;
                        } else {
                            html += `<div class="mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : ''}">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-plus-circle"></i> Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-robot"></i> Generate AI Subtasks
                                            </button>
                                        </div>
                                     </div>`;
                        }
                        return html;
                    })();

                    // Update the task picture HTML to make it clickable
                    const taskPictureHtml = task.picture ? `
                        <div class="task-picture mb-2">
                            <img src="${task.picture}" 
                                 alt="Task Picture" 
                                 class="enlarge-image"
                                 style="max-width:100%; border-radius:4px; cursor: pointer;"
                                 onerror="console.error('Image failed to load: ' + this.src); this.style.border='2px solid red';">
                        </div>
                    ` : '';

                    // Updated innerHTML now includes the due date in the task-meta section
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">${escapeHtml(task.title)}</h6>
                            <button class="btn btn-sm btn-danger delete-task-btn" data-id="${task.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        ${taskPictureHtml}
                        ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-meta">
                            ${dueDateHtml}
                            ${task.assigned_users ? `
                                <div class="task-assignees d-flex gap-1 border-0 m-0 p-0">
                                    ${Object.entries(task.assigned_users).map(([id, username]) => `
                                        <span class="task-assignee">
                                            <i class="bi bi-person-fill"></i> ${escapeHtml(username)}
                                        </span>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                        ${subtasksHtml}
                    `;

                    // Attach event listeners (existing listeners for manual subtask buttons, etc.)
                    const aiSubtaskBtn = div.querySelector('.ai-add-subtask-btn');
                    if (aiSubtaskBtn) {
                        aiSubtaskBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openAISubtaskGeneration(task);
                        });
                    }
                    // ... other event listeners such as for manual add subtask, delete, etc.

                    return div;
                }

                // Initialize drag and drop
                function initializeDragAndDrop() {
                    const taskCards = document.querySelectorAll('.task-card');
                    const taskColumns = document.querySelectorAll('.task-column');

                    taskCards.forEach(card => {
                        card.addEventListener('dragstart', () => {
                            card.classList.add('dragging');
                        });

                        card.addEventListener('dragend', () => {
                            card.classList.remove('dragging');
                            taskColumns.forEach(col => col.classList.remove('drag-over'));
                        });
                    });

                    taskColumns.forEach(column => {
                        column.addEventListener('dragenter', e => {
                            e.preventDefault();
                            column.classList.add('drag-over');
                        });

                        column.addEventListener('dragleave', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                        });

                        column.addEventListener('dragover', e => {
                            e.preventDefault();
                        });

                        column.addEventListener('drop', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                            const draggingCard = document.querySelector('.dragging');
                            if (draggingCard) {
                                const newStatus = column.dataset.status;
                                debouncedUpdateTaskStatus(draggingCard.dataset.id, newStatus);
                            }
                        });
                    });
                }

                // Update task status
                function updateTaskStatus(taskId, newStatus) {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating task status:', error))
                        .finally(hideLoading);
                }

                // Handle chat form submission
                chatForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!currentProject) {
                        // alert('Please select a project first');
                        // return;
                        showToastAndHideModal(
                            'assignUserModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }

                    const message = messageInput.value.trim();
                    if (!message) return;

                    appendMessage(message, 'user');
                    messageInput.value = '';

                    showChatLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: message,
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.function_call && data.function_call.name === 'suggest_new_tasks') {
                                    const args = JSON.parse(data.function_call.arguments);
                                    if (args.suggestions) {
                                        renderSuggestedTasks(args.suggestions);
                                    } else {
                                        appendMessage(data.message, 'ai');
                                    }
                                } else {
                                    appendMessage(data.message, 'ai');
                                }
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error sending message:', error))
                        .finally(hideChatLoading);
                });

                // Handle new project creation
                createProjectBtn.addEventListener('click', function () {
                    const title = document.getElementById('projectTitle').value.trim();
                    const description = document.getElementById('projectDescription').value.trim();

                    if (!title) {
                        // alert('Please enter a project title');
                        showToastAndHideModal(
                            '',
                            'error',
                            'Error',
                            'Please enter a project title'
                        );
                        return;
                    }

                    showLoading();
                    fetch('?api=create_project', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ title, description })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadProjects();
                                bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
                                document.getElementById('projectTitle').value = '';
                                document.getElementById('projectDescription').value = '';
                                Toast("success", "Success", "Project created successfully", "bottomCenter");

                                selectProject(data.project_id);
                            }
                        })
                        .catch(error => console.error('Error creating project:', error))
                        .finally(hideLoading);
                });

                // Add new functions for task editing
                function openEditTaskModal(task) {
                    document.getElementById('editTaskId').value = task.id;
                    document.getElementById('editTaskTitle').value = task.title;
                    document.getElementById('editTaskDescription').value = task.description || '';
                    document.getElementById('editTaskDueDate').value = task.due_date || '';

                    const editTaskAssignees = document.getElementById('editTaskAssignees');
                    $(editTaskAssignees).empty();  // Clear using jQuery

                    // Fetch all users to populate the multi-select
                    showLoading();
                    fetch('?api=get_project_users', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                data.users.forEach(user => {
                                    const newOption = new Option(
                                        `${user.username} (${user.email}) - (${user.role})`,
                                        user.id,
                                        false,
                                        false
                                    );
                                    $(editTaskAssignees).append(newOption);
                                });
                                $(editTaskAssignees).trigger('change');  // Update Select2
                                // Now fetch already assigned users for this task
                                return fetch('?api=get_task_assignees', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ task_id: task.id })
                                });
                            } else {
                                alert('Failed to load users.');
                                throw new Error('Failed to load users.');
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const assignedIds = data.assignees;
                                Array.from(editTaskAssignees.options).forEach(option => {
                                    if (assignedIds.includes(parseInt(option.value))) {
                                        option.selected = true;
                                    }
                                });
                            } else {
                                alert('Failed to load assigned users.');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading assigned users:', error);
                            alert('Error loading assigned users.');
                        })
                        .finally(hideLoading);

                    // Add this new section to load task activity log
                    showLoading();
                    fetch('?api=get_task_activity_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: task.id })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const activityLogContainer = document.getElementById('taskActivityLog');
                                if (data.logs.length > 0) {
                                    activityLogContainer.innerHTML = data.logs.map(log => `
                                    <div class="activity-log-item mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">${formatDateTime(log.created_at)}</span>
                                            <span class="text-primary">${escapeHtml(log.username)}</span>
                                        </div>
                                        <div>${escapeHtml(log.description)}</div>
                                    </div>
                                `).join('');
                                } else {
                                    activityLogContainer.innerHTML = '<p class="text-muted">No activity recorded for this task.</p>';
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading task activity log:', error);
                            document.getElementById('taskActivityLog').innerHTML =
                                '<p class="text-danger">Failed to load activity log.</p>';
                        });

                    const editTaskModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
                    editTaskModal.show();

                    // Add this new code to populate subtasks
                    const subtasksList = document.getElementById('subtasksList');
                    subtasksList.innerHTML = '';

                    if (task.subtasks && task.subtasks.length > 0) {
                        task.subtasks.forEach(subtask => {
                            const subtaskElement = document.createElement('div');
                            subtaskElement.className = 'subtask-item d-flex align-items-center mb-2 p-2 border rounded';
                            subtaskElement.dataset.id = subtask.id;

                            const dueDate = subtask.due_date ? new Date(subtask.due_date) : null;
                            const isOverdue = dueDate && dueDate < new Date();

                            subtaskElement.innerHTML = `
                                <div class="form-check me-2">
                                    <input class="form-check-input subtask-status" type="checkbox" 
                                           ${subtask.status === 'done' ? 'checked' : ''}>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="subtask-title ${subtask.status === 'done' ? 'text-decoration-line-through' : ''}">${escapeHtml(subtask.title)}</div>
                                    <small class="text-muted">${escapeHtml(subtask.description || '')}</small>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <input type="date" 
                                               class="form-control form-control-sm subtask-due-date" 
                                               value="${subtask.due_date || ''}"
                                               style="max-width: 150px;"
                                               ${subtask.status === 'done' ? 'disabled' : ''}>
                                        <small class="due-date ${isOverdue ? 'overdue' : ''}">
                                            <i class="bi bi-calendar-event"></i>
                                        </small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-link text-danger delete-subtask-btn" data-id="${subtask.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                            subtasksList.appendChild(subtaskElement);
                        });
                    } else {
                        subtasksList.innerHTML = '<p class="text-muted">No subtasks yet</p>';
                    }

                    // Add click handler for the Add Subtask button in modal
                    document.getElementById('addSubtaskInModalBtn').onclick = () => {
                        document.getElementById('parentTaskId').value = task.id;
                        document.getElementById('subtaskTitle').value = '';
                        document.getElementById('subtaskDescription').value = '';
                        document.getElementById('subtaskDueDate').value = '';
                        const addSubtaskModal = new bootstrap.Modal(document.getElementById('addSubtaskModal'));
                        addSubtaskModal.show();
                    };
                }

                // Add event listener for save button
                document.getElementById('saveTaskBtn').addEventListener('click', function () {
                    const taskId = document.getElementById('editTaskId').value;
                    const title = document.getElementById('editTaskTitle').value.trim();
                    const description = document.getElementById('editTaskDescription').value.trim();
                    const dueDate = document.getElementById('editTaskDueDate').value || null; // Convert empty string to null
                    const assignees = $('#editTaskAssignees').val().map(value => parseInt(value));
                    const pictureInput = document.getElementById('editTaskPicture');

                    function sendUpdateTask(pictureData) {
                        let payload = {
                            task_id: taskId,
                            title: title,
                            description: description,
                            due_date: dueDate, // This will now be null instead of empty string
                            assignees: assignees
                        };
                        if (pictureData !== null) {
                            payload.picture = pictureData;
                        }
                        fetch('?api=update_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
                                    loadTasks(currentProject);
                                } else {
                                    throw new Error(data.message || 'Failed to update task');
                                }
                            })
                            .catch(error => {
                                console.error('Error updating task:', error);
                                alert('Failed to update task. Please try again.');
                            })
                            .finally(hideLoading);
                    }
                    if (pictureInput.files && pictureInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const base64String = e.target.result;
                            sendUpdateTask(base64String);
                        };
                        reader.readAsDataURL(pictureInput.files[0]);
                    } else {
                        sendUpdateTask(null);
                    }
                });

                // Add event listener for "Assign User" button in the modal
                // const inviteUserBtn = document.getElementById('inviteUserBtn');
                // inviteUserBtn.addEventListener('click', function () {
                //     alert('inviteUserBtn clicked');
                //     // alert('assignUserBtn clicked');
                //     // return;
                //     // const userSelect = document.getElementById('userSelect');
                //     // const userId = userSelect.value;
                //     // const userRole = document.getElementById('userRole').value.trim();
                //     if (!currentProject) {
                //         Toast('error', 'Error', 'Please select a project first', 'bottomCenter');
                //         return;
                //     }
                //     // if (!userId || !userRole) {
                //     //     // alert('Please select a user and enter a role');
                //     //     Toast('error', 'Error', 'Please select a user and enter a role', 'bottomCenter');
                //     //     return;
                //     // }


                //     showLoading();
                //     fetch('?api=assign_user_to_project', {
                //         method: 'POST',
                //         headers: { 'Content-Type': 'application/json' },
                //         body: JSON.stringify({
                //             project_id: currentProject,
                //             user_id: userId,
                //             role: userRole
                //         })
                //     })
                //         .then(response => response.json())
                //         .then(data => {
                //             if (data.success) {
                //                 alert("User assigned successfully");
                //                 bootstrap.Modal.getInstance(document.getElementById('assignUserModal')).hide();
                //                 // Optionally clear the form fields
                //                 document.getElementById('userSelect').value = '';
                //                 document.getElementById('userRole').value = '';
                //                 // You may want to refresh context or notify the user
                //             } else {
                //                 throw new Error(data.message || 'Failed to assign user');
                //             }
                //         })
                //         .catch(error => {
                //             console.error('Error assigning user:', error);
                //             alert('Failed to assign user. Please try again.');
                //         })
                //         .finally(hideLoading);
                // });

                // Populate the user dropdown when the "Assign User" modal is shown

                const assignUserModal = document.getElementById('assignUserModal');
                assignUserModal.addEventListener('shown.bs.modal', function () {
                    if (!currentProject) {
                        showToastAndHideModal(
                            'assignUserModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }
                    showLoading();
                    fetch(`?api=get_all_project_users&project_id=${currentProject}`)
                        .then(async response => {
                            const text = await response.text();
                            console.log('Raw response:', text); // Debug log
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                throw new Error('Invalid server response');
                            }
                        })
                        // mujtabatesting1
                        .then(data => {
                            if (data.success) {
                                const userListContainer = document.getElementById('userListContainer');
                                const noUsersMessage = document.getElementById("noUsersMessage");
                                userListContainer.innerHTML = '<h6 >Users</h6>';


                                if (data.users.length === 0) {
                                    noUsersMessage.classList.remove("d-none");
                                    userListContainer.classList.add("d-none");
                                } else {
                                    noUsersMessage.classList.add("d-none");
                                    userListContainer.classList.remove("d-none");

                                    data.users.forEach((user) => {
                                        const userCard = document.createElement("div");
                                        userCard.className = "d-flex justify-content-between align-items-center p-2 mb-2 border rounded dark-primaryborder ";
                                        let actionButtons = "<div>";

                                        //                                     actionButtons += `
                                        //     <button class="btn btn-sm btn-outline-primary editUser" data-id="${user.id}">
                                        //         <i class="bi bi-pencil"></i>
                                        //     </button>

                                        // `;
                                        if (user.role != "Creator") {
                                            actionButtons += `
           
            <button class="btn btn-sm btn-outline-danger deleteUser" data-id="${user.id}">
                <i class="bi bi-trash"></i>
            </button>
        `;
                                        }
                                        actionButtons += "</div>";
                                        userCard.innerHTML = `
                    <div>
                        <strong>${user.username}</strong>
                        <span class="text-muted">(${user.role})</span>
                    </div>
                    ${actionButtons}
                `;

                                        userListContainer.appendChild(userCard);
                                    });
                                }

                                // Add project users
                                // data.users.forEach(user => {
                                //     userSelect.innerHTML += `<option value="${user.id}">${user.username} (${user.email}) - ${user.role}</option>`;
                                // });

                                // // Add "Add New User" option at the end
                                // userSelect.innerHTML += '<option value="new">+ Add New User</option>';
                            } else {
                                throw new Error(data.message || 'Failed to load users');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading users:', error);
                            showToastAndHideModal(
                                'assignUserModal',
                                'error',
                                'Error',
                                'Failed to load users'
                            );
                        })
                        .finally(hideLoading);
                });
                // userListContainer.addEventListener("click", function (e) {
                //     if (e.target.closest(".deleteUser")) {
                //         const userId = e.target.closest(".deleteUser").getAttribute("data-id");
                //         // users = users.filter((user) => user.id !== parseInt(userId));
                //         // renderUserList();
                //     }
                // });

                // Edit User (You can expand this to open an edit modal)
                // userListContainer.addEventListener("click", function (e) {
                //     if (e.target.closest(".editUser")) {
                //         const userId = e.target.closest(".editUser").getAttribute("data-id");
                //         // const user = users.find((u) => u.id === parseInt(userId));
                //         // renderUserList();
                //         // alert(`Edit User: ${user.name}`); // Replace with actual edit functionality
                //     }
                // });


                userListContainer.addEventListener("click", function (e) {
                    const deleteBtn = e.target.closest(".deleteUser");
                    const editBtn = e.target.closest(".editUser");
                    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));

                    if (deleteBtn) {
                        const userId = deleteBtn.getAttribute("data-id");
                        const projectId = currentProject;
                        const userDiv = deleteBtn.closest(".d-flex");
                        if (!userDiv) return;
                        // Extract username from the <strong> tag
                        const userName = userDiv.querySelector("strong")?.textContent.trim() || "Unknown User";
                        if (confirm(`Are you sure you want to remove ${userName} ?`)) {
                            fetch(`?api=delete_user&user_id=${userId}&project_id=${projectId}&user_name=${encodeURIComponent(userName)}`, {
                                method: "DELETE"
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        userDiv.remove();
                                        // showToast('success', 'User deleted successfully');
                                        Toast('success', 'Success', 'User deleted successfully');
                                    } else {
                                        Toast('error', 'Error', 'Failed to delete user');
                                    }
                                })
                                .catch(error => console.error("Error deleting user:", error));
                        }
                    }

                    // if (editBtn) {
                    //     const userId = editBtn.getAttribute("data-id");
                    //     const username = editBtn.getAttribute("data-username");
                    //     const email = editBtn.getAttribute("data-email");
                    //     const role = editBtn.getAttribute("data-role");
                    //     // Populate the Add/Edit Modal with User Data
                    //     document.getElementById("newUserName").value = username;
                    //     document.getElementById("newUserEmail").value = email;
                    //     document.getElementById("newUserRole").value = role;

                    //     // Show the modal
                    //     addUserModal.show();
                    // }
                });

                // Handle "New User" selection
                // document.getElementById('userSelect').addEventListener('change', function () {
                //     if (this.value === 'new') {
                //         new bootstrap.Modal(document.getElementById('addUserModal')).show();
                //         this.value = ''; // Reset dropdown selection
                //     }
                // });
                $('#addUserBtn').click(function () {
                    $('#addUserModal').modal('show');
                });
                document.getElementById('addNewUserBtn').addEventListener('click', function () {
                    const username = document.getElementById('newUserName').value.trim();
                    const email = document.getElementById('newUserEmail').value.trim();
                    const role = document.getElementById('newUserRole').value.trim();

                    if (!username || !email || !role) {
                        Toast('error', 'Error', 'Please fill in all fields', 'bottomCenter');
                        return;
                    }
                    if (!email.includes('@')) {
                        Toast('error', 'Error', 'Please enter a valid email', 'bottomCenter');

                        return;
                    }

                    showLoading();
                    fetch('?api=create_or_assign_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            username: username,
                            email: email,
                            project_id: currentProject,
                            role: role
                        })
                    })
                        .then(async response => {
                            const text = await response.text();
                            try {
                                const data = JSON.parse(text);
                                return data;
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                console.error('Raw response was:', text);
                                throw new Error(`Server response error: ${text}`);
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                // Close the add user modal
                                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();

                                // Clear the form
                                document.getElementById('newUserName').value = '';
                                document.getElementById('newUserEmail').value = '';
                                document.getElementById('newUserRole').value = '';

                                // Refresh the user list in the assign user modal
                                // const userSelect = document.getElementById('userSelect');
                                // // Remove the existing "Add New User" option if it exists
                                // const addNewOption = Array.from(userSelect.options).find(option => option.value === 'new');
                                // if (addNewOption) {
                                //     addNewOption.remove();
                                // }

                                // Add the new user option
                                // const newOption = new Option(
                                //     `${username} (${email}) - ${role}`,
                                //     data.data.user_id
                                // );
                                // userSelect.add(newOption);

                                // Show different messages based on whether it's a new or existing user
                                console.log(data)
                                const successMessage = data.data.is_new_user
                                    ? "User created and assigned successfully! An invite has been sent along with login credentials."
                                    : "User assigned to project successfully!";

                                showToastAndHideModal('addUserModal', 'success', "Success", successMessage);
                                bootstrap.Modal.getInstance(document.getElementById('assignUserModal')).hide();
                            } else {
                                throw new Error(data.message || 'Failed to create or assign user');
                            }
                        })
                        .catch(error => {
                            console.error('Error creating/assigning user:', error);
                            Toast('error', 'Error', `Error: ${error.message}`, 'bottomCenter');
                        })
                        .finally(hideLoading);
                });
                // Helper functions
                function appendMessage(message, sender) {
                    const div = document.createElement('div');
                    div.className = `message ${sender}`;
                    div.textContent = message;
                    chatMessages.appendChild(div);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                function escapeHtml(unsafe) {
                    return unsafe
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                // Initial load
                loadProjects();
                // selectProject(5);


                // Font size management
                const fontSizeRange = document.getElementById('fontSizeRange');
                const fontSizeValue = document.getElementById('fontSizeValue');
                const mainContent = document.body;

                // Load saved font size or use default
                const savedFontSize = localStorage.getItem('preferredFontSize') || '16';
                fontSizeRange.value = savedFontSize;
                fontSizeValue.textContent = `${savedFontSize}px`;
                mainContent.style.fontSize = `${savedFontSize}px`;

                // Update font size when slider changes
                fontSizeRange.addEventListener('input', function () {
                    const newSize = this.value;
                    fontSizeValue.textContent = `${newSize}px`;
                    mainContent.style.fontSize = `${newSize}px`;
                    localStorage.setItem('preferredFontSize', newSize);
                });

                // Initialize Select2 for the edit task assignees
                $('#editTaskAssignees').select2({
                    placeholder: 'Select users to assign',
                    allowClear: true,
                    width: '100%'
                });

                // Fix for Select2 in Bootstrap modal
                $('#editTaskModal').on('shown.bs.modal', function () {
                    $('#editTaskAssignees').select2({
                        dropdownParent: $('#editTaskModal')
                    });
                });

                // Initialize Select2 for the new task assignees
                $('#newTaskAssignees').select2({
                    placeholder: 'Select users to assign',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#newTaskModal')
                });

                // Populate users when new task modal is shown
                const newTaskModal = document.getElementById('newTaskModal');
                newTaskModal.addEventListener('shown.bs.modal', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        // bootstrap.Modal.getInstance(newTaskModal).hide();
                        return;
                    }

                    showLoading();
                    fetch('?api=get_project_users', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const assigneeSelect = document.getElementById('newTaskAssignees');
                                $(assigneeSelect).empty();  // Clear using jQuery
                                data.users.forEach(user => {
                                    const newOption = new Option(
                                        `${user.username} (${user.role})`,
                                        user.id,
                                        false,
                                        false
                                    );
                                    $(assigneeSelect).append(newOption);
                                });
                                $(assigneeSelect).trigger('change');  // Update Select2
                            } else {
                                alert('Failed to load project users');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading project users:', error);
                            alert('Error loading project users');
                        })
                        .finally(hideLoading);
                });

                // Handle new task creation
                document.getElementById('createTaskBtn').addEventListener('click', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }

                    const title = document.getElementById('newTaskTitle').value.trim();
                    const description = document.getElementById('newTaskDescription').value.trim();
                    const dueDate = document.getElementById('newTaskDueDate').value;
                    const assignees = $('#newTaskAssignees').val().map(value => parseInt(value));
                    const pictureInput = document.getElementById('newTaskPicture');
                    function sendCreateTask(pictureData) {
                        fetch('?api=create_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                project_id: currentProject,
                                title: title,
                                description: description,
                                due_date: dueDate,
                                assignees: assignees,
                                picture: pictureData
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('newTaskTitle').value = '';
                                    document.getElementById('newTaskDescription').value = '';
                                    document.getElementById('newTaskDueDate').value = '';
                                    $('#newTaskAssignees').val(null).trigger('change');
                                    document.getElementById('newTaskPicture').value = '';
                                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                                    loadTasks(currentProject);
                                } else {
                                    throw new Error(data.message || 'Failed to create task');
                                }
                            })
                            .catch(error => {
                                console.error('Error creating task:', error);
                                alert('Failed to create task. Please try again.');
                            })
                            .finally(hideLoading);
                    }
                    if (pictureInput.files && pictureInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const base64String = e.target.result;
                            sendCreateTask(base64String);
                        };
                        reader.readAsDataURL(pictureInput.files[0]);
                    } else {
                        sendCreateTask(null);
                    }
                });

                // Add the deleteTask function
                function deleteTask(taskId) {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: 'deleted'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to delete task');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting task:', error);
                            alert('Failed to delete task. Please try again.');
                        })
                        .finally(hideLoading);
                }

                // Add these functions inside the DOMContentLoaded event listener
                function openAddSubtaskModal(taskId) {
                    document.getElementById('parentTaskId').value = taskId;
                    document.getElementById('subtaskTitle').value = '';
                    document.getElementById('subtaskDescription').value = '';
                    document.getElementById('subtaskDueDate').value = '';
                    const modal = new bootstrap.Modal(document.getElementById('addSubtaskModal'));
                    modal.show();
                }

                document.getElementById('saveSubtaskBtn').addEventListener('click', function () {
                    const taskId = document.getElementById('parentTaskId').value;
                    const title = document.getElementById('subtaskTitle').value.trim();
                    const description = document.getElementById('subtaskDescription').value.trim();
                    const dueDate = document.getElementById('subtaskDueDate').value;

                    if (!currentProject) {
                        alert('Please select a project first');
                        return;
                    }

                    if (!title) {
                        alert('Please enter a subtask title');
                        return;
                    }

                    showLoading();
                    fetch('?api=create_subtask', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            title: title,
                            description: description,
                            due_date: dueDate
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('addSubtaskModal')).hide();
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to create subtask');
                            }
                        })
                        .catch(error => {
                            console.error('Error creating subtask:', error);
                            alert('Failed to create subtask. Please try again.');
                        })
                        .finally(hideLoading);
                });

                function updateSubtaskStatus(subtaskId, status) {
                    showLoading();
                    fetch('?api=update_subtask_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            subtask_id: subtaskId,
                            status: status
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating subtask status:', error))
                        .finally(hideLoading);
                }

                function deleteSubtask(subtaskId) {
                    showLoading();
                    fetch('?api=delete_subtask', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            subtask_id: subtaskId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error deleting subtask:', error))
                        .finally(hideLoading);
                }

                // Add this inside the DOMContentLoaded event listener, where other modal handlers are defined
                // Activity Log Modal handler
                const activityLogModal = document.getElementById('activityLogModal');
                activityLogModal.addEventListener('show.bs.modal', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'activityLogModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }
                    loadActivityLog();
                });

                function loadActivityLog() {
                    showLoading();
                    fetch('?api=get_activity_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const tbody = document.getElementById('activityLogTable');
                                tbody.innerHTML = data.logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>${escapeHtml(log.username)}</td>
                                    <td>${escapeHtml(log.action_type)}</td>
                                    <td>${escapeHtml(log.description)}</td>
                                </tr>
                            `).join('');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading activity log:', error);
                            alert('Failed to load activity log');
                        })
                        .finally(hideLoading);
                }

                function formatDateTime(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString();
                }

                function openAISubtaskGeneration(task) {
                    if (!currentProject) {
                        alert('Please select a project first.');
                        return;
                    }
                    // Construct a prompt that instructs the AI to generate subtasks including the correct project and task IDs
                    const prompt = `
 Please generate a list of detailed subtasks for the following task using AI.
 Project ID: ${currentProject}
 Task ID: ${task.id}
 Task Details:
 Title: ${task.title}
 Description: ${task.description || 'No description provided'}
 ${task.due_date ? 'Due Date: ' + task.due_date : ''}
 
 Consider the overall project context and the existing tasks to ensure the subtasks are relevant, actionable, and detailed.
 Return the response using a function call named "create_multiple_subtasks" with parameters: task_id and subtasks (each having title, description, and due_date).
                    `;
                    showLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: prompt,
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                appendMessage(data.message, 'ai');
                                loadTasks(currentProject);
                            } else {
                                alert('Failed to generate subtasks: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error generating AI subtasks:', error);
                            alert('Error generating AI subtasks.');
                        })
                        .finally(hideLoading);
                }

                // Add this binding for subtask status changes:
                $(document).on('change', '.subtask-status', function () {
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    const newStatus = $(this).is(':checked') ? 'done' : 'todo';
                    updateSubtaskStatus(subtaskId, newStatus);
                });

                // Add event delegation for delete subtask buttons
                $(document).on('click', '.delete-subtask-btn', function (e) {
                    e.stopPropagation(); // Prevent task card click event
                    if (confirm('Are you sure you want to delete this subtask?')) {
                        const subtaskId = $(this).data('id');
                        deleteSubtask(subtaskId);
                    }
                });

                function renderSuggestedTasks(suggestions) {
                    const suggestionsContainer = document.createElement('div');
                    suggestionsContainer.className = 'suggestions-container mt-3';
                    suggestionsContainer.innerHTML = '<h6 class="mb-3">Suggested Tasks & Features</h6>';
                    suggestions.forEach(suggestion => {
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.className = 'suggestion-item border p-2 mb-2';
                        suggestionDiv.innerHTML = `
                            <strong>${escapeHtml(suggestion.title)}</strong><br>
                            <span class="my-2">${escapeHtml(suggestion.description)}</span><br>
                            ${suggestion.due_date ? `<i class="bi bi-calendar-event"></i><em class="text-muted"> Due: ${escapeHtml(suggestion.due_date)}</em>` : ''}<br>
                            <button class="btn btn-sm btn-primary mt-1">Add Task</button>
                         `;
                        suggestionDiv.querySelector('button').addEventListener('click', () => {
                            addSuggestedTask(suggestion);
                        });
                        suggestionsContainer.appendChild(suggestionDiv);
                    });
                    chatMessages.appendChild(suggestionsContainer);
                }

                function addSuggestedTask(suggestion) {
                    if (!currentProject) {
                        alert('Please select a project first');
                        return;
                    }
                    showLoading();
                    fetch('?api=create_task', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject,
                            title: suggestion.title,
                            description: suggestion.description,
                            due_date: suggestion.due_date
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                alert('Failed to add task: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error adding suggested task:', error);
                            alert('Error adding suggested task');
                        })
                        .finally(hideLoading);
                }

                // Add this new code to handle image clicks (add it where other event listeners are defined)
                document.addEventListener('click', function (e) {
                    if (e.target.classList.contains('enlarge-image')) {
                        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                        const enlargedImage = document.getElementById('enlargedImage');
                        enlargedImage.src = e.target.src;
                        imageModal.show();
                    }
                });

                // Add this event delegation handler before the closing of isDashboard block
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.delete-task-btn')) {
                        e.stopPropagation(); // Prevent task card click event
                        const taskId = e.target.closest('.delete-task-btn').dataset.id;
                        if (confirm('Are you sure you want to delete this task?')) {
                            deleteTask(taskId);
                        }
                    }
                });

                // Add the deleteTask function here as well
                function deleteTask(taskId) {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: 'deleted'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to delete task');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting task:', error);
                            alert('Failed to delete task. Please try again.');
                        })
                        .finally(hideLoading);
                }

                // New event listener for removing task picture
                document.getElementById('removeTaskPictureBtn').addEventListener('click', function () {
                    if (!confirm('Are you sure you want to remove the picture from this task?')) {
                        return;
                    }
                    const taskId = document.getElementById('editTaskId').value;
                    if (!taskId) {
                        alert('Task ID is missing.');
                        return;
                    }
                    showLoading();
                    fetch('?api=remove_task_picture', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: taskId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Task picture removed successfully.');
                                // Clear the file input value
                                document.getElementById('editTaskPicture').value = '';
                            } else {
                                alert('Failed to remove task picture: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error removing task picture:', error);
                            alert('Error removing task picture.');
                        })
                        .finally(() => hideLoading());
                });

                // Add this new function to handle AI date updates
                function updateSubtaskDatesWithAI(task) {
                    if (!currentProject) {
                        alert('Please select a project first.');
                        return;
                    }

                    const mainDueDate = task.due_date ? new Date(task.due_date.replace(/\s*<[^>]*>/g, '')).toISOString().split('T')[0] : null;

                    const prompt = `
STRICT DEADLINE ASSIGNMENT REQUEST:
Task: "${task.title}"
Current Date: ${new Date().toISOString().split('T')[0]}
PARENT TASK DUE DATE: ${mainDueDate || 'Not set'}

CRITICAL CONSTRAINTS:
1. PARENT TASK DUE DATE IS ABSOLUTE DEADLINE
2. ALL subtask deadlines MUST be BEFORE ${mainDueDate || 'parent due date'} 
3. Last subtask deadline must have at least 24 hours buffer before parent deadline
4. NO EXCEPTIONS to these constraints

SCHEDULING REQUIREMENTS:
- Create extremely aggressive timeline with NO SLACK
- Distribute subtasks across available time window
- Earlier dates preferred - create urgency
- Consider task dependencies (earlier subtasks first)
- Account for task complexity in duration
- NO FLEXIBLE or LOOSE deadlines
- Maximum pressure for quick completion

Current Subtasks (Must maintain IDs):
${task.subtasks.map(st => `- ID: ${st.id}, Title: ${st.title} (Current due: ${st.due_date || 'None'})`).join('\n')}

CRITICAL RESPONSE FORMAT INSTRUCTIONS:
You must respond using ONLY this exact format:
{
    "task_id": ${task.id},
    "subtasks": [
        {
            "id": <existing_subtask_id>,
            "due_date": "YYYY-MM-DD"
        }
    ]
}

VALIDATION RULES:
1. ALL due_dates MUST be <= ${mainDueDate || 'parent due date'}
2. ALL due_dates MUST be >= current date
3. MUST maintain existing subtask IDs
4. MUST use YYYY-MM-DD format
5. NO additional text or explanations
6. Dates MUST create high pressure timeline

ERROR: If parent due date exists and any subtask date would be after it, FAIL.
`;

                    showLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: prompt,
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log('AI Response:', data); // Debug log

                            if (data.success) {
                                try {
                                    // Try to parse the JSON response from the message
                                    const message = data.message || '';
                                    let jsonStr = message;

                                    // Try to extract JSON if it's wrapped in other text
                                    const jsonMatch = message.match(/\{[\s\S]*\}/);
                                    if (jsonMatch) {
                                        jsonStr = jsonMatch[0];
                                    }

                                    const args = JSON.parse(jsonStr);
                                    console.log('Parsed args:', args); // Debug log

                                    if (!args.task_id || !Array.isArray(args.subtasks)) {
                                        throw new Error('Response missing required fields');
                                    }

                                    // Update each subtask's date individually
                                    const updatePromises = args.subtasks.map(subtask => {
                                        if (!subtask.id || !subtask.due_date) {
                                            console.error('Invalid subtask data:', subtask);
                                            return Promise.reject('Invalid subtask data');
                                        }
                                        return fetch('?api=update_subtask', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                subtask_id: subtask.id,
                                                due_date: subtask.due_date
                                            })
                                        }).then(response => response.json());
                                    });

                                    return Promise.all(updatePromises);
                                } catch (e) {
                                    console.error('Error parsing AI response:', e);
                                    console.error('AI response data:', data);
                                    throw new Error(`Invalid AI response structure: ${e.message}`);
                                }
                            } else {
                                throw new Error(data.message || 'Failed to get AI response');
                            }
                        })
                        .then(() => {
                            appendMessage("Subtask dates have been aggressively updated to ensure tight deadlines.", 'ai');
                            loadTasks(currentProject);
                        })
                        .catch(error => {
                            console.error('Error updating subtask dates:', error);
                            alert('Error updating subtask dates: ' + error.message);
                        })
                        .finally(hideLoading);
                }

                // Add this event delegation for the new AI Update Dates button
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.ai-update-dates-btn')) {
                        e.stopPropagation();
                        const taskId = e.target.closest('.ai-update-dates-btn').dataset.taskId;
                        const taskCard = e.target.closest('.task-card');
                        const taskData = {
                            id: taskId,
                            title: taskCard.querySelector('h6').textContent,
                            due_date: taskCard.querySelector('.due-date')?.textContent.trim(),
                            subtasks: Array.from(taskCard.querySelectorAll('.subtask-item')).map(item => ({
                                id: item.dataset.id,
                                title: item.querySelector('.subtask-title').textContent,
                                due_date: item.querySelector('.due-date')?.textContent.trim(),
                                description: '' // Maintain existing description
                            }))
                        };
                        updateSubtaskDatesWithAI(taskData);
                    }
                });

                // Add these event handlers for subtask management in the modal
                $(document).on('change', '.subtask-status', function () {
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    const newStatus = $(this).is(':checked') ? 'done' : 'todo';
                    updateSubtaskStatus(subtaskId, newStatus);

                    // Update the visual state
                    const titleElement = $(this).closest('.subtask-item').find('.subtask-title');
                    titleElement.toggleClass('text-decoration-line-through', $(this).is(':checked'));
                });

                $(document).on('click', '.delete-subtask-btn', function (e) {
                    e.preventDefault();
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    if (confirm('Are you sure you want to delete this subtask?')) {
                        deleteSubtask(subtaskId);
                        $(this).closest('.subtask-item').remove();
                        if ($('#subtasksList').children().length === 0) {
                            $('#subtasksList').html('<p class="text-muted">No subtasks yet</p>');
                        }
                    }
                });

                // Add some CSS styles
                const style = document.createElement('style');
                style.textContent = `
                    #subtasksList .subtask-item {
                        background-color: var(--bs-light);
                        transition: all 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover {
                        background-color: var(--bs-light);
                        transform: translateX(4px);
                    }

                    body.dark-mode #subtasksList .subtask-item {
                        background-color: #2c2d2e;
                    }

                    body.dark-mode #subtasksList .subtask-item:hover {
                        background-color: #3a3b3c;
                    }

                    #subtasksList .delete-subtask-btn {
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover .delete-subtask-btn {
                        opacity: 1;
                    }
                `;
                document.head.appendChild(style);

                // Add these styles to the existing style element
                style.textContent += `
                    .subtask-due-date {
                        opacity: 0.7;
                        transition: all 0.2s ease;
                    }

                    .subtask-due-date:hover,
                    .subtask-due-date:focus {
                        opacity: 1;
                    }

                    body.dark-mode .subtask-due-date {
                        background-color: #3a3b3c;
                        border-color: #2f3031;
                        color: #e4e6eb;
                    }

                    body.dark-mode .subtask-due-date:hover,
                    body.dark-mode .subtask-due-date:focus {
                        background-color: #4a4b4c;
                        border-color: #2374e1;
                    }

                    .subtask-due-date:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                    }
                `;

            } else {
                // We're on the login or register page
                // Only initialize necessary elements
                const loadingIndicator = document.querySelector('.loading');

                if (loadingIndicator) {
                    function showLoading() {
                        loadingIndicator.style.display = 'flex';
                    }

                    function hideLoading() {
                        loadingIndicator.style.display = 'none';
                    }
                }
            }

            // Font size management - keep this outside the dashboard check since it applies to all pages
            const fontSizeRange = document.getElementById('fontSizeRange');
            const fontSizeValue = document.getElementById('fontSizeValue');

            if (fontSizeRange && fontSizeValue) {
                const mainContent = document.body;
                const savedFontSize = localStorage.getItem('preferredFontSize') || '16';
                fontSizeRange.value = savedFontSize;
                fontSizeValue.textContent = `${savedFontSize}px`;
                mainContent.style.fontSize = `${savedFontSize}px`;

                fontSizeRange.addEventListener('input', function () {
                    const newSize = this.value;
                    fontSizeValue.textContent = `${newSize}px`;
                    mainContent.style.fontSize = `${newSize}px`;
                    localStorage.setItem('preferredFontSize', newSize);
                });
            }

            // --- Dark Mode Toggle Code ---
            const toggleDarkModeBtn = document.getElementById('toggleDarkModeBtn');
            if (toggleDarkModeBtn) {
                // Check localStorage to apply dark mode preference on load
                if (localStorage.getItem('preferredDarkMode') === 'false') {
                    // document.body.classList.add('dark-mode');
                    // toggleDarkModeBtn.textContent = 'Light Mode';
                    document.body.classList.remove('dark-mode');
                    toggleDarkModeBtn.textContent = 'Dark Mode';
                } else {
                    document.body.classList.add('dark-mode');
                    toggleDarkModeBtn.textContent = 'Light Mode';
                    // toggleDarkModeBtn.textContent = 'Dark Mode';
                }

                // Toggle dark mode when the button is clicked
                toggleDarkModeBtn.addEventListener('click', function () {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('preferredDarkMode', 'true');
                        toggleDarkModeBtn.textContent = 'Light Mode';
                    } else {
                        localStorage.setItem('preferredDarkMode', 'false');
                        toggleDarkModeBtn.textContent = 'Dark Mode';
                    }
                });
            }
            // --- End Dark Mode Toggle Code ---

            // Add mouse movement tracking for task card hover effects
            document.addEventListener('mousemove', function (e) {
                document.querySelectorAll('.task-card:hover').forEach(function (card) {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });
            });

            // Add this new event handler after the other subtask-related event handlers:
            $(document).on('change', '.subtask-due-date', function () {
                const subtaskId = $(this).closest('.subtask-item').data('id');
                const newDueDate = $(this).val();

                showLoading();
                fetch('?api=update_subtask', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subtask_id: subtaskId,
                        due_date: newDueDate
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the visual state if needed
                            const dueDate = new Date(newDueDate);
                            const isOverdue = dueDate < new Date();
                            $(this).siblings('.due-date').toggleClass('overdue', isOverdue);
                        } else {
                            // If there's an error (like due date after parent task), revert the change
                            alert(data.message || 'Failed to update due date');
                            loadTasks(currentProject); // Reload to get the original state
                        }
                    })
                    .catch(error => {
                        console.error('Error updating subtask due date:', error);
                        alert('Failed to update due date. Please try again.');
                        loadTasks(currentProject); // Reload to get the original state
                    })
                    .finally(hideLoading);
            });

            // Auto-load the saved project if available
            if (isDashboard) {
                // Initialize projects
                loadProjects();
                // After projects are loaded, select the saved project if available
                setTimeout(() => {
                    const savedProject = localStorage.getItem('lastSelectedProject');
                    if (savedProject && savedProject !== 'null' && savedProject !== '0') {
                        const projectId = parseInt(savedProject);

                        const savedProjectTab = document.querySelector(`.nav-link[data-id="${projectId}"]`);
                        // alert("sdf"+projectId)
                        if (savedProjectTab) {
                            document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                            savedProjectTab.classList.add('active');
                            selectProject(projectId);
                        }
                    }
                }, 500); // Small delay to ensure projects are loaded
            }
        }); // End of DOMContentLoaded

        function sendWelcomeEmailTest() {

            fetch('?api=send_welcome_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: "taimoorhamza1999@gmail.com",
                    username: "User123",
                    tempPassword: "TempPass123",
                    projectId: 48
                })
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(error => {
                    console.error("Error:", error.message);
                    alert("Failed to send email.");
                });
        }
        function sendNotificationTest(projectId = 42, title = "DFs Title", body = "DFs Body") {
            alert("Sending Notification Test");
            fetch('?api=send_notification_project', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send_notification_project',
                    project_id: projectId,
                    title: title,
                    body: body
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("✅ Notification sent successfully:", data.message);
                    } else {
                        console.error("❌ Error sending notification:", data.error);
                    }
                })
                .catch(error => console.error("❌ Request failed:", error));
        }

        initializeChatLoading();

    </script>

    <!-- Firebase -->
    <?php if (isset($page) && $page === 'register'): ?>
        <script type="module">
            // Import the functions you need from the SDKs you need
            import { initializeApp } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-app.js";
            import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-messaging.js";

            // TODO: Add SDKs for Firebase products that you want to use
            // https://firebase.google.com/docs/web/setup#available-libraries
            // Your web app's Firebase configuration
            const firebaseConfig = {
                apiKey: "AIzaSyAPByoVru7fAR1Mk8_y8AW73vWVRwEDma4",
                authDomain: "bossgpt-367ab.firebaseapp.com",
                projectId: "bossgpt-367ab",
                storageBucket: "bossgpt-367ab.firebasestorage.app",
                messagingSenderId: "1078128619253",
                appId: "1:1078128619253:web:edf3e5f2306ab349191fbc"
            };

            // Initialize Firebase
            const app = initializeApp(firebaseConfig);
            const messaging = getMessaging(app);

            // Register service worker first
            navigator.serviceWorker.register("./assets/js/sw.js")
                .then((registration) => {
                    console.log('Service worker registered:', registration);

                    // Then get the messaging token
                    return getToken(messaging, {
                        serviceWorkerRegistration: registration,
                        vapidKey: 'BNvQzVggQ4j6sTH5W6sxSa4K8Q-K0BhPn2tJT1en85dcp1P46M4EFJjoxe_uJI3PnEgQ06LO2mgv0SvcpBfyL00'
                    });
                })
                .then((currentToken) => {
                    if (currentToken) {
                        console.log("FCM Token:", currentToken);
                        // Set the token in the hidden input
                        document.getElementById('fcm_token').value = currentToken;                        // Here you can send the token to your server
                    } else {
                        console.log('No FCM token available. Request permission to generate one.');
                        // You might want to request permission here
                    }
                })
                .catch((err) => {
                    console.error('Service worker registration or token retrieval failed:', err);
                });
        </script>
    <?php endif; ?>
    <!-- Pusher -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        // Pusher.logToConsole = true;

        // var pusher = new Pusher('83a162dc942242f89892', {
        //   cluster: 'ap2'
        // });

        // channel is project id
        // var channel = pusher.subscribe('my-channel');
        // channel.bind('my-event', function(data) {
        //     // Increment the notification count
        //     const badge = document.getElementById('notificationBadge');
        //     let currentCount = parseInt(badge.textContent) || 0;
        //     badge.textContent = currentCount + 1;
        //     badge.style.display = "inline-block";

        //     // Append new notification to dropdown
        //     appendNotification(data);

        // });
        // channel.bind('user_added', function(data) {
        //     // alert("User Added"); 
        //     // Toast("success", "Success", "New Notification received successfully",'topRight');

        // });

        // channel.bind('task_created', function (data) {
        //     alert(data.message); // Show notification
        // });

        // channel.bind('task_updated', function (data) {
        //     alert(data.message); // Show notification
        // });

        function appendNotification(notification) {
            const notificationList = document.querySelector(".notification-list");
            const isDarkMode = document.body.classList.contains('dark-mode');
            const actionType = getActionTypeDisplay(notification.action_type);
            const timeAgo = formatTimeAgo(notification.created_at);
            const icon = getNotificationIcon(notification.action_type);

            const newNotification = `
            <div class="dropdown-item border-bottom py-3">
                <div class="d-flex align-items-start">
                    <div class="notification-icon ${isDarkMode ? actionType.darkBgColor : actionType.bgColor} rounded-circle me-3"
                        style="padding:0.6rem 0.8rem !important;">
                        <i class="bi ${icon} ${actionType.textColor}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge ${isDarkMode ? actionType.darkBgColor : actionType.bgColor} ${actionType.textColor} rounded-pill px-3 py-1">
                                ${actionType.text}
                            </span>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                ${timeAgo}
                            </small>
                        </div>
                        <div class="notification-text" style="font-size: 0.8rem;">
                            ${notification.description}
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Prepend the new notification to the list
            notificationList.insertAdjacentHTML('afterbegin', newNotification);
        }


        function initPusher(currentProject) {
            Pusher.logToConsole = true;

            var pusher = new Pusher('83a162dc942242f89892', {
                cluster: 'ap2'
            });
            // Enable pusher logging - don't include this in production

            var channel = pusher.subscribe('project_' + currentProject);

            channel.bind('project_created', function (data) {
                appendNotification(data);
                Toast("success", "Project Created", data.message, 'topRight');
            });
            channel.bind('user_assigned', function (data) {
                appendNotification(data);
                Toast("success", "User Joined", data.message, 'topRight');
            });
            channel.bind('task_created', function (data) {
                appendNotification(data);
                Toast("success", "Task Created", data.message, 'topRight');
            });
            channel.bind('task_updated', function (data) {
                appendNotification(data);
                Toast("success", "Success", data.message, 'topRight');
            });
        }
    </script>

</body>

</html>
<!-- # Training parameters -->