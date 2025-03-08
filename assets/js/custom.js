// alert("DFDF")
// Toasts iziToast Helper
function Toast(type, title, message, positionToast) {
  const toastOptions = {
    title: title,
    message: message,
    position: positionToast?positionToast:"bottomCenter",
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
          if (typeof $ !== 'undefined') {
              $(`#${modalId}`).modal('hide');
          } else {
              // Using Bootstrap native
              const modalElement = document.getElementById(modalId);
              const modalInstance = bootstrap.Modal.getInstance(modalElement);
              if (modalInstance) {
                  modalInstance.hide();
              }
          }
      } catch (error) {
          console.error('Error hiding modal:', error);
      }
  }, delay);
}

function showToastAndHideModal(modalId, toastType, toastTitle, toastMessage, delay = 1500) {
  // Show toast first
  Toast(toastType, toastTitle, toastMessage);
  // Hide modal with delay
  hideModalWithDelay(modalId, delay);
}

// Chat Loading Animation
function initializeChatLoading() {
  // Add the CSS for loading animation
  const style = document.createElement('style');
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
          background-color: var(--primary-color, #0d6efd);
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
          background: var(--primary-color, #0d6efd);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 14px;
          font-weight: bold;
      }

      body.dark-mode .chat-loading-avatar {
          background: rgba(255,255,255,0.4);
          color: #fff;
          
      }
  `;
  document.head.appendChild(style);
}

// Show Chat Loading Animation
function showChatLoading() {
  const chatMessages = document.querySelector('.chat-messages');
  if (!chatMessages) return;

  const loadingElement = document.createElement('div');
  loadingElement.className = 'chat-loading';
  loadingElement.innerHTML = `
      <div class="chat-loading-container">
          <div class="chat-loading-avatar"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 640 512" class="text-5xl" height="1.5em" width="1.5em" xmlns="http://www.w3.org/2000/svg"><path d="M32,224H64V416H32A31.96166,31.96166,0,0,1,0,384V256A31.96166,31.96166,0,0,1,32,224Zm512-48V448a64.06328,64.06328,0,0,1-64,64H160a64.06328,64.06328,0,0,1-64-64V176a79.974,79.974,0,0,1,80-80H288V32a32,32,0,0,1,64,0V96H464A79.974,79.974,0,0,1,544,176ZM264,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,264,256Zm-8,128H192v32h64Zm96,0H288v32h64ZM456,256a40,40,0,1,0-40,40A39.997,39.997,0,0,0,456,256Zm-8,128H384v32h64ZM640,256V384a31.96166,31.96166,0,0,1-32,32H576V224h32A31.96166,31.96166,0,0,1,640,256Z"></path></svg></div>
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
  const loadingElement = document.querySelector('.chat-loading');
  if (loadingElement) {
      loadingElement.remove();
  }
}


function openNewProjectModal() {
  // Get your existing new project modal
  const modal = new bootstrap.Modal(document.getElementById('newProjectModal'));
  modal.show();
}
function scrollToBottom() {
  welcomeThread.scrollTop = welcomeThread.scrollHeight;
}


// Welcome AI Messages 
