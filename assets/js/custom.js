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