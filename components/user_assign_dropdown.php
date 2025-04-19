<?php
/**
 * User Assignment Dropdown Component
 * Displays a dropdown for assigning users to tasks
 */

/**
 * Generate user initials from username
 * 
 * @param string $username User's name
 * @return string Initials (max 2 characters)
 */
function getUserInitials($username) {
    $parts = preg_split('/\s+/', $username);
    $initials = '';
    
    if (count($parts) >= 2) {
        // Get first letter of first and last name
        $initials = mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts)-1], 0, 1);
    } else {
        // If single name, take up to 2 first letters
        $initials = mb_substr($username, 0, min(2, mb_strlen($username)));
    }
    
    return mb_strtoupper($initials);
}

/**
 * Generate a background color based on username
 * 
 * @param string $username User's name
 * @return string CSS color code
 */
function getUserAvatarColor($username) {
    // Predefined colors that match the design
    $colors = [
        '#7A6AFF', // Purple
        '#FF6A8E', // Pink
        '#6AC4FF', // Blue
        '#FFB46A', // Orange
        '#6AFFB4', // Green
    ];
    
    // Get a consistent color based on username
    $hash = crc32($username);
    $colorIndex = abs($hash) % count($colors);
    
    return $colors[$colorIndex];
}
?>

<div class="user-assign-dropdown">
    <label for="taskAssignees" class="form-label">
        <?= $label ?? 'Assign to' ?><?php if (isset($required) && $required): ?><span class="required-asterisk">*</span><?php endif; ?>
    </label>
    
    <div class="dropdown">
        <button class="btn dropdown-toggle assign-dropdown-btn" type="button" id="assignDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-plus-fill me-2"></i><span>Assign to</span>
        </button>
        
        <div class="dropdown-menu assign-users-menu p-2" aria-labelledby="assignDropdown">
            <div class="mb-2">
                <input type="text" class="form-control search-users" placeholder="Search or enter email..." autocomplete="off">
            </div>
            
            <div class="user-list">
                <?php if (isset($users) && is_array($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            $initials = getUserInitials($user['username']);
                            $bgColor = getUserAvatarColor($user['username']);
                        ?>
                        <div class="user-item" data-user-id="<?= $user['id'] ?>">
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" value="<?= $user['id'] ?>" id="user-<?= $user['id'] ?>" 
                                    <?= isset($selectedUsers) && in_array($user['id'], $selectedUsers) ? 'checked' : '' ?>>
                                <label class="form-check-label d-flex align-items-center" for="user-<?= $user['id'] ?>">
                                    <div class="user-avatar" style="background-color: <?= $bgColor ?>">
                                        <?= $initials ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                                        <?php if (!empty($user['role'])): ?>
                                            <span class="user-role"><?= htmlspecialchars($user['role']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-users">No users available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="selected-users mt-2">
        <!-- Selected users will appear here -->
    </div>
    
    <?php if (isset($helperText)): ?>
        <small class="form-text text-muted"><?= $helperText ?></small>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userItems = document.querySelectorAll('.user-item');
    const selectedUsersContainer = document.querySelector('.selected-users');
    const searchInput = document.querySelector('.search-users');
    
    // Handle user selection
    userItems.forEach(item => {
        const checkbox = item.querySelector('.user-checkbox');
        const userId = item.dataset.userId;
        const userName = item.querySelector('.user-name').textContent;
        const userInitials = item.querySelector('.user-avatar').textContent.trim();
        const avatarColor = item.querySelector('.user-avatar').style.backgroundColor;
        
        // Initialize selected users display
        if (checkbox.checked) {
            addSelectedUserTag(userId, userName, userInitials, avatarColor);
        }
        
        item.addEventListener('click', function(e) {
            // Don't intercept actual checkbox clicks
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    addSelectedUserTag(userId, userName, userInitials, avatarColor);
                } else {
                    removeSelectedUserTag(userId);
                }
                
                // Trigger change event for form validation
                checkbox.dispatchEvent(new Event('change'));
            }
        });
        
        // Handle direct checkbox changes
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                addSelectedUserTag(userId, userName, userInitials, avatarColor);
            } else {
                removeSelectedUserTag(userId);
            }
        });
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        userItems.forEach(item => {
            const userName = item.querySelector('.user-name').textContent.toLowerCase();
            if (userName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Add user tag to selected users area
    function addSelectedUserTag(userId, userName, initials, bgColor) {
        // Check if already added
        if (document.querySelector(`.selected-user-tag[data-user-id="${userId}"]`)) {
            return;
        }
        
        const tag = document.createElement('div');
        tag.className = 'selected-user-tag';
        tag.dataset.userId = userId;
        tag.innerHTML = `
            <div class="user-avatar" style="background-color: ${bgColor}">${initials}</div>
            <span>${userName}</span>
            <button type="button" class="remove-user-btn" aria-label="Remove user">×</button>
        `;
        
        // Add remove event
        tag.querySelector('.remove-user-btn').addEventListener('click', function() {
            // Uncheck the checkbox
            const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
            if (checkbox) {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            }
            
            // Remove the tag
            tag.remove();
        });
        
        selectedUsersContainer.appendChild(tag);
    }
    
    // Remove user tag
    function removeSelectedUserTag(userId) {
        const tag = document.querySelector(`.selected-user-tag[data-user-id="${userId}"]`);
        if (tag) {
            tag.remove();
        }
    }
});
</script>

<style>
.user-assign-dropdown {
    position: relative;
}

.assign-dropdown-btn {
    display: flex;
    align-items: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #212529;
    width: 100%;
    text-align: left;
    justify-content: flex-start;
}

.assign-users-menu {
    width: 100%;
    max-height: 250px;
    overflow-y: auto;
}

.user-list {
    margin-top: 8px;
}

.user-item {
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
}

.user-item:hover {
    background-color: #f8f9fa;
}

.user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 10px;
    font-size: 12px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
}

.user-role {
    font-size: 12px;
    color: #6c757d;
}

.selected-users {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.selected-user-tag {
    display: flex;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 16px;
    padding: 4px 8px 4px 4px;
    font-size: 14px;
}

.selected-user-tag .user-avatar {
    width: 24px;
    height: 24px;
    font-size: 10px;
    margin-right: 6px;
}

.remove-user-btn {
    background: none;
    border: none;
    color: #6c757d;
    font-size: 18px;
    cursor: pointer;
    padding: 0 0 0 4px;
    line-height: 1;
}

.form-check-label {
    cursor: pointer;
    width: 100%;
}

.search-users {
    margin-bottom: 8px;
    font-size: 14px;
}

/* Adaptations for dark mode */
body.dark-mode .assign-dropdown-btn {
    background-color: #2c2c2c;
    border-color: #3d3d3d;
    color: #e1e1e1;
}

body.dark-mode .user-item:hover {
    background-color: #2c2c2c;
}

body.dark-mode .selected-user-tag {
    background-color: #2c2c2c;
    color: #e1e1e1;
}
</style> 