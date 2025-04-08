<?php

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
    public function getProjectTasks($project_id)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT t.*, 
                        GROUP_CONCAT(DISTINCT ta.user_id) as assigned_user_ids,
                        GROUP_CONCAT(DISTINCT u.username) as assigned_usernames,
                        g.stage as plant_stage,
                        g.plant_type,
                        g.size as plant_size
                 FROM tasks t
                 LEFT JOIN task_assignees ta ON t.id = ta.task_id
                 LEFT JOIN users u ON ta.user_id = u.id
                 LEFT JOIN user_garden g ON t.id = g.task_id
                 WHERE t.project_id = ?
                 GROUP BY t.id
                 ORDER BY t.created_at DESC"
            );
            $stmt->execute([$project_id]);
            $tasks = $stmt->fetchAll();

            // Process tasks to create proper structure for assigned users
            foreach ($tasks as &$task) {
                if ($task['assigned_user_ids'] && $task['assigned_usernames']) {
                    $userIds = explode(',', $task['assigned_user_ids']);
                    $usernames = explode(',', $task['assigned_usernames']);
                    $task['assigned_users'] = array_combine($userIds, array_map(function($username) {
                        return [$username];
                    }, $usernames));
                } else {
                    $task['assigned_users'] = [];
                }
                unset($task['assigned_user_ids']);
                unset($task['assigned_usernames']);
            }

            return $tasks;
        } catch (Exception $e) {
            error_log("Get Project Tasks Error: " . $e->getMessage());
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
                    ) AS subtasks,
                    g.stage as plant_stage,
                    g.plant_type,
                    g.size as plant_size
                FROM tasks t
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN users u ON ta.user_id = u.id
                LEFT JOIN user_garden g ON t.id = g.task_id AND g.user_id = ?
                WHERE t.project_id = ? 
                AND t.status != 'deleted'
                GROUP BY t.id, t.project_id, t.title, t.description, t.picture, t.status, t.due_date, t.created_at, t.updated_at, g.stage, g.plant_type, g.size
                ORDER BY t.created_at DESC"
            );

            $stmt->execute([$_SESSION['user_id'], $project_id]);
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
                
                // Add garden info if available
                $task['garden'] = [
                    'stage' => $task['plant_stage'] ?? null,
                    'plant_type' => $task['plant_type'] ?? null,
                    'size' => $task['plant_size'] ?? null
                ];
                
                // Remove the raw garden fields
                unset($task['plant_stage']);
                unset($task['plant_type']);
                unset($task['plant_size']);
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

?>