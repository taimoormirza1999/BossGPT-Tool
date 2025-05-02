<script>
  <?php if(isLoginUserPage()){ ?>
  // Initial Loader
document.addEventListener("DOMContentLoaded", function () {
  
  // function checkFCMStatus(){
  //   const token = localStorage.getItem('fcm_token');
  //   if(token){
  //     document.getElementById('fcmContent').style.display = 'block';
  //   }
  // }
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
  <?php } ?>
  document.addEventListener('DOMContentLoaded', function () {
    var enableNotificationsBtn = false;
    <?php if (!isset($_SESSION['fcm_token']) || $_SESSION['fcm_token'] == '0'): ?>
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
    return "notification-icon-" + action_type || '';
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
    themes.forEach(function (currentTheme) {
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
  document.addEventListener('DOMContentLoaded', function () {
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
  function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const diffSeconds = Math.floor((now - past) / 1000);

    if (diffSeconds < 60) return 'just now';
    const minutes = Math.floor(diffSeconds / 60);
    if (minutes < 60) return minutes + ' minutes ago';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + ' hours ago';
    const days = Math.floor(hours / 24);
    return days + ' days ago';
  }
  function escapeHtml(text) {
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

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

      if (parts.length === 1) {
        initials = parts[0].substring(0, 2).toUpperCase(); // 2 letters
      } else {
        const firstInitial = parts[0][0] ? parts[0][0].toUpperCase() : '';
        const secondInitial = parts[1][0] ? parts[1][0].toUpperCase() : '';
        initials = firstInitial + '.' + secondInitial;
      }

      const createdAt = formatDateTime(log.created_at);
      const actionType = escapeHtml(log.action_type);
      const description = escapeHtml(log.description);

      const activityItem = `
      <div class="list-group-item border-0 list-group-item-action bg-transparent text-white d-flex align-items-start border-light border rounded mb-2">
        <div class="me-3">
          <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
               style="width: 3.1rem; height: 3.1rem; background: rgba(42, 95, 255, 1);">
            ${initials}
          </div>
        </div>
        <div class="flex-grow-1">
          <div>
            <strong>${username}</strong> moved <span class="text-decoration-underline">${description}</span> as <strong>${actionType}</strong>.
          </div>
          <small class="text-muted d-flex align-items-center mt-1">
            <i class="bi bi-clock me-1"></i> ${createdAt}
          </small>
        </div>
      </div>
    `;

      activityContainer.innerHTML += activityItem;
    });
  }

 









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
    // alert('markAsEnabledNotification called');
  // 1. Check browser Notification permission
  // console.log(Notification.permission)
  
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
      return '<span class="text-muted">No Assignee</span>';
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
            <td style="width: 25%; text-align: center;" class="d-flex align-items-center gap-2">
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
  <?php } ?>
</script>