<?php
require './vendor/autoload.php';
//Added to load the environment variables
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require_once './classes/UserManager.php';
require_once './classes/Notification.php';
require_once './classes/NotificationManager.php';
require_once './classes/GardenManager.php';
// Added to persist the login cookie for one year
session_set_cookie_params(60 * 60 * 24 * 365);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 365);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['DISPLAY_ERRORS']);
ini_set('log_errors', 0);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);
require_once 'config/constants.php';
ob_start();
// Database Class

require_once './classes/Database.php';

// Auth Class
require_once './classes/Auth.php';
// Project Manager Class
require_once './classes/ProjectManager.php';

// AI Assistant Class
require_once './classes/AIAssistant.php';

// Initialize database and handle API requests
$database = Database::getInstance();
$database->initializeTables();



// Move the POST handling code here, after all classes are defined
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth = new Auth();

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'register':
                    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                        throw new Exception('All fields are required');
                    }

                    $auth->register(
                        $_POST['username'],
                        $_POST['email'],
                        $_POST['password'],
                        $_POST['fcm_token']
                    );

                    $user = new UserManager();
                    // After successful registration, log the user in
                    $user->sendWelcomeEmail($_POST['email'], $_POST['username'], $_ENV['BASE_URL']);                    // After successful registration, log the user in
                    $auth->login($_POST['email'], $_POST['password']);


                    exit;

                case 'login':
                    if (empty($_POST['email']) || empty($_POST['password'])) {
                        throw new Exception('Email and password are required');
                    }
                    // Store FCM token in session before login
                    if (isset($_POST['fcm_token']) && $_POST['fcm_token'] !== '0') {
                        $_SESSION['fcm_token'] = $_POST['fcm_token'];
                    }
                    $auth->login($_POST['email'], $_POST['password']);
                    header('Location:' . $_ENV['BASE_URL'] . '?page=dashboard');
                    exit;

                case 'logout':
                    $auth->logout();
                    echo "<script>clearRewardfulCookies();</script>";
                    header('Location:' . $_ENV['BASE_URL'] . '?page=login');
                    exit;
            }
        }
    }catch(Exception $e){$error_message = $e->getMessage();}
}
// API Endpoint Handler
require_once './api_endPoints.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
<script>
 const applyTheme = new Promise((resolve) => {
    const savedTheme = localStorage.getItem('userTheme') || 'system-mode';
    
    // Wait for body to exist
    const interval = setInterval(() => {
      if (document.body) {
        document.body.classList.add(savedTheme);
        clearInterval(interval);
        resolve(); 
      }
    }, 1);
  });
</script>
    <!-- Google Tag Manager -->

    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            }); var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-5JFVBHSJ');</script>
    <!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-signin-client_id"
        content="949298386531-pbk4td6p6ga18e6diee9rifskto0ou0v.apps.googleusercontent.com.apps.googleusercontent.com">
    <meta name="fcm_token_value" content="0" id="fcm_token_value">

    <title>BOSS GPT - Project Manager AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <!-- iziToast CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
    <!-- Flatpickr Core CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Tailwind CSS -->
    <!-- <script src="https://unpkg.com/@tailwindcss/browser@4"></script> -->
    <!-- Initialize user ID for project management -->
    <script>
        window.userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
    </script>
    <!-- Custom js -->
    <script src="./assets/js/custom.js"></script>

    <script type="module">
        import { DotLottie } from "https://cdn.jsdelivr.net/npm/@lottiefiles/dotlottie-web/+esm";

        document.addEventListener('DOMContentLoaded', function () {
            function initializeLottie(canvasId, lottieUrl) {
                const canvas = document.getElementById(canvasId);
                if (canvas) {
                    new DotLottie({
                        canvas: canvas,
                        src: lottieUrl,
                        loop: true,
                        autoplay: true
                    });
                } else {
                    // console.warn(`Canvas with ID '${canvasId}' not found.`);
                }
            }
            // Initialize both animations
            initializeLottie("myLottie", "https://res.cloudinary.com/da6qujoed/raw/upload/v1745325374/loader_bossgpt_htiw2q.lottie");
            initializeLottie("mychatLoader", "https://res.cloudinary.com/da6qujoed/raw/upload/v1745325374/loader_bossgpt_htiw2q.lottie");
        });
    </script>
    <link rel="icon" type="image/png" sizes="32x32" href="faviconbossgpt.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <!-- Custom css -->
    <link rel="stylesheet" href="./assets/css/custom.css">

    <link rel="stylesheet" href="./assets/css/customstyle2.css">
    <link rel="stylesheet"
        href="./assets/css/optimize.css?v=<?php echo filemtime(__DIR__ . '/assets/css/optimize.css'); ?>">

    <!-- Firebase Scripts for FCM -->
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js"></script>


</head>
<!-- Reuseable Stuff -->
<?php
function required_field()
{
    return '<span class="required-asterisk">*</span>';
}
function displayGoogleLoginBtn($text = "Sign in with Google")
{
    // If the user is NOT logged in (no access token in session):
    if (!isset($_SESSION['access_token'])) {
        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['BASE_URL'] . '/callback.php');
        $client->addScope("email");
        $client->addScope("profile");
        $client->setPrompt('select_account');
        $authUrl = $client->createAuthUrl();
        // Show the "Sign in with Google" button
        echo "
         <div class='text-center mt-2'>
                                 <div class='divider mt-2 mb-3'>
  <hr />
  <span>OR</span>
  <hr />
</div>
                                </div>
        <a href='$authUrl' class='btn btn-outline-link  w-100 d-flex align-items-center justify-content-center' style='gap: 8px;'>
               " . getGoogleIcon() . "
               " . $text . "
              </a>";
    }
    // Otherwise, the user IS logged in:
    else {
        echo "<a onclick='logout()' >Logout</a>";
    }
}
?>

<body
    style="background-color:<?php echo isset($_GET['page']) && ($_GET['page'] == 'login' || $_GET['page'] == 'register') ? '#000' : ''; ?> "
    class="system-mode">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5JFVBHSJ" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
    $auth = new Auth();

    $currentUrl = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; // Get full current URL
    
    if (!isset($_GET['page']) && strpos($currentUrl, $_ENV['STRIPE_PAYMENT_LINK']) == true) {
        header('Location: index.php?page=login');
        exit;
    }
    // Check if the page parameter is set in the URL
    if (isset($_GET['page']) && $_GET['page'] === 'register') {
        $page = 'register';
    } else {
        $page = $_GET['page'] ?? ($auth->isLoggedIn() ? 'dashboard' : 'login');
    }
    if (!$auth->isLoggedIn() && !in_array($page, ['login', 'register'])) {
        header('Location: ?page=login');
        exit;
    }

    // Show loading indicator
    echo '<div class="loading"><div class="spinner-border text-primary" role="status"></div></div>';

    // Navigation for logged-in users
    if ($auth->isLoggedIn()):
        if (isset($_SESSION['user_id'])) {
            if ($page != 'login' && $page != 'register' && $page != 'aitone') {
                require_once 'components/navigation.php';
            }
        }
    endif; ?>

    <div class="container-fluid mt-4">
        <?php

        require_once 'components/url.php';
        function include_login_page()
        {
            global $error_message;
            ?>
            <div class="d-flex justify-content-center align-items-center min-vh-100 login-page ">
                <div class="row justify-content-center w-100 position-relative">

                    <?php echo getLogoImage("", "-70px"); ?>
                    <div class="col-md-6 col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Login</h2>
                                <?php if (isset($error_message)): ?>
                                    <script>
                                        Toast("error", "Error", "<?php echo htmlspecialchars($error_message); ?>");
                                    </script>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="fcm_token" value="0" id="fcm_token">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                                <?php
                                displayGoogleLoginBtn();
                                ?>
                                <p class="text-center mt-3">
                                    <a href="?page=register" class="footer-text"><span class="normal-text">Don't have an
                                            account?</span> Sign Up </a>
                                </p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }

        function include_register_page()
        {
            global $error_message; // Add this line to access the error message
            ?>
            <div class="d-flex justify-content-center align-items-center min-vh-100 register-page">
                <div class="row justify-content-center w-100 position-relative">
                <?php echo getLogoImage("", "-70px"); ?>
                    <div class="col-md-6 col-lg-12 lg:mt-5">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Register</h2>
                                <?php if (isset($error_message)): ?>
                                    <script>
                                        Toast("error", "Error", "<?php echo htmlspecialchars($error_message); ?>");
                                    </script>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="fcm_token" value="0" id="fcm_token">
                                    <div class="mb-3">
                                        <label for="username" class="form-label" autocomplete="off">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label" autocomplete="off">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label"
                                            autocomplete="new-password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required
                                            autocomplete="new-password">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Register</button>
                                </form>

                                <?php
                                displayGoogleLoginBtn("Sign up with Google");
                                ?>
                                <p class="text-center mt-3">
                                    <a href="?page=login" class="footer-text"><span class="normal-text">Already have an
                                            account?</span> Login</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }



        function include_dashboard()
        {
            if(isset($_SESSION['user_id'])){
            if(!isset($_GET['page'])){
                header('Location: ?page=dashboard');
                exit;
            }
            }
            require_once 'components/misc.php';
            ?>
            <div class="container-fluid pb-3">
                <!-- New Tab Navigation -->
                <?php
 // Display welcome message if set
 if (isset($_SESSION['welcome_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Toast('success', 'Welcome', '" . htmlspecialchars($_SESSION['welcome_message']) . "');
        });
    </script>";
    unset($_SESSION['welcome_message']);
}
?>

                <!-- Main Content Area -->
                <div class="row sides-padding " style="width: 100%!important;">
                    <button class="btn btn-link p-0 text-white show open-icon-btn " data-bs-dismiss="modal"
                        aria-label="Close"
                        onclick="openChatPannel()"><?php echo getIconImage(0, 0, "2.5rem", "auto", "https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png", 0); ?></button>
                    <!-- Tasks Panel (Board) - now spans 9 columns -->
                    <div class="col-12 col-md-9 tasks-panel">
                        <div class="card h-100 projects_card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="dropdown">
                                    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false" id="projectDropdownButton">
                                        Select&nbsp;Project
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu hide-scrollbar" id="projectDropdown">
                                        <!-- Dynamically loaded items will be appended here -->
                                    </ul>
                                </div>

                                <div class="d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-main-primary me-2" data-bs-toggle="modal"
                                        data-bs-target="#newTaskModal">
                                        <?php echo getAddSquareIcon(); ?>New&nbsp;Task
                                    </button>
                                   
                                </div>

                            </div>
                            <div class="card-body pb-0">
                                <div class="row">
                                    <div class="col-10 col-md-4">
                                        <div class="task-column_section ">
                                            <h6 class="text-center task-column_header column_todo">To Do</h6>
                                            <div class="task-column" id="todoTasks" data-status="todo">
                                                <!-- Todo tasks will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-10 col-md-4">
                                        <div class="task-column_section">
                                            <h6 class="text-center task-column_header column_in_progress">In Progress</h6>
                                            <div class="task-column" id="inProgressTasks" data-status="in_progress">
                                                <!-- In progress tasks will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-10 col-md-4">
                                        <div class="task-column_section">
                                            <h6 class="text-center task-column_header column_done">Done</h6>
                                            <div class="task-column" id="doneTasks" data-status="done">
                                                <!-- Completed tasks will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Panel - now spans 3 columns -->
                    <?php require_once 'components/chat_pannel.php'; ?>
                </div>
            </div>

            <?php require_once 'components/modals.php'; ?>
          

           

        <?php } ?>

        <?php 
        if(isLoginUserPage()){
            require_once 'components/specific_pageScripts.php';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Firebase -->
    <script type="module" src="assets/js/firbase.js"></script>
    <?php 
        if(isLoginUserPage()){?>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <!-- Pusher -->
            <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
                    <!-- for logoIcon -->
            <script>
                const iconImage = `<?php echo getIconImage(0, 0, "1.5rem"); ?>`
                const welcomeLogoImage = `<?php echo getIconImage(0, 0, '3.7rem'); ?>`;
            </script>
        <?php } ?>
   
  






    <?php
    function include_aitone_page()
    {
        ?>
        <div class="d-flex justify-content-center align-items-center min-vh-100 aitone-page">
            <div class="row justify-content-center w-100 position-relative">
            <?php echo getLogoImage("", "-70px"); ?>
                <div class="col-md-6 col-lg-5 lg:mt-5">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="card-title text-center mb-4">How do you like your<br>AI Boss to be?</h2>

                            <!-- Replace the existing AI tone options with the helper function -->
                            <?php echo renderAIToneOptions(); ?>

                            <button id="continueToDashboard" class="btn btn-primary w-100 mt-3">Continue to
                                Dashboard</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toneOptions = document.querySelectorAll('.ai-tone-option');
                let selectedTone = 'friendly'; // Default selection
                // Get existing settings from localStorage if available
                const savedTone = localStorage.getItem('aiToneMode');
                if (savedTone) {
                    selectedTone = savedTone;
                }
                // Set initial selection in both storage keys for compatibility
                localStorage.setItem('aiToneMode', selectedTone);
                // Set initial active indicators based on saved tone
                toneOptions.forEach(option => {
                    const optionTone = option.getAttribute('data-tone');
                    if (optionTone === selectedTone) {
                        option.querySelector('.tone-indicator').classList.add('active');
                    } else {
                        option.querySelector('.tone-indicator').classList.remove('active');
                    }
                });

                // Handle tone selection
                toneOptions.forEach(option => {
                    option.addEventListener('click', function () {
                        // Remove active class from all options
                        toneOptions.forEach(opt => {
                            opt.querySelector('.tone-indicator').classList.remove('active');
                        });
                        // Add active class to selected option
                        this.querySelector('.tone-indicator').classList.add('active');
                        // Update selected tone
                        selectedTone = this.getAttribute('data-tone');
                        localStorage.setItem('aiToneMode', selectedTone);
                    });
                });

                // Handle continue button
                document.getElementById('continueToDashboard').addEventListener('click', function () {
                    window.location.href = '?page=dashboard';
                });
            });
        </script>

        <?php
    }

    ?>

</body>

</html>