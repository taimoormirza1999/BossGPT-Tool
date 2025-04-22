<?php
require './vendor/autoload.php';
//Added to load the environment variables
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require_once './classes/UserManager.php';
require_once './classes/Notification.php';
require_once './classes/NotificationManager.php';
require_once './classes/GardenManager.php';
use Dotenv\Dotenv;

// // Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);

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
                    header('Location: ?page=dashboard');
                    exit;

                case 'logout':
                    $auth->logout();
                    exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// API Endpoint Handler
require_once './api_endPoints.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google Tag Manager -->

<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-5JFVBHSJ');</script>
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
    <!-- Tailwind CSS -->
    <!-- <script src="https://unpkg.com/@tailwindcss/browser@4"></script> -->
    <!-- Initialize user ID for project management -->
    <script>
        window.userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;

    </script>
    <!-- Custom js -->
    <script src="./assets/js/custom.js"></script>


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
    <script>
        // Initialize Firebase with your config
        const firebaseConfig = {
            apiKey: "YOUR_API_KEY",
            authDomain: "YOUR_AUTH_DOMAIN",
            projectId: "YOUR_PROJECT_ID",
            storageBucket: "YOUR_STORAGE_BUCKET",
            messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
            appId: "YOUR_APP_ID"
        };

        // Initialize Firebase only if it's not already initialized
        if (typeof firebase === 'undefined' || !firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }
    </script>

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
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5JFVBHSJ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
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

        switch ($page) {
            case 'login':
                include_login_page();
                break;
            case 'register':
                include_register_page();
                break;
            case 'dashboard':
                // Check if AI tone is set
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (!localStorage.getItem('aiToneMode')) {
                            window.location.href = '?page=aitone';
                        }
                    });
                </script>";
                include_dashboard();
                break;
            case 'aitone':
                include_aitone_page();
                break;
            case 'garden_stats':
                // Redirect to dashboard since we now use a modal for garden stats
                header('Location: ?page=dashboard');
                exit;
            default:
                echo "<h1>404 - Page Not Found</h1>";
        }
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
                                <?php if (isset($error_message) && $_GET['page'] == 'login'): ?>
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
                    <?php echo getLogoImage(); ?>
                    <div class="col-md-6 col-lg-12 mt-5">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Register</h2>
                                <?php if (isset($error_message) && $_GET['page'] == 'register'): ?>
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
            require_once 'components/misc.php';
            ?>
            <?php $projectManager = new ProjectManager();
            $projects = $projectManager->getProjects($_SESSION['user_id']);
            $gardenManager = new GardenManager();

            try {
                $allPlants = $gardenManager->getUserGarden($_SESSION['user_id']);

                // Map 'lush_tree' to 'tree' for consistency
                $allPlants = array_map(function ($plant) {
                    if ($plant['stage'] == 'lush_tree') {
                        $plant['stage'] = 'tree';
                    }
                    if ($plant['stage'] == 'seed') {
                        $plant['stage'] = 'sprout';
                    }
                    return $plant;
                }, $allPlants);

            } catch (Exception $e) {
                $allPlants = [];
            }

            ?>

            <div class="container-fluid pb-3">
                <!-- New Tab Navigation -->
                <?php require_once 'components/user_highlighter_bar.php'; ?>

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
                                        Select Project
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu" id="projectDropdown">
                                        <!-- Dynamically loaded items will be appended here -->
                                    </ul>
                                </div>

                                <div class="d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-main-primary me-2" data-bs-toggle="modal"
                                        data-bs-target="#newTaskModal">
                                        <?php echo getAddSquareIcon(); ?>Create&nbsp;New&nbsp;Task
                                    </button>
                                    <button type="button" class="btn btn-sm btn-main-primary me-2" data-bs-toggle="modal"
                                        data-bs-target="#gardenStatsModal">
                                        <i class="bi bi-tree"></i> My&nbsp;Garden&nbsp;Stats
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
            <script>


                document.addEventListener('DOMContentLoaded', function () {


                    // 1) The tree images
                    const treeImages = [
                        { file: 'treelv2.png', alt: 'Tree Level 2' },
                        { file: 'treelv3.png', alt: 'Tree Level 3' },
                        { file: 'treelv4.png', alt: 'Tree Level 4' },
                        { file: 'treelv5.png', alt: 'Tree Level 5' },
                        { file: 'treelv6.png', alt: 'Tree Level 6' },
                        { file: 'treelv7.png', alt: 'Tree Level 7' },
                        { file: 'treelv8.png', alt: 'Tree Level 8' },
                    ];

                    const container = document.getElementById('taskTreeContainer');
                    const hiddenInput = document.getElementById('selectedTreeType');

                    // 2) Build and insert the images
                    let html = '';
                    treeImages.forEach(({ file, alt }) => {
                        html += `
      <div class="tree-option" data-tree="${file}">
        <img src="assets/images/garden/${file}" alt="${alt}">
      </div>
    `;
                    });
                    container.innerHTML = html;

                    // 3) Attach click listeners
                    container.querySelectorAll('.tree-option').forEach(optionDiv => {
                        optionDiv.addEventListener('click', () => {
                            // Set hidden input
                            const treeValue = optionDiv.dataset.tree;
                            hiddenInput.value = treeValue;
                            console.log('Selected tree:', treeValue);

                            // Highlight selected
                            container.querySelectorAll('.tree-option').forEach(o => o.classList.remove('selected'));
                            optionDiv.classList.add('selected');
                        });
                    });


                    // Clean up any remnants of previous modals
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    $('.modal').removeClass('show');

                    // Add Garden Stats button to the project header
                    const projectHeader = document.querySelector('.projects_card .card-header');
                    if (projectHeader) {
                        // const gardenStatsBtn = document.createElement('button');
                        // gardenStatsBtn.className = 'btn btn-sm btn-info me-2';
                        // gardenStatsBtn.innerHTML = '<i class="bi bi-tree"></i> Garden Stats';
                        // gardenStatsBtn.id = 'openGardenStatsBtn';

                        // const existingBtns = projectHeader.querySelector('.btn, .dropdown');
                        // if (existingBtns) {
                        //     projectHeader.insertBefore(gardenStatsBtn, existingBtns);
                        // } else {
                        //     projectHeader.appendChild(gardenStatsBtn);
                        // }

                        // // Add click handler directly
                        // gardenStatsBtn.addEventListener('click', function() {
                        //     openGardenStatsModal();
                        // });
                    }

                    // Direct functions to ensure modal works
                    function openGardenStatsModal() {
                        $('#gardenStatsModal').modal('show');
                        // Loading overlay code removed as requested
                    }

                    function closeGardenStatsModal() {
                        // Multiple ways to try closing the modal
                        $('#gardenStatsModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('padding-right', '');
                    }

                    // Attach event handlers
                    $('#closeGardenStats, #closeModalBtn').on('click', function () {
                        closeGardenStatsModal();
                    });

                    $('#viewGardenBtn').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = 'garden.php';
                    });

                    // Handle View 3D Garden and Close buttons in the modal
                    document.getElementById('viewGardenBtn')?.addEventListener('click', function (e) {
                        e.preventDefault();
                        window.location.href = 'garden.php';
                    });

                    document.getElementById('closeModalBtn')?.addEventListener('click', function () {
                        closeGardenStatsModal();
                    });

                    // Close with ESC key
                    $(document).on('keydown', function (e) {
                        if (e.key === 'Escape' && $('#gardenStatsModal').hasClass('show')) {
                            closeGardenStatsModal();
                        }
                    });
                });
            </script>

            <style>
                /* Override modal styles */
                .modal-backdrop {
                    z-index: 1040 !important;
                }

                #gardenStatsModal {
                    z-index: 1050 !important;
                }

                #gardenStatsModal .modal-content {
                    z-index: 1050 !important;
                }

                .garden-stat-item {
                    padding: 1rem;
                    text-align: center;
                    flex: 1;
                }

                .garden-icon {
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                }

                .garden-count {
                    font-weight: bold;
                    font-size: 1.8rem;
                    color: #fff;
                }

                .garden-label {
                    font-size: 1rem;
                    color: #666;
                }
            </style>

            <!-- Reminder button -->
            <!-- <button id="reminderButton" class="reminder-button">
                <i class="bi bi-bell-fill bell-icon"></i>
                <span>Turn on Reminders</span>
            </button> -->
            <?php
            if (!isset($_SESSION['fcm_token'])) {
                echo getPopupAlert('Enable Notifications', 'Stay updated! Enable browser notifications to get the 
latest alerts instantly.', 'reminderButton', '<h6 class="font-secondaryBold button-text" id="enableNowBtn" onclick="DynamicOpen(\'#notificationPermissionModal\')">Enable Now</h6>');
            }
            ?>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- for logoIcon -->
    <script>
        const iconImage = `<?php echo getIconImage(0, 0, "1.8rem"); ?>`
        const welcomeLogoImage = `<?php echo getIconImage(0, 0, '3.7rem'); ?>`; 
        // document.cookie = "rewardful_referral=taimoor; path=/; max-age=86400";

// Quick check in console:
// console.log("All cookies:", document.cookie);
// console.log(
//   "rewardful_referral:",
//   document.cookie
//     .split("; ")
//     .find(c => c.startsWith("rewardful_referral="))
// );
    </script>


    <script>
        var userId = null;
        // Keep the initialization but don't add duplicate script
        
          
        // 2) Grab referral from ?ref= or ?via=
    const params      = new URLSearchParams(window.location.search);
    const referral    = params.get('ref') || params.get('via') || null;
    const email = "<?php echo addslashes(isset($_SESSION['email']) ? $_SESSION['email'] : ''); ?>";
    // console.log('Got email:', email);
    
    console.log('Referral:', referral);
    console.log('window.rewardful:', window.rewardful);

    // 3) Only fire convert once the library is actually present
    if (typeof rewardful === 'function') {
      rewardful('convert', {
        email: email,
        // Use 'referral' exactly as Rewardful expects
        referral: referral || undefined
      });
      console.log('🔥 rewardful.convert() called');
    } else {
      console.error('🚨 rewardful() not available yet');
      // retry once after a short delay
      setTimeout(() => {
        if (typeof rewardful === 'function') {
          rewardful('convert', { email, referral });
          console.log('🔥 rewardful.convert() called on retry');
        } else {
          console.error('🚨 rewardful() still not loaded');
        }
      }, 500);
    }

    // 4) Trigger your pro‑status update if needed
    <?php if (!empty($_GET['pro-member']) && $_GET['pro-member']==='true'): ?>
      updateProStatus();
    <?php endif; ?>
        function getLastSelectedProject() {
            if (userId) { // Check if userId is available
                return localStorage.getItem(`lastSelectedProject_${userId}`);
            }
            return null; // No user logged in or session expired
        }
        function updateProStatus(){
    fetch('?api=update_pro_status')
    .then(response => response.json())
    .then(data => {
        console.log(data);
    })
}

document.addEventListener('DOMContentLoaded', function () {
    // console.log('window.rewardful →', window.rewardful);
    <?php if(isset($_GET['pro-member']) && $_GET['pro-member'] == 'true') { ?>
        // alert('updateProStatus');
    updateProStatus();
    <?php } ?>
});
        document.addEventListener('DOMContentLoaded', function () {
            // Check if we're on the garden stats page
            const isGardenStats = window.location.href.includes('page=garden_stats');
            if (isGardenStats) {
                // Skip the dashboard-specific code for garden stats page
                return;
            }

            const currentProject = $('#myselectedcurrentProject').val();
            // console.log(currentProject)
            // alert(currentProject);
            // initPusher(currentProject);
            // First check if we're on the dashboard page
            const isDashboard = document.querySelector('.chat-container') !== null;

            if (isDashboard) {

                // Check if user is a pro member and redirect if necessary
                fetch('?api=check_pro_status')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && (!data.is_pro)) {
                            if (data.invited_by == null && (data.payment_link == '<?php echo $_ENV['STRIPE_PAYMENT_LINK_REFREAL']; ?>' || data.payment_link == '<?php echo $_ENV['STRIPE_PAYMENT_LINK']; ?>')) {

                                // window.location.href = data.payment_link;
                            }
                        }
                    })
                    .catch(error => console.error('Error checking pro status:', error));

                // Check URL parameters for pro-member status
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('pro-member') && urlParams.get('pro-member') === 'true') {
                    console.log('Updating pro status...');
                    fetch('?api=update_pro_status')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Pro status update response:', data);
                            if (data.success) {
                                Toast('success', 'Success', 'Your account has been upgraded to Pro!');
                                // setTimeout(() => {
                                //     window.location.href = '?page=dashboard';
                                // }, 1500);
                            } else {
                                Toast('error', 'Error', data.message || 'Failed to update pro status');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating pro status:', error);
                            Toast('error', 'Error', 'Failed to update pro status. Please try again.');
                        });
                }

                // Add debounce function at the start
                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };


                }

                // Create the debounced update function
                const debouncedUpdateTaskStatus = debounce((taskId, newStatus) => {
                    // showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating task status:', error))
                        .finally();
                }, 300); // 500ms debounce time

                let currentProject = null;
                // Load saved project from localStorage if available
                const savedProject = getLastSelectedProject();
                if (savedProject && savedProject !== 'null') {
                    currentProject = parseInt(savedProject);
                    $('#myselectedcurrentProject').val(currentProject);
                }

                const projectsList = document.getElementById('projectsList');
                const chatMessages = document.getElementById('chatMessages');
                const chatForm = document.getElementById('chatForm');
                const messageInput = document.getElementById('messageInput');
                const createProjectBtn = document.getElementById('createProjectBtn');
                const loadingIndicator = document.querySelector('.loading');

                // Show/hide loading indicator
                function showLoading() {
                    loadingIndicator.style.display = 'flex';
                }

                function hideLoading() {
                    loadingIndicator.style.display = 'none';
                }

                // Load projects
                function loadProjects() {
                    // showLoading();
                    fetch('?api=get_projects')
                        .then(response => response.json())
                        .then(data => {
                            console.log("Loaded projects: ", data.projects);
                            if (data.success) {
                                const projectDropdown = document.getElementById('projectDropdown');
                                projectDropdown.innerHTML = '';

                                if (!data.projects || data.projects.length === 0) {
                                    // If no projects exist, display a placeholder item
                                    const placeholder = document.createElement('li');
                                    placeholder.className = 'dropdown-item disabled';
                                    placeholder.textContent = 'No projects found';
                                    projectDropdown.appendChild(placeholder);
                                } else {
                                    // Loop through the projects and create dropdown items
                                    data.projects.forEach(project => {
                                        const li = document.createElement('li');
                                        li.className = 'dropdown-item';
                                        li.innerHTML = `
                            <button class="dropdown-item" type="button" data-id="${project.id}" title="${escapeHtml(project.title)}">
                                ${escapeHtml(project.title)}
                            </button>
                        `;
                                        projectDropdown.appendChild(li);
                                    });
                                }

                                // Add click handlers for project selection
                                document.querySelectorAll('.dropdown-item').forEach(item => {
                                    item.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        const button = item.querySelector('button');
                                        if (button) {
                                            const projectId = button.dataset.id;
                                            const projectTitle = button.getAttribute('title');
                                            selectProject(projectId, projectTitle);
                                        }
                                    });
                                });

                                // After projects are loaded, check for saved project
                                const savedProject = getLastSelectedProject();
                                if (savedProject && savedProject !== 'null' && savedProject !== '0') {
                                    const projectId = parseInt(savedProject);
                                    const projectButton = document.querySelector(`#projectDropdown button[data-id="${projectId}"]`);
                                    if (projectButton) {
                                        const projectTitle = projectButton.getAttribute('title');
                                        selectProject(projectId, projectTitle);
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading projects:', error);
                            const projectDropdown = document.getElementById('projectDropdown');
                            projectDropdown.innerHTML = `
                <li class="dropdown-item">
                    <div class="alert alert-danger">
                        Unable to load projects. Please try again later.
                    </div>
                </li>
            `;
                        })
                        .finally(hideLoading);
                }

                // Select project
                function selectProject(projectId, selectedProjectTitle = "") {
                    const $button = $('#projectDropdownButton');

                    // If no title is provided, get it from the dropdown item
                    if (!selectedProjectTitle) {
                        const selectedButton = $(`#projectDropdown button[data-id="${projectId}"]`);
                        if (selectedButton.length) {
                            selectedProjectTitle = selectedButton.attr('title');
                        }
                    }

                    // Get the current SVG if it exists
                    const $svg = $button.find('svg').clone();

                    // Clear and update the button text
                    $button.text(selectedProjectTitle);

                    // Add the SVG back if it exists
                    if ($svg.length > 0) {
                        $button.append($svg);
                    } else {
                        // If SVG doesn't exist, add a new one
                        $button.append(`
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L8 10L12 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        `);
                    }

                    // Update project ID and state
                    projectId = parseInt(projectId);
                    currentProject = parseInt(projectId);
                    $('#myselectedcurrentProject').val(currentProject);

                    // Save current project to localStorage for persistence
                    localStorage.setItem(`lastSelectedProject_${userId}`, currentProject);

                    // Update dropdown selection state
                    $('#projectDropdown button').removeClass('active').attr('data-selected', false);
                    $(`#projectDropdown button[data-id="${projectId}"]`).addClass('active').attr('data-selected', true);

                    // Update project items state
                    document.querySelectorAll('.project-item').forEach(item => {
                        const itemId = parseInt(item.dataset.id);
                        item.classList.toggle('active', itemId === projectId);
                    });

                    // call to fetch notifications
                    fetchNotificationsAndOpen(false);

                    // Load project data
                    loadTasks(projectId);

                    loadChatHistory(projectId);
                    initPusher(projectId);
                }

                // Load chat history
                function loadChatHistory(projectId) {
                    showLoading();
                    fetch('?api=get_chat_history', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ project_id: projectId })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                chatMessages.innerHTML = '';
                                if (Array.isArray(data.history) && data.history.length > 0) {
                                    appendWelcomeLogo();
                                    data.history.forEach(msg => {
                                        appendMessage(msg.message, msg.sender);
                                    });
                                } else {
                                    // If no chat history, show welcome messages
                                    displayProjectCreationWelcomeMessages();
                                }
                            } else {
                                throw new Error(data.message || 'Failed to load chat history');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chat history:', error);
                            chatMessages.innerHTML = `
                            <div class="alert alert-danger">
                                Failed to load chat history. Please try again later.
                            </div>
                        `;
                        })
                        .finally(hideLoading);
                }

                // Load tasks
                function loadTasks(projectId) {
                    // showLoading();
                    fetch('?api=get_tasks', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ project_id: projectId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateTasksBoard(data.tasks);
                            }
                        })
                        .catch(error => console.error('Error loading tasks:', error))
                        .finally();
                }

                // Update tasks board
                function updateTasksBoard(tasks) {
                    const todoTasks = document.getElementById('todoTasks');
                    const inProgressTasks = document.getElementById('inProgressTasks');
                    const doneTasks = document.getElementById('doneTasks');

                    todoTasks.innerHTML = '';
                    inProgressTasks.innerHTML = '';
                    doneTasks.innerHTML = '';

                    tasks.forEach(task => {
                        const taskElement = createTaskElement(task);
                        switch (task.status) {
                            case 'todo':
                                todoTasks.appendChild(taskElement);
                                break;
                            case 'in_progress':
                                inProgressTasks.appendChild(taskElement);
                                break;
                            case 'done':
                                doneTasks.appendChild(taskElement);
                                break;
                        }
                    });

                    // Initialize drag and drop
                    initializeDragAndDrop();
                }

                // Create task element
                function createTaskElement(task) {
                    const div = document.createElement('div');
                    div.className = 'task-card';
                    div.draggable = true;
                    div.dataset.id = task.id;

                    // Add click event listener for editing
                    div.addEventListener('click', (e) => {
                        // Don't open edit modal if clicking delete button, subtask buttons, or subtask elements
                        if (!e.target.closest('.delete-task-btn') &&
                            !e.target.closest('.add-subtask-btn') &&
                            !e.target.closest('.ai-add-subtask-btn') &&
                            !e.target.closest('.subtask-item')) {
                            openEditTaskModal(task);
                        }
                    });

                    // NEW: Generate HTML for due date if it exists
                    let dueDateHtml = '';
                    if (task.due_date) {
                        const dueDateObj = new Date(task.due_date);
                        const now = new Date();
                        const overdueClass = (dueDateObj < now ? 'overdue' : '');
                        const formattedDueDate = dueDateObj.toLocaleDateString(); // you can customize the format if needed
                        dueDateHtml = `<span class="due-date ${overdueClass}"><?php echo getCalendarIcon(); ?> ${formattedDueDate}</span>`;
                    }

                    // Build subtasks section with both manual and AI add buttons
                    const subtasksHtml = (function () {
                        let html = '';
                        if (task.subtasks && task.subtasks.length > 0) {
                            // Add a class to control subtasks visibility based on task status
                            html += `<div class="subtasks mt-2 hover-show-subtasks">
                                        <div class="subtasks-list">
                                            ${task.subtasks.map(subtask => {
                                const subtaskDueDate = subtask.due_date ? new Date(subtask.due_date) : null;
                                const isOverdue = subtaskDueDate && subtaskDueDate < new Date();
                                return `
                                                    <div class="subtask-item d-flex align-items-center mb-1" data-id="${subtask.id}">
                                                        <div class="form-check me-2">
                                                            <input class="form-check-input subtask-status" type="checkbox" 
                                                                   ${subtask.status === 'done' ? 'checked' : ''}>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="subtask-title ${subtask.status === 'done' ? 'text-decoration-line-through' : ''}">${escapeHtml(subtask.title)}</div>
                                                            ${subtask.due_date ? `
                                                                <small class="text-muted due-date ${isOverdue ? 'overdue' : ''}">
                                                                    <i class="bi bi-calendar-event"></i>
                                                                    ${subtask.due_date}
                                                                </small>
                                                            ` : ''}
                                                        </div>
                                                        <button class="btn btn-sm btn-link delete-subtask-btn" data-id="${subtask.id}">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                `;
                            }).join('')}
                                        </div>
                                        <div class="d-flex gap-2 mt-2 justify-content-center">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-update-dates-btn" data-task-id="${task.id}">
                                                AI Update Dates
                                            </button>
                                             <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-robot"></i> Generate AI Subtasks
                                            </button>
                                        </div>
                                    </div>`;
                        } else {
                            html += `<div class="mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : 'hover-show-subtasks'}">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-sm btn-link add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-plus-circle"></i> Add Subtask
                                            </button>
                                            <button class="btn btn-sm btn-link ai-add-subtask-btn" data-task-id="${task.id}">
                                                <i class="bi bi-robot"></i> Generate AI Subtasks
                                            </button>
                                        </div>
                                     </div>`;
                        }
                        return html;
                    })();

                    // Update the task picture HTML to make it clickable
                    const taskPictureHtml = task.picture ? `
                        <div class="task-picture mb-2">
                            <img src="${task.picture}" 
                                 alt="Task Picture" 
                                 class="enlarge-image"
                                 style="max-width:100%; border-radius:4px; cursor: pointer;"
                                 onerror="console.error('Image failed to load: ' + this.src); this.style.border='2px solid red';">
                        </div>
                    ` : '';
                    // Get plant stage based on task status and garden data
                    const getPlantImage = (task) => {
                        // console.log(task);
                        // If we have garden data, use it
                        if (task.garden.plant_stage && task.garden.plant_type) {
                            switch (task.garden.plant_stage) {
                                case 'dead': return 'dead.png';
                                case 'sprout': return 'seed.png';
                                case 'growing': return 'flower3.png';
                                case 'tree':
                                    // Return the specific tree type image
                                    return `${task.garden.plant_type}.png`;
                                default: return 'seed.png';
                            }
                        }

                        // Fallback to status-based images if no garden data
                        switch (task.status) {
                            case 'todo': return 'seed.png';
                            case 'in_progress': return 'flower3.png';
                            case 'done':
                                // Default to treelv3 if no plant_type specified
                                return task.garden.plant_type ? `${task.garden.plant_type}.png` : 'treelv3.png';
                            default: return 'seed.png';
                        }
                    };

                    const plantBallHtml = `<div class="plant-ball-container" >
    <img src="assets/images/garden/plant-ball.png" alt="Plant Ball" class="plant-ball" 
    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Plant Stage"
    style="
    height: 42px;
    box-shadow: 0 0 15px 5px rgba(255, 255, 150, 0.8);
    border-radius: 50%;
"  >
    <img src="assets/images/garden/${getPlantImage(task)}" 
         alt="Plant" 
         class="inner-plant" 
         style="
    height: 35px;
    position: absolute;
    left: 50%;
    transform: translate(-50%);
">
</div>`;

                    div.innerHTML = `
                    <div class="task-card-labels mb-2">
                            ${task.status === 'todo' ? '<span class="task-label label-red"></span>' : ''}
                            ${task.status === 'in_progress' ? '<span class="task-label label-orange"></span>' : ''}
                            ${task.status === 'done' ? '<span class="task-label label-green"></span>' : ''}
                            <span class="task-label label-blue"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0 task_title">${escapeHtml(task.title)}</h6>
                            <button class="btn btn-sm btn-danger delete-task-btn" data-id="${task.id}">
                                <?php echo getTrashIcon(); ?>
                            </button>
                        </div>
                        ${taskPictureHtml}
                        ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-meta">
                            ${dueDateHtml}
                            ${plantBallHtml}
                            ${task.assigned_users ? `
                                <div class="task-assignees d-flex gap-1 border-0 m-0 p-0">
                                    ${Object.entries(task.assigned_users).map(([id, username]) => {
                        // Generate background color based on user ID
                        // First user gets rgba(61, 127, 41, 1), others get varied hues
                        const hues = [61, 200, 0, 280, 30, 320, 170, 60, 340, 250]; // Green, blue, red, purple, orange, etc.
                        const index = parseInt(id) % hues.length;
                        const hue = hues[index];
                        const bgColor = `hsl(${hue}, 50%, 40%)`; // Consistent saturation and lightness
                        return `
                                        <span class="task-assignee text-capitalize" style="background-color: ${bgColor}; color: white;">
                                            ${escapeHtml(username[0].charAt(0).toUpperCase() + username[1].charAt(0).toUpperCase())}
                                        </span>
                                        `;
                    }).join('')}
                                </div>
                            ` : ''}
                        </div>
                        ${subtasksHtml}
                    `;

                    // Attach event listeners (existing listeners for manual subtask buttons, etc.)
                    const aiSubtaskBtn = div.querySelector('.ai-add-subtask-btn');
                    if (aiSubtaskBtn) {
                        aiSubtaskBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openAISubtaskGeneration(task);
                        });
                    }
                    // ... other event listeners such as for manual add subtask, delete, etc.

                    return div;
                }

                // Initialize drag and drop
                function initializeDragAndDrop() {
                    const taskCards = document.querySelectorAll('.task-card');
                    const taskColumns = document.querySelectorAll('.task-column');

                    taskCards.forEach(card => {
                        card.addEventListener('dragstart', () => {
                            card.classList.add('dragging');
                        });

                        card.addEventListener('dragend', () => {
                            card.classList.remove('dragging');
                            taskColumns.forEach(col => col.classList.remove('drag-over'));
                        });
                    });

                    taskColumns.forEach(column => {
                        column.addEventListener('dragenter', e => {
                            e.preventDefault();
                            column.classList.add('drag-over');
                        });

                        column.addEventListener('dragleave', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                        });

                        column.addEventListener('dragover', e => {
                            e.preventDefault();
                        });

                        column.addEventListener('drop', e => {
                            e.preventDefault();
                            column.classList.remove('drag-over');
                            const draggingCard = document.querySelector('.dragging');
                            if (draggingCard) {
                                const newStatus = column.dataset.status;
                                debouncedUpdateTaskStatus(draggingCard.dataset.id, newStatus);
                            }
                        });
                    });
                }

                // Update task status
                function updateTaskStatus(taskId, newStatus) {
                    // showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating task status:', error))
                        .finally();
                }

                // Handle chat form submission
                chatForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!currentProject) {
                        showToastAndHideModal(
                            'assignUserModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }

                    const message = messageInput.value.trim();
                    if (!message) return;

                    appendMessage(message, 'user');
                    messageInput.value = '';

                    showChatLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: message,
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.function_call && data.function_call.name === 'suggest_new_tasks') {
                                    const args = JSON.parse(data.function_call.arguments);
                                    if (args.suggestions) {
                                        renderSuggestedTasks(args.suggestions);
                                    } else {
                                        appendMessage(data.message, 'ai');
                                    }
                                } else {
                                    appendMessage(data.message, 'ai');
                                }
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error sending message:', error))
                        .finally(hideChatLoading);
                });

                // Handle new project creation
                createProjectBtn.addEventListener('click', function () {
                    const title = document.getElementById('projectTitle').value.trim();
                    const description = document.getElementById('projectDescription').value.trim();

                    if (!title) {
                        // alert('Please enter a project title');
                        showToastAndHideModal(
                            '',
                            'error',
                            'Error',
                            'Please enter a project title'
                        );
                        return;
                    }

                    showLoading();
                    fetch('?api=create_project', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ title, description })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // No need to call loadProjects() here since selectProject will refresh the UI
                                bootstrap.Modal.getInstance(document.getElementById('newProjectModal')).hide();
                                document.getElementById('projectTitle').value = '';
                                document.getElementById('projectDescription').value = '';
                                Toast("success", "Success", "Project created successfully", "bottomCenter");
                                selectProject(data.project_id, title);  // Pass the title here
                            }
                        })
                        .catch(error => console.error('Error creating project:', error))
                        .finally(hideLoading);
                });

                // Add new functions for task editing
                function openEditTaskModal(task) {
                    document.getElementById('editTaskId').value = task.id;
                    document.getElementById('editTaskTitle').value = task.title;
                    document.getElementById('editTaskDescription').value = task.description || '';
                    document.getElementById('editTaskDueDate').value = task.due_date || '';

                    const editTaskAssignees = document.getElementById('editTaskAssignees');
                    $(editTaskAssignees).empty();  // Clear using jQuery

                    // Fetch all users to populate the multi-select
                    showLoading();
                    fetch('?api=get_project_users', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                data.users.forEach(user => {
                                    const newOption = new Option(
                                        `${user.username} (${user.email}) - (${user.role})`,
                                        user.id,
                                        false,
                                        false
                                    );
                                    $(editTaskAssignees).append(newOption);
                                });
                                $(editTaskAssignees).trigger('change');  // Update Select2
                                // Now fetch already assigned users for this task
                                return fetch('?api=get_task_assignees', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ task_id: task.id })
                                });
                            } else {
                                alert('Failed to load users.');
                                throw new Error('Failed to load users.');
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const assignedIds = data.assignees;
                                Array.from(editTaskAssignees.options).forEach(option => {
                                    if (assignedIds.includes(parseInt(option.value))) {
                                        option.selected = true;
                                    }
                                });
                            } else {
                                alert('Failed to load assigned users.');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading assigned users:', error);
                            alert('Error loading assigned users.');
                        })
                        .finally(hideLoading);

                    // Add this new section to load task activity log
                    showLoading();
                    fetch('?api=get_task_activity_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: task.id })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const activityLogContainer = document.getElementById('taskActivityLog');
                                if (data.logs.length > 0) {
                                    activityLogContainer.innerHTML = data.logs.map(log => `
                                    <div class="activity-log-item mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">${formatDateTime(log.created_at)}</span>
                                            <span class="text-primary">${escapeHtml(log.username)}</span>
                                        </div>
                                        <div>${escapeHtml(log.description)}</div>
                                    </div>
                                `).join('');
                                } else {
                                    activityLogContainer.innerHTML = '<p class="text-muted">No activity recorded for this task.</p>';
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading task activity log:', error);
                            document.getElementById('taskActivityLog').innerHTML =
                                '<p class="text-danger">Failed to load activity log.</p>';
                        });

                    const editTaskModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
                    editTaskModal.show();

                    // Add this new code to populate subtasks
                    const subtasksList = document.getElementById('subtasksList');
                    subtasksList.innerHTML = '';

                    if (task.subtasks && task.subtasks.length > 0) {
                        task.subtasks.forEach(subtask => {
                            const subtaskElement = document.createElement('div');
                            subtaskElement.className = 'subtask-item d-flex align-items-center mb-2 p-2 border rounded';
                            subtaskElement.dataset.id = subtask.id;

                            const dueDate = subtask.due_date ? new Date(subtask.due_date) : null;
                            const isOverdue = dueDate && dueDate < new Date();

                            subtaskElement.innerHTML = `
                                <div class="form-check me-2">
                                    <input class="form-check-input subtask-status" type="checkbox" 
                                           ${subtask.status === 'done' ? 'checked' : ''}>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="subtask-title ${subtask.status === 'done' ? 'text-decoration-line-through' : ''}">${escapeHtml(subtask.title)}</div>
                                    <small class="text-muted">${escapeHtml(subtask.description || '')}</small>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <input type="date" 
                                               class="form-control form-control-sm subtask-due-date" 
                                               value="${subtask.due_date || ''}"
                                               style="max-width: 150px;"
                                               ${subtask.status === 'done' ? 'disabled' : ''}>
                                        <small class="due-date ${isOverdue ? 'overdue' : ''}">
                                            <i class="bi bi-calendar-event"></i>
                                        </small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-link text-danger delete-subtask-btn" data-id="${subtask.id}">
                                    <?php echo getTrashIcon(); ?>
                                </button>
                            `;
                            subtasksList.appendChild(subtaskElement);
                        });
                    } else {
                        subtasksList.innerHTML = '<p class="text-muted">No subtasks yet</p>';
                    }

                    // Add click handler for the Add Subtask button in modal
                    document.getElementById('addSubtaskInModalBtn').onclick = () => {
                        document.getElementById('parentTaskId').value = task.id;
                        document.getElementById('subtaskTitle').value = '';
                        document.getElementById('subtaskDescription').value = '';
                        document.getElementById('subtaskDueDate').value = '';
                        const addSubtaskModal = new bootstrap.Modal(document.getElementById('addSubtaskModal'));
                        addSubtaskModal.show();
                    };
                }

                // Add event listener for save button
                document.getElementById('saveTaskBtn').addEventListener('click', function () {
                    const taskId = document.getElementById('editTaskId').value;
                    const title = document.getElementById('editTaskTitle').value.trim();
                    const description = document.getElementById('editTaskDescription').value.trim();
                    const dueDate = document.getElementById('editTaskDueDate').value || null; // Convert empty string to null
                    const assignees = $('#editTaskAssignees').val().map(value => parseInt(value));
                    const pictureInput = document.getElementById('editTaskPicture');
                    const plantType = document.getElementById('editPlantType').value;
                    function sendUpdateTask(pictureData) {
                        let payload = {
                            task_id: taskId,
                            title: title,
                            description: description,
                            due_date: dueDate, // This will now be null instead of empty string
                            assignees: assignees
                        };
                        if (pictureData !== null) {
                            payload.picture = pictureData;
                        }
                        fetch('?api=update_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
                                    loadTasks(currentProject);
                                } else {
                                    throw new Error(data.message || 'Failed to update task');
                                }
                            })
                            .catch(error => {
                                console.error('Error updating task:', error);
                                alert('Failed to update task. Please try again.');
                            })
                            .finally(hideLoading);
                    }
                    if (pictureInput.files && pictureInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const base64String = e.target.result;
                            sendUpdateTask(base64String);
                        };
                        reader.readAsDataURL(pictureInput.files[0]);
                    } else {
                        sendUpdateTask(null);
                    }
                });


                const assignUserModal = document.getElementById('assignUserModal');
                assignUserModal.addEventListener('shown.bs.modal', function () {
                    if (!currentProject) {
                        showToastAndHideModal(
                            'assignUserModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }
                    showLoading();
                    fetch(`?api=get_all_project_users&project_id=${currentProject}`)
                        .then(async response => {
                            const text = await response.text();
                            console.log('Raw response:', text); // Debug log
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                throw new Error('Invalid server response');
                            }
                        })
                        // mujtabatesting1
                        .then(data => {
                            if (data.success) {
                                const userListContainer = document.getElementById('userListContainer');
                                const noUsersMessage = document.getElementById("noUsersMessage");
                                const addUserBtnTopRight = document.getElementById("add-user-btn-top-right");
                                userListContainer.innerHTML = '<h6 >Users</h6>';
                                if (data.users.length === 0) {
                                    noUsersMessage.classList.remove("d-none");
                                    userListContainer.classList.add("d-none");
                                } else {
                                    noUsersMessage.classList.add("d-none");
                                    userListContainer.classList.remove("d-none");
                                    data.users.forEach((user) => {
                                        const userCard = document.createElement("div");
                                        userCard.className = "d-flex justify-content-between align-items-center p-2 mb-2 border rounded dark-primaryborder ";
                                        let actionButtons = "<div>";
                                        if (user.role != "Creator") {
                                            actionButtons += `
            <button class="btn btn-sm btn-outline-danger deleteUser" data-id="${user.id}">
                <?php echo getTrashIcon(); ?>
            </button>`;
                                        }
                                        actionButtons += "</div>";
                                        userCard.innerHTML = `
                    <div>
                        <strong>@${user.username}</strong>
                        <span class="">(${user.role})</span>
                    </div>
                    ${actionButtons}
                `;

                                        userListContainer.appendChild(userCard);
                                    });
                                }
                            } else {
                                throw new Error(data.message || 'Failed to load users');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading users:', error);
                            showToastAndHideModal(
                                'assignUserModal',
                                'error',
                                'Error',
                                'Failed to load users'
                            );
                        })
                        .finally(hideLoading);
                });


                userListContainer.addEventListener("click", function (e) {
                    const deleteBtn = e.target.closest(".deleteUser");
                    const editBtn = e.target.closest(".editUser");
                    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));

                    if (deleteBtn) {
                        const userId = deleteBtn.getAttribute("data-id");
                        const projectId = currentProject;
                        const userDiv = deleteBtn.closest(".d-flex");
                        if (!userDiv) return;
                        // Extract username from the <strong> tag
                        const userName = userDiv.querySelector("strong")?.textContent.trim() || "Unknown User";
                        if (confirm(`Are you sure you want to remove ${userName} ?`)) {
                            fetch(`?api=delete_user`, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: new URLSearchParams({
                                    user_id: userId,
                                    project_id: projectId,
                                    user_name: userName
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        userDiv.remove();
                                        // showToast('success', 'User deleted successfully');
                                        Toast('success', 'Success', userName + ' removed successfully!');
                                    } else {
                                        Toast('error', 'Error', 'Failed to delete user');
                                    }
                                })
                                .catch(error => console.error("Error deleting user:", error));
                        }
                    }


                });

                // Handle "New User" selection
                // document.getElementById('userSelect').addEventListener('change', function () {
                //     if (this.value === 'new') {
                //         new bootstrap.Modal(document.getElementById('addUserModal')).show();
                //         this.value = ''; // Reset dropdown selection
                //     }
                // });
                $('#addUserBtn').click(function () {
                    $('#addUserModal').modal('show');
                });
                document.getElementById('addNewUserBtn').addEventListener('click', function () {
                    const email = document.getElementById('newUserEmail').value.trim();
                    const role = document.getElementById('newUserRole').value.trim();

                    if (!email || !role) {
                        Toast('error', 'Error', 'Please fill in all fields', 'bottomCenter');
                        return;
                    }
                    if (!email.includes('@')) {
                        Toast('error', 'Error', 'Please enter a valid email', 'bottomCenter');
                        return;
                    }

                    showLoading();
                    fetch('?api=create_or_assign_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: email,
                            project_id: currentProject,
                            role: role
                        })
                    })
                        .then(async response => {
                            const text = await response.text();
                            try {
                                const data = JSON.parse(text);
                                return data;
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                console.error('Raw response was:', text);
                                throw new Error(`Server response error: ${text}`);
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                // Close the add user modal
                                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                                // Clear the form
                                document.getElementById('newUserEmail').value = '';
                                document.getElementById('newUserRole').value = '';

                                const successMessage = data.data?.is_new_user
                                    ? "User created and assigned successfully! An invite has been sent along with login credentials."
                                    : "User assigned to project successfully!";

                                showToastAndHideModal('addUserModal', 'success', "Success", successMessage);
                                bootstrap.Modal.getInstance(document.getElementById('assignUserModal')).hide();
                            } else {
                                throw new Error(data.message || 'Failed to create or assign user');
                            }
                        })
                        .catch(error => {
                            console.error('Error creating/assigning user:', error);
                            Toast('error', 'Error', `Error: ${error.message}`, 'bottomCenter');
                        })
                        .finally(hideLoading);
                });
                // Helper functions
                function appendMessage(message, sender) {
                    // Check if message is a raw JSON response for subtask dates
                    if (sender === 'ai' && typeof message === 'string' &&
                        (message.includes('"task_id":') && message.includes('"subtasks":') && message.includes('"due_date":'))) {
                        try {
                            // Parse the JSON and format it using our formatter
                            const jsonMatch = message.match(/\{[\s\S]*\}/);
                            if (jsonMatch) {
                                const jsonStr = jsonMatch[0];
                                const jsonData = JSON.parse(jsonStr);

                                // Try to find the task details
                                let taskDetails = null;
                                if (jsonData.task_id) {
                                    const taskCards = document.querySelectorAll(`.task-card[data-id="${jsonData.task_id}"]`);
                                    if (taskCards.length > 0) {
                                        const taskCard = taskCards[0];
                                        taskDetails = {
                                            id: jsonData.task_id,
                                            title: taskCard.querySelector('h6').textContent,
                                            subtasks: Array.from(taskCard.querySelectorAll('.subtask-item')).map(item => ({
                                                id: item.dataset.id,
                                                title: item.querySelector('.subtask-title').textContent
                                            }))
                                        };
                                    }
                                }

                                // Replace the message with formatted HTML
                                message = formatAIResponse(jsonData, taskDetails);
                            }
                        } catch (e) {
                            console.error('Error formatting JSON response:', e);
                            message = '<div class="alert alert-success">Subtask dates have been updated successfully.</div>';
                        }
                    }

                    const div = document.createElement('div');
                    div.className = sender === 'user' ? 'user-message d-flex align-items-start justify-content-end' : 'ai-message d-flex align-items-start';
                    const iconImage = `<?php echo getIconImage(0, 0, '1.93rem'); ?>`;

                    if (sender === 'user') {
                        // Get username from session
                        const username = '<?php echo isset($_SESSION["username"]) ? $_SESSION["username"] : ""; ?>';
                        const initials = username.split(' ').map(word => word[0].toUpperCase()).join('').slice(0, 2) + username.split(' ').map(word => word[1].toUpperCase()).join('').slice(0, 2);

                        div.innerHTML = `
                            <div class="message user me-2">${message}</div>
                            <div class="user-avatar">
                                <div class="chat-loading-avatar d-flex align-items-center justify-content-center" style="background-color: rgba(8, 190, 139, 1);">
                                    ${initials}
                                </div>
                            </div>
                        `;
                    } else {
                        div.innerHTML = `
                            <div class="ai-avatar">
                                <div class="chat-loading-avatar">
                                    ${iconImage}
                                </div>
                            </div>
                            <div class="message ai">${message}</div>
                        `;
                    }

                    chatMessages.appendChild(div);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                function escapeHtml(unsafe) {
                    return unsafe
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                // Initial load
                loadProjects();

                // Auto-load the saved project if available
                if (isDashboard) {
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo "userId = " . json_encode($_SESSION['user_id']) . ";";
                    }
                    ?>
                }

                // Font size management
                // const fontSizeRange = document.getElementById('fontSizeRange');
                // const fontSizeValue = document.getElementById('fontSizeValue');
                // const mainContent = document.body;

                // Load saved font size or use default
                // const savedFontSize = localStorage.getItem('preferredFontSize') || '16';
                // fontSizeRange.value = savedFontSize;
                // fontSizeValue.textContent = `${savedFontSize}px`;
                // mainContent.style.fontSize = `${savedFontSize}px`;

                // Update font size when slider changes
                // fontSizeRange.addEventListener('input', function () {
                //     const newSize = this.value;
                //     fontSizeValue.textContent = `${newSize}px`;
                //     mainContent.style.fontSize = `${newSize}px`;
                //     localStorage.setItem('preferredFontSize', newSize);
                // });

                // Initialize Select2 for the edit task assignees
                $('#editTaskAssignees').select2({
                    placeholder: 'Select users to assign',
                    allowClear: true,
                    width: '100%'
                });

                // Fix for Select2 in Bootstrap modal
                $('#editTaskModal').on('shown.bs.modal', function () {
                    $('#editTaskAssignees').select2({
                        dropdownParent: $('#editTaskModal')
                    });
                });

                // Initialize Select2 for the new task assignees
                $('#newTaskAssignees').select2({
                    placeholder: 'Select users to assign',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#newTaskModal')
                });

                // Populate users when new task modal is shown
                const newTaskModal = document.getElementById('newTaskModal');
                newTaskModal.addEventListener('shown.bs.modal', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        bootstrap.Modal.getInstance(newTaskModal).hide();
                        return;
                    }

                    showLoading();
                    fetch('?api=get_project_users', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const assigneeSelect = document.getElementById('newTaskAssignees');
                                $(assigneeSelect).empty();  // Clear using jQuery
                                data.users.forEach(user => {
                                    const newOption = new Option(
                                        `${user.username} (${user.role})`,
                                        user.id,
                                        false,
                                        false
                                    );
                                    $(assigneeSelect).append(newOption);
                                });
                                $(assigneeSelect).trigger('change');  // Update Select2
                            } else {
                                alert('Failed to load project users');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading project users:', error);
                            alert('Error loading project users');
                        })
                        .finally(hideLoading);
                });

                // Handle new task creation
                document.getElementById('createTaskBtn').addEventListener('click', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }

                    const title = document.getElementById('newTaskTitle').value.trim();
                    const description = document.getElementById('newTaskDescription').value.trim();
                    const dueDate = document.getElementById('newTaskDueDate').value;
                    const assignees = $('#newTaskAssignees').val().map(value => parseInt(value));
                    const pictureInput = document.getElementById('newTaskPicture');
                    const selectedTree = document.querySelector('.tree-option.selected');
                    // const plantType = selectedTree ? selectedTree.dataset.tree : '';
                    const plantTypeRaw = selectedTree ? selectedTree.dataset.tree : '';
                    const plantType = plantTypeRaw.replace('.png', '');
                    // console.warn(selectedTree)
                    if (!title) {
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please enter a task title'
                        );
                        return;
                    }

                    if (!plantType) {
                        showToastAndHideModal(
                            'newTaskModal',
                            'error',
                            'Error',
                            'Please select a tree type'
                        );
                        return;
                    }
                    function sendCreateTask(pictureData) {
                        showLoading();
                        fetch('?api=create_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                project_id: currentProject,
                                title: title,
                                description: description,
                                due_date: dueDate,
                                assignees: assignees,
                                picture: pictureData,
                                plant_type: plantType
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('newTaskForm').reset();
                                    document.querySelectorAll('.tree-option').forEach(opt =>
                                        opt.classList.remove('selected')
                                    );
                                    $('#newTaskAssignees').val(null).trigger('change');

                                    // Close modal and refresh
                                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                                    loadTasks(currentProject);
                                } else {
                                    throw new Error(data.message || 'Failed to create task');
                                }
                            })
                            .catch(error => {
                                console.error('Error creating task:', error);
                                showToastAndHideModal('newTaskModal', 'error', 'Error', 'Failed to create task');
                            })
                            .finally(hideLoading);
                    }
                    if (pictureInput.files && pictureInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const base64String = e.target.result;
                            sendCreateTask(base64String);
                        };
                        reader.readAsDataURL(pictureInput.files[0]);
                    } else {
                        sendCreateTask(null);
                    }
                });

                // Add the deleteTask function
                function deleteTask(taskId) {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: 'deleted'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to delete task');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting task:', error);
                            alert('Failed to delete task. Please try again.');
                        })
                        .finally(hideLoading);
                }

                // Add these functions inside the DOMContentLoaded event listener
                function openAddSubtaskModal(taskId) {
                    document.getElementById('parentTaskId').value = taskId;
                    document.getElementById('subtaskTitle').value = '';
                    document.getElementById('subtaskDescription').value = '';
                    document.getElementById('subtaskDueDate').value = '';
                    const modal = new bootstrap.Modal(document.getElementById('addSubtaskModal'));
                    modal.show();
                }

                document.getElementById('saveSubtaskBtn').addEventListener('click', function () {
                    const taskId = document.getElementById('parentTaskId').value;
                    const title = document.getElementById('subtaskTitle').value.trim();
                    const description = document.getElementById('subtaskDescription').value.trim();
                    const dueDate = document.getElementById('subtaskDueDate').value;

                    if (!currentProject) {
                        alert('Please select a project first');
                        return;
                    }

                    if (!title) {
                        alert('Please enter a subtask title');
                        return;
                    }

                    showLoading();
                    fetch('?api=create_subtask', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            title: title,
                            description: description,
                            due_date: dueDate
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('addSubtaskModal')).hide();
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to create subtask');
                            }
                        })
                        .catch(error => {
                            console.error('Error creating subtask:', error);
                            alert('Failed to create subtask. Please try again.');
                        })
                        .finally(hideLoading);
                });

                function updateSubtaskStatus(subtaskId, status) {
                    showLoading();
                    fetch('?api=update_subtask_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            subtask_id: subtaskId,
                            status: status
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error updating subtask status:', error))
                        .finally(hideLoading);
                }

                function deleteSubtask(subtaskId) {
                    showLoading();
                    fetch('?api=delete_subtask', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            subtask_id: subtaskId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            }
                        })
                        .catch(error => console.error('Error deleting subtask:', error))
                        .finally(hideLoading);
                }

                // Add this inside the DOMContentLoaded event listener, where other modal handlers are defined
                // Activity Log Modal handler
                const activityLogModal = document.getElementById('activityLogModal');
                activityLogModal.addEventListener('show.bs.modal', function () {
                    if (!currentProject) {
                        // alert('Please select a project first');
                        showToastAndHideModal(
                            'activityLogModal',
                            'error',
                            'Error',
                            'Please select a project first'
                        );
                        return;
                    }
                    loadActivityLog();
                });

                function loadActivityLog() {
                    showLoading();
                    fetch('?api=get_activity_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const tbody = document.getElementById('activityLogTable');
                                tbody.innerHTML = data.logs.map(log => `
                                <tr>
                                    <td>${formatDateTime(log.created_at)}</td>
                                    <td>@${escapeHtml(log.username)}</td>
                                    <td>${escapeHtml(log.action_type)}</td>
                                    <td>${escapeHtml(log.description)}</td>
                                </tr>
                            `).join('');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading activity log:', error);
                            alert('Failed to load activity log');
                        })
                        .finally(hideLoading);
                }

                function formatDateTime(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString();
                }

                function openAISubtaskGeneration(task) {
                    if (!currentProject) {
                        alert('Please select a project first.');
                        return;
                    }
                    // Construct a prompt that instructs the AI to generate subtasks including the correct project and task IDs
                    const prompt = `
 Please generate a list of detailed subtasks for the following task using AI.
 Project ID: ${currentProject}
 Task ID: ${task.id}
 Task Details:
 Title: ${task.title}
 Description: ${task.description || 'No description provided'}
 ${task.due_date ? 'Due Date: ' + task.due_date : ''}
 
 Consider the overall project context and the existing tasks to ensure the subtasks are relevant, actionable, and detailed.
 Return the response using a function call named "create_multiple_subtasks" with parameters: task_id and subtasks (each having title, description, and due_date).
                    `;
                    showLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: prompt,
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                appendMessage(data.message, 'ai');
                                loadTasks(currentProject);
                            } else {
                                alert('Failed to generate subtasks: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error generating AI subtasks:', error);
                            alert('Error generating AI subtasks.');
                        })
                        .finally(hideLoading);
                }

                // Add this binding for subtask status changes:
                $(document).on('change', '.subtask-status', function () {
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    const newStatus = $(this).is(':checked') ? 'done' : 'todo';
                    updateSubtaskStatus(subtaskId, newStatus);
                });

                // Add event delegation for delete subtask buttons
                $(document).on('click', '.delete-subtask-btn', function (e) {
                    e.stopPropagation(); // Prevent task card click event
                    if (confirm('Are you sure you want to delete this subtask?')) {
                        const subtaskId = $(this).data('id');
                        deleteSubtask(subtaskId);
                    }
                });

                function renderSuggestedTasks(suggestions) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'd-flex';

                    // Avatar block
                    const avatarDiv = document.createElement('div');
                    avatarDiv.className = 'ai-avatar';
                    avatarDiv.innerHTML = `
    <div class="chat-loading-avatar">
      <img src="https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png" 
           alt="Logo" class="logo-icon" 
           style="filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3)); margin-top: 0; margin-bottom: 0; width: 1.8rem; height: auto">
    </div>
  `;

                    // Suggestions container
                    const suggestionsContainer = document.createElement('div');
                    suggestionsContainer.className = 'suggestions-container mt-3 message ai';
                    suggestionsContainer.innerHTML = `<h6 class="mb-3">Suggested Tasks & Features</h6>`;

                    suggestions.forEach(suggestion => {
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.className = 'suggestion-item border p-2 mb-2';
                        suggestionDiv.innerHTML = `
      <strong>${escapeHtml(suggestion.title)}</strong><br>
      <span class="my-2">${escapeHtml(suggestion.description)}</span><br>
      <div class="d-flex mt-1" style="justify-content: space-between; flex-direction: row-reverse;">
        <div class="suggested-task-due-date">
          ${suggestion.due_date ? `
            <?php echo getCalendarIcon(); ?>
            <em class="text-muted"> Due: ${escapeHtml(suggestion.due_date)}</em>` : ''}
        </div>
        <button class="btn btn-sm btn-add-task mt-1">
          <?php echo getAddIcon(); ?> Add Task
        </button>
      </div>
    `;
                        suggestionDiv.querySelector('button').addEventListener('click', () => {
                            addSuggestedTask(suggestion);
                        });
                        suggestionsContainer.appendChild(suggestionDiv);
                    });

                    // Append avatar + container to wrapper
                    wrapper.appendChild(avatarDiv);
                    wrapper.appendChild(suggestionsContainer);

                    // Add to chat
                    chatMessages.appendChild(wrapper);
                }


                function addSuggestedTask(suggestion) {
                    if (!currentProject) {
                        alert('Please select a project first');
                        return;
                    }
                    showLoading();
                    fetch('?api=create_task', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            project_id: currentProject,
                            title: suggestion.title,
                            description: suggestion.description,
                            due_date: suggestion.due_date
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                alert('Failed to add task: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error adding suggested task:', error);
                            alert('Error adding suggested task');
                        })
                        .finally(hideLoading);
                }

                // Add this new code to handle image clicks (add it where other event listeners are defined)
                document.addEventListener('click', function (e) {
                    if (e.target.classList.contains('enlarge-image')) {
                        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                        const enlargedImage = document.getElementById('enlargedImage');
                        enlargedImage.src = e.target.src;
                        imageModal.show();
                    }
                });

                // Add the deleteTask function here as well
                function deleteTask(taskId) {
                    showLoading();
                    fetch('?api=update_task_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: 'deleted'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadTasks(currentProject);
                            } else {
                                throw new Error(data.message || 'Failed to delete task');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting task:', error);
                            alert('Failed to delete task. Please try again.');
                        })
                        .finally(hideLoading);
                }

                // New event listener for removing task picture
                document.getElementById('removeTaskPictureBtn').addEventListener('click', function () {
                    if (!confirm('Are you sure you want to remove the picture from this task?')) {
                        return;
                    }
                    const taskId = document.getElementById('editTaskId').value;
                    if (!taskId) {
                        alert('Task ID is missing.');
                        return;
                    }
                    showLoading();
                    fetch('?api=remove_task_picture', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: taskId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Task picture removed successfully.');
                                // Clear the file input value
                                document.getElementById('editTaskPicture').value = '';
                            } else {
                                alert('Failed to remove task picture: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error removing task picture:', error);
                            alert('Error removing task picture.');
                        })
                        .finally(() => hideLoading());
                });

                // Function to format AI JSON responses into user-friendly HTML
                function formatAIResponse(jsonData, taskDetails) {
                    if (!jsonData || !jsonData.subtasks || !Array.isArray(jsonData.subtasks) || jsonData.subtasks.length === 0) {
                        return "<p>No subtask updates available.</p>";
                    }

                    // Create a map of subtask IDs to their titles
                    const subtaskMap = {};
                    if (taskDetails && taskDetails.subtasks) {
                        taskDetails.subtasks.forEach(subtask => {
                            subtaskMap[subtask.id] = subtask.title;
                        });
                    }

                    // Group subtasks by due date for better organization
                    const subtasksByDate = {};
                    jsonData.subtasks.forEach(subtask => {
                        if (!subtasksByDate[subtask.due_date]) {
                            subtasksByDate[subtask.due_date] = [];
                        }
                        subtasksByDate[subtask.due_date].push(subtask);
                    });

                    // Sort dates for chronological order
                    const sortedDates = Object.keys(subtasksByDate).sort();

                    let html = `
                        <div class="ai-schedule-response">
                            <h6 class="mb-3">📅 Optimized Task Schedule</h6>
                            <div class="timeline-container">
                    `;

                    sortedDates.forEach(date => {
                        // Format date for display (from YYYY-MM-DD to more readable format)
                        const dateObj = new Date(date);
                        const displayDate = dateObj.toLocaleDateString(undefined, {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });

                        // Get day of month for calendar icon
                        const dayOfMonth = dateObj.getDate();

                        html += `
                            <div class="date-group mb-3">
                                <div class="date-header">
                                    <div class="calendar-day-icon">
                                        <div class="day-number">${dayOfMonth}</div>
                                    </div>
                                    <span class="due-date"><?php echo getCalendarIcon(); ?> ${displayDate}</span>
                                </div>
                                <ul class="task-list list-unstyled ps-3 pt-2">
                        `;

                        subtasksByDate[date].forEach(subtask => {
                            const subtaskTitle = subtaskMap[subtask.id] || `Subtask #${subtask.id}`;
                            html += `<li class="mb-1">• <strong>${subtaskTitle}</strong></li>`;
                        });

                        html += `
                                </ul>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                            <p class="mt-3 text-success small">✓ All deadlines have been optimized to ensure completion before the main task deadline.</p>
                        </div>
                    `;

                    return html;
                }

                // Add this new function to handle AI date updates
                function updateSubtaskDatesWithAI(task) {
                    if (!currentProject) {
                        alert('Please select a project first.');
                        return;
                    }

                    const mainDueDate = task.due_date ? new Date(task.due_date.replace(/\s*<[^>]*>/g, '')).toISOString().split('T')[0] : null;

                    const prompt = `
STRICT DEADLINE ASSIGNMENT REQUEST:
Task: "${task.title}"
Current Date: ${new Date().toISOString().split('T')[0]}
PARENT TASK DUE DATE: ${mainDueDate || 'Not set'}

CRITICAL CONSTRAINTS:
1. PARENT TASK DUE DATE IS ABSOLUTE DEADLINE
2. ALL subtask deadlines MUST be BEFORE ${mainDueDate || 'parent due date'} 
3. Last subtask deadline must have at least 24 hours buffer before parent deadline
4. NO EXCEPTIONS to these constraints

SCHEDULING REQUIREMENTS:
- Create extremely aggressive timeline with NO SLACK
- Distribute subtasks across available time window
- Earlier dates preferred - create urgency
- Consider task dependencies (earlier subtasks first)
- Account for task complexity in duration
- NO FLEXIBLE or LOOSE deadlines
- Maximum pressure for quick completion

Current Subtasks (Must maintain IDs):
${task.subtasks.map(st => `- ID: ${st.id}, Title: ${st.title} (Current due: ${st.due_date || 'None'})`).join('\n')}

CRITICAL RESPONSE FORMAT INSTRUCTIONS:
You must respond using ONLY this exact format:
{
    "task_id": ${task.id},
    "subtasks": [
        {
            "id": <existing_subtask_id>,
            "due_date": "YYYY-MM-DD"
        }
    ]
}

VALIDATION RULES:
1. ALL due_dates MUST be <= ${mainDueDate || 'parent due date'}
2. ALL due_dates MUST be >= current date
3. MUST maintain existing subtask IDs
4. MUST use YYYY-MM-DD format
5. NO additional text or explanations
6. Dates MUST create high pressure timeline

ERROR: If parent due date exists and any subtask date would be after it, FAIL.
`;

                    showLoading();
                    fetch('?api=send_message', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: prompt,
                            project_id: currentProject,
                            aiTone: getCurrentAITone()
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log('AI Response:', data); // Debug log

                            if (data.success) {
                                try {
                                    // Try to parse the JSON response from the message
                                    const message = data.message || '';
                                    let jsonStr = message;

                                    // Try to extract JSON if it's wrapped in other text
                                    const jsonMatch = message.match(/\{[\s\S]*\}/);
                                    if (jsonMatch) {
                                        jsonStr = jsonMatch[0];
                                    }

                                    const args = JSON.parse(jsonStr);
                                    console.log('Parsed args:', args); // Debug log

                                    if (!args.task_id || !Array.isArray(args.subtasks)) {
                                        throw new Error('Response missing required fields');
                                    }

                                    // Convert the response to a user-friendly format and display it
                                    const formattedResponse = formatAIResponse(args, task);
                                    appendMessage(formattedResponse, 'ai');

                                    // Update each subtask's date individually
                                    const updatePromises = args.subtasks.map(subtask => {
                                        if (!subtask.id || !subtask.due_date) {
                                            console.error('Invalid subtask data:', subtask);
                                            return Promise.reject('Invalid subtask data');
                                        }
                                        return fetch('?api=update_subtask', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                subtask_id: subtask.id,
                                                due_date: subtask.due_date
                                            })
                                        }).then(response => response.json());
                                    });

                                    return Promise.all(updatePromises);
                                } catch (e) {
                                    console.error('Error parsing AI response:', e);
                                    console.error('AI response data:', data);
                                    throw new Error(`Invalid AI response structure: ${e.message}`);
                                }
                            } else {
                                throw new Error(data.message || 'Failed to get AI response');
                            }
                        })
                        .then(() => {
                            // Remove the generic message as we now show a formatted response
                            // appendMessage("Subtask dates have been aggressively updated to ensure tight deadlines.", 'ai');
                            loadTasks(currentProject);
                        })
                        .catch(error => {
                            console.error('Error updating subtask dates:', error);
                            alert('Error updating subtask dates: ' + error.message);
                        })
                        .finally(hideLoading);
                }

                // Add this event delegation for the new AI Update Dates button
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.ai-update-dates-btn')) {
                        e.stopPropagation();
                        const taskId = e.target.closest('.ai-update-dates-btn').dataset.taskId;
                        const taskCard = e.target.closest('.task-card');
                        const taskData = {
                            id: taskId,
                            title: taskCard.querySelector('h6').textContent,
                            due_date: taskCard.querySelector('.due-date')?.textContent.trim(),
                            subtasks: Array.from(taskCard.querySelectorAll('.subtask-item')).map(item => ({
                                id: item.dataset.id,
                                title: item.querySelector('.subtask-title').textContent,
                                due_date: item.querySelector('.due-date')?.textContent.trim(),
                                description: '' // Maintain existing description
                            }))
                        };
                        updateSubtaskDatesWithAI(taskData);
                    }
                });

                // Add these event handlers for subtask management in the modal
                $(document).on('change', '.subtask-status', function () {
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    const newStatus = $(this).is(':checked') ? 'done' : 'todo';
                    updateSubtaskStatus(subtaskId, newStatus);

                    // Update the visual state
                    const titleElement = $(this).closest('.subtask-item').find('.subtask-title');
                    titleElement.toggleClass('text-decoration-line-through', $(this).is(':checked'));
                });

                $(document).on('click', '.delete-subtask-btn', function (e) {
                    e.preventDefault();
                    const subtaskId = $(this).closest('.subtask-item').data('id');
                    if (confirm('Are you sure you want to delete this subtask?')) {
                        deleteSubtask(subtaskId);
                        $(this).closest('.subtask-item').remove();
                        if ($('#subtasksList').children().length === 0) {
                            $('#subtasksList').html('<p class="text-muted">No subtasks yet</p>');
                        }
                    }
                });

                // Add some CSS styles
                const style = document.createElement('style');
                style.textContent = `
                    #subtasksList .subtask-item {
                        background-color: var(--bs-light);
                        transition: all 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover {
                        background-color: var(--bs-light);
                        transform: translateX(4px);
                    }


                    #subtasksList .delete-subtask-btn {
                    border: 0 !important;
    background: transparent !important;
    font-size: 1.2rem !important;
    margin-top: -1.2rem;
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover .delete-subtask-btn {
                        opacity: 1;
                    }
                    
                   
                   
                    .timeline-container {
                        position: relative;
                    }
                    
                    .timeline-container:before {
                        content: '';
                        position: absolute;
                        left: 4px;
                        top: 8px;
                        bottom: 8px;
                        width: 2px;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 1px;
                    }
                    
                    .date-group {
                        position: relative;
                        padding-left: 20px;
                    }
                    
                    .date-header {
                        margin-bottom: 5px;
                    }
                    
                    .date-header:before {
                        content: '';
                        position: absolute;
                        left: -1px;
                        top: 8px;
                        width: 10px;
                        height: 10px;
                        background: #ffffff;
                        border-radius: 50%;
                        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
                    }
                    
                    .task-list {
                        margin-left: 10px;
                    }
                    
                    .task-list li {
                        border-radius: 5px;
                        padding: 2px 8px;
                        font-size: 0.9rem;
                        transition: all 0.2s ease;
                    }
                    
                    .task-list li:hover {
                        background: rgba(255, 255, 255, 0.1);
                    }
                `;
                document.head.appendChild(style);

                // Add these styles to the existing style element
                style.textContent += `
                    .subtask-due-date {
                        opacity: 0.7;
                        transition: all 0.2s ease;
                    }

                    .subtask-due-date:hover,
                    .subtask-due-date:focus {
                        opacity: 1;
                    }
                    .subtask-due-date:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                    }
                `;
                // Add event delegation for the add-subtask-btn
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.add-subtask-btn')) {
                        e.stopPropagation(); // Prevent task card click event
                        const taskId = e.target.closest('.add-subtask-btn').dataset.taskId;
                        openAddSubtaskModal(taskId);
                    }
                });



                // Image preview for new task modal
                document.getElementById('newTaskPicture').addEventListener('change', function (e) {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        const previewContainer = document.getElementById('imagePreviewContainer');
                        const imagePreview = document.getElementById('imagePreview');

                        reader.onload = function (e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';
                        }

                        reader.readAsDataURL(file);
                    }
                });

                // Remove preview button for new task modal
                document.getElementById('removePreviewBtn').addEventListener('click', function () {
                    const previewContainer = document.getElementById('imagePreviewContainer');
                    const fileInput = document.getElementById('newTaskPicture');

                    // Clear the file input
                    fileInput.value = '';
                    // Hide the preview
                    previewContainer.style.display = 'none';
                });

                // Image preview for edit task modal
                document.getElementById('editTaskPicture').addEventListener('change', function (e) {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        const previewContainer = document.getElementById('editImagePreviewContainer');
                        const imagePreview = document.getElementById('editImagePreview');

                        reader.onload = function (e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';

                            // Hide the remove picture button when showing preview
                            document.getElementById('taskPictureContainer').style.display = 'none';
                        }

                        reader.readAsDataURL(file);
                    }
                });

                // Remove preview button for edit task modal
                document.getElementById('editRemovePreviewBtn').addEventListener('click', function () {
                    const previewContainer = document.getElementById('editImagePreviewContainer');
                    const fileInput = document.getElementById('editTaskPicture');

                    // Clear the file input
                    fileInput.value = '';
                    // Hide the preview
                    previewContainer.style.display = 'none';

                    // Show the remove picture button if task had an existing picture
                    const taskId = document.getElementById('editTaskId').value;
                    const taskCards = document.querySelectorAll('.task-card');
                    taskCards.forEach(card => {
                        if (card.dataset.id === taskId && card.querySelector('.task-picture')) {
                            document.getElementById('taskPictureContainer').style.display = 'block';
                        }
                    });
                });

                // Modify openEditTaskModal to handle image preview for existing task picture
                const originalOpenEditTaskModal = openEditTaskModal;
                openEditTaskModal = function (task) {
                    // Call the original function first
                    originalOpenEditTaskModal(task);

                    // Reset file input and hide preview container
                    document.getElementById('editTaskPicture').value = '';
                    document.getElementById('editImagePreviewContainer').style.display = 'none';

                    // If task has an existing picture, show it in the preview
                    if (task.picture) {
                        const imagePreview = document.getElementById('editImagePreview');
                        imagePreview.src = task.picture;
                        document.getElementById('editImagePreviewContainer').style.display = 'block';
                        // Hide the remove button since we're showing the preview
                        document.getElementById('taskPictureContainer').style.display = 'none';
                    }

                    // Set the plant type in the edit tree selection
                    if (task.garden && task.garden.plant_type) {
                        const plantType = task.garden.plant_type;
                        document.getElementById('editPlantType').value = plantType + '.png';

                        // Highlight the selected tree
                        const treeOptions = document.querySelectorAll('#editTaskTreeContainer .tree-option');
                        treeOptions.forEach(option => {
                            option.classList.remove('selected');
                            if (option.dataset.tree === plantType + '.png') {
                                option.classList.add('selected');
                            }
                        });
                    }
                };

                // Uncomment this code and modify it to create a reusable function for showing enlarged images
                function openEnlargedImage(imageSrc) {
                    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                    const enlargedImage = document.getElementById('enlargedImage');
                    enlargedImage.src = imageSrc;
                    imageModal.show();
                }

                // Add event delegation for enlarging images when clicked
                document.addEventListener('click', function (e) {
                    // Check if the clicked element is an image preview in either modal
                    if (e.target.id === 'imagePreview' || e.target.id === 'editImagePreview') {
                        openEnlargedImage(e.target.src);
                    }

                    // Also handle task pictures in the task cards
                    if (e.target.classList.contains('enlarge-image')) {
                        openEnlargedImage(e.target.src);
                    }
                });

                // Add this event delegation handler before the closing of isDashboard block
                document.addEventListener('click', function (e) {
                    if (e.target.closest('.delete-task-btn')) {
                        e.stopPropagation(); // Prevent task card click event
                        const taskId = e.target.closest('.delete-task-btn').dataset.id;
                        if (confirm('Are you sure you want to delete this task?')) {
                            deleteTask(taskId);
                        }
                    }
                });
            } else {
                // We're on the login or register page
                // Only initialize necessary elements
                const loadingIndicator = document.querySelector('.loading');

                if (loadingIndicator) {
                    function showLoading() {
                        loadingIndicator.style.display = 'flex';
                    }

                    function hideLoading() {
                        loadingIndicator.style.display = 'none';
                    }
                }
            }


            // --- Dark Mode Toggle Code ---
            const toggleDarkModeBtn = document.getElementById('toggleDarkModeBtn');
            if (toggleDarkModeBtn) {
                // Check localStorage to apply dark mode preference on load
                if (localStorage.getItem('preferredDarkMode') === 'false') {
                    document.body.classList.remove('dark-mode');
                } else {
                    document.body.classList.add('dark-mode');
                }

                // Toggle dark mode when the button is clicked
                toggleDarkModeBtn.addEventListener('click', function () {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('preferredDarkMode', 'true');
                    } else {
                        localStorage.setItem('preferredDarkMode', 'false');
                    }
                });
            }
            // --- End Dark Mode Toggle Code ---

            // Add mouse movement tracking for task card hover effects
            document.addEventListener('mousemove', function (e) {
                document.querySelectorAll('.task-card:hover').forEach(function (card) {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });
            });

            // Add this new event handler after the other subtask-related event handlers:
            $(document).on('change', '.subtask-due-date', function () {
                const subtaskId = $(this).closest('.subtask-item').data('id');
                const newDueDate = $(this).val();

                showLoading();
                fetch('?api=update_subtask', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subtask_id: subtaskId,
                        due_date: newDueDate
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the visual state if needed
                            const dueDate = new Date(newDueDate);
                            const isOverdue = dueDate < new Date();
                            $(this).siblings('.due-date').toggleClass('overdue', isOverdue);
                        } else {
                            // If there's an error (like due date after parent task), revert the change
                            alert(data.message || 'Failed to update due date');
                            loadTasks(currentProject); // Reload to get the original state
                        }
                    })
                    .catch(error => {
                        console.error('Error updating subtask due date:', error);
                        alert('Failed to update due date. Please try again.');
                        loadTasks(currentProject); // Reload to get the original state
                    })
                    .finally(hideLoading);
            });
        }); // End of DOMContentLoaded

        function sendWelcomeEmailTest() {

            fetch('?api=send_welcome_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: "taimoorhamza1999@gmail.com",
                    username: "User123",
                    tempPassword: "TempPass123",
                    projectId: 48
                })
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(error => {
                    console.error("Error:", error.message);
                    alert("Failed to send email.");
                });
        }
        function sendNotificationTest(projectId = 42, title = "DFs Title", body = "DFs Body") {
            alert("Sending Notification Test");
            fetch('?api=send_notification_project', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send_notification_project',
                    project_id: projectId,
                    title: title,
                    body: body
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("✅ Notification sent successfully:", data.message);
                    } else {
                        console.error("❌ Error sending notification:", data.error);
                    }
                })
                .catch(error => console.error("❌ Request failed:", error));
        }
        let displayedReminders = new Set();
        // get Reminders
        function getReminders() {
            const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
            // console.log("FCM Token for reminders:", fcmToken);
            if (!fcmToken) {
                console.error('No FCM token found');
                return;
            }

            fetch('?api=get_fcm_reminders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fcm_token: fcmToken })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    // console.log("Reminders data:", data);
                    if (data.success && data.reminders && data.reminders.length > 0) {
                        const popupContainer = document.querySelector('.popup-container');
                        if (popupContainer) {
                            // Append each reminder as a popup alert
                            data.reminders.forEach(reminder => {
                                if (!displayedReminders.has(reminder.id)) {
                                    // Add to the set to track the displayed reminder
                                    displayedReminders.add(reminder.id);
                                    // Create popup using the PHP getPopupAlert function output
                                    const title = reminder.title || 'Reminder';
                                    const description = reminder.description || '';

                                    const popupHtml = `<?php echo getPopupAlert("TITLE_PLACEHOLDER", "DESCRIPTION_PLACEHOLDER", "REMINDER_ID_PLACEHOLDER"); ?>`
                                        .replace('TITLE_PLACEHOLDER', title)
                                        .replace('DESCRIPTION_PLACEHOLDER', description)
                                        .replace('REMINDER_ID_PLACEHOLDER', reminder.id);

                                    // Insert at the beginning to make the newest appear on top
                                    popupContainer.insertAdjacentHTML('afterbegin', popupHtml);

                                    // Limit visible alerts to 3
                                    updatePopupVisibility();
                                }
                            });

                            // Initialize popup functionality
                            const popups = document.querySelectorAll('.popup-alert');
                            // updatePopupVisibility();
                        }
                    } else {
                        // console.log("No reminders found or empty response");
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch reminders:', error);
                });
        }

        // Add the event listener when DOM is loaded
        document.addEventListener("DOMContentLoaded", function () {
            const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
            // console.log("FCM Token for reminders:", fcmToken);
            if (fcmToken && <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                interval = setInterval(getReminders, 10000); //every 10 seconds it will check for reminders
            }
        });

        function delete_fcm_reminders(reminder_id) {
            const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
            console.log("FCM Token for reminders:", fcmToken);
            if (reminder_id) {
                fetch('?api=delete_fcm_reminders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fcm_token: fcmToken, reminder_id: reminder_id })
                });
            }
        }
        initializeChatLoading();
    </script>


    <!-- Firebase -->
    <?php if (isset($page) && ($page === 'register' || $page === 'login' || $page === 'dashboard')): ?>
        <script type="module" src="assets/js/firbase.js"></script>
    <?php endif; ?>
    <!-- Pusher -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <div class="popup-container">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initial setup of popup visibility
            updatePopupVisibility();
        });

        function updatePopupVisibility() {
            const popups = document.querySelectorAll(".popup-alert");

            // Show only the first 3 popups, hide the rest
            popups.forEach((popup, index) => {
                if (index < 3) {
                    popup.classList.remove("hidden");
                } else {
                    popup.classList.add("hidden");
                }
            });
        }

        function closePopup(button) {
            let popup = button.parentElement;
            const reminderId = popup.dataset.reminderId;

            // Check if this is the notification reminder or enable now button
            const isNotificationReminder = popup.id === 'reminderButton';
            const isEnableNowBtn = button.id === 'enableNowBtn';

            if (isEnableNowBtn) {
                // Handle the Enable Now button click
                handleEnableNowBtn();
                return;
            }

            // For other reminders
            popup.remove();

            // Delete from database/backend for regular reminders
            if (reminderId && !isNotificationReminder) {
                delete_fcm_reminders(reminderId);
            }

            // Update popup visibility to show the next one if available
            updatePopupVisibility();
        }

        // Add this new function after the closePopup function
        function handleEnableNowBtn() {
            // Hide the notification popup
            const notificationPopup = document.getElementById('reminderButton');
            if (notificationPopup) {
                notificationPopup.style.display = 'none';
            }

            // Show the notification permission modal
            const notificationModal = new bootstrap.Modal(
                document.getElementById("notificationPermissionModal")
            );
            notificationModal.show();

            // Add event listener to show the popup again if the modal is dismissed
            const notificationModalEl = document.getElementById("notificationPermissionModal");
            notificationModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                // Only show the popup again if we don't have FCM token yet
                const fcmToken = localStorage.getItem('fcm_token');
                if (!fcmToken && notificationPopup) {
                    notificationPopup.style.display = '';
                } else if (notificationPopup) {
                    // If we now have FCM token, permanently remove the popup
                    notificationPopup.remove();
                }
                // Remove this listener to prevent multiple bindings
                notificationModalEl.removeEventListener('hidden.bs.modal', onHidden);
            });
        }
    </script>

    <?php
    function include_aitone_page()
    {
        ?>
        <div class="d-flex justify-content-center align-items-center min-vh-100 aitone-page">
            <div class="row justify-content-center w-100 position-relative">
                <?php echo getLogoImage(); ?>
                <div class="col-md-6 col-lg-5 mt-5">
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