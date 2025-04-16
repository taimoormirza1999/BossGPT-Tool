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
                            <button type="button" class="btn btn-main-primary" id="createProjectBtn"><?php echo getAddSquareIcon(); ?>Create Project</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Task Modal -->
            <div class="modal fade" id="editTaskModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Task</h5>
                            <button type="button" class="btn-close close-icon-btn" data-bs-dismiss="modal"></button>
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

          
            <div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="assignUserModalLabel">Invite User to Project</h5>
                            <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                                aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                        </div>

                        <div class="modal-body position-relative">
                            <button class="btn btn-main-primary position-absolute top-5 add-user-btn-top-right"
                                style="right: 10px;" id="addUserBtn">
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
                            <button type="button" class="btn btn-main-primary" id="addNewUserBtn"><i
                                    class="bi bi-send"></i>&nbsp;Send Invite</button>
                        </div>
                    </div>
                </div>
            </div>
<!-- Selected Tree template -->


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
                                <small class="text-muted">Create a new task by setting its title, deadline, and assigning team members to keep everyone aligned.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskTitle" class="form-label">Task&nbsp;Title<?php echo required_field(); ?></label>
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
                                    <label for="newTaskPicture" class="form-label"> <?php echo getFileIcon(); ?> <!-- Bootstrap Icon -->
                                    <span>Choose Files</span><?php echo required_field(); ?></label>
                                    <input type="file" class="d-none" id="newTaskPicture" accept="image/*">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-main-primary" id="createTaskBtn"><?php echo getAddSquareIcon(); ?>Create Task</button>
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
]                            <button type="button" class="btn btn-main-primary" id="saveSubtaskBtn">Add Subtask</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log Modal -->
            <div class="modal fade" id="activityLogModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered ">
                    <div class="modal-content">
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
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this new modal for enlarged images after your other modals -->
            <div class="modal fade" id="imageModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Task Image</h5>
                            <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                                aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="enlargedImage" src="" alt="Enlarged task image"
                                style="max-width: 100%; max-height: 80vh;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Garden Stats Modal -->
            <div class="modal fade" id="gardenStatsModal" tabindex="-1" aria-labelledby="gardenStatsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <!-- Loading overlay removed as requested -->            
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="gardenStatsModalLabel">Garden Statistics</h5>
                            <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal"
                            aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                        </div>
                        
                        <div class="modal-body p-0">
                            <ul class="nav nav-pills mb-3 p-3" id="garden-stats-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="garden-all-tab" data-bs-toggle="pill" data-bs-target="#garden-all" type="button" role="tab">All Projects</button>
                                </li>
                                <?php foreach ($projects as $project): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="garden-project-<?= $project['id'] ?>-tab" data-bs-toggle="pill" 
                                            data-bs-target="#garden-project-<?= $project['id'] ?>" type="button" role="tab">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="tab-content p-3" id="garden-stats-content">
                                <!-- All Projects Tab -->
                                <div class="tab-pane fade show active" id="garden-all" role="tabpanel">
                                    <?php
                                    $plants = $allPlants;
                                    $completedPlants = array_filter($plants, function($p) { return $p['stage'] == 'tree'; });
                                    $growingPlants = array_filter($plants, function($p) { return $p['stage'] == 'growing'; });
                                    $sproutPlants = array_filter($plants, function($p) { return $p['stage'] == 'sprout'; });
                                    $deadPlants = array_filter($plants, function($p) { return $p['stage'] == 'dead'; });
                                    
                                    // Group plants by task status
                                    $plantsByStatus = [
                                        'todo' => [],
                                        'in_progress' => [],
                                        'done' => [],
                                        'deleted' => []
                                    ];
                                    
                                    foreach ($plants as $plant) {
                                        $status = $plant['task_status'] ?? 'unknown';
                                        if (isset($plantsByStatus[$status])) {
                                            $plantsByStatus[$status][] = $plant;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-4">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between text-center mb-4">
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌱</div>
                                                            <div class="garden-count"><?= count($sproutPlants) ?></div>
                                                            <div class="garden-label">Seeds</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌿</div>
                                                            <div class="garden-count"><?= count($growingPlants) ?></div>
                                                            <div class="garden-label">Growing</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌳</div>
                                                            <div class="garden-count"><?= count($completedPlants) ?></div>
                                                            <div class="garden-label">Flourishing</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🥀</div>
                                                            <div class="garden-count"><?= count($deadPlants) ?></div>
                                                            <div class="garden-label">Dead</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (count($plants) > 0): ?>
                                                        <div class="progress mb-3" style="height: 20px; border-radius: 10px;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: <?= round(count($completedPlants) / count($plants) * 100) ?>%" 
                                                                title="Flourishing: <?= count($completedPlants) ?>">
                                                                <?= round(count($completedPlants) / count($plants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                style="width: <?= round(count($growingPlants) / count($plants) * 100) ?>%"
                                                                title="Growing: <?= count($growingPlants) ?>">
                                                                <?= round(count($growingPlants) / count($plants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                style="width: <?= round(count($sproutPlants) / count($plants) * 100) ?>%"
                                                                title="Seeds: <?= count($sproutPlants) ?>">
                                                                <?= round(count($sproutPlants) / count($plants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?= round(count($deadPlants) / count($plants) * 100) ?>%"
                                                                title="Dead: <?= count($deadPlants) ?>">
                                                                <?= round(count($deadPlants) / count($plants) * 100) ?>%
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Garden Stats</h6>
                                                                <table class="table table-sm">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>Total Plants:</td>
                                                                            <td class="text-end"><strong><?= count($plants) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks Completed:</td>
                                                                            <td class="text-end"><strong><?= count($plantsByStatus['done']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks In Progress:</td>
                                                                            <td class="text-end"><strong><?= count($plantsByStatus['in_progress']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks Pending:</td>
                                                                            <td class="text-end"><strong><?= count($plantsByStatus['todo']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Garden Health:</td>
                                                                            <td class="text-end"><strong><?php 
                                                                                $health = count($plants) > 0 ? 
                                                                                    round(((count($completedPlants) + count($growingPlants)) / count($plants)) * 100) : 0;
                                                                                echo $health . '%';
                                                                            ?></strong></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Achievements</h6>
                                                                <div class="d-flex flex-wrap">
                                                                    <?php if (count($plants) >= 5): ?>
                                                                        <div class="badge bg-primary p-2 m-1">
                                                                            🌱 Garden Starter
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count($completedPlants) >= 3): ?>
                                                                        <div class="badge bg-success p-2 m-1">
                                                                            🌳 Forest Creator
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count(array_filter($completedPlants, function($p) { return $p['size'] == 'large'; })) >= 1): ?>
                                                                        <div class="badge bg-info p-2 m-1">
                                                                            🌲 Project Completer
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count($plants) == 0): ?>
                                                                        <div class="alert alert-warning w-100">Complete tasks to earn achievement badges!</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info text-center">
                                                            Your garden is empty. Start completing tasks to grow your garden!
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (count($plants) > 0): ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-dark text-white">
                                                    <h6 class="mb-0">Your Plants</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th>Project</th>
                                                                    <th>Task</th>
                                                                    <th>Plant Type</th>
                                                                    <th>Growth Stage</th>
                                                                    <th>Size</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($plants as $plant): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($plant['project_title'] ?? 'No Project') ?></td>
                                                                        <td><?= htmlspecialchars($plant['task_title'] ?? 'Unnamed Task') ?></td>
                                                                        <td><?= htmlspecialchars($plant['plant_type'] ?? 'Unknown') ?></td>
                                                                        <td>
                                                                            <?php 
                                                                                $stageIcons = [
                                                                                    'sprout' => '🌱',
                                                                                    'growing' => '🌿',
                                                                                    'tree' => '🌳',
                                                                                    'dead' => '🥀'
                                                                                ];
                                                                                echo ($stageIcons[$plant['stage']] ?? '') . ' ' . ucfirst($plant['stage'] ?? 'Unknown');
                                                                            ?>
                                                                        </td>
                                                                        <td><?= ucfirst($plant['size'] ?? 'Unknown') ?></td>
                                                                        <td>
                                                                            <?php
                                                                                $statusBadges = [
                                                                                    'todo' => '<span class="badge bg-secondary">To Do</span>',
                                                                                    'in_progress' => '<span class="badge bg-warning">In Progress</span>',
                                                                                    'done' => '<span class="badge bg-success">Completed</span>',
                                                                                    'deleted' => '<span class="badge bg-danger">Deleted</span>'
                                                                                ];
                                                                                echo $statusBadges[$plant['task_status'] ?? 'unknown'] ?? $plant['task_status'] ?? 'Unknown';
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Project Specific Tabs -->
                                <?php foreach ($projects as $project): 
                                    $projectId = $project['id'];
                                    $projectPlants = array_filter($allPlants, function($p) use ($projectId) { 
                                        return isset($p['project_id']) && $p['project_id'] == $projectId; 
                                    });
                                    
                                    $projectCompletedPlants = array_filter($projectPlants, function($p) { return $p['stage'] == 'tree'; });
                                    $projectGrowingPlants = array_filter($projectPlants, function($p) { return $p['stage'] == 'growing'; });
                                    $projectSproutPlants = array_filter($projectPlants, function($p) { return $p['stage'] == 'sprout'; });
                                    $projectDeadPlants = array_filter($projectPlants, function($p) { return $p['stage'] == 'dead'; });
                                    
                                    // Group plants by task status
                                    $projectPlantsByStatus = [
                                        'todo' => [],
                                        'in_progress' => [],
                                        'done' => [],
                                        'deleted' => []
                                    ];
                                    
                                    foreach ($projectPlants as $plant) {
                                        $status = $plant['task_status'] ?? 'unknown';
                                        if (isset($projectPlantsByStatus[$status])) {
                                            $projectPlantsByStatus[$status][] = $plant;
                                        }
                                    }
                                ?>
                                <div class="tab-pane fade" id="garden-project-<?= $projectId ?>" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12 mb-4">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h5 class="mb-3"><?= htmlspecialchars($project['title']) ?> Garden</h5>
                                                    <div class="d-flex justify-content-between text-center mb-4">
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌱</div>
                                                            <div class="garden-count"><?= count($projectSproutPlants) ?></div>
                                                            <div class="garden-label">Seeds</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌿</div>
                                                            <div class="garden-count"><?= count($projectGrowingPlants) ?></div>
                                                            <div class="garden-label">Growing</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🌳</div>
                                                            <div class="garden-count"><?= count($projectCompletedPlants) ?></div>
                                                            <div class="garden-label">Flourishing</div>
                                                        </div>
                                                        <div class="garden-stat-item">
                                                            <div class="garden-icon">🥀</div>
                                                            <div class="garden-count"><?= count($projectDeadPlants) ?></div>
                                                            <div class="garden-label">Dead</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (count($projectPlants) > 0): ?>
                                                        <div class="progress mb-3" style="height: 20px; border-radius: 10px;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: <?= round(count($projectCompletedPlants) / count($projectPlants) * 100) ?>%" 
                                                                title="Flourishing: <?= count($projectCompletedPlants) ?>">
                                                                <?= round(count($projectCompletedPlants) / count($projectPlants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                style="width: <?= round(count($projectGrowingPlants) / count($projectPlants) * 100) ?>%"
                                                                title="Growing: <?= count($projectGrowingPlants) ?>">
                                                                <?= round(count($projectGrowingPlants) / count($projectPlants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                style="width: <?= round(count($projectSproutPlants) / count($projectPlants) * 100) ?>%"
                                                                title="Seeds: <?= count($projectSproutPlants) ?>">
                                                                <?= round(count($projectSproutPlants) / count($projectPlants) * 100) ?>%
                                                            </div>
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?= round(count($projectDeadPlants) / count($projectPlants) * 100) ?>%"
                                                                title="Dead: <?= count($projectDeadPlants) ?>">
                                                                <?= round(count($projectDeadPlants) / count($projectPlants) * 100) ?>%
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Project Garden Stats</h6>
                                                                <table class="table table-sm">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>Total Plants:</td>
                                                                            <td class="text-end"><strong><?= count($projectPlants) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks Completed:</td>
                                                                            <td class="text-end"><strong><?= count($projectPlantsByStatus['done']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks In Progress:</td>
                                                                            <td class="text-end"><strong><?= count($projectPlantsByStatus['in_progress']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Tasks Pending:</td>
                                                                            <td class="text-end"><strong><?= count($projectPlantsByStatus['todo']) ?></strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Garden Health:</td>
                                                                            <td class="text-end"><strong><?php 
                                                                                $health = count($projectPlants) > 0 ? 
                                                                                    round(((count($projectCompletedPlants) + count($projectGrowingPlants)) / count($projectPlants)) * 100) : 0;
                                                                                echo $health . '%';
                                                                            ?></strong></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Project Achievements</h6>
                                                                <div class="d-flex flex-wrap">
                                                                    <?php if (count($projectPlants) >= 5): ?>
                                                                        <div class="badge bg-primary p-2 m-1">
                                                                            🌱 Garden Starter
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count($projectCompletedPlants) >= 3): ?>
                                                                        <div class="badge bg-success p-2 m-1">
                                                                            🌳 Forest Creator
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count(array_filter($projectCompletedPlants, function($p) { return $p['size'] == 'large'; })) >= 1): ?>
                                                                        <div class="badge bg-info p-2 m-1">
                                                                            🌲 Project Completer
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (count($projectPlants) == 0): ?>
                                                                        <div class="alert alert-warning w-100">Complete tasks to earn achievement badges!</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info text-center">
                                                            This project's garden is empty. Start completing tasks to grow your garden!
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (count($projectPlants) > 0): ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-dark text-white">
                                                    <h6 class="mb-0">Project Plants</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th>Task</th>
                                                                    <th>Plant Type</th>
                                                                    <th>Growth Stage</th>
                                                                    <th>Size</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($projectPlants as $plant): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($plant['task_title'] ?? 'Unnamed Task') ?></td>
                                                                        <td><?= htmlspecialchars($plant['plant_type'] ?? 'Unknown') ?></td>
                                                                        <td>
                                                                            <?php 
                                                                                $stageIcons = [
                                                                                    'sprout' => '🌱',
                                                                                    'growing' => '🌿',
                                                                                    'tree' => '🌳',
                                                                                    'dead' => '🥀'
                                                                                ];
                                                                                echo ($stageIcons[$plant['stage']] ?? '') . ' ' . ucfirst($plant['stage'] ?? 'Unknown');
                                                                            ?>
                                                                        </td>
                                                                        <td><?= ucfirst($plant['size'] ?? 'Unknown') ?></td>
                                                                        <td>
                                                                            <?php
                                                                                $statusBadges = [
                                                                                    'todo' => '<span class="badge bg-secondary">To Do</span>',
                                                                                    'in_progress' => '<span class="badge bg-warning">In Progress</span>',
                                                                                    'done' => '<span class="badge bg-success">Completed</span>',
                                                                                    'deleted' => '<span class="badge bg-danger">Deleted</span>'
                                                                                ];
                                                                                echo $statusBadges[$plant['task_status'] ?? 'unknown'] ?? $plant['task_status'] ?? 'Unknown';
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif;?>
                                </div>
                                <?php endforeach;?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="garden.php" class="btn btn-main-primary" id="viewGardenBtn"><?php echo getTreeIcon(); ?> My Garden</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                .garden-stat-item {
                    padding: 1rem;
                    text-align: center;
                    flex: 1;
                }
                .garden-icon {
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                }
                .garden-count {
                    font-weight: bold;
                    font-size: 1.8rem;
                }
                .garden-label {
                    font-size: 1rem;
                    color: #666;
                }
            </style>
            <?php if(!isset($_SESSION['fcm_token']) || $_SESSION['fcm_token'] == '0'): ?>
            <!-- Notification Permission Modal -->
            <div class="modal fade" id="notificationPermissionModal" tabindex="-1" aria-labelledby="notificationPermissionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="notificationPermissionModalLabel">Enable Task Reminders</h5>
                            <button type="button" class="btn btn-link p-0 text-white close-icon-btn" data-bs-dismiss="modal" aria-label="Close"><?php echo getCloseSquareIcon(); ?></button>
                        </div>
                        <div class="modal-body">
                
                            <div class="ratio ratio-16x9 mb-4" style="border-radius: 12px; overflow: hidden; ">
                                <iframe width="560" height="315" src="https://www.youtube.com/embed/-fTV9_SqnKE?si=wizXX7DUlSgTXPfZ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                            </div>
                            
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <div>
                                    You'll need to enable browser notifications to receive reminders for your tasks and deadlines.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Remind me later</button>
                            <button type="button" class="btn btn-main-primary" id="enableNotificationsBtn">
                                <i class="bi bi-bell-fill me-2"></i>Enable Notifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>