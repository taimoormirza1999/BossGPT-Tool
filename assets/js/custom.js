// Initial Loader
document.addEventListener('DOMContentLoaded', function() {
    // Create and append loader
    const loader = document.createElement('div');
    loader.className = 'initial-loader';
    loader.innerHTML = `
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <div class="loader-text">Loading BossGPT...</div>
        </div>
    `;
    document.body.appendChild(loader);

    // Remove loader after 3 seconds
    setTimeout(() => {
        loader.classList.add('fade-out');
        setTimeout(() => {
            loader.remove();
        }, 500);
    }, 3000);
});

// Notification System
let isFetchingNotifications = false;
let isDropdownOpen = false;

function fetchNotificationsAndOpen(showDropdown = true) {
  if (isFetchingNotifications) return;
  isFetchingNotifications = true;
  isDropdownOpen = true;

  const dropdown = new bootstrap.Dropdown(
    document.getElementById("notificationDropdown")
  );
  const currentProject = $("#myselectedcurrentProject").val();

  if (!currentProject || currentProject == undefined) {
    Toast("error", "Error", "Please select a project first");
    isFetchingNotifications = false;
    return;
  }

  fetchNotifications(currentProject)
    .then(() => {
      if (showDropdown && !isDropdownOpen) {
        dropdown.show(); //toggle dropdown
      } else if (!isDropdownOpen) {
        dropdown.hide(); //hide dropdown
      }
      isFetchingNotifications = false;
    })
    .catch((error) => {
      console.error("Error fetching notifications:", error);
      isFetchingNotifications = false;
    });
}

function fetchNotifications(project_id) {
  return new Promise((resolve, reject) => {
    fetch("?api=get_unreadnotifications&project_id=" + project_id)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          updateNotificationDropdown(data.logs || []);
          resolve();
        } else {
          console.error("Error fetching notifications:", data.message);
          reject(new Error(data.message));
        }
      })
      .catch((error) => {
        console.error("Request failed:", error);
        reject(error);
      });
  });
}


// Toasts iziToast Helper
function Toast(type, title, message, positionToast) {
  const toastOptions = {
    title: title,
    message: message,
    position: positionToast ? positionToast : "bottomCenter",
  };
  switch (type) {
    case "info":
      iziToast.info({ ...toastOptions });
      break;
    case "success":
      iziToast.success({ ...toastOptions });
      break;
    case "warning":
      iziToast.warning({ ...toastOptions });
      break;
    case "error":
      iziToast.error({ ...toastOptions });
      break;
    case "question":
      iziToast.question({
        ...toastOptions,
        timeout: 20000,
        close: false,
        overlay: true,
        displayMode: "once",
        position: "center",
        buttons: [
          [
            "<button><b>YES</b></button>",
            function (instance, toast) {
              instance.hide({ transitionOut: "fadeOut" }, toast, "button");
            },
            true,
          ],
          [
            "<button>NO</button>",
            function (instance, toast) {
              instance.hide({ transitionOut: "fadeOut" }, toast, "button");
            },
          ],
        ],
      });
      break;
    default:
      console.warn("Unknown toast type:", type);
  }
}

function hideModalWithDelay(modalId, delay = 1500) {
  setTimeout(() => {
    // Try both jQuery and Bootstrap methods for maximum compatibility
    try {
      // Using jQuery if available
      if (typeof $ !== "undefined") {
        $(`#${modalId}`).modal("hide");
      } else {
        // Using Bootstrap native
        const modalElement = document.getElementById(modalId);
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
          modalInstance.hide();
        }
      }
    } catch (error) {
      console.error("Error hiding modal:", error);
    }
  }, delay);
}

function showToastAndHideModal(
  modalId,
  toastType,
  toastTitle,
  toastMessage,
  delay = 1500
) {
  // Show toast first
  Toast(toastType, toastTitle, toastMessage);
  // Hide modal with delay
  hideModalWithDelay(modalId, delay);
}

// Chat Loading Animation
function initializeChatLoading() {
  // Add the CSS for loading animation
  const style = document.createElement("style");
  style.textContent = `
      .chat-loading {
          display: flex;
          align-items: center;
          padding: 10px 15px;
          margin: 5px 0;
          max-width: 80%;
          animation: fadeIn 0.3s ease;
      }

      .chat-loading .dots {
          display: flex;
          gap: 4px;
      }

      .chat-loading .dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
        //   background-color: var(--primary-color, #0d6efd);
       
          opacity: 0.6;
      }

      body.dark-mode .chat-loading .dot {
          background-color: #fff;
      }

      .chat-loading .dot:nth-child(1) {
          animation: bounce 1.2s infinite;
      }

      .chat-loading .dot:nth-child(2) {
          animation: bounce 1.2s infinite 0.2s;
      }

      .chat-loading .dot:nth-child(3) {
          animation: bounce 1.2s infinite 0.4s;
      }

      @keyframes fadeIn {
          from {
              opacity: 0;
              transform: translateY(10px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      @keyframes bounce {
          0%, 60%, 100% {
              transform: translateY(0);
              opacity: 0.6;
          }
          30% {
              transform: translateY(-4px);
              opacity: 1;
          }
      }

      .chat-loading-container {
          display: flex;
          align-items: center;
          gap: 10px;
          background: var(--message-bg, #f0f2f5);
          padding: 10px 20px;
          border-radius: 18px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
      }

      body.dark-mode .chat-loading-container {
          background: #3a3b3c;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      }

      .chat-loading-avatar {
          width: 32px;
          height: 32px;
          border-radius: 50%;
        //   background: var(--primary-color, #0d6efd);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 14px;
          font-weight: bold;
      }

      body.dark-mode .chat-loading-avatar {
       background-color: rgba(194, 194, 194, 0.2);
backdrop-filter: blur(8px);
          color: #fff;
          
      }
  `;
  document.head.appendChild(style);
}

// Show Chat Loading Animation
function showChatLoading() {
  const chatMessages = document.querySelector(".chat-messages");
  if (!chatMessages) return;

  const loadingElement = document.createElement("div");
  loadingElement.className = "chat-loading";
  loadingElement.innerHTML = `
      <div class="chat-loading-container">
        <div class="chat-loading-avatar">
         <img src='https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png' alt="Logo"
            class="logo-icon"
            style="margin-top: 0; margin-bottom: 0; width: 1.5rem; height:auto">
          </div>
          <div class="dots">
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
          </div>
      </div>
  `;
  chatMessages.appendChild(loadingElement);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Hide Chat Loading Animation
function hideChatLoading() {
  const loadingElement = document.querySelector(".chat-loading");
  if (loadingElement) {
    loadingElement.remove();
  }
}

function openNewProjectModal() {
  // Get your existing new project modal
  const modal = new bootstrap.Modal(document.getElementById("newProjectModal"));
  modal.show();
}
function scrollToBottom() {
  if (welcomeThread) {
    welcomeThread.scrollTop = welcomeThread.scrollHeight;
  }
}
const aiMessageClasses = "ai-message d-flex align-items-start intro-message";

// Add this new function to append a welcome logo
function appendWelcomeLogo() {
  const chatMessages = document.getElementById("chatMessages");
  if (!chatMessages) return;
  
  // Remove any existing logo to avoid duplicates
  const existingLogo = chatMessages.querySelector('.welcome-logo-container');
  if (existingLogo) {
    existingLogo.remove();
  }
  
  // Create a centered logo container at the top
  const logoContainer = document.createElement("div");
  logoContainer.className = "welcome-logo-container text-center mb-2 mt-2";
  logoContainer.innerHTML = `${welcomeLogoImage}  `;
  
  // Always insert at the beginning of the chat container
  chatMessages.insertBefore(logoContainer, chatMessages.firstChild);
  
  // Log to verify it's being called
  console.log("Welcome logo added to chat container");
}

// Add styles for the welcome logo
document.addEventListener('DOMContentLoaded', function() {
  const style = document.createElement('style');
  style.textContent = `
    .welcome-logo-container {
        margin-bottom: 1.5rem;
       padding: 0.8rem;
    transition: all 0.3s ease;
    background: rgba(124, 124, 124, 0.2);
    border: 1px solid rgba(211, 211, 211, 0.5);
    width: 5rem;
    border-radius: 50%;
    height: 5rem;
    margin:auto;
    }
  
    
  `;
  document.head.appendChild(style);
});

// Welcome AI Messages - modified to add the logo
function displayProjectCreationWelcomeMessages(title) {
  const chatMessages = document.getElementById("chatMessages");
  if (!chatMessages) return;

  chatMessages.innerHTML = "";
  // Add welcome logo first
  appendWelcomeLogo();

  const welcomeMessages = [
    {
      message: `👋 Hi! I'm here to help you with your project`,
      delay: 900,
    },
    {
      message:
        "You can ask me to:\n• Create new tasks of your project\n• Assign or subTasks\n• Get project insights\n• Manage team assignments",
      delay: 2500,
    },
  ];

  showChatLoading(); // Show loading animation

  welcomeMessages.forEach((msg, index) => {
    setTimeout(() => {
      if (index === welcomeMessages.length - 1) {
        hideChatLoading(); 
      }

      const messageDiv = document.createElement("div");
      
      messageDiv.className = aiMessageClasses;
      messageDiv.innerHTML = `
              <div class="ai-avatar">
                  <div class="chat-loading-avatar">
                    ${iconImage}
                  </div>
              </div>
              <div class="message ai">
                  <p>${msg.message.replace(/\n/g, "<br>")}</p>
              </div>
          `;
      chatMessages.appendChild(messageDiv);
      scrollToBottom();
    }, msg.delay);
  });
}

function getActionTypeDisplay(action_type) {
  const actionTypes = {
    project_created: {
      text: "New Project Created",
      bgColor: "bg-success bg-opacity-10",
      textColor: "text-success-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-success",
    },
    user_assigned: {
      text: "New User Added",
      bgColor: "bg-info bg-opacity-10",
      textColor: "text-info-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-info",
    },
    task_created: {
      text: "New Task Created",
      bgColor: "bg-primary bg-opacity-10",
      textColor: "text-primary-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-primary",
    },
    user_removed: {
      text: "User Removed",
      bgColor: "bg-danger bg-opacity-10",
      textColor: "text-primary-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-danger bg-danger bg-opacity-50",
    },
    task_status_updated: {
      text: "Task Status Updated",
      bgColor: "bg-warning bg-opacity-10",
      textColor: "text-warning-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-primary",
    },
  };
  return (
    actionTypes[action_type] || {
      text: action_type,
      bgColor: "bg-secondary bg-opacity-10",
      textColor: "text-secondary-emphasis text-success-emphasis-light",
      darkBgColor: "dark-mode-secondary",
    }
  );
}

function formatTimeAgo(dateString) {
  // Create date objects
  const date = new Date(dateString + " UTC"); // Treat the server time as UTC
  const now = new Date();

  // Calculate time differences
  const diffInMs = now - date;
  const diffInSeconds = Math.floor(diffInMs / 1000);
  const diffInMinutes = Math.floor(diffInSeconds / 60);
  const diffInHours = Math.floor(diffInMinutes / 60);
  const diffInDays = Math.floor(diffInHours / 24);

  // Return appropriate time format
  if (diffInDays > 0) {
    if (diffInDays === 1) return "yesterday";
    if (diffInDays <= 7) return `${diffInDays} days ago`;
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: date.getFullYear() !== now.getFullYear() ? "numeric" : undefined,
    });
  } else if (diffInHours > 0) {
    return `${diffInHours} ${diffInHours === 1 ? "hour" : "hours"} ago`;
  } else if (diffInMinutes > 0) {
    return `${diffInMinutes} ${diffInMinutes === 1 ? "minute" : "minutes"} ago`;
  } else {
    return "just now";
  }
}

function getNotificationIcon(action_type) {
  const icons = {
    project_created: "bi-folder-plus",
    user_removed: "bi-person-dash",
    user_assigned: "bi-person-plus",
    task_created: "bi-list-check",
    task_status_updated: "bi-arrow-repeat",
  };
  return icons[action_type] || "bi-bell";
}

function updateNotificationDropdown(notifications) {
  const notificationList = document.querySelector(".notification-list");
  const badge = document.getElementById("notificationBadge");
  const isDarkMode = document.body.classList.contains("dark-mode");

  if (!notificationList || !badge) return;

  // Add styles for dark mode if not already added
  if (!document.getElementById("notification-dark-mode-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "notification-dark-mode-styles";
    styleSheet.textContent = `
            .dark-mode .notification-dropdown {
                background-color: #1a1a1a !important;
                border-color: #2d2d2d !important;
              
            }
            .text-success-emphasis-light{
                color: #212121 !important;
            }
            .dark-mode .text-success-emphasis-light{
                color: #fff !important;
            }
            .dark-mode .dropdown-item {
                color: #e1e1e1;
                // border-color: #2d2d2d !important;
                // background-color: #1a1a1a !important;
            }
            .dark-mode .dropdown-item:hover {
                // background-color: #2d2d2d !important;
            }
            .dark-mode .dropdown-header {
                border-color: #2d2d2d;
                color: #ffffff;
                background-color: #1a1a1a !important;
            }
            .dropdown-header {
               border-bottom: 0.3rem #d3d4d5 solid;
            }
            .dark-mode .notification-text {
                color: #e1e1e1 !important;
            }
            .dark-mode .text-muted {
                color: #a0a0a0 !important;
            }
            .dark-mode-success { background-color: rgba(25, 135, 84, 0.35) !important;  }
            .dark-mode-info { background-color: rgba(13, 202, 240, 0.35) !important; }
            .dark-mode-primary { background-color: rgba(13, 110, 253,.35) !important; }
            .dark-mode-warning { background-color: rgba(255, 193, 7, 0.35) !important; }
            .dark-mode-secondary { background-color: rgba(108, 117, 125, 0.35) !important; }
            .dark-mode .notification-icon {
                background-color: rgba(255, 255, 255, 0.15) !important;
            }
            .dark-mode .dropdown-menu {
                // background-color: #1a1a1a !important;
                // border-color: #2d2d2d !important;
                // border-width: 0.25rem !important;
            }
            #notificationDropdownMenu.dropdown-menu {
                border-width: 0.25rem !important;
                border-radius: 0.5rem !important;
                background: rgba(255, 255, 255, 0.1);
border: 1px solid rgba(211, 211, 211, 0.5);
backdrop-filter: blur(14.3px);
                
            }

            #notificationDropdownMenu.dark-mode .dropdown-menu {
                // background-color: #1a1a1a !important;
                // border-color: #2d2d2d !important;
                border-width: 0.25rem !important;
            }
            .dark-mode .dropdown-item:active,
            .dark-mode .dropdown-item:focus {
                // background-color: #2d2d2d !important;
            }
        `;
    document.head.appendChild(styleSheet);
  }

  // Update badge
  if (notifications.length > 0) {
    badge.textContent = notifications.length;
    badge.style.display = "inline-block";
  } else {
    badge.style.display = "none";
  }

  // Update notification list
  if (notifications.length > 0) {
    notificationList.innerHTML = notifications
      .map((notification) => {
        const actionType = getActionTypeDisplay(notification.action_type);
        const timeAgo = formatTimeAgo(notification.created_at);
        const icon = getNotificationIcon(notification.action_type);

        return `
                <div class="dropdown-item border-bottom py-3">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon ${
                          isDarkMode
                            ? actionType.darkBgColor
                            : actionType.bgColor
                        } rounded-circle  me-3"
                        style="padding:0.6rem 0.8rem !important;"
                        >
                            <i class="bi ${icon} ${actionType.textColor}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge ${
                                  isDarkMode
                                    ? actionType.darkBgColor
                                    : actionType.bgColor
                                } ${
          actionType.textColor
        } rounded-pill px-3 py-1" >
                                    ${actionType.text}
                                </span>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    ${timeAgo}
                                </small>
                            </div>
                            <div class="notification-text" style="font-size: 0.8rem;">
                                ${notification.description}
                            </div>
                        </div>
                    </div>
                </div>
            `;
      })
      .join("");
  } else {
    notificationList.innerHTML = `
            <div class="dropdown-item text-center py-4">
                <i class="bi bi-bell text-muted mb-2 d-block" style="font-size: 1.5rem;"></i>
                <p class="text-muted mb-0">No new notifications</p>
            </div>`;
  }
}

// function sendNotificationTest(projectId, title, body) {
//   fetch("?api=send_notification_test", {
//     method: "POST",
//     headers: {
//       "Content-Type": "application/json",
//     },
//     body: JSON.stringify({
//       project_id: projectId,
//       title: title,
//       body: body,
//     }),
//   })
//     .then((response) => response.json())
//     .then((data) => {
//       if (data.success) {
//         Toast("success", "Success", "Notification sent successfully");
//         // Refresh notifications after sending
//         fetchNotifications();
//       } else {
//         Toast("error", "Error", data.message || "Failed to send notification");
//       }
//     })
//     .catch((error) => {
//       console.error("Request failed:", error);
//       Toast("error", "Error", "Failed to send notification");
//     });
// }


// Notification
function appendNotification(notification) {
  const notificationList = document.querySelector(".notification-list");
  const isDarkMode = document.body.classList.contains('dark-mode');
  const actionType = getActionTypeDisplay(notification.action_type);
  const timeAgo = formatTimeAgo(notification.created_at);
  const icon = getNotificationIcon(notification.action_type);

  const newNotification = `
  <div class="dropdown-item border-bottom py-3">
      <div class="d-flex align-items-start">
          <div class="notification-icon ${isDarkMode ? actionType.darkBgColor : actionType.bgColor} rounded-circle me-3"
              style="padding:0.6rem 0.8rem !important;">
              <i class="bi ${icon} ${actionType.textColor}"></i>
          </div>
          <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="badge ${isDarkMode ? actionType.darkBgColor : actionType.bgColor} ${actionType.textColor} rounded-pill px-3 py-1">
                      ${actionType.text}
                  </span>
                  <small class="text-muted" style="font-size: 0.75rem;">
                      ${timeAgo}
                  </small>
              </div>
              <div class="notification-text" style="font-size: 0.8rem;">
                  ${notification.description}
              </div>
          </div>
      </div>
  </div>
`;

  // Prepend the new notification to the list
  notificationList.insertAdjacentHTML('afterbegin', newNotification);
}


function initPusher(currentProject) {
  Pusher.logToConsole = true;

  var pusher = new Pusher('83a162dc942242f89892', {
      cluster: 'ap2'
  });
  // Enable pusher logging - don't include this in production

  var channel = pusher.subscribe('project_' + currentProject);

  channel.bind('project_created', function (data) {
      appendNotification(data);
      Toast("success", "Project Created", data.message, 'topRight');
  });
  channel.bind('user_assigned', function (data) {
      appendNotification(data);
      Toast("success", "User Joined", data.message, 'topRight');
  });
  channel.bind('task_created', function (data) {
      appendNotification(data);
      Toast("success", "Task Created", data.message, 'topRight');
  });
  channel.bind('task_updated', function (data) {
      appendNotification(data);
      Toast("success", "Success", data.message, 'topRight');
  });
}

