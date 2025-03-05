<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beautiful iziToast Notifications</title>

    <!-- iziToast CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>

  
</head>

<body>

    <h2>iziToast Notifications</h2>

    <button class="info" onclick="Notifier()">Info</button>
    
    <script>
        function Notifier() {
            // alert("SDFS")
            Toast('error', 'Error!', 'Your data has been rejected.');}

        function showInfo() {
            iziToast.info({
                title: 'Hello',
                message: 'Welcome!',
            });
        }

        function showSuccess() {
            iziToast.success({
                title: 'OK',
                message: 'Successfully inserted record!',
            });
        }

        function showWarning() {
            iziToast.warning({
                title: 'Caution',
                message: 'You forgot important data',
            });
        }

        function showError() {
            iziToast.error({
                title: 'Error',
                message: 'Illegal operation',
            });

        }

        function showQuestion() {
            iziToast.question({
                timeout: 20000,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: 'Hey',
                message: 'Are you sure about that?',
                position: 'center',
                buttons: [
                    ['<button><b>YES</b></button>', function (instance, toast) {

                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');

                    }, true],
                    ['<button>NO</button>', function (instance, toast) {

                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');

                    }],
                ],
                onClosing: function (instance, toast, closedBy) {
                    console.info('Closing | closedBy: ' + closedBy);
                },
                onClosed: function (instance, toast, closedBy) {
                    console.info('Closed | closedBy: ' + closedBy);
                }
            });
        }


        // iziToast Helper Functions
        function Toast(type, title, message) {
            const toastOptions = {
                title: title,
                message: message,
                position: 'topRight',
            };
            switch (type) {
                case 'info':
                    iziToast.info({ ...toastOptions, });
                    break;
                case 'success':
                    iziToast.success({ ...toastOptions, });
                    break;
                case 'warning':
                    iziToast.warning({ ...toastOptions, });
                    break;
                case 'error':
                    iziToast.error({ ...toastOptions, });
                    break;
                case 'question':
                    iziToast.question({
                        ...toastOptions,
                        timeout: 20000,
                        close: false,
                        overlay: true,
                        displayMode: 'once',
                        position: 'center',
                        buttons: [
                            ['<button><b>YES</b></button>', function (instance, toast) {
                                instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            }, true],
                            ['<button>NO</button>', function (instance, toast) {
                                instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            }],
                        ]
                    });
                    break;
                default:
                    console.warn('Unknown toast type:', type);
            }
        }

    </script>

    <!-- FontAwesome for Icons -->
    <!-- <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> -->

</body>

</html>

