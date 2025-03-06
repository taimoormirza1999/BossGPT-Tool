// Toasts iziToast Helper
function Toast(type, title, message) {
  const toastOptions = {
    title: title,
    message: message,
    position: "topRight",
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
