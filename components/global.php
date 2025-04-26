<script>

function displayTasks(tasks) {
    const tbody = document.getElementById('tasksTableBody');
    tbody.innerHTML = '';

    if (tasks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No tasks found.</td></tr>';
        return;
    }

    tasks.forEach(task => {
        const assignedUsername = escapeHtml(task.assigned_username || 'Unknown');
        const assignedRole = escapeHtml(task.assigned_role || '');
        const dueDate = formatDueDate(task.due_date);  // You can format dates nicely
        const title = formatDateTime(task.created_at); // Assuming title is datetime here based on your image
        const description = escapeHtml(task.description || '');

        const avatarUrl = task.avatar_url || 'default-avatar.png'; // fallback if no image

        const row = `
            <tr>
                <td>${title}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="${avatarUrl}" class="rounded-circle" style="width:30px;height:30px;object-fit:cover;">
                        <div>
                            <div>${assignedUsername}</div>
                            <small class="text-muted">${assignedRole}</small>
                        </div>
                    </div>
                </td>
                <td>${dueDate}</td>
                <td>${description}</td>
            </tr>
        `;

        tbody.innerHTML += row;
    });
}

    function loadProjects2() {
        // showLoading();
        fetch('?api=get_projects')
            .then(response => response.json())
            .then(data => {
                // console.log("Loaded projects: ", data.projects);
                if (data.success) {
                    const projectDropdown = document.getElementById('projectDropdown');
                    projectDropdown.innerHTML = '';

                    if (!data.projects || data.projects.length === 0) {
                        // If no projects exist, display a placeholder item
                        const placeholder = document.createElement('li');
                        placeholder.className = 'dropdown-item disabled';
                        placeholder.textContent = 'No projects found';
                        projectDropdown.appendChild(placeholder);
                    } else {
                        // Loop through the projects and create dropdown items
                        data.projects.forEach(project => {
                            const li = document.createElement('li');
                            li.className = 'dropdown-item';
                            li.innerHTML = `
                            <button class="dropdown-item text-capitalize" type="button" data-id="${project.id}" title="${escapeHtml(project.title)}">
                                ${escapeHtml(project.title)}
                            </button>
                        `;
                            projectDropdown.appendChild(li);
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
                                loadActivityLog2();
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
            .finally();
    }

    function loadProjects3() {
    
        // showLoading();
        fetch('?api=get_projects')
            .then(response => response.json())
            .then(data => {
                // console.log("Loaded projects: ", data.projects);
                if (data.success) {
                    const projectDropdown = document.getElementById('projectDropdown3');
                    projectDropdown.innerHTML = '';

                    if (!data.projects || data.projects.length === 0) {
                        // If no projects exist, display a placeholder item
                        const placeholder = document.createElement('li');
                        placeholder.className = 'dropdown-item disabled';
                        placeholder.textContent = 'No projects found';
                        projectDropdown.appendChild(placeholder);
                    } else {
                        // Loop through the projects and create dropdown items
                        data.projects.forEach(project => {
                            const li = document.createElement('li');
                            li.className = 'dropdown-item';
                            li.innerHTML = `
                            <button class="dropdown-item text-capitalize" type="button" data-id="${project.id}" title="${escapeHtml(project.title)}">
                                ${escapeHtml(project.title)}
                            </button>
                        `;
                            projectDropdown.appendChild(li);
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
                                loadActivityLog2();
                            }
                        });
                    });

                    // After projects are loaded, check for saved project
                    const savedProject = getLastSelectedProject();
                    if (savedProject && savedProject !== 'null' && savedProject !== '0') {
                        const projectId = parseInt(savedProject);
                        const projectButton = document.querySelector(`#projectDropdown3 button[data-id="${projectId}"]`);
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
            .finally();
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadProjects2();
        loadProjects3();
        loadActivityLog2();
        loadTasks2(currentProject, displayTasks);
    });

    function loadActivityLog2(startDate = null, endDate = null) {
    // showLoading();
    const payload = {
        project_id: currentProject
    };

    if (startDate && endDate) {
        payload.start_date = startDate;
        payload.end_date = endDate;
    }

    fetch('?api=get_activity_log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        const activityList = document.getElementById('activityLogList');
        activityList.innerHTML = '';

        if (!data.success || data.logs.length === 0) {
            activityList.innerHTML = '<p class="text-center text-muted">No activities found.</p>';
            return;
        }

        data.logs.forEach(log => {
            const username = escapeHtml(log.username || 'User');
            const parts = username.trim().split(' ');

            let initials = '';
            if (parts.length === 1) {
                initials = parts[0].substring(0, 2).toUpperCase();
            } else {
                const firstInitial = parts[0][0] ? parts[0][0].toUpperCase() : '';
                const secondInitial = parts[1][0] ? parts[1][0].toUpperCase() : '';
                initials = firstInitial + '.' + secondInitial;
            }

            const createdAt = formatDateTime(log.created_at);
            const actionType = escapeHtml(log.action_type);
            const description = escapeHtml(log.description);

            const activityItem = `
                <div class="list-group-item list-group-item-action bg-transparent text-white d-flex align-items-start rounded mb-3" style="border: 0px;">
                    <div class="me-3">
                        <div style="background: rgba(42, 95, 255, 1); height:3.1rem; width:3.1rem;" class="rounded-circle text-white d-flex align-items-center justify-content-center">
                            ${initials}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div>
                            <strong>${username}</strong> moved <span class="text-decoration-underline">${description}</span> as <strong>${actionType}</strong>.
                        </div>
                        <small class="text-blue d-flex align-items-center mt-1" style="color: rgba(130, 161, 255, 1);">
                            <i class="bi bi-clock me-1"></i> ${createdAt}
                        </small>
                    </div>
                </div>
            `;

            activityList.innerHTML += activityItem;
        });
    })
    .catch(error => {
        console.error('Error loading activity log:', error);
        alert('Failed to load activity log');
    })
    .finally(hideLoading);
}

   

</script>