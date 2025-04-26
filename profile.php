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


// API Endpoint Handler
require_once './api_endPoints.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
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
                    console.warn(`Canvas with ID '${canvasId}' not found.`);
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

</head>
<!-- Reuseable Stuff -->


<body
    style="background-color:<?php echo isset($_GET['page']) && ($_GET['page'] == 'login' || $_GET['page'] == 'register') ? '#000' : ''; ?> "
    class="system-mode">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5JFVBHSJ" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php

    require_once 'components/navigation.php';
    ?>

    <div class="container-fluid mt-4">
        <?php include_dashboard(); ?>
        <style>
            .tabs-pannel .nav-tabs .nav-link.active {

                color: #fff !important;
            }

            .tabs-pannel .nav-tabs .nav-link {

                color: rgba(255, 255, 255, 0.5) !important;
            }
            #avatarImage {
    display: none;
  }
  #avatarPreview {
    cursor: pointer;
  }
        </style>
        <?php



        function include_dashboard()
        {
            require_once 'components/misc.php';
            ?>



            <div class="container-fluid pb-3">
                <!-- Main Content Area -->
                <div class="row sides-padding " style="width: 99.4%!important;">
                    <button class="btn btn-link p-0 text-white show open-icon-btn " data-bs-dismiss="modal"
                        aria-label="Close"
                        onclick="openChatPannel()"><?php echo getIconImage(0, 0, "2.5rem", "auto", "https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png", 0); ?></button>
                    <!-- Tasks Panel (Board) - now spans 9 columns -->
                    <div class="col-12 col-md-12 tasks-panel">
                        <div class="card h-100 projects_card tabs-pannel">


                            <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                                <ul class="py-0 px-0 my-0 nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active bg-transparent border-0" id="profile-tab"
                                            data-bs-toggle="tab" data-bs-target="#profile" type="button"
                                            role="tab">Profile</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 px-2" id="activity-tab"
                                            data-bs-toggle="tab" data-bs-target="#activity" type="button"
                                            role="tab">Activity</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 " id="cards-tab"
                                            data-bs-toggle="tab" data-bs-target="#cards" type="button"
                                            role="tab">Cards</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 px-2" id="settings-tab"
                                            data-bs-toggle="tab" data-bs-target="#settings" type="button"
                                            role="tab">Settings</button>
                                    </li>
                                </ul>
                            </div>


                            <div class="card-body p-0">
                                <div class="content-container" style="height: 80vh!important; background: rgba(24, 25, 28, 0.5);
backdrop-filter: blur(14.7px);
border-radius: 16px;">

                                    <div class="card-body p-0">
                                        <div class="tab-content p-3" id="profileTabsContent"
                                            style="height: 80vh!important; background: rgba(24, 25, 28, 0.5); backdrop-filter: blur(14.7px); border-radius: 16px;">
                                            <div class="tab-pane fade show active" id="profile" role="tabpanel"
                                                aria-labelledby="profile-tab">
                                                <form id="profileForm" class="text-white" enctype="multipart/form-data"
                                                    method="POST">
                                                    <div class="mt-2">
                                                    <div class="text-center">
  <img id="avatarPreview" src="uploads/avatars/default.jpg" alt="Avatar" class="rounded-circle mb-2" width="100" height="100">
  <input type="file" id="avatarImage" accept="image/*">
</div>
                                                    </div>
                                                    <div class="mb-3 text-center">
          
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileUserName"
                                                            class="form-label">Username<?php ?></label>
                                                        <input type="text" class="form-control" id="profileUserName"
                                                            placeholder="Zeeshanali96">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileName" class="form-label">Name<?php ?></label>
                                                        <input type="text" class="form-control" id="profileName"
                                                            placeholder="Enter full name">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileEmail" class="form-label">Email<?php ?></label>
                                                        <input type="email" class="form-control text-lowercase"
                                                            id="profileEmail" placeholder="user@example.com">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileBio" class="form-label">Bio</label>
                                                        <textarea class="form-control" id="profileBio" rows="3"
                                                            placeholder="Tell us about yourself..."></textarea>
                                                    </div>
                                                </form>
                                                <script>
                                                     // Clicking avatar triggers input
  document.getElementById('avatarPreview').addEventListener('click', function () {
    document.getElementById('avatarImage').click();
  });

  document.getElementById('avatarImage').addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    const res = await fetch('api_endPoints.php?action=upload_profile_image', {
      method: 'POST',
      body: formData
    });

    try {
      const data = await res.json();
      if (data.success) {
        document.getElementById('avatarPreview').src = data.image_url;
        iziToast.success({ title: 'Success', message: 'Profile image updated!' });
      } else {
        iziToast.error({ title: 'Error', message: data.message || 'Upload failed' });
      }
    } catch (err) {
      iziToast.error({ title: 'Error', message: 'Invalid response from server' });
    }
  });
                                                </script>
                                            </div>
                                            <div class="tab-pane fade" id="activity" role="tabpanel"
                                                aria-labelledby="activity-tab">
                                                <p>Activity content coming soon... Activity</p>
                                            </div>
                                            <div class="tab-pane fade" id="cards" role="tabpanel"
                                                aria-labelledby="cards-tab">
                                                <p>Cards content coming soon... Cards</p>
                                            </div>
                                            <div class="tab-pane fade" id="settings" role="tabpanel"
                                                aria-labelledby="settings-tab">
                                                <p>Settings content coming soon... Settings </p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- for logoIcon -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>