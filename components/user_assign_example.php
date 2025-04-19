<?php
// Example of how to use the user assignment dropdown component

// Typically you'd get this from your database
$projectUsers = [
    [
        'id' => 72,
        'username' => 'mirzaahmad7553',
        'role' => 'Creator'
    ],
    [
        'id' => 78,
        'username' => 'Taimoor',
        'role' => 'DESIGNER'
    ],
    [
        'id' => 80,
        'username' => 'Abdul Kareem',
        'role' => 'Developer'
    ],
    [
        'id' => 81,
        'username' => 'Sheikh Omer',
        'role' => 'Manager'
    ]
];

// Optional: Pre-selected users
$selectedUsers = [72]; // For example, pre-select the creator

// Define variables to be used in the component
$label = 'Assigned Users';
$required = true;
$helperText = 'You can select multiple users. Click to select/deselect.';
$users = $projectUsers;

// Include the component
include 'user_assign_dropdown.php';
?>

<!-- In a real form, you might have hidden inputs to collect the selected user IDs -->
<form id="taskForm" method="post" action="process_task.php">
    <!-- Other form fields would go here -->
    
    <div id="hiddenAssigneesContainer">
        <!-- This will be populated with hidden inputs by JavaScript -->
    </div>
    
    <button type="submit" class="btn btn-primary mt-3">Save Task</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taskForm = document.getElementById('taskForm');
    const hiddenContainer = document.getElementById('hiddenAssigneesContainer');
    
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            // Clear previous hidden inputs
            hiddenContainer.innerHTML = '';
            
            // Get all checked user checkboxes
            const checkedUsers = document.querySelectorAll('.user-checkbox:checked');
            
            if (checkedUsers.length === 0) {
                e.preventDefault();
                alert('Please assign at least one user to this task.');
                return false;
            }
            
            // Create hidden inputs for each selected user
            checkedUsers.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'assignees[]';
                hiddenInput.value = checkbox.value;
                hiddenContainer.appendChild(hiddenInput);
            });
        });
    }
});
</script> 