

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
        
        // Clear notifications after viewing
        const fcmToken = localStorage.getItem("fcm_token");
        if (fcmToken) {
          // Check if there are reminders to delete
          fetch("?api=get_fcm_reminders", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ fcm_token: fcmToken }),
          })
            .then(response => response.json())
            .then(data => {
              if (data.success && data.reminders && data.reminders.length > 0) {
                // Clear each reminder
                data.reminders.forEach(reminder => {
                  fetch("?api=delete_fcm_reminders", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                      fcm_token: fcmToken,
                      reminder_id: reminder.id 
                    }),
                  }).catch(error => {
                    console.error("Error clearing reminder:", error);
                  });
                });
              }
            })
            .catch(error => {
              console.error("Error checking FCM reminders:", error);
            });
        }
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

function fetchNotifications(project_id, startDate = null, endDate = null) {
  return new Promise((resolve, reject) => {
    let url = "?api=get_unreadnotifications&project_id=" + project_id;
    if (startDate && endDate) {
      const formattedStartDate = startDate;
      const formattedEndDate = endDate;
      // alert(formattedStartDate + " " + formattedEndDate);
      url += `&start_date=${formattedStartDate}&end_date=${formattedEndDate}`;
    }
    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          updateNotificationDropdown(data.logs || []);
          if(isProfilePage()){
            updateActivityBoard(data.logs || []);
          }
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
          background:rgba(255, 255, 255, 1);
       
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
          padding: 10px 20px;
          border-radius: 18px;
      }
      .chat-loading-avatar {
          width: 32px;
          height: 32px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 14px;
          font-weight: bold;
      }

      body.dark-mode .chat-loading-avatar {
       background: var(--bs-primary-darkpurple-linear-gradient);
backdrop-filter: blur(8px);
          color: #fff;
          
      }
  `;
  document.head.appendChild(style);
}

// Show Chat Loading Animation
function showChatLoading() {
  initializeChatLoading();
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
  // if (welcomeThread) {
  //   welcomeThread.scrollTop = welcomeThread.scrollHeight;
  // }
}
const aiMessageClasses = "ai-message d-flex align-items-start intro-message";
function hideWelcomeLogo() {
  const chatMessages = document.getElementById("chatMessages");
  if (!chatMessages) return;
  const existingLogo = chatMessages.querySelector('.welcome-logo-container');
  if (existingLogo) {
    existingLogo.remove();
  }
}
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
  

}

// Add styles for the welcome logo
document.addEventListener('DOMContentLoaded', function() {
  const style = document.createElement('style');
  style.textContent = `
    .welcome-logo-container {
        margin-bottom: 1.5rem;
       padding: 0.8rem;
    transition: all 0.3s ease;
    background: var(--bs-primary-darkpurple-linear-gradient);
    border-radius: 50%;
    width: 5.5rem;
    height: 5.5rem;
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
    },
    user_assigned: {
      text: "New User Added",
    },
    task_created: {
      text: "New Task Created",
    },
    task_deleted: {
      text: "Task Deleted",
    },
    user_removed: {
      text: "User Removed",
    },
    task_picture_removed: {
      text: "Task Status Updated",
    },
    task_status_updated: {
      text: "Task Status Updated",
    },
    task_updated: {
      text: "Task Updated",
    },
    subtask_created: {
      text: "Subtask Created",
    },
    subtask_status_updated: {
      text: "Subtask Status Updated",
    },
    subtask_deleted: {
      text: "Subtask Deleted",
    },
  };
  return (
    actionTypes[action_type] || {
      text: action_type,
    }
  );
}

function formatTimeAgo(dateString) {
  const date = new Date(dateString); // Let JavaScript handle the format properly
  const now = new Date();
  const diffInMs = now - date;
  const diffInSeconds = Math.floor(diffInMs / 1000);
  const diffInMinutes = Math.floor(diffInSeconds / 60);
  const diffInHours = Math.floor(diffInMinutes / 60);
  const diffInDays = Math.floor(diffInHours / 24);
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
           .notification-text {
               color: rgba(131, 131, 131, 1);
               white-space: normal;
            }
           .text-muted {
                color: #a0a0a0 !important;
            }
            #notificationDropdownMenu.dropdown-menu {
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(3.1px);
                border-radius: 12px;
                width: 300px; 
                overflow-x: hidden;    
            }
            #notificationDropdownMenu.dark-mode .dropdown-menu {
                border-width: 0.25rem !important;
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
        const iconClass = getNotificationIconClass(notification.action_type);

        return `
                <div class="dropdown-item border-bottom pt-2">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon ${iconClass} ${
          isDarkMode ? actionType.darkBgColor : actionType.bgColor
        } rounded-circle  me-3"
                       
                        >
                        ${icon}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="title-notification rounded-pill" >
                                    ${actionType.text}
                                </span>
                                 <small class=" notification-time" style="font-size: 0.75rem;">
                                    ${timeAgo}
                                </small>
                            </div>
                            <div class="notification-text" style="font-size: 0.8rem;text-wrap: auto;">
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

// Notification
function appendNotification(notification) {
  const notificationList = document.querySelector(".notification-list");
  const badge = document.getElementById("notificationBadge");
  const isDarkMode = document.body.classList.contains("dark-mode");
  
  // Check if this notification is already in the list (avoid duplicates)
  const existingNotifications = notificationList.querySelectorAll('.dropdown-item');
  let isDuplicate = false;
  
  existingNotifications.forEach(item => {
    const description = item.querySelector('.notification-text')?.textContent?.trim();
    const actionSpan = item.querySelector('.title-notification')?.textContent?.trim();
    const actionTypeText = getActionTypeDisplay(notification.action_type).text;
    
    // If both the description and action type match, consider it a duplicate
    if (description === notification.description.trim() && 
        actionSpan === actionTypeText) {
      isDuplicate = true;
    }
  });
  
  // Don't add duplicate notifications
  if (isDuplicate) {
    return;
  }
  
  const actionType = getActionTypeDisplay(notification.action_type);
  const timeAgo = formatTimeAgo(notification.created_at);
  const icon = getNotificationIcon(notification.action_type);
  const iconClass = getNotificationIconClass(notification.action_type);

  const newNotification = `
  <div class="dropdown-item border-bottom pt-2">
      <div class="d-flex align-items-start">
          <div class="notification-icon ${iconClass} ${
            isDarkMode ? actionType.darkBgColor : actionType.bgColor
          } rounded-circle me-3"
              >
              ${icon}
          </div>
          <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="title-notification rounded-pill">
                      ${actionType.text}
                  </span>
                  <small class="notification-time" style="font-size: 0.75rem;">
                      ${timeAgo}
                  </small>
              </div>
              <div class="notification-text" style="font-size: 0.8rem;text-wrap: auto;">
                  ${notification.description}
              </div>
          </div>
      </div>
  </div>
`;

  // Prepend the new notification to the list
  notificationList.insertAdjacentHTML("afterbegin", newNotification);
  
  // Update badge count
  const currentCount = badge.style.display === 'none' ? 0 : parseInt(badge.textContent || '0');
  badge.textContent = currentCount + 1;
  badge.style.display = 'inline-block';
  
  // If "No notifications" message is showing, remove it
  const emptyNotification = notificationList.querySelector('.dropdown-item.text-center');
  if (emptyNotification) {
    emptyNotification.remove();
  }
}

function initPusher(currentProject) {
  // Disable pusher logging
  Pusher.logToConsole = false;

  var pusher = new Pusher("83a162dc942242f89892", {
    cluster: "ap2",
  });

  var channel = pusher.subscribe("project_" + currentProject);

  channel.bind("project_created", function (data) {
    appendNotification(data);
    Toast("success", "Project Created", data.message, "topRight");
  });
  channel.bind("user_assigned", function (data) {
    appendNotification(data);
    Toast("success", "User Joined", data.message, "topRight");
  });
  channel.bind("task_created", function (data) {
    appendNotification(data);
    Toast("success", "Task Created", data.message, "topRight");
  });
  channel.bind("task_updated", function (data) {
    appendNotification(data);
    Toast("success", "Success", data.message, "topRight");
  });
}

// Handle prompt button clicks
document.addEventListener("DOMContentLoaded", function () {
  const promptButtons = document.querySelectorAll(".prompt-btn");
  const messageInput = document.getElementById("messageInput");

  promptButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Get the text without the emoji
      const promptText = this.textContent.replace(/^[^\w\s]+ /, "");
      messageInput.value = promptText;
      messageInput.focus();
    });
  });
});

// Function to handle errors
function handleAjaxError(xhr, status, error) {
  console.error("Ajax Error:", error);
  Toast("error", "Error", "An error occurred: " + error, "topRight");
}

// FCM Token and Notifications

let fcmTokenSaved = false;

// Check if FCM is already enabled
function checkFCMStatus() {
  const fcmToken = localStorage.getItem("fcm_token");
  const showFcmPopup = document.getElementsByClassName('showFcmPopup');
  if (fcmToken && Notification.permission === 'granted') {
    showFcmPopup[0].style.display = 'none';
    // alert('fcmToken: ' + fcmToken);
   saveFCMToken(fcmToken);
  }else{
    showFcmPopup[0].style.display = 'flex';
  }
  // if (reminderButton) {
  //   if (fcmToken) {
  //     reminderButton.classList?.add("active");
  //     const span = reminderButton.querySelector("span");
  //     if (span) {
  //       span.textContent = "Reminders Active";
  //     }
  //     fcmTokenSaved = true;
  //   } else {
  //     reminderButton.classList?.remove("active");
  //     const span = reminderButton.querySelector("span");
  //     if (span) {
  //       span.textContent = "Turn on Reminders";
  //     }
  //     fcmTokenSaved = false;
  //   }
  // }
}

// Initialize FCM
function initializeFCM() {
  if (!firebase.messaging.isSupported()) {
    console.warn("Firebase messaging is not supported in this browser");
    return;
  }

  const messaging = firebase.messaging();
  // Request permission and get token
  messaging
    .getToken({ vapidKey: 'BNvQzVggQ4j6sTH5W6sxSa4K8Q-K0BhPn2tJT1en85dcp1P46M4EFJjoxe_uJI3PnEgQ06LO2mgv0SvcpBfyL00' })
    .then((currentToken) => {
      if (currentToken) {
        saveFCMToken(currentToken);
      } else {
        console.log(
          "No registration token available. Request permission to generate one."
        );
      }
    })
    .catch((err) => {
      // console.log("An error occurred while retrieving token. ", err);
    });

  // Handle token refresh
  messaging.onTokenRefresh(() => {
    messaging
      .getToken()
      .then((refreshedToken) => {
        console.log("Token refreshed.");
        saveFCMToken(refreshedToken);
      })
      .catch((err) => {
        console.log("Unable to retrieve refreshed token ", err);
      });
  });
  // Handle foreground messages
  messaging.onMessage((payload) => {
    console.log("Message received. ", payload);
    showNotification(payload.notification.title, payload.notification.body);
  });
}

// Save token to database
function saveFCMToken(token) {
  if(!token){
    return;
    Toast("error", "Error", "No FCM token found", "topRight");
  }
   // Save to database
   fetch("?api=update_fcm_token", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ fcm_token: token }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        fcmTokenSaved = true;
        // console.log("FCM token saved to database");
      } else {
        console.error("Failed to save FCM token to database");
      }
    })
    .catch((error) => {
      console.error("Error saving FCM token:", error);
    });
  return;
  localStorage.setItem("fcm_token", token);

  // Update UI
  const reminderButton = document.getElementById("reminderButton");
  if (reminderButton) {
    reminderButton.classList.add("active");
    reminderButton.querySelector("span").textContent = "Reminders Active";
  }

 
}

// Show browser notification
function showNotification(title, body) {
  if (!("Notification" in window)) {
    console.log("This browser does not support desktop notification");
    return;
  }

  if (Notification.permission === "granted") {
    const notification = new Notification(title, {
      body: body,
      icon: "/favicon.ico",
    });

    notification.onclick = function () {
      window.focus();
      this.close();
    };
  }
}

// Request notification permission
function requestNotificationPermission() {
  if (!("Notification" in window)) {
    Toast(
      "error",
      "Error",
      "This browser does not support desktop notifications",
      "topRight"
    );
    return Promise.reject("Notifications not supported");
  }

  if (Notification.permission === "granted") {
    return Promise.resolve();
  }

  // Show the modal instead of directly requesting permission
  const notificationModal = new bootstrap.Modal(
    document.getElementById("notificationPermissionModal")
  );
  notificationModal.show();

  // Return a promise that will be resolved when the enable button is clicked
  return new Promise((resolve, reject) => {
    const enableBtn = document.getElementById("enableNotificationsBtn");

    if (enableBtn) {
      // One-time event listener for the enable button
      const clickHandler = () => {
        enableBtn.removeEventListener("click", clickHandler);

        // Hide the modal and request actual permission
        notificationModal.hide();

        // Request the actual browser permission
        Notification.requestPermission().then((permission) => {
          if (permission === "granted") {
            Toast(
              "success",
              "Notifications Enabled",
              "You will now receive task reminders",
              "topRight"
            );
            resolve();
          } else {
            Toast(
              "error",
              "Permission Denied",
              "Please enable notifications to receive reminders",
              "topRight"
            );
            reject("Permission denied");
          }
        });
      };

      enableBtn.addEventListener("click", clickHandler);

      // Also handle modal dismiss
      const modalElement = document.getElementById(
        "notificationPermissionModal"
      );
      modalElement.addEventListener(
        "hidden.bs.modal",
        () => {
          enableBtn.removeEventListener("click", clickHandler);
          reject("Modal closed");
        },
        { once: true }
      );
    } else {
      reject("Enable button not found");
    }
  });
}

// Handle reminder button click
document.addEventListener("DOMContentLoaded", function () {
  const reminderButton = document.getElementById("reminderButton");
  checkFCMStatus();

  if (reminderButton) {
    reminderButton.addEventListener("click", function () {
      if (fcmTokenSaved) {
        // If notifications are already enabled, show settings
        Toast(
          "info",
          "Reminders Active",
          "You are already receiving notifications for task reminders",
          "topRight"
        );
      } else {
        // Request permission and initialize FCM
        requestNotificationPermission()
          .then(() => {
            if (typeof firebase !== "undefined" && firebase.messaging) {
              initializeFCM();
            } else {
              // Firebase not loaded, load it first
              loadFirebaseScript()
                .then(() => {
                  initializeFCM();
                })
                .catch((error) => {
                  console.error("Error loading Firebase:", error);
                  Toast(
                    "error",
                    "Error",
                    "Failed to load notification service",
                    "topRight"
                  );
                });
            }
          })
          .catch((error) => {
            console.error("Error requesting notification permission:", error);
          });
      }
    });
  }
});

// Load Firebase scripts dynamically
function loadFirebaseScript() {
  return new Promise((resolve, reject) => {
    // Load Firebase App
    const firebaseAppScript = document.createElement("script");
    firebaseAppScript.src =
      "https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js";
    firebaseAppScript.onload = () => {
      // Load Firebase Messaging
      const firebaseMessagingScript = document.createElement("script");
      firebaseMessagingScript.src =
        "https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js";
      firebaseMessagingScript.onload = () => {
        // Initialize Firebase
        const firebaseConfig = {
          apiKey: "AIzaSyAPByoVru7fAR1Mk8_y8AW73vWVRwEDma4",
          authDomain: "bossgpt-367ab.firebaseapp.com",
          projectId: "bossgpt-367ab",
          storageBucket: "bossgpt-367ab.firebasestorage.app",
          messagingSenderId: "1078128619253",
          appId: "1:1078128619253:web:edf3e5f2306ab349191fbc"
        };
        

        firebase.initializeApp(firebaseConfig);
        resolve();
      };
      firebaseMessagingScript.onerror = reject;
      document.body.appendChild(firebaseMessagingScript);
    };
    firebaseAppScript.onerror = reject;
    document.body.appendChild(firebaseAppScript);
  });
}

// Check notification permission on page load
document.addEventListener("DOMContentLoaded", function () {
  // Wait 3 seconds after page load to check notification permission
  // setTimeout(() => {
  //   // Only show modal if permission hasn't been granted or denied yet
  //   if (Notification.permission === "default") {
  //     // Show the notification permission modal
  //     const notificationModal = new bootstrap.Modal(
  //       document.getElementById("notificationPermissionModal")
  //     );
  //     notificationModal.show();
  //   } else if (Notification.permission === "granted" && !fcmTokenSaved) {
  //     // If permission is already granted but token not saved
  //     if (typeof firebase !== "undefined" && firebase.messaging) {
  //       initializeFCM();
  //     } else {
  //       loadFirebaseScript()
  //         .then(() => {
  //           initializeFCM();
  //         })
  //         .catch((error) => {
  //           console.error("Error loading Firebase:", error);
  //         });
  //     }
  //   }
  // }, 3000); // 3 second delay
});
