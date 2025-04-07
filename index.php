<?php
require './vendor/autoload.php';
//Added to load the environment variables
// require_once 'env.php';
require_once './classes/UserManager.php';
require_once './classes/Notification.php';
require_once './classes/NotificationManager.php';
use Dotenv\Dotenv;

// // Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
session_start();
// Added to persist the login cookie for one year
session_set_cookie_params(60 * 60 * 24 * 365);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 365);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['DISPLAY_ERRORS']);
ini_set('log_errors', 0);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);
require_once './config/constants.php';
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

                    $paymentLink = $_ENV['STRIPE_PAYMENT_LINK'];
                    header("Location: $paymentLink");
                    exit;

                case 'login':
                    if (empty($_POST['email']) || empty($_POST['password'])) {
                        throw new Exception('Email and password are required');
                    }

                    $auth->login($_POST['email'], $_POST['password']);
                    header('Location: ?page=dashboard');
                    exit;

                case 'logout':
                    $auth->logout();
                    session_start();
                    session_unset();
                    session_destroy();
                    header('Location: ?page=login');
                    exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// API Endpoint Handler
require_once './api_endPoints.php';


// if (!isset($_SESSION["pro_member"])) {
//     header("Location: " . $_ENV['STRIPE_PAYMENT_LINK']);
//     // exit;
// }
// echo $_SESSION;
// echo "dfdsf";
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre><br/>";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-signin-client_id"
        content="949298386531-pbk4td6p6ga18e6diee9rifskto0ou0v.apps.googleusercontent.com.apps.googleusercontent.com">
    <meta name="fcm_token_value" content="0" id="fcm_token_value">

    <title>Project Manager AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        // console.log('User ID initialized:', window.userId)\;
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

        $authUrl = $client->createAuthUrl();
        // Show the "Sign in with Google" button
        echo "
         <div class='text-center mt-2'>
                                    <p class='text-muted mb-1'>OR</p>
                                </div>
        <a href='$authUrl' class='btn btn-outline-link  w-100 d-flex align-items-center justify-content-center' style='gap: 8px;'>
                <svg width='18' height='19' viewBox='0 0 16 17' fill='none' xmlns='http://www.w3.org/2000/svg'>
                  <path d='M13.8824 7.41113H8.11768V9.72516H11.4231C11.3564 10.0945 11.2134 10.4465 11.0029 10.7595C10.7925 11.0726 10.5191 11.34 10.1995 11.5454V13.051H12.1665C12.7703 12.4802 13.2452 11.7912 13.5605 11.0286C14.0327 9.88636 14.0878 8.62111 13.8824 7.41113Z' fill='#4285F4'></path>
                  <path d='M8.11765 14.4996C9.76488 14.4996 11.1599 13.9718 12.1665 13.0506L10.1995 11.545C9.58012 11.9375 8.85452 12.1373 8.11765 12.1181C7.35471 12.1088 6.61379 11.8656 5.99848 11.4224C5.38317 10.9793 4.92424 10.3584 4.68585 9.64648H2.65015V11.1856C3.15915 12.1814 3.94 13.0187 4.9055 13.604C5.87099 14.1892 6.98311 14.4992 8.11765 14.4996Z' fill='#34A853'></path>
                  <path d='M4.68589 9.64706C4.42873 8.90009 4.42873 8.09081 4.68589 7.34384V5.79395H2.65019C2.22264 6.63065 2 7.55387 2 8.49004C2 9.42621 2.22264 10.3494 2.65019 11.1861L4.68589 9.64706Z' fill='#FBBC04'></path>
                  <path d='M8.11765 4.87211C8.98898 4.85751 9.83116 5.18027 10.4621 5.77064L12.2126 4.05185C11.5147 3.43218 10.6808 2.9789 9.77551 2.72723C8.87026 2.47556 7.91812 2.43227 6.99307 2.60073C6.06803 2.76919 5.19498 3.14487 4.44177 3.69857C3.68856 4.25226 3.07548 4.96907 2.65015 5.7933L4.68585 7.34371C4.92424 6.63182 5.38317 6.0109 5.99848 5.56776C6.61379 5.12461 7.35471 4.8814 8.11765 4.87211Z' fill='#EA4335'></path>
                </svg>
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
style="background-image: url('https://trello-backgrounds.s3.amazonaws.com/53baf533e697a982248cd73f/2048x2048/22ec03aab9d36ea49139c569a62bb079/shutterstock_134707556.jpg'); background-size: cover; background-position: top; background-repeat: no-repeat; background-attachment: fixed; background-color:<?php echo isset($_GET['page']) && ($_GET['page'] == 'login' || $_GET['page'] == 'register') ? '#000' : ''; ?> ">
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
        ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid sides-padding" style="overflow: visible;">
                <a class="navbar-brand" href="?page=dashboard">
                    <?php echo getLogoImage($bottomMargin = '0.4rem', $topMargin = "0.4rem", $width = "11rem", $height = "auto", $positionClass = " ", $positionStyle = " ", $src = "https://res.cloudinary.com/da6qujoed/image/upload/v1742651528/bossgpt-transparent_n4axv7.png"); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <!-- <li class="nav-item">
                            <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>"
                                href="?page=dashboard">Dashboard</a>
                        </li> -->
                    </ul>
                    <div class="d-flex align-items-center">

                        <div class="d-flex align-items-center me-4">
                            <label for="fontSizeRange" class="text-light me-2 mb-0">Font Size:</label>
                            <input type="range" class="form-range" id="fontSizeRange" min="12" max="24" step="1"
                                style="width: 100px;">
                            <span id="fontSizeValue" class="text-light ms-2" style="min-width: 45px;">16px</span>
                        </div>

                        <!-- Notification Icon with Red Badge -->
                        <?php
                        $unreadNotifications = 0;
                        $notifications = [];
                        ?>
                        <div class="dropdown">
                            <input type="hidden" id="myselectedcurrentProject" value="0">
                            <button class="btn btn-icon-only position-relative " id="notificationDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <svg width="24" height="24" viewBox="0 0 24 24" class="" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 6.43994V9.76994" stroke="white" stroke-width="1.5" stroke-miterlimit="10"
                                        stroke-linecap="round" />
                                    <path
                                        d="M12.0199 2C8.3399 2 5.3599 4.98 5.3599 8.66V10.76C5.3599 11.44 5.0799 12.46 4.7299 13.04L3.4599 15.16C2.6799 16.47 3.2199 17.93 4.6599 18.41C9.4399 20 14.6099 20 19.3899 18.41C20.7399 17.96 21.3199 16.38 20.5899 15.16L19.3199 13.04C18.9699 12.46 18.6899 11.43 18.6899 10.76V8.66C18.6799 5 15.6799 2 12.0199 2Z"
                                        stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" />
                                    <path
                                        d="M15.3299 18.8199C15.3299 20.6499 13.8299 22.1499 11.9999 22.1499C11.0899 22.1499 10.2499 21.7699 9.64992 21.1699C9.04992 20.5699 8.66992 19.7299 8.66992 18.8199"
                                        stroke="white" stroke-width="1.5" stroke-miterlimit="10" />
                                </svg>
                                <span
                                    class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"
                                    id="notificationBadge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu" id="notificationDropdownMenu"
                                aria-labelledby="notificationDropdown"
                                style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                               
                                <div class="notification-list">
                                    <div class="dropdown-item text-center">No notification found 🎉 </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle Button -->
                        <!-- <button id="toggleDarkModeBtn" class="btn btn-outline-light mx-2">Dark Mode</button> -->
                        <button id="toggleDarkModeBtn" class="btn btn-icon-only mx-2">
                            <svg width="24" id="light-icon" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 18.5C15.5899 18.5 18.5 15.5899 18.5 12C18.5 8.41015 15.5899 5.5 12 5.5C8.41015 5.5 5.5 8.41015 5.5 12C5.5 15.5899 8.41015 18.5 12 18.5Z"
                                    stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M19.14 19.14L19.01 19.01M19.01 4.99L19.14 4.86L19.01 4.99ZM4.86 19.14L4.99 19.01L4.86 19.14ZM12 2.08V2V2.08ZM12 22V21.92V22ZM2.08 12H2H2.08ZM22 12H21.92H22ZM4.99 4.99L4.86 4.86L4.99 4.99Z"
                                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <svg id="dark-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 28 30">
                                <path d="
            M 23, 5
            A 12 12 0 1 0 23, 25
            A 12 12 0 0 1 23, 5
        "></path>
                            </svg>
                        </button>
                        <!-- Logout Form -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-outline-light">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

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
                include_dashboard();
                break;
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
                    <div class="col-md-6 col-lg-4">
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
                                    <a href="?page=register">Don't have an account? Sign Up </a>
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
                    <!-- <img src="assets/images/bossgptlogo.svg" alt="Logo"
                        class="position-absolute top-0 start-50 translate-middle "
                        style="margin-top: -1rem; width: 15rem; height: 10rem;position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);"> -->
                    <?php echo getLogoImage(); ?>
                    <div class="col-md-6 col-lg-4 mt-5">
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
                                        <label for="password" class="form-label" autocomplete="off">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Register</button>
                                </form>

                                <?php
                                displayGoogleLoginBtn("Sign up with Google");
                                ?>
                                <p class="text-center mt-3">
                                    <a href="?page=login">Already have an account? Login</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }

        function include_dashboard()
        {
            ?>
            <?php $projectManager = new ProjectManager();
            $projects = $projectManager->getProjects($_SESSION['user_id']);

            // Display welcome message if set
            if (isset($_SESSION['welcome_message'])) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Toast('success', 'Welcome', '" . htmlspecialchars($_SESSION['welcome_message']) . "');
                    });
                </script>";
                unset($_SESSION['welcome_message']); // Clear the message after displaying
            }
            ?>
            <!-- Replace the existing row div with this new layout -->
            <div class="container-fluid pb-3">
                <!-- New Tab Navigation -->
                <div class="nav-container row justify-content-between nav-background"
                    style="background-color: var(--bs-primary-dark60percent); ">
                    <div class="col-md-6 d-flex justify-content-between align-items-center self-center">
                        <h4 class="text-capitalize font-weight-normal d-flex align-items-center"
                            style="font-size: 1.83rem;">
                            <span style="color: var(--bs-primary-white55percent);">Welcome, </span> <span
                                class=" text-capitalize" style="color: var(--bs-primary-white); font-size: 1.43rem;">
                                &nbsp;<?php echo $_SESSION['username']; ?>&nbsp;</span>&nbsp;👋
                        </h4>
                    </div>
                    <ul class="col-md-6 nav nav-tabs mb-0 d-flex justify-content-end align-items-center" id="projectTabs"
                        style="width: auto; ">
                        <li class="nav-item">
                            <button type="button" class="btn btn-sm btn-main-primary" data-bs-toggle="modal"
                                data-bs-target="#assignUserModal">
                                <i class="bi bi-person-plus"></i> Invite User
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="btn btn-sm btn-main-primary" data-bs-toggle="modal"
                                data-bs-target="#activityLogModal">
                                <i class="bi bi-clock-history"></i> Activity Log
                            </button>
                        </li>
                        <?php if (TESTING_FEATURE == 1): ?>
                            <li class="nav-item">

                                <button type="button" class="btn btn-sm btn-info" onclick='sendWelcomeEmailTest()'>
                                    <i class="bi bi-clock-history"></i> Testing Feature
                                </button>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <button type="button" class="btn btn-main-primary" data-bs-toggle="modal"
                                data-bs-target="#newProjectModal">
                                <i class="bi bi-plus"></i> New Project
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Main Content Area -->
                <div class="row sides-padding " style="width: 100%!important;">
                    <!-- Tasks Panel (Board) - now spans 9 columns -->
                    <div class="col-md-9">
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


                                <button type="button" class="btn btn-sm btn-main-primary me-2" data-bs-toggle="modal"
                                    data-bs-target="#newTaskModal">
                                    <i class="bi bi-plus"></i> Create New Task
                                </button>

                            </div>
                            <div class="card-body pb-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="task-column_section ">
                                            <h6 class="text-center task-column_header column_todo">To Do</h6>
                                            <div class="task-column" id="todoTasks" data-status="todo">
                                                <!-- Todo tasks will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="task-column_section">
                                            <h6 class="text-center task-column_header column_in_progress">In Progress</h6>
                                            <div class="task-column" id="inProgressTasks" data-status="in_progress">
                                                <!-- In progress tasks will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
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
                    <div class="col-md-3">
                        <div class="card" style="background-color: transparent!important;">
                            <div class="card-header" style="
    display: flex;
    flex-direction: column;
    justify-content: center;
    /* align-items: center; */
    padding: 12px 13px;
    gap: 10px;
    border-bottom: 0.5px solid;
">
                                <h5 class="mb-0"><?php echo getIconImage(0, 0, "2.5rem","auto","https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png",0); ?> &nbsp; Boss<span
                                style="font-weight: 700;">GPT</span> Assistant </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="chat-container">
                                    <div class="chat-messages" id="chatMessages">
                                        <?php if (empty($projects)): ?>
                                            <div class="welcome-guide">
                                                <div class="message-thread" id="welcomeThread">
                                                    <!-- Messages will be inserted here by JavaScript -->
                                                </div>
                                            </div>

                                            <script>
                                                // Immediately invoke function to initialize welcome messages
                                                (function initializeWelcomeMessages() {
                                                    // console.log('Initializing welcome messages...'); // Debug log

                                                    const welcomeThread = document.getElementById('welcomeThread');
                                                    const chatMessages = document.getElementById('chatMessages');

                                                    if (!welcomeThread || !chatMessages) {
                                                        console.error('Required elements not found!');
                                                        return;
                                                    }

                                                    const welcomeMessages = [
                                                        {
                                                            delay: 0,
                                                            title: '👋 Welcome to BossGPT!',
                                                            content: "I'm your AI Project Manager, ready to help you organize and manage your projects efficiently."
                                                        },
                                                        {
                                                            delay: 2000,
                                                            title: '🚀 Let\'s Get Started!',
                                                            content: {
                                                                text: "To begin your journey, click the \"Create New Project\" button above. Here's what I can help you with:",
                                                                list: [
                                                                    '✨ Project planning and organization',
                                                                    '📋 Task management and tracking',
                                                                    '👥 Team collaboration',
                                                                    '📊 Progress monitoring'
                                                                ]
                                                            }
                                                        },
                                                        {
                                                            delay: 4000,
                                                            title: '💡 How I Can Help',
                                                            content: {
                                                                text: 'Once you create a project, I can:',
                                                                list: [
                                                                    '🤖 Generate task suggestions based on your project needs',
                                                                    '📅 Help manage deadlines and priorities',
                                                                    '🔍 Provide insights and recommendations',
                                                                    '💬 Answer questions about your project anytime'
                                                                ]
                                                            }
                                                        },
                                                        {
                                                            delay: 6000,
                                                            title: '🎯 Next Steps',
                                                            content: {
                                                                text: 'To get the most out of BossGPT:',
                                                                list: [
                                                                    'Click "Create New Project" and give your project a name',
                                                                    'Describe your project goals and requirements',
                                                                    'I\'ll help you break it down into manageable tasks',
                                                                    'Invite team members to collaborate'
                                                                ],
                                                                isOrdered: true
                                                            }
                                                        },
                                                        {
                                                            delay: 8000,
                                                            title: '🌟 Ready to Begin?',
                                                            content: {
                                                                text: 'Create your first project and let\'s make something amazing together!',
                                                                cta: true
                                                            }
                                                        }
                                                    ];

                                                    async function showMessage(message) {

                                                        // Show loading animation first
                                                        showChatLoading();

                                                        // Wait for loading animation
                                                        await new Promise(resolve => setTimeout(resolve, 1500));

                                                        // Hide loading animation
                                                        hideChatLoading();
                                                        appendWelcomeLogo();
                                                        // Create the message div
                                                        const messageDiv = document.createElement('div');
                                                        messageDiv.className = aiMessageClasses;
                                                        messageDiv.style.opacity = "0";  // Start invisible
                                                        messageDiv.style.transition = "opacity 0.5s ease-in-out"; // Smooth transition

                                                        let content = `
        <div class="ai-avatar">
            <div class="chat-loading-avatar">
            ${iconImage}
            </div>
        </div>
        <div class="message ai text-center mt-3">
            <h5>${message.title}</h5>`;

                                                        if (typeof message.content === 'string') {
                                                            content += `<p>${message.content}</p>`;
                                                        } else {
                                                            content += `<p>${message.content.text}</p>`;
                                                            if (message.content.list) {
                                                                const listType = message.content.isOrdered ? 'ol' : 'ul';
                                                                content += `<${listType}>`;
                                                                message.content.list.forEach(item => {
                                                                    content += `<li>${item}</li>`;
                                                                });
                                                                content += `</${listType}>`;
                                                            }
                                                            if (message.content.cta) {
                                                                content += `
                <div class="cta-message">
                    <button class="btn btn-main-primary" onclick="openNewProjectModal()">
                        <i class="fas fa-plus-circle"></i> Create New Project
                    </button>
                </div>`;
                                                            }
                                                        }

                                                        content += '</div>';
                                                        messageDiv.innerHTML = content;
                                                        welcomeThread?.appendChild(messageDiv);

                                                        // Apply fade-in effect
                                                        setTimeout(() => {
                                                            messageDiv.style.opacity = "1";
                                                        }, 100);

                                                        // Scroll to bottom smoothly
                                                        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: "smooth" });
                                                    }


                                                    async function displayMessages() {
                                                        for (const message of welcomeMessages) {
                                                            await new Promise(resolve => setTimeout(resolve, message.delay));
                                                            await showMessage(message);
                                                        }
                                                    }

                                                    displayMessages().catch(error => console.error('Error displaying messages:', error));
                                                })();
                                            </script>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chat-input">
                                    <?php


$prompts = [
    "🎯 Create task 'Your Task' and assign it to myself",
    "📋 Create tasks for Your Feature",
    "✏️ Move task #number to in_progress",
    "👥 Assign task 'Your Task' to @name",
    "📅 Set deadline for task #number to next Friday",
    "📝 Update description of task 'Your Task'",
    "🔄 Mark task #number as completed",
    "📊 Show project progress",
    "📑 List all tasks in current project",
    "🔍 Show tasks assigned to me"
];

function renderPromptButtons($prompts) {
    foreach ($prompts as $prompt) {
        echo '<button style="border-radius: 20px!important;" class="btn btn-outline-light  prompt-btn" type="button" onclick="handlePromptClick(this)">' . $prompt . '</button>';
    }
}
?>

<!-- Prompt suggestions -->
<div class="prompt-suggestions">
    <div class="nav nav-tabs border-0 flex-nowrap overflow-auto mb-0 px-0" style="scrollbar-width: none; -ms-overflow-style: none;">
        <?php renderPromptButtons($prompts); ?>
    </div>
</div>


                                        <form id="chatForm" class="d-flex">
                                        <textarea class="form-control me-2" id="messageInput"
                                                placeholder="Type your message..." rows="1"></textarea>
                                            <button type="submit" id="aiSendMessageBtn"
                                                class="btn btn-send-primary"><?php echo file_get_contents("assets/icons/send.svg"); ?>
                                            </button>
                                        </form>
                                        <script>
// Auto-resize textarea as user types
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Reset height when form is submitted
    document.getElementById('chatForm').addEventListener('submit', function() {
        setTimeout(() => {
            // messageInput.style.height = 'auto';
        }, 100);
    });
}

function handlePromptClick(button) {
    let promptText = button.innerText;
    let inputField = document.getElementById("messageInput");
    let aiSendMessageBtn=document.getElementById("aiSendMessageBtn");

    // Set input field value
    inputField.value = promptText;

    // Trigger the input event to resize the textarea
    const inputEvent = new Event('input', { bubbles: true });
    inputField.dispatchEvent(inputEvent);

    // Auto-submit the form
    setTimeout(() => {
        // aiSendMessageBtn.click();
        // chatForm.submit();
    }, 200); // Small delay to make it smooth
}
</script>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Project Modal -->
            <div class="modal fade" id="newProjectModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Create New Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>
                        <div class="modal-body">
                            <form id="newProjectForm">
                                <div class="mb-3">
                                    <label for="projectTitle" class="form-label">Project
                                        Title<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="projectTitle"
                                        placeholder="Enter project title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="projectDescription"
                                        class="form-label">Description<?php echo required_field(); ?></label>
                                    <textarea class="form-control" id="projectDescription" rows="8"
                                        placeholder="Define your project in few lines."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="createProjectBtn">Create Project</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Task Modal -->
            <div class="modal fade" id="editTaskModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editTaskForm">
                                <input type="hidden" id="editTaskId">
                                <div class="mb-3">
                                    <label for="editTaskTitle" class="form-label">Task Title</label>
                                    <input type="text" class="form-control" id="editTaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editTaskDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="editTaskDueDate">
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskAssignees" class="form-label">Assigned Users</label>
                                    <select class="form-select select2-multiple" id="editTaskAssignees" multiple required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">You can select multiple users. Click to
                                        select/deselect.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="editTaskPicture" class="form-label">Task Picture</label>
                                    <input type="file" class="form-control" id="editTaskPicture" accept="image/*">
                                </div>
                                <!-- New: Remove Picture Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-danger" id="removeTaskPictureBtn">Remove
                                        Picture</button>
                                </div>
                            </form>

                            <!-- Add this new section for subtasks -->
                            <div class="mt-4">
                                <h6 class="border-bottom pb-2 d-flex justify-content-between align-items-center">
                                    Subtasks
                                    <button type="button" class="btn btn-sm btn-primary" id="addSubtaskInModalBtn">
                                        <i class="bi bi-plus"></i> Add Subtask
                                    </button>
                                </h6>
                                <div id="subtasksList" class="mt-3">
                                </div>
                            </div>

                            <!-- Existing activity log section -->
                            <div class="mt-4">
                                <h6 class="border-bottom pb-2">Task Activity Log</h6>
                                <div class="task-activity-log" style="max-height: 200px; overflow-y: auto;">
                                    <div id="taskActivityLog" class="small">
                                        <!-- Activity logs will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTaskBtn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assign User Modal -->
            <!-- <div class="modal fade" id="assignUserModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign User to Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignUserForm">
                                <div class="mb-3">
                                    <label for="userSelect" class="form-label">Select User</label>
                                    <select class="form-select" id="userSelect" required>
                                        <option value="">Select a user</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="userRole" class="form-label">Role in Project</label>
                                    <input type="text" class="form-control" id="userRole" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="assignUserBtn">Assign User</button>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="assignUserModalLabel">Invite User to Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>

                        <div class="modal-body position-relative">
                            <button class="btn btn-primary position-absolute top-5 add-user-btn-top-right"
                                style="right: 10px;" id="addUserBtn">
                                <i class="bi bi-person-plus"></i> Add New User
                            </button>
                            <div id="userListContainer" class="mt-5">
                                <!-- Dynamically populated users will appear here -->
                            </div>

                            <!-- No Users Message -->
                            <div id="noUsersMessage" class="text-center py-2 d-none">
                                <p class="text-muted">No users assigned yet.</p>
                                <button class="btn btn-primary" id="addUserBtn">
                                    <i class="bi bi-person-plus"></i> Add New User
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- <div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-lg">
                        <div class="modal-header bg-primary text-white border-0 rounded-t-lg">
                            <h5 class="modal-title" id="assignUserModalLabel">Assign User to Project</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close "></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignUserForm">
                                <div class="mb-3">
                                    <label for="userSelect" class="form-label">Select
                                        User<?php echo required_field(); ?></label>
                                    <select class="form-select" id="userSelect" required>
                                        <option value="">Select a user</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="userRole" class="form-label">Role in
                                        Project<?php echo required_field(); ?></label>
                                    <input type="text" placeholder="Enter role (e.g., Developer, Designer, Manager)"
                                        class="form-control" id="userRole" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="assignUserBtn">Assign User</button>
                        </div>
                    </div>
                </div>
            </div> -->



            <!-- New User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addUserForm">
                                <div class="mb-3">
                                    <label for="newUserEmail"
                                        class="form-label">Email<?php echo required_field(); ?></label>
                                    <input type="email" class="form-control text-lowercase" id="newUserEmail" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newUserRole" class="form-label">Role<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="newUserRole"
                                        placeholder="Enter role (e.g., Developer, Designer, Manager)" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="addNewUserBtn"><i
                                    class="bi bi-send"></i>&nbsp;Send Invite</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- New Task Modal -->
            <div class="modal fade" id="newTaskModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Create New Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="newTaskForm">
                                <div class="mb-3">
                                    <label for="newTaskTitle" class="form-label">Task
                                        Title<?php echo required_field(); ?></label>
                                    <input type="text" class="form-control" id="newTaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskDescription"
                                        class="form-label">Description<?php echo required_field(); ?></label>
                                    <textarea class="form-control" id="newTaskDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskDueDate" class="form-label">Due
                                        Date<?php echo required_field(); ?></label>
                                    <input type="date" class="form-control" id="newTaskDueDate">
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskAssignees" class="form-label">Assigned
                                        Users<?php echo required_field(); ?></label>
                                    <select class="form-select select2-multiple" id="newTaskAssignees" multiple required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">You can select multiple users. Click to
                                        select/deselect.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="newTaskPicture" class="form-label">Task
                                        Picture<?php echo required_field(); ?></label>
                                    <input type="file" class="form-control" id="newTaskPicture" accept="image/*">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="createTaskBtn">Create Task</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this after the other modals -->
            <div class="modal fade" id="addSubtaskModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Subtask</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addSubtaskForm">
                                <input type="hidden" id="parentTaskId">
                                <div class="mb-3">
                                    <label for="subtaskTitle" class="form-label">Subtask Title</label>
                                    <input type="text" class="form-control" id="subtaskTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subtaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="subtaskDescription" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="subtaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="subtaskDueDate">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveSubtaskBtn">Add Subtask</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log Modal -->
            <div class="modal fade" id="activityLogModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered ">
                    <div class="modal-content">
                        <div class="modal-header text-white border-0 rounded-t-lg">
                            <h5 class="modal-title">Project Activity Log</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activityLogTable">
                                        <!-- Activity logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this new modal for enlarged images after your other modals -->
            <div class="modal fade" id="imageModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Task Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="enlargedImage" src="" alt="Enlarged task image"
                                style="max-width: 100%; max-height: 80vh;">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- for logoIcon -->
<script>
    const iconImage = `<?php echo getIconImage(0, 0, "1.8rem"); ?>`
    const welcomeLogoImage= `<?php echo getIconImage(0,0,'3.7rem'); ?>`; 
</script>


    <script>
        var userId = null;
        function getLastSelectedProject() {
            if (userId) { // Check if userId is available
                return localStorage.getItem(`lastSelectedProject_${userId}`);
            }
            return null; // No user logged in or session expired
        }

        document.addEventListener('DOMContentLoaded', function () {

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

                            if (data.invited_by == null) {
                                window.location.href = data.payment_link;
                            }
                        }
                    })
                    .catch(error => console.error('Error checking pro status:', error));

                // check if user is pro member
                // if no then redirect to stripe page simply
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('pro-member') && urlParams.get('pro-member') === 'true') {
                    // Call API to update pro status
                    // alert('pro-member');
                    // return;
                    fetch('?api=update_pro_status')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Pro status updated successfully');
                                Toast('success', 'Upgrade Complete', 'Your account has been upgraded to Pro!');
                                // Remove the parameter from URL without page reload
                                const newUrl = window.location.pathname + window.location.search.replace(/[?&]pro-member=true/, '');
                                window.history.replaceState({}, document.title, newUrl);
                            } else {
                                console.error('Failed to update pro status:', data.message);
                            }
                        })
                        .catch(error => console.error('Error updating pro status:', error));
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
                    showLoading();
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
                        .finally(hideLoading);
                }, 500); // 500ms debounce time

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
                    showLoading();
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
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
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
                    showLoading();
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
                        .finally(hideLoading);
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
                            html += `<div class="subtasks mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : ''}">
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
                            html += `<div class="mt-2 ${task.status !== 'in_progress' ? 'hover-show-subtasks' : ''}">
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

                    // Updated innerHTML now includes the due date in the task-meta section
                    const plantBallHtml = `<div class="plant-ball-container">
    <img src="assets/images/garden/plant-ball.png" alt="Plant Ball" class="plant-ball" style="
    height: 39px;
    box-shadow: 0 0 15px 5px rgba(255, 255, 150, 0.8);
    border-radius: 50%;
">
    <img src="assets/images/garden/lush.png" alt="Plant" class="inner-plant" style="
    height: 30px;
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
                    showLoading();
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
                        .finally(hideLoading);
                }

                // Handle chat form submission
                chatForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!currentProject) {
                        // alert('Please select a project first');
                        // return;
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
                            project_id: currentProject
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
                                selectProject(data.project_id, title); 
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
                                    // addUserBtnTopRight.classList.add("d-none");
                                } else {
                                    noUsersMessage.classList.add("d-none");
                                    userListContainer.classList.remove("d-none");

                                    data.users.forEach((user) => {
                                        const userCard = document.createElement("div");
                                        userCard.className = "d-flex justify-content-between align-items-center p-2 mb-2 border rounded dark-primaryborder ";
                                        let actionButtons = "<div>";

                                        //                                     actionButtons += `
                                        //     <button class="btn btn-sm btn-outline-primary editUser" data-id="${user.id}">
                                        //         <i class="bi bi-pencil"></i>
                                        //     </button>

                                        // `;
                                        if (user.role != "Creator") {
                                            actionButtons += `
           
            <button class="btn btn-sm btn-outline-danger deleteUser" data-id="${user.id}">
                <?php echo getTrashIcon(); ?>
            </button>
        `;
                                        }
                                        actionButtons += "</div>";
                                        userCard.innerHTML = `
                    <div>
                        <strong>${user.username}</strong>
                        <span class="text-muted">(${user.role})</span>
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

                    // if (editBtn) {
                    //     const userId = editBtn.getAttribute("data-id");
                    //     const username = editBtn.getAttribute("data-username");
                    //     const email = editBtn.getAttribute("data-email");
                    //     const role = editBtn.getAttribute("data-role");
                    //     // Populate the Add/Edit Modal with User Data
                    //     document.getElementById("newUserName").value = username;
                    //     document.getElementById("newUserEmail").value = email;
                    //     document.getElementById("newUserRole").value = role;
                    //     // Show the modal
                    //     addUserModal.show();
                    // }
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
                    const div = document.createElement('div');
                    div.className = sender === 'user' ? 'user-message d-flex align-items-start justify-content-end' : 'ai-message d-flex align-items-start';
                    const iconImage = `<?php echo getIconImage(0,0,'1.93rem'); ?>`;

                    if (sender === 'user') {
                        // Get username from session
                        const username = '<?php echo $_SESSION["username"]; ?>';
                        const initials = username.split(' ').map(word => word[0].toUpperCase()).join('').slice(0, 2);

                        div.innerHTML = `
                            <div class="message user me-2">${message}</div>
                            <div class="user-avatar">
                                <div class="chat-loading-avatar d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #44125c 0%, #713e7f 100%);">
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
                const fontSizeRange = document.getElementById('fontSizeRange');
                const fontSizeValue = document.getElementById('fontSizeValue');
                const mainContent = document.body;

                // Load saved font size or use default
                const savedFontSize = localStorage.getItem('preferredFontSize') || '16';
                fontSizeRange.value = savedFontSize;
                fontSizeValue.textContent = `${savedFontSize}px`;
                mainContent.style.fontSize = `${savedFontSize}px`;

                // Update font size when slider changes
                fontSizeRange.addEventListener('input', function () {
                    const newSize = this.value;
                    fontSizeValue.textContent = `${newSize}px`;
                    mainContent.style.fontSize = `${newSize}px`;
                    localStorage.setItem('preferredFontSize', newSize);
                });

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
                    function sendCreateTask(pictureData) {
                        fetch('?api=create_task', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                project_id: currentProject,
                                title: title,
                                description: description,
                                due_date: dueDate,
                                assignees: assignees,
                                picture: pictureData
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('newTaskTitle').value = '';
                                    document.getElementById('newTaskDescription').value = '';
                                    document.getElementById('newTaskDueDate').value = '';
                                    $('#newTaskAssignees').val(null).trigger('change');
                                    document.getElementById('newTaskPicture').value = '';
                                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                                    loadTasks(currentProject);
                                } else {
                                    throw new Error(data.message || 'Failed to create task');
                                }
                            })
                            .catch(error => {
                                console.error('Error creating task:', error);
                                alert('Failed to create task. Please try again.');
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
                                    <td>${escapeHtml(log.username)}</td>
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
                            project_id: currentProject
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
                    const suggestionsContainer = document.createElement('div');
                    suggestionsContainer.className = 'suggestions-container mt-3';
                    suggestionsContainer.innerHTML = '<h6 class="mb-3">Suggested Tasks & Features</h6>';
                    suggestions.forEach(suggestion => {
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.className = 'suggestion-item border p-2 mb-2';
                        suggestionDiv.innerHTML = `
                            <strong>${escapeHtml(suggestion.title)}</strong><br>
                            <span class="my-2">${escapeHtml(suggestion.description)}</span><br>
                            ${suggestion.due_date ? `<?php echo getCalendarIcon(); ?><em class="text-muted"> Due: ${escapeHtml(suggestion.due_date)}</em>` : ''}<br>
                            <button class="btn btn-sm btn-primary mt-1">Add Task</button>
                         `;
                        suggestionDiv.querySelector('button').addEventListener('click', () => {
                            addSuggestedTask(suggestion);
                        });
                        suggestionsContainer.appendChild(suggestionDiv);
                    });
                    chatMessages.appendChild(suggestionsContainer);
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
                            project_id: currentProject
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
                            appendMessage("Subtask dates have been aggressively updated to ensure tight deadlines.", 'ai');
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

                    body.dark-mode #subtasksList .subtask-item {
                        background-color: #2c2d2e;
                    }

                    body.dark-mode #subtasksList .subtask-item:hover {
                        background-color: #3a3b3c;
                    }

                    #subtasksList .delete-subtask-btn {
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    }

                    #subtasksList .subtask-item:hover .delete-subtask-btn {
                        opacity: 1;
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

                    body.dark-mode .subtask-due-date {
                        background-color: #3a3b3c;
                        border-color: #2f3031;
                        color: #e4e6eb;
                    }

                    body.dark-mode .subtask-due-date:hover,
                    body.dark-mode .subtask-due-date:focus {
                        background-color: #4a4b4c;
                        border-color: #2374e1;
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

            // Font size management - keep this outside the dashboard check since it applies to all pages
            const fontSizeRange = document.getElementById('fontSizeRange');
            const fontSizeValue = document.getElementById('fontSizeValue');

            if (fontSizeRange && fontSizeValue) {
                const mainContent = document.body;
                const savedFontSize = localStorage.getItem('preferredFontSize') || '16';
                fontSizeRange.value = savedFontSize;
                fontSizeValue.textContent = `${savedFontSize}px`;
                mainContent.style.fontSize = `${savedFontSize}px`;

                fontSizeRange.addEventListener('input', function () {
                    const newSize = this.value;
                    fontSizeValue.textContent = `${newSize}px`;
                    mainContent.style.fontSize = `${newSize}px`;
                    localStorage.setItem('preferredFontSize', newSize);
                });
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

        initializeChatLoading();
        let displayedReminders = new Set();
// get Reminders
function getReminders() {
    const fcmToken = "<?php echo isset($_SESSION['fcm_token']) ? $_SESSION['fcm_token'] : ''; ?>";
    console.log("FCM Token for reminders:", fcmToken);
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
        console.log("Reminders data:", data);
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
                    
                    popupContainer.insertAdjacentHTML('beforeend', popupHtml);
                    }
                });

                // Initialize popup functionality
                const popups = document.querySelectorAll('.popup-alert');
                // updatePopupVisibility();
            }
        } else {
            console.log("No reminders found or empty response");
        }
    })
    .catch(error => {
        console.error('Failed to fetch reminders:', error);
    });
}

// Add the event listener when DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    interval = setInterval(getReminders, 10000); //every 10 seconds it will check for reminders
});

function delete_fcm_reminders(reminder_id) {
    const fcmToken = "<?php echo $_SESSION['fcm_token']; ?>";
    console.log("FCM Token for reminders:", fcmToken);
    if(reminder_id){
    fetch('?api=delete_fcm_reminders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fcm_token: fcmToken,reminder_id:reminder_id })
    }); }
}
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
    const popups = document.querySelectorAll(".popup-alert");

    function updatePopupVisibility() {
        popups.forEach((popup, index) => {
            // if (index < 3) {
            //     popup.classList.remove("hidden");
            // } else {
            //     popup.classList.add("hidden");
            // }
        });
    }


    updatePopupVisibility(); // Ensure only 3 popups are shown initially
});
function closePopup(button) {
    let popup = button.parentElement;
    popup.remove(); // Remove the closed popup

    // Show next hidden popup if available
    setTimeout(() => {
        delete_fcm_reminders(popup.dataset.reminderId);
        let hiddenPopups = document.querySelectorAll(".popup-alert.hidden");
        if (hiddenPopups.length > 0) {
            hiddenPopups[0].classList.remove("hidden");
        }
    }, 300); // Small delay to create a smooth transition
}
</script>
</body>

</html>
<!-- # Training parameters -->