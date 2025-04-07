<?php
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
                
                // Update plant growth in the garden
                $gardenManager = new GardenManager();
                $gardenManager->updatePlantStage($data['task_id'], $data['status']);
                
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

                    $userManager = new UserManager();
                    $result = $userManager->createOrAssignUser(
                        $data['email'],
                        $data['project_id'] ?? null,
                        $data['role'] ?? null,
                        $_ENV['BASE_URL']
                    );

                    // Set timezone to match your server/application timezone
                    // date_default_timezone_set('Asia/Manila'); // Adjust this to your timezone

                    // Send Notification
                    $result = Notification::send('project_' . $data['project_id'], 'user_assigned', [
                        'message' => 'New User joined as the ' . $data['role'] . ' in the project',
                        'action_type' => 'user_assigned',
                        'description' => 'New User joined as the ' . $data['role'] . ' in the project',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
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

            case 'get_fcm_reminders':
                $data = json_decode(file_get_contents("php://input"), true);
                $fcm_token = $data['fcm_token'];
                $stmt = $db->prepare("SELECT * FROM fcm_reminders_temp WHERE fcm_token = ?");
                $stmt->execute([$fcm_token]);
                $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['success' => true, 'reminders' => $reminders];
                break;
            case 'delete_fcm_reminders':
                $data = json_decode(file_get_contents("php://input"), true);
                $fcm_token = $data['fcm_token'];
                $id = $data['reminder_id'];
                $stmt = $db->prepare("DELETE FROM fcm_reminders_temp WHERE fcm_token = ? and id = ?");
                $stmt->execute([$fcm_token, $id]);
                $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['success' => true, 'reminders' => $reminders];
                break;
            case 'get_users':
                $stmt = $db->query("SELECT id, username, email FROM users ORDER BY username ASC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['success' => true, 'users' => $users];
                break;
            case 'delete_user':
                if (!isset($_POST['user_id']) || !isset($_POST['project_id']) || !isset($_POST['user_name'])) {
                    $response = ['success' => false, 'message' => 'User ID, Project ID, and User Name are required'];
                    break;
                }

                $user_id = $_POST['user_id'];
                $project_id = $_POST['project_id'];
                $user_name = $_POST['user_name'];

                // Delete user
                $stmt = $db->prepare("DELETE FROM project_users WHERE user_id = ?");
                $success = $stmt->execute([$user_id]);

                // Nullify invited_by references if necessary
                $stmt = $db->prepare("UPDATE users SET invited_by = NULL WHERE id = ?");
                $stmt->execute([$user_id]);

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

                    // Send Notification
                    $notificationResult = Notification::send('project_' . $project_id, 'user_removed', [
                        'message' => $user_name . ' has been removed from the project',
                        'action_type' => 'user_removed',
                        'description' => $user_name . ' has been removed from the project',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $response = [
                        'success' => true,
                        'message' => $user_name . ' removed successfully',
                        'notification' => $notificationResult
                    ];

                    error_log("Notification Result: " . json_encode($notificationResult));
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

                $gardenManager = new GardenManager();
                $taskSize = isset($data['size']) ? $data['size'] : 'medium'; // Default to medium if size not specified
                $gardenManager->plantSeed($task_id, $_SESSION['user_id'], $taskSize);

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
                // if (!isset($data['email']) || !isset($data['username']) || !isset($data['tempPassword'])) {
                //     $response = [
                //         'success' => false,
                //         'message' => "Error: Missing required fields (email, username, tempPassword)."
                //     ];
                //     // Set the Content-Type header to application/json
                //     header('Content-Type: application/json');
                //     echo json_encode($response);
                //     exit;
                // }
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                // Simulate getting project users (Example users)

                $userManager = new UserManager();
                try {
                    $notificationManager = new NotificationManager($userManager);
                    $response = $notificationManager->sendProjectNotification(42, "New User Added", "New User Added successfully");
                    $response = [
                        'success' => true,
                        'message' => $response
                    ];
                    // exit;
                    // if ($emailSent) {
                    //     $response = [
                    //         'success' => true,
                    //         'message' => "An invite has been sent along with login credentials."
                    //     ];
                    // } else {
                    //     $response = [
                    //         'success' => false,
                    //         'message' => "Failed to send the invite."
                    //     ];
                    // }
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


            // Check if user is a pro member
            case 'check_pro_status':
                $auth = new Auth();
                $user = $auth->getCurrentUser();

                if (!$user) {
                    $response = [
                        'success' => false,
                        'is_pro' => false,
                        'message' => 'User not logged in'
                    ];
                } else {
                    $is_pro = isset($user['pro_member']) && $user['pro_member'] == 1;
                    $response = [
                        'success' => true,
                        'is_pro' => $is_pro,
                        'payment_link' => $_ENV['STRIPE_PAYMENT_LINK'],
                        'invited_by' => $user['invited_by']
                    ];
                }
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