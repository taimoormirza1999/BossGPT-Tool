<script>
  document.addEventListener('DOMContentLoaded', function () {
    var enableNotificationsBtn =false;
    <?php if(!isset($_SESSION['fcm_token']) || $_SESSION['fcm_token'] == '0'): ?>
      enableNotificationsBtn = true;
    <?php endif; ?>
    const openIconBtn = document.querySelector('.open-icon-btn');
    $(openIconBtn).toggleClass('d-none');
    $(openIconBtn).toggleClass('show');
  });
const notificationIcons = {
  project_created: `<?= getFolderIcon(); ?>`,
  user_removed: `<?= getProfileDeleteIcon(); ?>`,
  user_assigned: `<?= getProfileIcon(); ?>`,
  task_created: `<?= getclipboardIcon(); ?>`,
  task_status_updated: `<?= getclipboardIcon(); ?>`,
  task_picture_removed: `<?= getclipboardIcon(); ?>`,
  task_updated: `<?= getclipboardIcon(); ?>`,
  subtask_status_updated: `<?= getPaperclipIcon(); ?>`,
  subtask_created: `<?= getPaperclipIcon(); ?>`,
  subtask_deleted: `<?= getPaperclipIcon(); ?>`,
  subtask_updated: `<?= getPaperclipIcon(); ?>`,
  user_assigned: `<?= getProfileIcon(); ?>`,
};

function getNotificationIcon(action_type) {
  return notificationIcons[action_type] || '';
}

function getNotificationIconClass(action_type) {
  return "notification-icon-" + action_type|| '';
}
document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(el => {
    new bootstrap.Tooltip(el);
  });
});

function closeChatPannel() {
  const chatPannel = document.querySelector('.chat-pannel');
  chatPannel.classList.add('d-none');

  console.log($('.open-icon-btn'));
  $('.open-icon-btn').addClass('show');
  $('.open-icon-btn').removeClass('d-none');
  console.log($('.open-icon-btn'));
}
function DynamicClose(targetSelector) {
  const TargetElement = document.querySelector(targetSelector);
  TargetElement.classList.add('d-none');
}
function DynamicOpen(targetSelector) {
  const TargetElement = document.querySelector(targetSelector);
  if (TargetElement) {
    TargetElement.classList.remove('d-none');
    TargetElement.classList.add('show');
  }
}

function openChatPannel() {
  const openIconBtn = document.querySelector('.open-icon-btn');
  $(openIconBtn).toggleClass('d-none');
  $(openIconBtn).toggleClass('show');
  const chatPannel = document.querySelector('.chat-pannel');
  chatPannel.classList.remove('d-none');  
}
function changeTheme(theme) {
  // List of all possible theme classes
  const themes = ['light-mode', 'dark-mode', 'brown-mode', 'purple-mode', 'black-mode', 'system-mode'];
  
  // Remove all theme classes from the body
  themes.forEach(function(currentTheme) {
    $('body').removeClass(currentTheme);
  });
  
  // Add the selected theme
  $('body').addClass(theme);
  
  // Store the selected theme in localStorage
  localStorage.setItem('userTheme', theme);
}

// Function to initialize theme from localStorage
function initializeTheme() {
  const savedTheme = localStorage.getItem('userTheme');
  if (savedTheme) {
    changeTheme(savedTheme);
  } else {
    // Set default theme if none is saved
    changeTheme('system-mode');
  }
}

function toggleThemeClick() {
  const themeContainer = document.querySelector('.theme-icon-container');
  themeContainer.classList.toggle('d-none');
}

// Initialize theme when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  initializeTheme();
});

// AI Tone Management
function changeAITone(tone) {
  // Remove active class from all tone indicators
  document.querySelectorAll('.tone-indicator').forEach(indicator => {
    indicator.classList.remove('active');
  });
  
  // Add active class to selected tone
  const selectedToneIndicator = document.querySelector(`[data-tone="${tone}"] .tone-indicator`);
  if (selectedToneIndicator) {
    selectedToneIndicator.classList.add('active');
  }
  
  // Store the selected tone in localStorage - using only aiToneMode
  localStorage.setItem('aiToneMode', tone);
  
  // Close the AI Tone modal
  const aiToneModal = document.getElementById('AiToneModal');
  if (aiToneModal) {
    DynamicClose('#AiToneModal');
  }
}

// Function to initialize AI tone from localStorage
function initializeAITone() {
  // Check for the tone in localStorage
  const savedTone = localStorage.getItem('aiToneMode');
  if (savedTone) {
    changeAITone(savedTone);
  } else {
    // Default to friendly if no tone is set
    changeAITone('friendly');
  }
}

// Function to get the current AI tone from localStorage
function getCurrentAITone() {
  // Only use aiToneMode
  return localStorage.getItem('aiToneMode') || 'friendly'; // Default to friendly if not set
}

// Initialize AI tone when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  initializeAITone();
  // Add click event listeners to tone options
  document.querySelectorAll('.ai-tone-option').forEach(option => {
    option.addEventListener('click', function() {
      const tone = this.getAttribute('data-tone');
      if (tone) {
        changeAITone(tone);
      }
    });
  });
  
  // Add close button functionality
  const closeButton = document.querySelector('#AiToneModal .close-icon-btn');
  if (closeButton) {
    closeButton.addEventListener('click', function() {
      const aiToneModal = document.getElementById('AiToneModal');
      if (aiToneModal) {
        aiToneModal.style.display = 'none';
      }
    });
  }
});

// Function to show AI Tone modal
function showAIToneModal() {
  const aiToneModal = document.getElementById('AiToneModal');
  if (aiToneModal) {
    aiToneModal.style.display = 'block';
  }
}
function openLink(link) {
  window.open(link, '_blank');
}

</script>