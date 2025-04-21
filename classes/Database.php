<?php

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
            // throw new Exception("Unable to connect to database. Please try again later.");
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
                last_login DATETIME NULL,
                invited_by INT DEFAULT NULL,
                pro_plan BOOLEAN DEFAULT FALSE,
                fcm_token VARCHAR(255) NULL,
                verification_token VARCHAR(255) NULL,
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
                status ENUM('read', 'unread') DEFAULT 'unread',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            // Add user_garden table
            "CREATE TABLE IF NOT EXISTS user_garden (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                task_id INT,
                stage VARCHAR(20) DEFAULT 'seed',
                plant_type VARCHAR(20),
                size VARCHAR(10) DEFAULT 'medium',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (task_id) REFERENCES tasks(id)
            )",
            // Add fcm_reminders_temp table
            "CREATE TABLE IF NOT EXISTS fcm_reminders_temp (
                id INT PRIMARY KEY AUTO_INCREMENT,
                fcm_token TEXT NOT NULL,
                title VARCHAR(255),
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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