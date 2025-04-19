<?php
// Required for direct task updates
require_once __DIR__ . '/ProjectManager.php';
require_once __DIR__ . '/GoogleCalendarManager.php';

class AIAssistant
{
    private $api_key;
    private $db;
    private $calendar;

    public function __construct()
    {
        $this->api_key = OPENAI_API_KEY;
        $this->db = Database::getInstance()->getConnection();
        $this->calendar = new GoogleCalendarManager();
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

            // Get the AI tone from the parameter instead of $_REQUEST
            $aiTone = isset($_REQUEST['aiTone']) ? $_REQUEST['aiTone'] : 'demanding';

            // Check if this is a calendar-related request
            if ($this->isCalendarRequest($message)) {
                return $this->handleCalendarRequest($message);
            }

            // Check if this is a task status update request
            $enhanced_message = $message;
            if (preg_match('/move\s+task\s+[\'"]?([^\'"]*)[\'"]\s+from\s+todo\s+to\s+in\s+progress/i', $message, $matches)) {
                $task_title = $matches[1];
                error_log("Detected task status update request for: $task_title");

                // Find the task ID
                $task_id = $this->findTaskIdByTitle($project_id, $task_title);
                if ($task_id) {
                    $enhanced_message = "$message (Task ID: $task_id)";
                    error_log("Enhanced message with task ID: $enhanced_message");
                }
            }

            // Map tone values to different system messages
            $toneMessages = [
                'friendly' => "You are a friendly and supportive project manager. Your communication style is warm, encouraging, and focused on team morale. Use phrases like 'Let's try', 'We can', 'I think', and offer positive reinforcement. Be supportive and understanding while still maintaining progress.\n\nWhen responding:\n1. Be warm and approachable\n2. Offer encouragement\n3. Be understanding of challenges\n4. Focus on teamwork\n5. Celebrate progress\n6. Use collaborative language\n7. Balance goals with wellbeing\n8. Give constructive feedback\n\nProject Context:\n",

                'professional' => "You are a professional and methodical project manager. Your communication style is clear, balanced, and focused on process and quality. Use phrases like 'We should', 'The data shows', 'Our timeline requires', and emphasize best practices. Be logical and systematic while maintaining high standards.\n\nWhen responding:\n1. Be clear and objective\n2. Present information logically\n3. Remain neutral in tone\n4. Focus on process and quality\n5. Refer to data and evidence\n6. Use professional terminology\n7. Emphasize consistency\n8. Give balanced feedback\n\nProject Context:\n",

                'demanding' => "You are a demanding and results-driven executive manager. Your communication style is direct, authoritative, and focused on performance and deadlines. Use phrases like 'I expect', 'You need to', 'This must be done', and emphasize urgency and accountability. Be stern but fair, always pushing for excellence.\n\nWhen responding:\n1. Be direct and concise\n2. Set clear expectations and deadlines\n3. Show zero tolerance for excuses\n4. Emphasize accountability\n5. Push for high performance\n6. Use authoritative language\n7. Focus on results and metrics\n8. Give direct feedback\n\nProject Context:\n",

                'casual' => "You are a casual and relatable project coordinator. Your communication style is conversational, laid-back, and focused on maintaining a positive atmosphere. Use phrases like 'Hey team', 'Let's chat about', 'How's it going with', and keep things light but productive. Be approachable while still getting things done.\n\nWhen responding:\n1. Use casual, everyday language\n2. Be conversational in tone\n3. Show empathy and understanding\n4. Focus on human connection\n5. Balance work with team dynamics\n6. Use relaxed expressions\n7. Keep communication open\n8. Give friendly feedback\n\nProject Context:\n"
            ];

            // Set the content based on the tone or default to demanding if tone is not recognized
            $systemContent = ($toneMessages[$aiTone] ?? $toneMessages['demanding']) . $formatted_context;

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemContent
                ]
            ];

            // Append previous chat history...

            $messages[] = [
                'role' => 'user',
                'content' => $enhanced_message
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
                $enhanced_message,
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

            // Check if the message is a JSON response for subtask dates and clean it
            if ($ai_message && preg_match('/\{\s*"task_id":\s*\d+,\s*"subtasks":\s*\[\s*\{\s*"id":\s*\d+,\s*"due_date":/i', $ai_message)) {
                // This appears to be a raw JSON response for subtask dates
                // We'll store it but let the front-end handle formatting
                $ai_message = "Your subtask dates have been updated successfully. The schedule has been optimized for maximum efficiency.";
            }

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
                error_log("==== AI ASSISTANT: UPDATING TASK ====");
                error_log("Task ID: " . $arguments['task_id']);

                if (isset($arguments['status'])) {
                    error_log("Requested status change to: " . $arguments['status']);

                    // Get task info
                    $taskStmt = $this->db->prepare("SELECT title, project_id, status FROM tasks WHERE id = ?");
                    $taskStmt->execute([$arguments['task_id']]);
                    $task = $taskStmt->fetch();
                    error_log("Current task status: " . ($task ? $task['status'] : 'unknown'));

                    // Use ProjectManager to update status directly
                    try {
                        $projectManager = new ProjectManager();
                        if ($arguments['status'] == 'in_progress') {
                            error_log("Using updateTaskStatus for in_progress");
                            $projectManager->updateTaskStatus($arguments['task_id'], 'in_progress');
                            error_log("Status updated successfully");
                        } else {
                            error_log("Using normal status update for: " . $arguments['status']);
                            $projectManager->updateTaskStatus($arguments['task_id'], $arguments['status']);
                        }

                        // Log the activity regardless of other updates
                        if ($task) {
                            $this->logActivity(
                                $task['project_id'],
                                'task_status_updated',
                                "AI Assistant updated status of task '{$task['title']}' from '{$task['status']}' to '{$arguments['status']}'"
                            );
                        }

                        break; // Exit the case if we handled the status update
                    } catch (Exception $e) {
                        error_log("Error updating task status: " . $e->getMessage());
                        // Continue with normal update as fallback
                    }
                }

                // Normal update path continues
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
                // Only handle status updates here if we didn't handle it with ProjectManager above
                if (isset($arguments['status']) && count($updates) > 0) {
                    $updates[] = "status = ?";
                    $params[] = $arguments['status'];
                    error_log("Adding status update to batch update");
                }
                if (isset($arguments['due_date'])) {
                    $updates[] = "due_date = ?";
                    $params[] = $arguments['due_date'];
                }

                if (!empty($updates)) {
                    $params[] = $arguments['task_id'];
                    $sql = "UPDATE tasks SET " . implode(", ", $updates) . " WHERE id = ?";
                    error_log("SQL query: " . $sql . " with parameters: " . implode(", ", $params));
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);

                    // Log activity for status changes
                    if (isset($arguments['status'])) {
                        // Get task info for activity log
                        $taskStmt = $this->db->prepare("SELECT title, project_id FROM tasks WHERE id = ?");
                        $taskStmt->execute([$arguments['task_id']]);
                        $task = $taskStmt->fetch();

                        if ($task) {
                            $this->logActivity(
                                $task['project_id'],
                                'task_status_updated',
                                "AI Assistant updated status of task '{$task['title']}' to " . $arguments['status']
                            );
                        }
                    }
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

    // Add this function if it doesn't exist
    private function logActivity($project_id, $action_type, $description)
    {
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            $stmt = $this->db->prepare(
                "INSERT INTO activity_log (project_id, user_id, action_type, description) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $project_id,
                $user_id,
                $action_type,
                $description
            ]);

            error_log("Activity logged: $action_type - $description");
            return true;
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            // Don't throw exception to avoid breaking functionality
            return false;
        }
    }

    // Add helper function to find task ID by name
    private function findTaskIdByTitle($project_id, $task_title)
    {
        try {
            // Use LIKE query for fuzzy matching
            $stmt = $this->db->prepare("
                SELECT id, title FROM tasks 
                WHERE project_id = ? AND title LIKE ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$project_id, "%$task_title%"]);
            $tasks = $stmt->fetchAll();

            if (count($tasks) > 0) {
                error_log("Found task with title '$task_title': " . $tasks[0]['id']);
                return $tasks[0]['id'];
            }

            error_log("No task found with title like '$task_title'");
            return null;
        } catch (Exception $e) {
            error_log("Error finding task ID by title: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the message is a calendar-related request
     */
    private function isCalendarRequest($message)
    {
        $calendarKeywords = [
            'schedule',
            'book',
            'appointment',
            'meeting',
            'calendar',
            'event',
            'remind',
            'reminder',
            'set up',
            'arrange',
            'plan'
        ];

        $message = strtolower($message);
        foreach ($calendarKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle calendar-related requests
     */
    private function handleCalendarRequest($message)
    {
        try {
            if (!$this->calendar->isAuthenticated()) {
                return [
                    'message' => "Please connect your Google Calendar first. I'll help you schedule events once you're connected.",
                    'action' => 'connect_calendar'
                ];
            }

            // Use GPT to extract event details from the message
            $eventDetails = $this->extractEventDetails($message);

            // If no date is specified, default to tomorrow
            if (!isset($eventDetails['date']) || empty($eventDetails['date'])) {
                $eventDetails['date'] = date('Y-m-d', strtotime('+1 day'));
            }

            // Default time to 3:00 PM if not specified
            $startTime = $eventDetails['date'] . ' 15:00:00';
            $endTime = $eventDetails['date'] . ' 16:00:00';

            // Create the event with fixed time
            $event = new Google_Service_Calendar_Event([
                'summary' => $eventDetails['summary'],
                'description' => $eventDetails['description'],
                'start' => [
                    'dateTime' => date('c', strtotime($startTime)),
                    'timeZone' => 'Asia/Dubai',
                ],
                'end' => [
                    'dateTime' => date('c', strtotime($endTime)),
                    'timeZone' => 'Asia/Dubai',
                ],
            ]);

            $calendarId = 'primary';
            $service = new Google_Service_Calendar($this->calendar->getClient());
            $createdEvent = $service->events->insert($calendarId, $event);

            return [
                'message' => "✅ Event scheduled successfully!\n\n" .
                    "📅 Event: {$eventDetails['summary']}\n" .
                    "📆 Date: " . date('l, F j, Y', strtotime($eventDetails['date'])) . "\n" .
                    "⏰ Time: 3:00 PM - 4:00 PM\n" .
                    "📝 Description: {$eventDetails['description']}\n\n" .
                    "View in Calendar: " . $createdEvent->htmlLink,
                'event' => $eventDetails,
                'success' => true
            ];

        } catch (Exception $e) {
            error_log("Calendar Request Error: " . $e->getMessage());
            return [
                'message' => "Sorry, I encountered an error while scheduling your event: " . $e->getMessage(),
                'error' => true
            ];
        }
    }

    /**
     * Extract event details from user message using GPT
     */
    private function extractEventDetails($message)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a calendar assistant. Extract event details from the user's message and respond ONLY with a JSON object in this exact format: {\"summary\": \"event title\", \"description\": \"event description\", \"date\": \"YYYY-MM-DD\"} - If date is not specified, omit the date field. Keep the description brief and relevant."
            ],
            [
                'role' => 'user',
                'content' => $message
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
                'model' => 'gpt-4',
                'messages' => $messages,
                'temperature' => 0.7
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

        $eventDetails = json_decode($result['choices'][0]['message']['content'], true);
        if (!$eventDetails) {
            throw new Exception("Failed to parse event details from AI response");
        }

        // Set defaults if missing
        if (!isset($eventDetails['summary']) || empty($eventDetails['summary'])) {
            $eventDetails['summary'] = 'New Event';
        }
        if (!isset($eventDetails['description']) || empty($eventDetails['description'])) {
            $eventDetails['description'] = 'Event scheduled via AI Assistant';
        }

        return $eventDetails;
    }
}