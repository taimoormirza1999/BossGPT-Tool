<?php

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