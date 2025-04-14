<?php

?>

<div class="custom-date-input">
    <label for="<?= $id ?? 'taskDueDate' ?>" class="form-label">
        <?= $label ?? 'Due Date' ?><?php if (isset($required) && $required): ?><span class="required-asterisk">*</span><?php endif; ?>
    </label>
    
    <div class="date-input-wrapper">
        <input 
            type="date" 
            class="form-control" 
            id="<?= $id ?? 'taskDueDate' ?>" 
            name="<?= $name ?? 'due_date' ?>"
            <?= isset($value) ? 'value="' . $value . '"' : '' ?>
            <?= isset($required) && $required ? 'required' : '' ?>
            <?= isset($disabled) && $disabled ? 'disabled' : '' ?>
        >
        <div class="calendar-icon">
            <?php 
            if (function_exists('getCalendarIcon')) {
                echo getCalendarIcon();
            } else {
                // Fallback icon if function doesn't exist
                echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3.5 9.09H20.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>';
            }
            ?>
        </div>
    </div>
    
    <?php if (isset($helperText)): ?>
        <small class="form-text text-muted"><?= $helperText ?></small>
    <?php endif; ?>
</div>

<style>
.custom-date-input {
    position: relative;
    margin-bottom: 1rem;
}

.date-input-wrapper {
    position: relative;
}

.custom-date-input input[type="date"] {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    background-color: var(--bs-secondary-bg, #6c6577);
    border: 1px solid var(--bs-border-color, #dee2e6);
    width: 100%;
    color: var(--bs-body-color, #fff);
    /* box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); */
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px; /* Make room for the icon */
}

.custom-date-input input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    cursor: pointer;
}

.calendar-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Placeholder styling to match the screenshot */
.custom-date-input input[type="date"]::placeholder {
    color: #ffffff;
    opacity: 0.7;
}

/* Styling for dark mode */
body.dark-mode .custom-date-input input[type="date"] {
    background-color: #333;
    border-color: #444;
    color: #fff;
}

body.dark-mode .calendar-icon svg path {
    stroke: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('.custom-date-input input[type="date"]');
    
    dateInputs.forEach(input => {
        // Format placeholder on load
        updateDatePlaceholder(input);
        
        // Update placeholder when value changes
        input.addEventListener('change', function() {
            updateDatePlaceholder(this);
        });
    });
    
    function updateDatePlaceholder(input) {
        if (!input.value) {
            // If no date selected, display dd/mm/yyyy
            input.setAttribute('placeholder', 'dd/mm/yyyy');
        }
    }
});
</script> 