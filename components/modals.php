<!-- New Project Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-lg">
            <div class="modal-header text-white border-0 rounded-t-lg">
                <h5 class="modal-title">Create New Project</h5>
                <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                    aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
            </div>
            <div class="modal-body">
                <form id="newProjectForm">
                    <div class="mb-3">
                        <label for="projectTitle" class="form-label">Project
                            Title<?php echo required_field(); ?></label>
                        <input type="text" class="form-control" id="projectTitle" placeholder="Enter project title"
                            required>
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
                <button type="button" class="btn btn-main-primary"
                    id="createProjectBtn"><?php echo getAddSquareIcon(); ?>Create Project</button>
            </div>
        </div>
    </div>
</div>
<?php if (!isPage('profile')) { ?>
    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                        aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
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
                            <?php
                            // Use our custom date input component for edit task
                            $id = 'editTaskDueDate';
                            $name = 'edit_due_date';
                            $label = 'Due Date';
                            $required = false;
                            $helperText = '';
                            include 'components/date_input.php';
                            ?>
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
                            <label for="editTaskPicture" class="form-label"><?php echo getFileIcon(); ?>
                                <!-- Bootstrap Icon -->
                                <span class="mx-2">Task Picture</span></label>
                            <input type="file" class="d-none" id="editTaskPicture" accept="image/*">

                            <!-- Image preview container -->
                            <div id="editImagePreviewContainer" style="display: none; margin-top: 10px;">
                                <div style="position: relative; display: inline-block;">
                                    <img id="editImagePreview" src="" alt="Image Preview"
                                        style="max-width: 100%; max-height: 200px; border-radius: 8px; object-fit: contain; border: 1px solid #e0e0e0; cursor: pointer;">
                                    <button type="button" id="editRemovePreviewBtn"
                                        style="position: absolute; top: -10px; right: -10px; background: #fff; border: 1px solid #e0e0e0; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 6L6 18" stroke="#FF0000" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"></path>
                                            <path d="M6 6L18 18" stroke="#FF0000" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Remove Picture Button - only shown when a picture exists -->
                        <div class="mb-3" id="taskPictureContainer" style="display:none;">
                            <button type="button" class="btn btn-danger" id="removeTaskPictureBtn">Remove
                                Picture</button>
                        </div>

                        <!-- Add Tree Selection for Edit Task -->
                        <div class="mb-3">
                            <label class="form-label">
                                Choose Tree Type
                            </label>
                            <!-- A hidden input to store the chosen file -->
                            <input type="hidden" id="editPlantType" name="editPlantType">

                            <!-- Where the tree images will go -->
                            <div id="editTaskTreeContainer" class="tree-select-grid">
                            </div>
                        </div>
                    </form>

                    <!-- Add this new section for subtasks -->
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2 d-flex justify-content-between align-items-center">
                            Subtasks
                            <button type="button" class="btn btn-sm btn-main-primary" id="addSubtaskInModalBtn">
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
                    <button type="button" class="btn btn-main-primary" id="saveTaskBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- New Task Modal -->
    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white border-0 rounded-t-lg">
                    <h5 class="modal-title">Create New Task</h5>
                    <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                        aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                </div>
                <div class="modal-body">
                    <form id="newTaskForm">
                        <div class="mb-3">
                            <small class="text-muted">Create a new task by setting its title, deadline, and assigning team
                                members to keep everyone aligned.</small>
                        </div>
                        <div class="mb-3">
                            <label for="newTaskTitle"
                                class="form-label">Task&nbsp;Title<?php echo required_field(); ?></label>
                            <input type="text" class="form-control" id="newTaskTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="newTaskDescription"
                                class="form-label">Description<?php echo required_field(); ?></label>
                            <textarea class="form-control" id="newTaskDescription" rows="1"></textarea>
                        </div>
                        <div class="mb-3">
                            <?php
                            $id = 'newTaskDueDate';
                            $name = 'due_date';
                            $label = 'Due Date';
                            $required = true;
                            $helperText = '';
                            include 'components/date_input.php';
                            ?>
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
                            <label class="form-label">
                                Choose Tree Type <span style="color:red">*</span>
                            </label>
                            <!-- A hidden input to store the chosen file -->
                            <input type="hidden" id="selectedTreeType" name="selectedTreeType">

                            <!-- Where the tree images will go -->
                            <!-- The container with clickable tree images -->
                            <div id="taskTreeContainer" class="tree-select-grid">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newTaskPicture" class="form-label"> <?php echo getFileIcon(); ?>
                                <!-- Bootstrap Icon -->
                                <span class="mx-2">Choose Files</span><span class="required-asterisk">*</span></label>
                            <input type="file" class="d-none" id="newTaskPicture" accept="image/*">

                            <!-- Image preview container -->
                            <div id="imagePreviewContainer" style="display: none; margin-top: 10px;">
                                <div style="position: relative; display: inline-block;">
                                    <img id="imagePreview" src="" alt="Image Preview"
                                        style="max-width: 100%; max-height: 200px; border-radius: 8px; object-fit: contain; border: 1px solid #e0e0e0; cursor: pointer;">
                                    <button type="button" id="removePreviewBtn"
                                        style="position: absolute; top: -10px; right: -10px; background: #fff; border: 1px solid #e0e0e0; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 6L6 18" stroke="#FF0000" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"></path>
                                            <path d="M6 6L18 18" stroke="#FF0000" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-main-primary"
                        id="createTaskBtn"><?php echo getAddSquareIcon(); ?>Create Task</button>
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
                    <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                        aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
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
                            <textarea class="form-control" id="subtaskDescription" rows="1"></textarea>
                        </div>
                        <div class="mb-3">
                            <?php
                            // Use our custom date input component for subtask
                            $id = 'subtaskDueDate';
                            $name = 'subtask_due_date';
                            $label = 'Due Date';
                            $required = false;
                            $helperText = '';
                            include 'components/date_input.php';
                            ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    ] <button type="button" class="btn btn-main-primary" id="saveSubtaskBtn">Add Subtask</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Image Modal for Enlarged Task Images -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white border-0 rounded-t-lg">
                    <h5 class="modal-title" id="imageModalLabel">Task Image</h5>
                    <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                        aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="enlargedImage" src="" alt="Enlarged Image"
                        style="max-width: 100%; max-height: 80vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-lg">
            <div class="modal-header text-white border-0 rounded-t-lg">
                <h5 class="modal-title" id="assignUserModalLabel">Invite User to Project</h5>
                <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                    aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
            </div>

            <div class="modal-body position-relative">
                <button class="btn btn-main-primary position-absolute top-5 add-user-btn-top-right" style="right: 10px;"
                    id="addUserBtn">
                    <i class="bi bi-person-plus"></i> Add New User
                </button>
                <div id="userListContainer" class="mt-5">
                </div>
                <!-- No Users Message -->
                <div id="noUsersMessage" class="text-center py-2 d-none">
                    <p class="text-muted">No users assigned yet.</p>
                    <button class="btn btn-main-primary" id="addUserBtn">
                        <?php echo getAddUserIcon(); ?> Add New User
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- New User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white border-0 rounded-t-lg">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                    aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="newUserEmail" class="form-label">Email<?php echo required_field(); ?></label>
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
                <button type="button" class="btn btn-main-primary" id="addNewUserBtn"><i
                        class="bi bi-send"></i>&nbsp;Send Invite</button>
            </div>
        </div>
    </div>
</div>
<!-- Selected Tree template -->


<!-- Activity Log Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered ">
        <div class="modal-content d-flex justify-content-center mx-auto " style="max-width: 1250px;">
            <div class="modal-header text-white border-0 rounded-t-lg">
                <h5 class="modal-title">Recent Project Actions</h5>
                <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                    aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
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

        </div>
    </div>
</div>

<!-- Notification Permission Modal -->
<div class="modal fade" id="notificationPermissionModal" tabindex="-1"
    aria-labelledby="notificationPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white border-0 rounded-t-lg">
                <h5 class="modal-title" id="notificationPermissionModalLabel">Enable Task Reminders</h5>
                <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                    aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
            </div>
            <div class="modal-body">

                <div class="ratio ratio-16x9 mb-2" style="border-radius: 12px; overflow: hidden; ">
                    <iframe width="560" height="250" src="https://www.youtube.com/embed/-fTV9_SqnKE?si=wizXX7DUlSgTXPfZ"
                        title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>

                <div class="alert alert-main-primary d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div>
                        You'll need to enable browser notifications to receive reminders for your tasks and deadlines.
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal"
                    onclick="markAsEnabledNotification()">Mark as Enabled</button>
                <button type="button" class="btn btn-main-primary">
                    <i class="bi bi-bell-fill me-2"></i>Enable Notifications
                </button>
            </div>
        </div>
    </div>
</div>