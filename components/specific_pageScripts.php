<!-- Reminder button -->
<?php if(isLoginUserPage() && !isAitonePage()){ ?>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>      
<script>
            document.addEventListener('DOMContentLoaded', function () {
                if(!isProfilePage()){
                    // 1) The tree images
                    const treeImages = [
                        { file: 'treelv2.png', alt: 'Tree Level 2' },
                        { file: 'treelv3.png', alt: 'Tree Level 3' },
                        { file: 'treelv4.png', alt: 'Tree Level 4' },
                        { file: 'treelv5.png', alt: 'Tree Level 5' },
                        { file: 'treelv6.png', alt: 'Tree Level 6' },
                        { file: 'treelv7.png', alt: 'Tree Level 7' },
                        { file: 'treelv8.png', alt: 'Tree Level 8' },
                    ];
                    const container = document.getElementById('taskTreeContainer');
                    const hiddenInput = document.getElementById('selectedTreeType');
                    // 2) Build and insert the images
                    let html = '';
                    treeImages.forEach(({ file, alt }) => {
                        html += `
                            <div class="tree-option" data-tree="${file}">
                                <img src="assets/images/garden/${file}" alt="${alt}">
                            </div>
                            `;
                    });
                    container.innerHTML = html;

                    // 3) Attach click listeners
                    container.querySelectorAll('.tree-option').forEach(optionDiv => {
                        optionDiv.addEventListener('click', () => {
                            // Set hidden input
                            const treeValue = optionDiv.dataset.tree;
                            hiddenInput.value = treeValue;
                            console.log('Selected tree:', treeValue);
                            // Highlight selected
                            container.querySelectorAll('.tree-option').forEach(o => o.classList.remove('selected'));
                            optionDiv.classList.add('selected');
                        });
                    });
                }
                    function loadActivityLog() {
                    fetch('?api=get_activity_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: getLastSelectedProject()
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const tbody = document.getElementById('activityLogTable');
                                tbody.innerHTML = data.logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>@${escapeHtml(log.username)}</td>
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
                        .finally();
                }


                    // Activity Log Modal handler
                    const activityLogModal = document.getElementById('activityLogModal');
                    activityLogModal.addEventListener('show.bs.modal', function () {
                        if (!getLastSelectedProject()) {
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
                        // Clean up any remnants of previous modals
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('padding-right', '');
                        $('.modal').removeClass('show');

                
                    });
            </script>
            <?php } ?>



    <script>
        var userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; ?>;
        // Keep the initialization but don't add duplicate script
        // 2) Grab referral from ?ref= or ?via=
       <?php if(isset($_GET['pro-member']) && (isset($_GET['referral']) && $_GET['referral']=='true')){ ?>
        console.log('rewardfull called');
        const email = "<?php echo addslashes(isset($_SESSION['email']) ? $_SESSION['email'] : ''); ?>";
        const params = new URLSearchParams(window.location.search);
        const referral = params.get('ref') || params.get('via') || null;
        console.log('Referral:', referral);
        console.log('window.rewardful:', window?.rewardful);

        // 3) Only fire convert once the library is actually present
        if (typeof rewardful === 'function') {
            rewardful('convert', {
                email: email,
                // Use 'referral' exactly as Rewardful expects
                referral: referral || undefined
            });
            console.log('🔥 rewardful.convert() called');

            // clearRewardfulCookies(); 
        } else {
            //   console.error('🚨 rewardful() not available yet');
            // retry once after a short delay
            setTimeout(() => {
                if (typeof rewardful === 'function') {
                    // rewardful('convert', { email });
                    rewardful('convert', { email: email, referral: referral });
                    console.log('🔥 rewardful.convert() called on retry');
                } else {
                    //   console.error('🚨 rewardful() still not loaded');
                }
            }, 500);
        }
        <?php } ?>
        // 4) Trigger your pro‑status update if needed
        <?php if (!empty($_GET['pro-member']) && $_GET['pro-member'] === 'true'): ?>
            updateProStatus();
        <?php endif; ?>
        function getLastSelectedProject() {
            if (userId) { // Check if userId is available
                return localStorage.getItem(`lastSelectedProject_${userId}`);
            }
            return null; // No user logged in or session expired
        }
        function updateProStatus() {
            fetch('?api=update_pro_status')
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                })
        }
    
            document.addEventListener('DOMContentLoaded', function () {
                      <?php if (isset($_GET['pro-member']) && $_GET['pro-member'] == 'true') { ?>
                updateProStatus();
            <?php } ?>
            // Check if we're on the garden stats page
            const isGardenStats = window.location.href.includes('page=garden_stats');
            if (isGardenStats) {
                // Skip the dashboard-specific code for garden stats page
                return;
            }
            const currentProject = getLastSelectedProject();
            // First check if we're on the dashboard page
            const isDashboard = document.querySelector('.chat-container') !== null;
            const isProfile = <?php echo isPage('profile') ? 'true' : 'false'; ?>;
            if (isDashboard || isProfile) {
                // Check if user is a pro member and redirect if necessary
                fetch('?api=check_pro_status')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && (!data.is_pro)) {
                            if (data.invited_by == null && (data.payment_link == '<?php echo $_ENV['STRIPE_PAYMENT_LINK_REFREAL']; ?>' || data.payment_link == '<?php echo $_ENV['STRIPE_PAYMENT_LINK']; ?>')) {
                                // window.location.href = data.payment_link;
                            }
                        }
                    })
                    .catch(error => console.error('Error checking pro status:', error));
                // Check URL parameters for pro-member status
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('pro-member') && urlParams.get('pro-member') === 'true') {
                    fetch('?api=update_pro_status')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Toast('success', 'Success', 'Your account has been upgraded to Pro!');
                            } else {
                                Toast('error', 'Error', data.message || 'Failed to update pro status');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating pro status:', error);
                            Toast('error', 'Error', 'Failed to update pro status. Please try again.');
                        });
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
                const debouncedUpdateTaskStatus = debounce((taskId, newStatus, loadingAllTasks = true) => {
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
                            if (loadingAllTasks) {
                                    loadTasks(currentProject);
                            } else {
                                loadTaskById(taskId);
                                fetchNotifications(currentProject);
                                }
                            }
                        })
                    .catch(error => console.error('Error updating task status:', error));
                }, 0);
                let currentProject = null;
                const savedProject = getLastSelectedProject();
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
                <?php if(!isPage('profile')){ ?>
                const chatLoader = document.getElementById('mychatLoader');
                // Show/hide loading indicator
                function showChatLoading() {
                    chatLoader.classList.remove('d-none');
                }
                function hideChatLoading() {
                    chatLoader.classList.add('d-none');
                }
                <?php } ?>
                // Show/hide loading indicator
                function showLoading() {
                    loadingIndicator.style.display = 'flex';
                }
                function hideLoading() {
                    loadingIndicator.style.display = 'none';
                }
                function scrollToBottom() {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
                <?php if(!isPage('profile')){ ?>
                hideChatLoading();
                <?php } ?>
                // Load projects
            function loadProjects() {

                fetch('?api=get_projects')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const projectDropdown = document.getElementById('projectDropdown');
                            <?php if(isPage('profile')){ ?>
                            const projectDropdown1 = document.getElementById('projectDropdown1');
                            projectDropdown1.innerHTML = '';
                            <?php } ?>
                            projectDropdown.innerHTML = '';

                            if (!data.projects || data.projects.length === 0) {
                                const placeholder = document.createElement('li');
                                placeholder.className = 'dropdown-item disabled';
                                placeholder.textContent = 'No projects found';
                                projectDropdown.appendChild(placeholder);
                            } else {
                                data.projects.forEach(project => {
                                    const li = document.createElement('li');
                                    li.className = 'dropdown-item';
                                    li.innerHTML = `
                        <button class="dropdown-item text-capitalize" type="button" data-id="${project.id}" title="${escapeHtml(project.title)}">
                            ${escapeHtml(project.title)}
                        </button>
                    `;
                                    projectDropdown.appendChild(li);
                                    <?php if(isPage('profile')){ ?>
                                    // projectDropdown1.appendChild(li);
                                    const liClone = li.cloneNode(true);
                     projectDropdown1.appendChild(liClone);
                                    <?php } ?>
                                });
                            }

                            // Add click handlers for project selection
                            document.querySelectorAll('.dropdown-item').forEach(item => {
                                item.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const button = item.querySelector('button');
                                    if (button) {
                                        const projectId = button.dataset.id;
                                        const projectTitle = button.getAttribute('title');
                                        selectProject(projectId, projectTitle);
                                    }
                                });
                            });

                            // After projects are loaded, check for saved project
                            const savedProject = getLastSelectedProject();
                            if (savedProject && savedProject !== 'null' && savedProject !== '0') {
                                const projectId = parseInt(savedProject);
                                const projectButton = document.querySelector(`#projectDropdown button[data-id="${projectId}"]`);
                                if (projectButton) {
                                    const projectTitle = projectButton.getAttribute('title');
                                    selectProject(projectId, projectTitle);
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading projects:', error);
                        const projectDropdown = document.getElementById('projectDropdown');
                                    projectDropdown.innerHTML = `
                        <li class="dropdown-item">
                            <div class="alert alert-danger">
                                Unable to load projects. Please try again later.
                            </div>
                        </li>
                    `;
                    })
                    .finally(hideLoading);
            }
                let offset = 0;
                const limit = 20;
                let loading = false;
                let allLoaded = false;

                <?php if(!isPage('profile')){ ?>
                // Infinite scroll event
                chatMessages.addEventListener('scroll', () => {
                    const projectId = $('#myselectedcurrentProject').val();
                    // console.log(projectId)
                    const THRESHOLD = 20;  // pixels from the very top
                    if (chatMessages.scrollTop <= THRESHOLD && !loading && !allLoaded) {
                        loadChatHistory(projectId, offset, false);
                    }
                });
                <?php } ?>
                // Select project
                function selectProject(projectId, selectedProjectTitle = "") {
                    const $button = $('#projectDropdownButton');
                    <?php if (isPage('profile')){ ?>
                    const $button1 = $('#projectDropdownButton1');
                    <?php } ?>
                    // If no title is provided, get it from the dropdown item
                    if (!selectedProjectTitle) {
                        const selectedButton = $(`#projectDropdown button[data-id="${projectId}"]`);
                        if(isPage('profile')){ 
                        const selectedButton1 = $(`#projectDropdown1 button[data-id="${projectId}"]`);
                        }
                        if (selectedButton.length) {
                        selectedProjectTitle = selectedButton.attr('title');
                        }
                        <?php if(isPage('profile') ){ ?>
                            if (selectedButton1.length) {
                                selectedProjectTitle = selectedButton1.attr('title');
                            }
                        <?php } ?>
                    }

                    // Get the current SVG if it exists
                    const $svg = $button.find('svg').clone();

                    // Clear and update the button text
                    $button.text(selectedProjectTitle);
                    <?php if(isPage('profile')){ ?>
                    $button1.text(selectedProjectTitle);
                    <?php } ?>
                    // Add the SVG back if it exists
                    if ($svg.length > 0) {
                        $button.append($svg);
                        <?php if(isPage('profile') ){ ?>
                            $button.append(`
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L8 10L12 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        `);
                        $button1.append($svg);
                        <?php } ?>
                    } else {
                        // If SVG doesn't exist, add a new one
                        $button.append(`
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L8 10L12 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        `);
                        <?php if(isPage('profile') ){ ?>
                        $button1.append(`
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L8 10L12 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        `);
                        <?php } ?>
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
                    <?php if(isPage('profile')){ ?>
                    $('#projectDropdown1 button').removeClass('active').attr('data-selected', false);
                    $(`#projectDropdown1 button[data-id="${projectId}"]`).addClass('active').attr('data-selected', true);
                    <?php } ?>
                    // Update project items state
                    document.querySelectorAll('.project-item').forEach(item => {
                        const itemId = parseInt(item.dataset.id);
                        item.classList.toggle('active', itemId === projectId);
                    });

                    // call to fetch notifications
                    fetchNotificationsAndOpen(false,false);

                    // Load project data
                    <?php if (!isPage('profile')) { ?>
                        loadTasks(projectId);
                        loadChatHistory(projectId);
                        initPusher(projectId);
                    <?php } else { ?>
                        const today = new Date();
                        const startDate = formatDateForBackend(
                            new Date(today.getFullYear(), today.getMonth() - 1, 5)
                        );
                        const endDate = formatDateForBackend(
                            new Date(today.getFullYear(), today.getMonth() + 1, 5)
                        );
                        loadTasks2(projectId, startDate, endDate);
                    <?php } ?>
                }

                // Load chat history
                function loadChatHistory(projectId, currentOffset = 0, reset = true) {
                    // if (loading) return;
                    loading = true;
                    if (reset) {
                        offset = 0;
                        allLoaded = false;
                        chatMessages.innerHTML = '';
                    }
                    // showChatLoading(); 

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
                                    // if (offset === 0) displayProjectCreationWelcomeMessages();
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
                            hideChatLoading(); // Remove the setTimeout
                        });
                }

                // Add this function to update a single task in the UI
                function updateSingleTaskInBoard(task) {
                    // Find the existing task element
                    const existingTaskElement = document.querySelector(`[data-id="${task.id}"]`);
                    if (!existingTaskElement) return;

                    // Get the plant image based on task status and garden data
                    const getPlantImage = (task) => {
                        // If we have garden data, use it
                        if (task.garden && task.garden.stage && task.garden.plant_type) {
                            switch (task.garden.stage) {
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
                                return task.garden?.plant_type ? `${task.garden.plant_type}.png` : 'treelv3.png';
                            default: return 'seed.png';
                        }
                    };

                    // Update the plant image
                    const plantImage = existingTaskElement.querySelector('.inner-plant');
                    if (plantImage) {
                        plantImage.src = `assets/images/garden/${getPlantImage(task)}`;
                    }

                    // Update task label/status
                    const taskLabel = existingTaskElement.querySelector('.task-label');
                    if (taskLabel) {
                        taskLabel.className = `task-label ${task.status}`;
                        taskLabel.textContent = task.status.replace('_', ' ').toUpperCase();
                    }

                    // Get the correct column based on the task's status
                    const targetColumn = document.getElementById(`${task.status}Tasks`);
                    if (!targetColumn) return;

                    // Move the task to the correct column if it's not already there
                    if (existingTaskElement.parentElement.id !== `${task.status}Tasks`) {
                        targetColumn.appendChild(existingTaskElement);
                    }

                    // Update other task details if needed
                    const titleElement = existingTaskElement.querySelector('.task-title');
                    if (titleElement) {
                        titleElement.textContent = task.title;
                    }

                    const descriptionElement = existingTaskElement.querySelector('.task-description');
                    if (descriptionElement) {
                        descriptionElement.textContent = task.description || '';
                    }

                    const dueDateElement = existingTaskElement.querySelector('.due-date');
                    if (dueDateElement) {
                        dueDateElement.textContent = task.due_date || '';
                    }

                    // Update assignees if present
                    const assigneesContainer = existingTaskElement.querySelector('.task-assignees');
                    if (assigneesContainer && task.assigned_users) {
                        assigneesContainer.innerHTML = Object.values(task.assigned_users)
                            .map(user => `<span class="assignee">${user.username}</span>`)
                            .join(', ');
                    }

                    // Reinitialize drag and drop
                    initializeDragAndDrop();
                }

                // Add the loadTaskById function
                function loadTaskById(taskId) {
                    return fetch('?api=get_task_by_id', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: taskId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                            // Find the existing task element
                            const existingTaskElement = document.querySelector(`[data-id="${taskId}"]`);
                            if (!existingTaskElement) return;

                            // Get the correct column based on the task's status
                            const targetColumn = document.getElementById(`${data.task.status}Tasks`);
                            if (!targetColumn) return;

                            // Update plant image
                            const plantImage = existingTaskElement.querySelector('.inner-plant');
                            if (plantImage) {
                                const plantImageSrc = getPlantImage(data.task);
                                plantImage.src = `assets/images/garden/${plantImageSrc}`;
                            }

                            // Update task label
                            const taskLabels = existingTaskElement.querySelector('.task-card-labels');
                            if (taskLabels) {
                                taskLabels.innerHTML = `
                                    ${data.task.status === 'todo' ? '<span class="task-label label-red"></span>' : ''}
                                    ${data.task.status === 'in_progress' ? '<span class="task-label label-orange"></span>' : ''}
                                    ${data.task.status === 'done' ? '<span class="task-label label-green"></span>' : ''}
                                    <span class="task-label label-blue"></span>
                                `;
                            }

                            // Move task to correct column if needed
                            if (existingTaskElement.parentElement !== targetColumn) {
                                targetColumn.appendChild(existingTaskElement);
                            }

                            // Reinitialize drag and drop
                            initializeDragAndDrop();
                        }
                    })
                    .catch(error => console.error('Error loading task:', error));
                }

                // Helper function to get plant image based on task status and garden data
                function getPlantImage(task) {
                    // If we have garden data, use it
                    if (task.garden && task.garden.stage && task.garden.plant_type) {
                        switch (task.garden.stage) {
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
                            return task.garden?.plant_type ? `${task.garden.plant_type}.png` : 'treelv3.png';
                        default: return 'seed.png';
                    }
                }

                // Load tasks
                    function loadTasks(projectId, startDate=null, endDate=null, notify=false) {
                        // alert('loadTasks');
                        // showLoading();
                         // Add startDate and endDate to the data if provided
                    if (startDate && endDate) {
                        // Format the dates into 'YYYY-MM-DD' format (or adjust based on your format needs)
                        requestData.start_date = startDate;
                        requestData.end_date = endDate;
                        // alert(startDate+" "+endDate);
                    }

                        fetch('?api=get_tasks', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ project_id: projectId })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    <?php if (isPage('profile')) { ?>
                    updateCardsBoard(data.tasks);
                <?php } else { ?>
                    updateTasksBoard(data.tasks);
                   
                        fetchNotifications(currentProject,null,null,notify);
                    
                <?php } ?>
                                }
                            })
                            .catch(error => console.error('Error loading tasks:', error))
                            .finally();
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
                                                            <div class="subtask-title text-capitalize ${subtask.status === 'done' ? 'text-decoration-line-through' : ''}">${escapeHtml(subtask.title)}</div>
                                                            ${subtask.due_date ? `
                                                                <small class="text-muted due-date ${isOverdue ? 'overdue' : ''}">
                                                                  ${SVGCalendar()}
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
                                               ${SVGAdd()} Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-update-dates-btn" data-task-id="${task.id}">
                                              ${SVGAI()}AI Update Dates
                                            </button>
                                             <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                            ${SVGAI()}AI Subtasks
                                            </button>
                                        </div>
                                    </div>`;
                        } else {
                            html += `<div class="mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : 'hover-show-subtasks'}">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                ${SVGAdd()} Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}" style="display: flex; align-items: center; gap: 6px;">${SVGAI()} AI Subtasks
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
                        // If we have garden data, use it
                        if (task.garden && task.garden.stage && task.garden.plant_type) {
                            switch (task.garden.stage) {
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
                        ${task.description ? `<div class="task-description text-capitalize">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-meta">
                            ${dueDateHtml}
                            ${plantBallHtml}
                            ${task.assigned_users ? `
                                <div class="task-assignees d-flex gap-1 border-0 m-0 p-0">
                                    ${Object.entries(task.assigned_users).map(([id, username]) => {
                                     // First user gets rgba(61, 127, 41, 1), others get varied hues
                            const hues = [61, 200, 0, 280, 30, 320, 170, 60, 340, 250]; // Green, blue, red, purple, orange, etc.
                            const index = parseInt(id) % hues.length;
                            const hue = hues[index];
                            const bgColor = `hsl(${hue}, 50%, 40%)`; // Consistent saturation and lightness
                            return `
                                        <span class="task-assignee text-capitalize" style="background-color: ${bgColor}; color: white;">
                                            ${escapeHtml(username[0]?.charAt(0).toUpperCase() + username[1]?.charAt(0).toUpperCase())}
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

                    // Handle card drag events
                    taskCards.forEach(card => {
                        card.addEventListener('dragstart', () => card.classList.add('dragging'));
                        card.addEventListener('dragend', () => {
                            card.classList.remove('dragging');
                            taskColumns.forEach(col => col.classList.remove('drag-over'));
                        });
                    });

                    // Handle column drag events
                    taskColumns.forEach(column => {
                        column.addEventListener('dragenter', e => {
                            e.preventDefault();
                            column.classList.add('drag-over');
                        });

                        column.addEventListener('dragleave', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                        });

                        column.addEventListener('dragover', e => e.preventDefault());

                        column.addEventListener('drop', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                            
                            const draggingCard = document.querySelector('.dragging');
                            if (!draggingCard) return;

                            const newStatus = column.dataset.status;
                            const taskId = draggingCard.dataset.id;

                            // Update plant image based on new status
                            const plantImage = draggingCard.querySelector('.inner-plant');
                            if (plantImage) {
                                // For in_progress, always show flower3.png
                                if (newStatus === 'in_progress') {
                                    plantImage.src = 'assets/images/garden/flower3.png';
                                } else if (newStatus === 'todo') {
                                    plantImage.src = 'assets/images/garden/seed.png';
                                }
                                // For 'done' status, keep existing plant type image
                            }

                            // Update task label
                            const taskLabels = draggingCard.querySelector('.task-card-labels');
                            if (taskLabels) {
                                taskLabels.innerHTML = `
                                    ${newStatus === 'todo' ? '<span class="task-label label-red"></span>' : ''}
                                    ${newStatus === 'in_progress' ? '<span class="task-label label-orange"></span>' : ''}
                                    ${newStatus === 'done' ? '<span class="task-label label-green"></span>' : ''}
                                    <span class="task-label label-blue"></span>
                                `;
                            }

                            // Move card to new column
                            column.appendChild(draggingCard);
                            draggingCard.dataset.status = newStatus;

                            // Update server in background
                            debouncedUpdateTaskStatus(taskId, newStatus, false);
                        });
                    });
                }

               
                <?php if(!isPage('profile')){ ?>
                // Handle chat form submission
                chatForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!currentProject) {
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
                    playMessageSound();
                    // showChatLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: message,
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
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
                                loadTasks(currentProject,null,null,false);
                            }
                        })
                        .catch(error => console.error('Error sending message:', error))
                        .finally(hideChatLoading);
                });
                <?php } ?>

                // Handle new project creation
                createProjectBtn.addEventListener('click', function () {
                    const title = document.getElementById('projectTitle').value.trim();
                    const description = document.getElementById('projectDescription').value.trim();

                    if (!title) {
                        showToastAndHideModal(
                            '',
                            'error',
                            'Error',
                            'Please enter a project title'
                        );
                        return;
                    }

                    // showLoading();
                    fetch('?api=create_project', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ title, description })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
                                document.getElementById('projectTitle').value = '';
                                document.getElementById('projectDescription').value = '';
                                selectProject(data.project_id, title);
                                Toast("success", "Success", "Project created successfully", "bottomCenter");
                                fetchNotifications(data.project_id,null,null,true);
                                // First load the projects to refresh the dropdown
                                loadProjects().then(() => {
                                    // Then select the new project
                                selectProject(data.project_id, title);
                                });
                            }
                        })
                        .catch(error => console.error('Error creating project:', error))
                        .finally();
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
                    // showLoading();
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
                        .finally();

                    // Add this new section to load task activity log
                    // showLoading();
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
                                <div class="form-check me-2 align-self-start">
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
                                            ${SVGCalendar()}
                                        </small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-link text-danger delete-subtask-btn deleteUser" data-id="${subtask.id}">
                                    <?php echo getTrashIcon(); ?>
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

                if(!isProfilePage()){
                // Add event listener for save button
                document.getElementById('saveTaskBtn').addEventListener('click', function () {
                    const taskId = document.getElementById('editTaskId').value;
                    const title = document.getElementById('editTaskTitle').value.trim();
                    const description = document.getElementById('editTaskDescription').value.trim();
                    const dueDate = document.getElementById('editTaskDueDate').value || null; // Convert empty string to null
                    const assignees = $('#editTaskAssignees').val().map(value => parseInt(value));
                    const pictureInput = document.getElementById('editTaskPicture');
                    const plantType = document.getElementById('editPlantType').value;
                    function sendUpdateTask(pictureData) {
                        let payload = {
                            task_id: taskId,
                            title: title,
                            description: description,
                            due_date: dueDate,
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
            }

                const assignUserModal = document.getElementById('assignUserModal');
                assignUserModal.addEventListener('shown.bs.modal', function () {
                    if (!getLastSelectedProject()) {
                        showToastAndHideModal(
                            'assignUserModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }
                    fetch(`?api=get_all_project_users&project_id=${getLastSelectedProject()}`)
                        .then(async response => {
                            const text = await response.text();
                            // console.log('Raw response:', text); // Debug log
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                throw new Error('Invalid server response');
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                const userListContainer = document.getElementById('userListContainer');
                                const noUsersMessage = document.getElementById("noUsersMessage");
                                const addUserBtnTopRight = document.getElementById("add-user-btn-top-right");
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
                                        if (user.role != "Creator") {
                                            actionButtons += `
                                            <button class="btn btn-sm btn-outline-danger deleteUser " data-id="${user.id}">
                                                <?php echo getTrashIcon(); ?>
                                            </button>`;
                                        }
                                        actionButtons += "</div>";
                                        userCard.innerHTML = `
                    <div>
                        <strong>@${user.username}</strong>
                        <span class="">(${user.role})</span>
                    </div>
                    ${actionButtons}
                `;

                                        userListContainer.appendChild(userCard);
                                    });
                                }
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
                        .finally();
                });


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
                            fetch(`?api=delete_user`, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: new URLSearchParams({
                                    user_id: userId,
                                    project_id: projectId,
                                    user_name: userName
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        userDiv.remove();
                                        // showToast('success', 'User deleted successfully');
                                        Toast('success', 'Success', userName + ' removed successfully!');
                                    } else {
                                        Toast('error', 'Error', 'Failed to delete user');
                                    }
                                })
                                .catch(error => console.error("Error deleting user:", error));
                        }
                    }


                });

            
                $('#addUserBtn').click(function () {
                    $('#addUserModal').modal('show');
                });
                document.getElementById('addNewUserBtn').addEventListener('click', function () {
                    const email = document.getElementById('newUserEmail').value.trim();
                    const role = document.getElementById('newUserRole').value.trim();

                    if (!email || !role) {
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
                                document.getElementById('newUserEmail').value = '';
                                document.getElementById('newUserRole').value = '';

                                const successMessage = data.data?.is_new_user
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
               if(!isProfilePage()){
                // Helper functions
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
                    const iconImage = `<?php echo getIconImage(0, 0, '1.5rem'); ?>`;

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

            }
                // Initial load
                loadProjects();
                // Auto-load the saved project if available
                if (isDashboard) {
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo "userId = " . json_encode($_SESSION['user_id']) . ";";
                    }
                    ?>
                }

                if(!isProfilePage()){
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
                        bootstrap.Modal.getInstance(newTaskModal).hide();
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
                    const selectedTree = document.querySelector('.tree-option.selected');
                    // const plantType = selectedTree ? selectedTree.dataset.tree : '';
                    const plantTypeRaw = selectedTree ? selectedTree.dataset.tree : '';
                    const plantType = plantTypeRaw.replace('.png', '');
                    // console.warn(selectedTree)
                    if (!title || !description || !dueDate || !assignees) {
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please fill all the required fields'
                        );
                        return;
                    }
                   

                    if (!plantType) {
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a tree type'
                        );
                        return;
                    }
                    function sendCreateTask(pictureData) {
                        // showLoading();
                        fetch('?api=create_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                project_id: currentProject,
                                title: title,
                                description: description,
                                due_date: dueDate,
                                assignees: assignees,
                                picture: pictureData,
                                plant_type: plantType
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('newTaskForm').reset();
                                    document.querySelectorAll('.tree-option').forEach(opt =>
                                        opt.classList.remove('selected')
                                    );
                                    $('#newTaskAssignees').val(null).trigger('change');
                                    Toast('success','Task Created','Task created successfully!');
                                // Close modal and refresh
                                bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                                loadTasks(currentProject,null,null,true);
                            } else {
                                throw new Error(data.message || 'Failed to create task');
                            }
                        })
                        .catch(error => {
                            console.error('Error creating task:', error);
                            showToastAndHideModal('newTaskModal', 'error', 'Error', 'Failed to create task');
                        })
                        .finally();
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
                                loadTasks(currentProject,null,null,true);
                                Toast('success','Task Deleted','Task deleted successfully!');
                            } else {
                                throw new Error(data.message || 'Failed to delete task');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting task:', error);
                            Toast('error','Task Deleted','Failed to delete task. Please try again.');
                        })
                        .finally();
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
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
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
                // $(document).on('click', '.delete-subtask-btn', function (e) {
                //     e.stopPropagation(); // Prevent task card click event
                //     if (confirm('Are you sure you want to delete this subtask?')) {
                //         const subtaskId = $(this).data('id');
                //         deleteSubtask(subtaskId);
                //     }
                // });

                function renderSuggestedTasks(suggestions) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'd-flex';

                    // Avatar block
                    const avatarDiv = document.createElement('div');
                    avatarDiv.className = 'ai-avatar';
                    avatarDiv.innerHTML = `
                    <div class="chat-loading-avatar">
                    <img src="https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png" 
                        alt="Logo" class="logo-icon" 
                        style="filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3)); margin-top: 0; margin-bottom: 0; width: 1.5rem; height: auto">
                    </div>
                `;

                    // Suggestions container
                    const suggestionsContainer = document.createElement('div');
                    suggestionsContainer.className = 'suggestions-container mt-3 message ai';
                    suggestionsContainer.innerHTML = `<h6 class="mb-3">Suggested Tasks & Features</h6>`;

                    suggestions.forEach(suggestion => {
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.className = 'suggestion-item border p-2 mb-2';
                        suggestionDiv.innerHTML = `
                            <strong>${escapeHtml(suggestion.title)}</strong><br>
                            <span class="my-2">${escapeHtml(suggestion.description)}</span><br>
                            <div class="d-flex mt-1" style="justify-content: space-between; flex-direction: row-reverse;">
                                <div class="suggested-task-due-date">
                                ${suggestion.due_date ? `
                                    <?php echo getCalendarIcon(); ?>
                                    <em class="text-muted"> Due: ${escapeHtml(suggestion.due_date)}</em>` : ''}
                                </div>
                                <button class="btn btn-sm btn-add-task mt-1">
                                <?php echo getAddIcon(); ?> Add Task
                                </button>
                            </div>
                            `;
                        suggestionDiv.querySelector('button').addEventListener('click', () => {
                            addSuggestedTask(suggestion);
                        });
                        suggestionsContainer.appendChild(suggestionDiv);
                    });

                    // Append avatar + container to wrapper
                    wrapper.appendChild(avatarDiv);
                    wrapper.appendChild(suggestionsContainer);

                    // Add to chat
                    chatMessages.appendChild(wrapper);
                }


                function addSuggestedTask(suggestion) {
                    if (!currentProject) {
                        alert('Please select a project first');
                        return;
                    }
                    // showLoading();
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
                                loadTasks(currentProject,null,null,true);
                            } else {
                                alert('Failed to add task: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error adding suggested task:', error);
                            alert('Error adding suggested task');
                        })
                        .finally();
                }

                // Add this new code to handle image clicks (add it where other event listeners are defined)
                // document.addEventListener('click', function (e) {
                //     if (e.target.classList.contains('enlarge-image')) {
                //         const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                //         const enlargedImage = document.getElementById('enlargedImage');
                //         enlargedImage.src = e.target.src;
                //         imageModal.show();
                //     }
                // });
    
                // New event listener for removing task picture
                document.getElementById('editRemovePreviewBtn').addEventListener('click', function () {
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

                // Function to format AI JSON responses into user-friendly HTML
                function formatAIResponse(jsonData, taskDetails) {
                    if (!jsonData || !jsonData.subtasks || !Array.isArray(jsonData.subtasks) || jsonData.subtasks.length === 0) {
                        return "<p>No subtask updates available.</p>";
                    }

                    // Create a map of subtask IDs to their titles
                    const subtaskMap = {};
                    if (taskDetails && taskDetails.subtasks) {
                        taskDetails.subtasks.forEach(subtask => {
                            subtaskMap[subtask.id] = subtask.title;
                        });
                    }

                    // Group subtasks by due date for better organization
                    const subtasksByDate = {};
                    jsonData.subtasks.forEach(subtask => {
                        if (!subtasksByDate[subtask.due_date]) {
                            subtasksByDate[subtask.due_date] = [];
                        }
                        subtasksByDate[subtask.due_date].push(subtask);
                    });

                    // Sort dates for chronological order
                    const sortedDates = Object.keys(subtasksByDate).sort();

                    let html = `
                        <div class="ai-schedule-response">
                            <h6 class="mb-3">📅 Optimized Task Schedule</h6>
                            <div class="timeline-container">
                    `;

                    sortedDates.forEach(date => {
                        // Format date for display (from YYYY-MM-DD to more readable format)
                        const dateObj = new Date(date);
                        const displayDate = dateObj.toLocaleDateString(undefined, {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });

                        // Get day of month for calendar icon
                        const dayOfMonth = dateObj.getDate();

                        html += `
                            <div class="date-group mb-3">
                                <div class="date-header">
                                    <div class="calendar-day-icon">
                                        <div class="day-number">${dayOfMonth}</div>
                                    </div>
                                    <span class="due-date"><?php echo getCalendarIcon(); ?> ${displayDate}</span>
                                </div>
                                <ul class="task-list list-unstyled ps-3 pt-2">
                        `;

                        subtasksByDate[date].forEach(subtask => {
                            const subtaskTitle = subtaskMap[subtask.id] || `Subtask #${subtask.id}`;
                            html += `<li class="mb-1">• <strong>${subtaskTitle}</strong></li>`;
                        });

                        html += `
                                </ul>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                            <p class="mt-3 text-success small">✓ All deadlines have been optimized to ensure completion before the main task deadline.</p>
                        </div>
                    `;

                    return html;
                }

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
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
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

                                    // Convert the response to a user-friendly format and display it
                                    const formattedResponse = formatAIResponse(args, task);
                                    appendMessage(formattedResponse, 'ai');

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
                            // Remove the generic message as we now show a formatted response
                            // appendMessage("Subtask dates have been aggressively updated to ensure tight deadlines.", 'ai');
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
                       background-color: transparent;
    transition: all 0.2s ease;
    border: solid rgba(255, 255, 255, 0.25) !important;
                        transition: all 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover {
                        transform: translateY(4px);
                    }
                    #subtasksList .delete-subtask-btn {
                    border: 0 !important;
                    width: 50px!important;
                    font-size: 1.2rem !important;
                    margin-top: -1.2rem;
                       
                        transition: opacity 0.2s ease;
                    }

                    .timeline-container {
                        position: relative;
                    }
                    
                    .timeline-container:before {
                        content: '';
                        position: absolute;
                        left: 4px;
                        top: 8px;
                        bottom: 8px;
                        width: 2px;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 1px;
                    }
                    
                    .date-group {
                        position: relative;
                        padding-left: 20px;
                    }
                    
                    .date-header {
                        margin-bottom: 5px;
                    }
                    
                    .date-header:before {
                        content: '';
                        position: absolute;
                        left: -1px;
                        top: 8px;
                        width: 10px;
                        height: 10px;
                        background: #ffffff;
                        border-radius: 50%;
                        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
                    }
                    
                    .task-list {
                        margin-left: 10px;
                    }
                    
                    .task-list li {
                        border-radius: 5px;
                        padding: 2px 8px;
                        font-size: 0.9rem;
                        transition: all 0.2s ease;
                    }
                    
                    .task-list li:hover {
                        background: rgba(255, 255, 255, 0.1);
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
                    .subtask-due-date:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                    }
                `;
                // Add event delegation for the add-subtask-btn
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.add-subtask-btn')) {
                        e.stopPropagation(); // Prevent task card click event
                        const taskId = e.target.closest('.add-subtask-btn').dataset.taskId;
                        openAddSubtaskModal(taskId);
                    }
                });

                // Image preview for new task modal
                document.getElementById('newTaskPicture').addEventListener('change', function (e) {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        const previewContainer = document.getElementById('imagePreviewContainer');
                        const imagePreview = document.getElementById('imagePreview');

                        reader.onload = function (e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';
                        }

                        reader.readAsDataURL(file);
                    }
                });

                // Remove preview button for new task modal
                document.getElementById('removePreviewBtn').addEventListener('click', function () {
                    const previewContainer = document.getElementById('imagePreviewContainer');
                    const fileInput = document.getElementById('newTaskPicture');

                    // Clear the file input
                    fileInput.value = '';
                    // Hide the preview
                    previewContainer.style.display = 'none';
                });

                // Image preview for edit task modal
                document.getElementById('editTaskPicture').addEventListener('change', function (e) {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        const previewContainer = document.getElementById('editImagePreviewContainer');
                        const imagePreview = document.getElementById('editImagePreview');

                        reader.onload = function (e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';

                            // Hide the remove picture button when showing preview
                            document.getElementById('taskPictureContainer').style.display = 'none';
                        }

                        reader.readAsDataURL(file);
                    }
                });

                // Remove preview button for edit task modal
                document.getElementById('editRemovePreviewBtn').addEventListener('click', function () {
                    const previewContainer = document.getElementById('editImagePreviewContainer');
                    const fileInput = document.getElementById('editTaskPicture');

                    // Clear the file input
                    fileInput.value = '';
                    // Hide the preview
                    previewContainer.style.display = 'none';

                    // Show the remove picture button if task had an existing picture
                    const taskId = document.getElementById('editTaskId').value;
                    const taskCards = document.querySelectorAll('.task-card');
                    taskCards.forEach(card => {
                        if (card.dataset.id === taskId && card.querySelector('.task-picture')) {
                            document.getElementById('taskPictureContainer').style.display = 'block';
                        }
                    });
                });

                // Modify openEditTaskModal to handle image preview for existing task picture
                const originalOpenEditTaskModal = openEditTaskModal;
                openEditTaskModal = function (task) {
                    // Call the original function first
                    originalOpenEditTaskModal(task);

                    // Reset file input and hide preview container
                    document.getElementById('editTaskPicture').value = '';
                    document.getElementById('editImagePreviewContainer').style.display = 'none';

                    // If task has an existing picture, show it in the preview
                    if (task.picture) {
                        const imagePreview = document.getElementById('editImagePreview');
                        imagePreview.src = task.picture;
                        document.getElementById('editImagePreviewContainer').style.display = 'block';
                        // Hide the remove button since we're showing the preview
                        document.getElementById('taskPictureContainer').style.display = 'none';
                    }

                    // Set the plant type in the edit tree selection
                    if (task.garden && task.garden.plant_type) {
                        const plantType = task.garden.plant_type;
                        document.getElementById('editPlantType').value = plantType + '.png';

                        // Highlight the selected tree
                        const treeOptions = document.querySelectorAll('#editTaskTreeContainer .tree-option');
                        treeOptions.forEach(option => {
                            option.classList.remove('selected');
                            if (option.dataset.tree === plantType + '.png') {
                                option.classList.add('selected');
                            }
                        });
                    }
                };

                // Uncomment this code and modify it to create a reusable function for showing enlarged images
                function openEnlargedImage(imageSrc) {
                    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                    const enlargedImage = document.getElementById('enlargedImage');
                    enlargedImage.src = imageSrc;
                    imageModal.show();
                }

                // Add event delegation for enlarging images when clicked
                // document.addEventListener('click', function (e) {
                    // Check if the clicked element is an image preview in either modal
                    // if (e.target.id === 'imagePreview' || e.target.id === 'editImagePreview') {
                    //     openEnlargedImage(e.target.src);
                    // }

                    // Also handle task pictures in the task cards
                    // if (e.target.classList.contains('enlarge-image')) {
                    //     openEnlargedImage(e.target.src);
                    // }
                // });

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
        }
        }); // End of DOMContentLoaded

       
        let displayedReminders = new Set();
        // get Reminders
        function getReminders() {
            const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
            if (!fcmToken) {
                console.error('No FCM token found');
                return;
            }

            fetch('?api=get_fcm_reminders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fcm_token: fcmToken })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    // console.log("Reminders data:", data);
                    if (data.success && data.reminders && data.reminders.length > 0) {
                        const popupContainer = document.querySelector('.popup-container');
                        if (popupContainer) {
                            // Append each reminder as a popup alert
                            data.reminders.forEach(reminder => {
                                if (!displayedReminders.has(reminder.id)) {
                                    // Add to the set to track the displayed reminder
                                    displayedReminders.add(reminder.id);
                                    // Create popup using the PHP getPopupAlert function output
                                    const title = reminder.title || 'Reminder';
                                    const description = reminder.description || '';

                                    const popupHtml = `<?php echo getPopupAlert("TITLE_PLACEHOLDER", "DESCRIPTION_PLACEHOLDER", "REMINDER_ID_PLACEHOLDER"); ?>`
                                        .replace('TITLE_PLACEHOLDER', title)
                                        .replace('DESCRIPTION_PLACEHOLDER', description)
                                        .replace('REMINDER_ID_PLACEHOLDER', reminder.id);

                                    // Insert at the beginning to make the newest appear on top
                                    popupContainer.insertAdjacentHTML('afterbegin', popupHtml);

                                    // Limit visible alerts to 3
                                    updatePopupVisibility();
                                }
                            });

                            // Initialize popup functionality
                            const popups = document.querySelectorAll('.popup-alert');
                            // updatePopupVisibility();
                        }
                    } else {
                        // console.log("No reminders found or empty response");
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch reminders:', error);
                });
        }



        function delete_fcm_reminders(reminder_id) {
            const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
            console.log("FCM Token for reminders:", fcmToken);
            if (reminder_id) {
                fetch('?api=delete_fcm_reminders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fcm_token: fcmToken, reminder_id: reminder_id })
                });
            }
        }
    </script>


    <div class="popup-container">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initial setup of popup visibility
            updatePopupVisibility();
        });

        function updatePopupVisibility() {
            const popups = document.querySelectorAll(".popup-alert");

            // Show only the first 3 popups, hide the rest
            popups.forEach((popup, index) => {
                if (index < 3) {
                    popup.classList.remove("hidden");
                } else {
                    popup.classList.add("hidden");
                }
            });
        }

        function closePopup(button, type) {
            let popup = button.parentElement;
            // const type = popup.dataset.popupType;
            const reminderId = popup.dataset.reminderId;

            // Check if this is the notification reminder or enable now button
            const isNotificationReminder = popup.id == 'reminderButton';
            const isEnableNowBtn = button.id == 'enableNowBtn';

            if (isEnableNowBtn) {
                alert("Enable Now Button Clicked");
                return;
            }
            // alert(type);
            // if(type == 'telegram'){
                fetch('?api=disabled_notification_popups', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: type })
                });
            // }
            // For other reminders
            popup.remove();
            // Delete from database/backend for regular reminders
            if (reminderId && !isNotificationReminder) {
                delete_fcm_reminders(reminderId);
            }
            // Update popup visibility to show the next one if available
            updatePopupVisibility();
        }

      

const input = document.getElementById('newTaskDueDate');
const input1 = document.getElementById('editTaskDueDate');
const input2 = document.getElementById('subtaskDueDate');
const parent = input?.parentElement;
const parent1 = input1?.parentElement;
const parent2 = input2?.parentElement;

    const flatpickrInstance = flatpickr(input, {
    enableTime: false,
    dateFormat: "Y-m-d",
    position: "below",
    theme: 'dark',
    appendTo: parent,
    });
    const flatpickrInstance1 = flatpickr(input1, {
    enableTime: false,
    dateFormat: "Y-m-d",
    position: "below",
    theme: 'dark',
    appendTo: parent1,
    });

    const flatpickrInstance2 = flatpickr(input2, {
    enableTime: false,
    dateFormat: "Y-m-d",
    position: "below",
    theme: 'dark',
    appendTo: parent2,
    });

// Later, to get the selected dates:
const selectedDate = flatpickrInstance.selectedDates[0];
const selectedDate1 = flatpickrInstance1.selectedDates[0];
const selectedDate2 = flatpickrInstance2.selectedDates[0];// JS Date object
const dueDate = selectedDate ? selectedDate.toISOString() : null;
</script>
 <?php
// Reminder button
if(isLoginUserPage()){
                if (isset($_SESSION['telegram_token']) && isset($_SESSION['telegram_token_permission_disabled'])) {

                if($_SESSION['telegram_token'] == '0' && $_SESSION['telegram_token_permission_disabled'] == false){
                echo getPopupAlert(
                    'Link Telegram',
                    'Link your Telegram to stay updated!',
                    'reminderButton',
                    '<h6 class="font-secondaryBold button-text" id="enableNowBtn" onclick="openLink(\'https://t.me/BossGPTAssistantBot?start=connect_' . $_SESSION['user_id'] . '\')">Enable Now</h6>',
                    'special-popup-container',
                    'https://res.cloudinary.com/da6qujoed/image/upload/v1745509466/sendicon_zvrv33.png',
                    'telegram'
                );
            }}
            if (isset($_SESSION['discord_token']) && isset($_SESSION['discord_token_permission_disabled']) && $_SESSION['discord_token_permission_disabled'] == '0') {
                echo getPopupAlert('Link Discord', 'Link your Discord to stay updated!', 'reminderButton', '<h6 class="font-secondaryBold button-text" id="enableNowBtn" onclick="openLink(\'' . $_ENV['DISCORD_BOT_INVITE_URL'] . '\')">Enable Now</h6>', 'special-popup-container', 
                'https://res.cloudinary.com/da6qujoed/image/upload/v1745510472/discord_zowxul.png', 'discord');
            }
            if (isset($_SESSION['fcm_token_permission_disabled']) && $_SESSION['fcm_token_permission_disabled'] == '0') {
                if(isset($_SESSION['user_id'])){
                    
                echo getPopupAlert('Enable Notifications', 'Stay updated! Enable browser notifications to get the 
                latest alerts instantly.', 'reminderButton', '<h6 class="font-secondaryBold button-text" id="enableNowBtn" onclick="openModal(\'notificationPermissionModal\')">Enable Now</h6>','showFcmPopup', 'https://res.cloudinary.com/da6qujoed/image/upload/v1743687520/belliconImage_vnxkhi.png', 'fcm_permission_reminder');
            }   
            }

        }
            ?>
