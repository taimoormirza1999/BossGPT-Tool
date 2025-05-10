<script>
  <?php if(isLoginUserPage()){ ?>
  <?php if(!isPage('profile')){ ?>
  // Initial Loader
  document.addEventListener("DOMContentLoaded", function () {
    function hideAiErrorMessages() {
      document.querySelectorAll('.ai-message').forEach(el => {
        const message = el.querySelector('.message.ai');
        const errorMessage = <?php echo json_encode(renderAIErrorMessage()); ?>;
        const calendarSuccessMessage = <?php echo json_encode(renderAICalendarSuccessMessage()); ?>;
        if (
          message &&
          (
            message.textContent.includes('Sorry, I encountered an error while') 
          )
        ) {
          message.innerHTML = errorMessage;
        }
      });
    }

  // Run once on load
  hideAiErrorMessages();

  // Watch for dynamic AI messages
  const chatContainer = document.getElementById('chatMessages');
  if (chatContainer) {
    const observer = new MutationObserver(() => hideAiErrorMessages());
    observer.observe(chatContainer, { childList: true, subtree: true });
  }
  // Create and append loader
  const loader = document.createElement("div");
  loader.className = "initial-loader";
  loader.innerHTML = `
        <div class="loader-content">
       <canvas id="myLottie" width="53" height="53" style="height:50px;width:50px"></canvas>
        <div class="loader-text text-sm mt-2">
        Loading BossGPT...
        </div>
        </div>
    `;
  document.body.appendChild(loader);
  // Remove loader after 3 seconds
  setTimeout(() => {
    loader.classList.add("fade-out");
    setTimeout(() => {
      loader.remove();
    }, 500);
  }, 3000);
});

function SVGAI(){
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24" width="20" height="20">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
  </svg>`
}
function SVGCalendar(){
  return `<?php echo getCalendarIcon(); ?>`;
}
function SVGAdd(){
  return `<i class="bi bi-plus-circle"></i>`;
}
  
  <?php } } ?>


  document.addEventListener('DOMContentLoaded', function () {
    var enableNotificationsBtn = false;
    <?php if (!isset($_SESSION['fcm_token']) || $_SESSION['fcm_token'] == '0'): ?>
      enableNotificationsBtn = true;
    <?php endif; ?>
    const openIconBtn = document.querySelector('.open-icon-btn');
    // $(openIconBtn).toggleClass('d-none');
    // $(openIconBtn).toggleClass('show');
  });
  const notificationIcons = {
    project_created: `<?= getFolderIcon(); ?>`,
    user_removed: `<?= getProfileDeleteIcon(); ?>`,
    user_assigned: `<?= getProfileIcon(); ?>`,
    task_created: `<?= getclipboardIcon(); ?>`,
    task_deleted: `<?= getclipboardIcon(); ?>`,
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
    return "notification-icon-" + action_type || '';
  }
  document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => {
      new bootstrap.Tooltip(el);
    });
  });
  <?php if(!isPage('profile')){ ?>
  function closeChatPannel() {
    const chatPannel = document.querySelector('.chat-pannel');
    chatPannel.classList.add('d-none');
  
    $('.open-icon-btn').addClass('show');
    $('.open-icon-btn').removeClass('d-none');
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
  <?php } ?>
  function changeTheme(theme) {
    // List of all possible theme classes
    const themes = ['light-mode', 'dark-mode', 'brown-mode', 'purple-mode', 'black-mode', 'system-mode'];

    themes.forEach(t => document.body.classList.remove(t));
    document.body.classList.add(theme);
    localStorage.setItem('userTheme', theme);
  }
  // Function to initialize theme from localStorage
  function initializeTheme() {
    const savedTheme = localStorage.getItem('userTheme');
    changeTheme(savedTheme || 'system-mode');
  }

  function toggleThemeClick() {
    const themeContainer = document.querySelector('.theme-icon-container');
    themeContainer.classList.toggle('d-none');
  }

  // Add new click event listener for document
  document.addEventListener('click', function(event) {
    const themeContainer = document.querySelector('.theme-icon-container');
    const themeButton = document.getElementById('btn-theme');
    
    // If click is outside both the theme container and theme button
    if (!themeContainer.contains(event.target) && !themeButton.contains(event.target)) {
        themeContainer.classList.add('d-none');
    }
  });

  <?php if(!isPage('profile')){ ?>
  // Initialize theme when DOM is loaded
  document.addEventListener('DOMContentLoaded', initializeTheme);
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
  document.addEventListener('DOMContentLoaded', function () {
    initializeAITone();
    // Add click event listeners to tone options
    document.querySelectorAll('.ai-tone-option').forEach(option => {
      option.addEventListener('click', function () {
        const tone = this.getAttribute('data-tone');
        if (tone) {
          changeAITone(tone);
        }
      });
    });

    // Add close button functionality
    const closeButton = document.querySelector('#AiToneModal .close-icon-btn');
    if (closeButton) {
      closeButton.addEventListener('click', function () {
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
  <?php } ?>
  function openLink(link, newPage = true) {
    if (newPage) {
      window.open(link, '_blank');
    } else {
      window.location.href = link;
    }
  }

  function openModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
      console.error(`Modal with ID "${modalId}" not found!`);
      return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
  function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
  }

  function escapeHtml(text) {
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
<?php if(isPage('profile')){ ?>
  function updateActivityBoard(logs) {
    const activityContainer = document.getElementById('activityLogList');
    if (!activityContainer) return; // Safety check

    activityContainer.innerHTML = ''; // Clear existing

    if (logs.length === 0) {
      activityContainer.innerHTML = '<p class="text-muted text-center">No recent activity.</p>';
      return;
    }

    logs.forEach(log => {
      const username = escapeHtml(log.username || 'User'); // fallback
      const parts = username.trim().split(' ');
      let initials = '';

      const avatar = "<?php echo $_SESSION['avatar_image'] ?? ''; ?>";
      const fullName = "<?php echo $_SESSION['full_name'] ?? 'User'; ?>"; // You can change 'full_name' based on your session variable


      if (avatar && avatar !== '0') {
        initials = `<img src="${avatar}" alt="Avatar" class="rounded-circle" style="width: 3rem; height: 3rem; object-fit: cover;">`;
      } else {
        if (parts.length === 1) {
          initials = parts[0].substring(0, 2).toUpperCase(); // e.g. "Al"
        } else {
          const firstInitial = parts[0][0] ? parts[0][0].toUpperCase() : '';
          const secondInitial = parts[1][0] ? parts[1][0].toUpperCase() : '';
          initials = `${firstInitial}.${secondInitial}`; // e.g. "A.B"
        }
      }

      const createdAt = formatDateTime(log.created_at);
      const actionType = escapeHtml(log.action_type);
      const description = escapeHtml(log.description);

      const activityItem = `
      <div class="list-group-item border-0 list-group-item-action bg-transparent text-white d-flex align-items-start border-light border rounded mb-2">
        <div class="me-3">
          <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
               style="width: 3rem; height: 3rem; background: rgba(42, 95, 255, 1);">
            ${initials}
          </div>
        </div>
        <div class="flex-grow-1">
          <div class="text-capitalize text-sm">
            <strong>@${username}</strong> moved <span class="">${description}</span> as <strong>${actionType}</strong>.
          </div>
          <small class="d-flex align-items-center mt-1 text-purple text-sm">
            <?php echo getTimerIcon(); ?> &nbsp; ${createdAt}
          </small>
        </div>
      </div>
    `;

      activityContainer.innerHTML += activityItem;
    });
  }
  <?php } ?>
 

  function clearRewardfulCookies() {
    const cookies = document.cookie.split(";");
    console.log('Clearing Rewardful Cookies called');
    cookies.forEach(cookie => {
      const cookieName = cookie.split("=")[0].trim();
      // List of cookies Rewardful typically sets, but you need to verify them
      const rewardfulCookies = ["rewardful_referral", "rewardful_source", "rewardful_session"];

      if (rewardfulCookies.includes(cookieName)) {
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
      }
    });
  }
 
  function markAsEnabledNotification() {  
  if (Notification.permission === 'granted') {
    // 2. Gather any payload you need (e.g. existing FCM token you stored)
    const payload = {
      fcmToken: localStorage.getItem('fcm_token') || null
    };
    checkFCMStatus();
  }
}

  <?php if (isPage('profile')) { ?>
    function renderAssignedUsers(assignedUsers) {
    // pull in your session values right here:
    const sessionId = <?php echo json_encode($_SESSION['user_id']); ?>;
    const sessionUsername = <?php echo json_encode($_SESSION['username']); ?>;
    const sessionAvatar = <?php echo json_encode($_SESSION['avatar_image']); ?>;

    if (!assignedUsers || !Object.keys(assignedUsers).length) {
      return '<span class="text-muted" style="height: 3rem;display: flex;justify-content: center;align-items: center;" >No Assignee</span>';
    }

    return Object.entries(assignedUsers).map(([id, user]) => {
      // detect yourself
      const isMe = parseInt(id, 10) == <?php echo json_encode($_SESSION['user_id']); ?>;
      const avatar = isMe
        ? sessionAvatar
        : user.avatar_image;
      const name = isMe
        ? sessionUsername
        : user.username;

      if (avatar) {
        return `
              <div class="d-flex align-items-center gap-2 justify-content-center">
                <img src="${escapeHtml(avatar)}"
                     class="rounded-circle border border-secondary"
                     style="width:2.7rem;height:2.7rem;object-fit:cover;"
                     alt="Avatar">
                <span>@${escapeHtml(name)}</span>
              </div>`;
      } else {
        const parts = name.trim().split(' ');
        const initials = parts.length === 1
          ? parts[0].slice(0, 2).toUpperCase()
          : (parts[0][0] + '.' + parts[1][0]).toUpperCase();
        return `
              <div class="d-flex align-items-center gap-2 justify-content-center">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                     style="width:3.1rem;height:3.1rem;
                            background:rgba(42,95,255,1);
                            font-size:0.95rem;">
                  ${initials}
                </div>
                <span>${escapeHtml(name)}</span>
              </div>`;
      }
    }).join('');
  }
    function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }
    // Helper functions
    function formatDate(date) {
    // Check if the date is valid
    const validDate = new Date(date);
    if (isNaN(validDate)) {
      // If the date is invalid, return a fallback value
      return '-'; // or whatever fallback you prefer
    }
    const options = { day: '2-digit', month: 'short' };
    return validDate.toLocaleDateString('en-GB', options);
  }
   if (isProfilePage()) {
    function updateCardsBoard(tasks) {
    const tableBody = document.getElementById('tasksTableBody');

    if (!tableBody) return; // Safety check

    tableBody.innerHTML = ''; // Clear existing table content

    if (!tasks || tasks.length === 0) {
      tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">No tasks available.</td>
            </tr>
        `;
      return;
    }

    tasks.forEach(task => {
      const row = document.createElement('tr');

      row.innerHTML = `
            <td class="text-capitalize" style="width: 20%; text-align: center; ">${task.title ? escapeHtml(task.title) : '-'}</td>
            <td style="width: auto; text-align: center;" class="d-flex justify-content-center align-items-center gap-2">
                ${renderAssignedUsers(task.assigned_users)}
            </td>
            <td style="width: 15%; text-align: center;">${formatDate(task.due_date)}</td>
            <td  class="text-capitalize" style="width: 40%; text-align: center;">${task.description ? escapeHtml(task.description) : '-'}</td>
        `;

      tableBody.appendChild(row);
    });
  }
    function formatDateForBackend(date) {
      if (!(date instanceof Date) || isNaN(date)) {
        console.log('Invalid date provided:', date);
        return null; // Or handle differently, like returning an empty string or a default value
      }
      return date.toISOString().split('T')[0]; // YYYY-MM-DD
    }
    function loadTasks2(projectId, startDate = null, endDate = null) {
                // Add startDate and endDate to the data if provided
                let requestData = { project_id: projectId };
                if (startDate && endDate) {
                    // Format the dates into 'YYYY-MM-DD' format (or adjust based on your format needs)
                    requestData.start_date = startDate;
                    requestData.end_date = endDate;
                    // alert(startDate+" "+endDate);
                }

                fetch('?api=get_tasks', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            <?php if (isPage('profile')) { ?>
                                updateCardsBoard(data.tasks);
                            <?php } ?>
                        }
                    })
                    .catch(error => console.error('Error loading tasks:', error))
                    .finally();
            }
          } 
  <?php } ?>
</script>