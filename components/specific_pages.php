<script>

    function initPusher(currentProject) {
        // Disable pusher logging
        Pusher.logToConsole = false;

        var pusher = new Pusher("83a162dc942242f89892", {
            cluster: "ap2",
        });

        var channel = pusher.subscribe("project_" + currentProject);

        channel.bind("project_created", function (data) {
            appendNotification(data);
            Toast("success", "Project Created", data.message, "topRight");
        });
        channel.bind("user_assigned", function (data) {
            appendNotification(data);
            Toast("success", "User Joined", data.message, "topRight");
        });
        channel.bind("task_created", function (data) {
            appendNotification(data);
            Toast("success", "Task Created", data.message, "topRight");
        });
        channel.bind("task_updated", function (data) {
            appendNotification(data);
            Toast("success", "Success", data.message, "topRight");
        });
    }

    function getLastSelectedProject() {
        if (userId) { // Check if userId is available
            return localStorage.getItem(`lastSelectedProject_${userId}`);
        }
        return null; // No user logged in or session expired
    }

    let currentProject = null;
    // Load saved project from localStorage if available
    const savedProject = getLastSelectedProject();
    if (savedProject && savedProject !== 'null') {
        currentProject = parseInt(savedProject);
        $('#myselectedcurrentProject').val(currentProject);
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
            dueDateHtml = `<span class="due-date ${overdueClass}"><?php echo getCalendarIcon(); ?> ${formattedDueDate}</span>`;
        }

        // Build subtasks section with both manual and AI add buttons
        const subtasksHtml = (function () {
            let html = '';
            if (task.subtasks && task.subtasks.length > 0) {
                // Add a class to control subtasks visibility based on task status
                html += `<div class="subtasks mt-2 hover-show-subtasks">
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
                                        <div class="d-flex gap-2 mt-2 justify-content-center">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-update-dates-btn" data-task-id="${task.id}">
                                                AI Update Dates
                                            </button>
                                             <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-robot"></i> Generate AI Subtasks
                                            </button>
                                        </div>
                                    </div>`;
            } else {
                html += `<div class="mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : 'hover-show-subtasks'}">
                                        <div class="d-flex gap-2 justify-content-center">
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
        // Get plant stage based on task status and garden data
        const getPlantImage = (task) => {
            // console.log(task);
            // If we have garden data, use it
            if (task.garden.plant_stage && task.garden.plant_type) {
                switch (task.garden.plant_stage) {
                    case 'dead': return 'dead.png';
                    case 'sprout': return 'seed.png';
                    case 'growing': return 'flower3.png';
                    case 'tree':
                        // Return the specific tree type image
                        return `${task.garden.plant_type}.png`;
                    default: return 'seed.png';
                }
            }

            // Fallback to status-based images if no garden data
            switch (task.status) {
                case 'todo': return 'seed.png';
                case 'in_progress': return 'flower3.png';
                case 'done':
                    // Default to treelv3 if no plant_type specified
                    return task.garden.plant_type ? `${task.garden.plant_type}.png` : 'treelv3.png';
                default: return 'seed.png';
            }
        };

        const plantBallHtml = `<div class="plant-ball-container" >
    <img src="assets/images/garden/plant-ball.png" alt="Plant Ball" class="plant-ball" 
    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Plant Stage"
    style="
    height: 42px;
    box-shadow: 0 0 15px 5px rgba(255, 255, 150, 0.2);
    border-radius: 50%;
"  >
    <img src="assets/images/garden/${getPlantImage(task)}" 
         alt="Plant" 
         class="inner-plant" 
         style="
    height: 35px;
    position: absolute;
    left: 50%;
    transform: translate(-50%);
">
</div>`;

        div.innerHTML = `
                    <div class="task-card-labels mb-2">
                            ${task.status === 'todo' ? '<span class="task-label label-red"></span>' : ''}
                            ${task.status === 'in_progress' ? '<span class="task-label label-orange"></span>' : ''}
                            ${task.status === 'done' ? '<span class="task-label label-green"></span>' : ''}
                            <span class="task-label label-blue"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0 task_title">${escapeHtml(task.title)}</h6>
                            <button class="btn btn-sm btn-danger delete-task-btn" data-id="${task.id}">
                                <?php echo getTrashIcon(); ?>
                            </button>
                        </div>
                        ${taskPictureHtml}
                        ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-meta">
                            ${dueDateHtml}
                            ${plantBallHtml}
                            ${task.assigned_users ? `
                                <div class="task-assignees d-flex gap-1 border-0 m-0 p-0">
                                    ${Object.entries(task.assigned_users).map(([id, username]) => {
            // Generate background color based on user ID
            // First user gets rgba(61, 127, 41, 1), others get varied hues
            const hues = [61, 200, 0, 280, 30, 320, 170, 60, 340, 250]; // Green, blue, red, purple, orange, etc.
            const index = parseInt(id) % hues.length;
            const hue = hues[index];
            const bgColor = `hsl(${hue}, 50%, 40%)`; // Consistent saturation and lightness
            return `
                                        <span class="task-assignee text-capitalize" style="background-color: ${bgColor}; color: white;">
                                            ${escapeHtml(username[0].charAt(0).toUpperCase() + username[1].charAt(0).toUpperCase())}
                                        </span>
                                        `;
        }).join('')}
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

    let loadingIndicator = document.querySelector('.loading');
    function appendMessage(message, sender) {
        // Check if message is a raw JSON response for subtask dates
        if (sender === 'ai' && typeof message === 'string' &&
            (message.includes('"task_id":') && message.includes('"subtasks":') && message.includes('"due_date":'))) {
            try {
                // Parse the JSON and format it using our formatter
                const jsonMatch = message.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    const jsonStr = jsonMatch[0];
                    const jsonData = JSON.parse(jsonStr);

                    // Try to find the task details
                    let taskDetails = null;
                    if (jsonData.task_id) {
                        const taskCards = document.querySelectorAll(`.task-card[data-id="${jsonData.task_id}"]`);
                        if (taskCards.length > 0) {
                            const taskCard = taskCards[0];
                            taskDetails = {
                                id: jsonData.task_id,
                                title: taskCard.querySelector('h6').textContent,
                                subtasks: Array.from(taskCard.querySelectorAll('.subtask-item')).map(item => ({
                                    id: item.dataset.id,
                                    title: item.querySelector('.subtask-title').textContent
                                }))
                            };
                        }
                    }

                    // Replace the message with formatted HTML
                    message = formatAIResponse(jsonData, taskDetails);
                }
            } catch (e) {
                console.error('Error formatting JSON response:', e);
                message = '<div class="alert alert-success">Subtask dates have been updated successfully.</div>';
            }
        }

        const div = document.createElement('div');
        div.className = sender === 'user' ? 'user-message d-flex align-items-start justify-content-end' : 'ai-message d-flex align-items-start';
        const iconImage = `<?php echo getIconImage(0, 0, '1.93rem'); ?>`;

        if (sender === 'user') {
            // Get username from session
            const username = '<?php echo isset($_SESSION["username"]) ? $_SESSION["username"] : ""; ?>';
            const initials = username.split(' ').map(word => word[0].toUpperCase()).join('').slice(0, 2) + username.split(' ').map(word => word[1].toUpperCase()).join('').slice(0, 2);

            div.innerHTML = `
                            <div class="message user me-2">${message}</div>
                            <div class="user-avatar">
                                <div class="chat-loading-avatar d-flex align-items-center justify-content-center" style="background-color: rgba(8, 190, 139, 1);">
                                    ${initials}
                                </div>
                            </div>
                        `;
        } else {
            div.innerHTML = `
                            <div class="ai-avatar">
                                <div class="chat-loading-avatar">
                                    ${iconImage}
                                </div>
                            </div>
                            <div class="message ai">${message}</div>
                        `;
        }

        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function selectProject(projectId, selectedProjectTitle = "") {
        const $button = $('#projectDropdownButton');

        // If no title is provided, get it from the dropdown item
        if (!selectedProjectTitle) {
            const selectedButton = $(`#projectDropdown button[data-id="${projectId}"]`);
            if (selectedButton.length) {
                selectedProjectTitle = selectedButton.attr('title');
            }
        }

        // Get the current SVG if it exists
        const $svg = $button.find('svg').clone();

        // Clear and update the button text
        $button.text(selectedProjectTitle);

        // Add the SVG back if it exists
        if ($svg.length > 0) {
            $button.append($svg);
        } else {
            // If SVG doesn't exist, add a new one
            $button.append(`
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L8 10L12 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        `);
        }

        // Update project ID and state
        projectId = parseInt(projectId);
        currentProject = parseInt(projectId);
        $('#myselectedcurrentProject').val(currentProject);

        // Save current project to localStorage for persistence
        localStorage.setItem(`lastSelectedProject_${userId}`, currentProject);

        // Update dropdown selection state
        $('#projectDropdown button').removeClass('active').attr('data-selected', false);
        $(`#projectDropdown button[data-id="${projectId}"]`).addClass('active').attr('data-selected', true);

        // Update project items state
        document.querySelectorAll('.project-item').forEach(item => {
            const itemId = parseInt(item.dataset.id);
            item.classList.toggle('active', itemId === projectId);
        });

        // call to fetch notifications
        fetchNotificationsAndOpen(false);
        <?php if (!isPage('profile')): ?>
            // Load project data
            loadTasks(projectId);
            loadChatHistory(projectId);
        <?php endif; ?>
        initPusher(projectId);
    }


    let offset = 0;
    const limit = 20;
    let loading = false;
    let allLoaded = false;
    function loadChatHistory(projectId, currentOffset = 0, reset = true) {
        loading = true;
        if (reset) {
            offset = 0;
            allLoaded = false;
            chatMessages.innerHTML = '';
        }
        showChatLoading();

        const oldScrollHeight = chatMessages.scrollHeight;
        fetch('?api=get_chat_history', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ project_id: projectId, offset: currentOffset, limit })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (Array.isArray(data.history) && data.history.length > 0) {
                        if (offset === 0) {
                            chatMessages.innerHTML = '';
                            appendWelcomeLogo();
                            data.history.reverse().forEach(msg => appendMessage(msg.message, msg.sender));

                        } else {
                            hideWelcomeLogo();
                            data.history.forEach(msg => appendMessage(msg.message, msg.sender, 'top'));

                            // 2) AFTER prepending, restore the scroll position
                            const newScrollHeight = chatMessages.scrollHeight;
                            chatMessages.scrollTop = newScrollHeight - oldScrollHeight;

                        }
                        offset += data.history.length;
                    } else {
                        allLoaded = true;

                        // If no chat history, show welcome messages for first time users only
                        if (offset === 0) {
                            loading = false; hideChatLoading();
                            // alert(savedProject+" Last Project");
                            if (data.history.length == 0 && !(savedProject == 0 || savedProject == null)) {
                                displayProjectCreationWelcomeMessages();
                            }
                        }
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
            .finally(() => {
                loading = false;
                setTimeout(() => {
                    hideChatLoading();
                }, 500);

            });
    }

</script>