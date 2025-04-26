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
    <!-- Flatpickr Core CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
        <?php include_dashboard($images); ?>
        <style>
.calendar-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}
#dateRangeButton {
    background-color: transparent;
    font-weight: 500;
    font-size: 1rem;
    padding: 0.3rem 0.5rem;
}
</style>
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
                border: 2px solid white;
                width: 6.75rem;
                height: 6.75rem;
                object-fit: cover;
                object-position: center;
            }

            div.tab-pane {
                background: rgba(0, 0, 0, 0.8);
                border: 1px solid rgba(234, 234, 234, 0.3);
                backdrop-filter: blur(6.35px);
                border-radius: 16px;
                margin: auto;
                padding: 2.5rem 1.4rem;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                overflow-y: hidden;
            }

            div#profile {
                width: 50%;
                max-width: 576px;
            }

            div#settings {
                width: 50%;
                max-width: 576px;
            }

            div#activity,
            div#cards {
                width: 95%;
                height: 93%;
                /* max-width: 576px; */
            }

            div#profile .form-control {
                background-color: transparent;
                border: 1px solid rgba(156, 156, 156, 1);
            }

            div#profile input.form-control {
                height: 48px;
                color: white !important;
            }

            div#profile input.form-control::placeholder,
            div#profile textarea.form-control::placeholder {
                color: rgba(255, 255, 255, 0.8) !important;
            }
        </style>
        <?php

        function renderProfileTab()
        {
            require_once 'components/profile_tab.php';
        }
        function renderActivityTab()
        {
            require_once 'components/activity_tab.php';
        }
        function renderCardsTab()
        {
            require_once 'components/cards_tab.php';
        }
        function renderSettingsTab()
        {
            require_once 'components/cards_tab.php';
        }

        function include_dashboard($images)
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
                                        <button class="nav-link active bg-transparent border-0 font-secondaryLight"
                                            id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button"
                                            role="tab">Profile</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 px-2 font-secondaryLight"
                                            id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button"
                                            role="tab">Activity</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 font-secondaryLight" id="cards-tab"
                                            data-bs-toggle="tab" data-bs-target="#cards" type="button"
                                            role="tab">Cards</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-transparent border-0 px-2 font-secondaryLight"
                                            id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button"
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
                                                <h2 class="text-center text-white font-secondaryBold">Profile</h2>
                                                <div class="mt-3">
                                                    <div class="text-center">
                                                        <img id="avatarPreview"
                                                            src="<?= $_SESSION['avatar_image'] ?? $images['default-user-image'] ?>"
                                                            alt="Avatar" class="rounded-circle mb-2" width="100"
                                                            height="100">
                                                        <input type="file" id="avatarImage" accept="image/*">
                                                    </div>
                                                </div>
                                                <form id="profileForm" class="text-white" enctype="multipart/form-data"
                                                    method="POST">

                                                    <div class="mb-3">
                                                        <label for="profileUserName"
                                                            class="form-label">Username<?php ?></label>
                                                        <input type="text" class="form-control" id="profileUserName"
                                                            placeholder="Zeeshanali96"
                                                            value="<?php echo $_SESSION['username'] ?? ''; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileName" class="form-label">Name<?php ?></label>
                                                        <input type="text" class="form-control" id="profileName"
                                                            placeholder="Enter full name"
                                                            value="<?php echo $_SESSION['name'] ?? ''; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileEmail" class="form-label">Email<?php ?></label>
                                                        <input type="email" class="form-control text-lowercase text-white"
                                                            id="profileEmail" placeholder="user@example.com"
                                                            value="<?php echo $_SESSION['email'] ?? ''; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="profileBio" class="form-label">Bio</label>
                                                        <textarea class="form-control" id="profileBio" rows="5"
                                                            placeholder="Tell us about yourself..."
                                                            value="<?php echo $_SESSION['bio'] ?? ''; ?>"></textarea>
                                                    </div>
                                                </form>
                                                <script>
                                                    // Clicking avatar triggers file input
                                                    document.getElementById('avatarPreview').addEventListener('click', function () {
                                                        document.getElementById('avatarImage').click();
                                                    });

                                                    document.getElementById('avatarImage').addEventListener('change', async function () {
                                                        const file = this.files[0];
                                                        if (!file) return;

                                                        const formData = new FormData();
                                                        formData.append('avatar', file);

                                                        try {
                                                            const res = await fetch('?api=upload_profile_image', {
                                                                method: 'POST',
                                                                body: formData
                                                            });

                                                            const data = await res.json();

                                                            if (res.ok && data.success) {
                                                                // Update avatar preview immediately
                                                                document.getElementById('avatarPreview').src = data.image_url;

                                                                iziToast.success({
                                                                    title: 'Success',
                                                                    message: 'Profile image updated!'
                                                                });
                                                            } else {
                                                                iziToast.error({
                                                                    title: 'Error',
                                                                    message: data.message || 'Upload failed'
                                                                });
                                                            }
                                                        } catch (err) {
                                                            iziToast.error({
                                                                title: 'Error',
                                                                message: 'Network error or server is offline.'
                                                            });
                                                            console.log(err);
                                                        }
                                                    });

                                                </script>
                                            </div>
                                            <div class="tab-pane fade" id="activity" role="tabpanel"
                                                aria-labelledby="activity-tab">
                                                <!-- Top Bar with Project Dropdown + Date Filter -->
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-4 card-header p-0">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            id="projectDropdownButton">
                                                            Select Project
                                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M4 6L8 10L12 6" stroke="currentColor"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                        <ul class="dropdown-menu" id="projectDropdown">
                                                            <!-- Dynamically loaded items will be appended here -->
                                                        </ul>
                                                    </div>

                                                    <!-- Date Range Filter -->
                                                    <div class="d-flex align-items-center gap-2">
    <!-- Calendar Icon -->
    <div class="calendar-icon">
        <?php echo getCalendarIcon(24, 24); ?>
    </div>

    <!-- Date Range Display -->
    <button id="dateRangeButton" class="btn btn-dark text-white d-flex align-items-center gap-2 border-0">
        <span id="selectedDateRange">Select Date Range</span>
    </button>
</div>
                                                </div>

                                                <div id="activityLogList" class="list-group w-1/2 mx-auto"
                                                    style="max-height: 95%; overflow-y: auto; width: 55%;">
                                                    <!-- Repeat for more activities dynamically -->

                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="cards" role="tabpanel"
                                                aria-labelledby="cards-tab">
                                                <!-- Top Bar with Project Dropdown + Date Filter -->
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-4 card-header p-0">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            id="projectDropdownButton3">
                                                            Select Project
                                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M4 6L8 10L12 6" stroke="currentColor"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                        <ul class="dropdown-menu" id="projectDropdown3">
                                                            <!-- Dynamically loaded items will be appended here -->
                                                        </ul>
                                                    </div>

                                                    <!-- Date Range Filter -->
                                                    <div class="d-flex align-items-center gap-2">
    <!-- Calendar Icon -->
    <div class="calendar-icon">
        <?php echo getCalendarIcon(24, 24); ?>
    </div>

    <!-- Date Range Display -->
    <button id="dateRangeButton" class="btn btn-dark text-white d-flex align-items-center gap-2 border-0">
        <span id="selectedDateRange">Select Date Range</span>
    </button>
</div>

                                                </div>
                                                <table class="table text-white table-hover align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Assign to</th>
                                                            <th>Due Date</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tasksTableBody">
                                                        <!-- Tasks will be inserted dynamically here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="tab-pane fade" id="settings" role="tabpanel"
                                                aria-labelledby="settings-tab">
                                                <div class="text-center mb-4">
                                                    <h2 class="text-white font-secondaryBold">Settings</h2>
                                                </div>

                                                <!-- Password Section -->
                                                <div class="mb-4">
                                                    <label for="password" class="form-label text-white">Password</label>
                                                    <div class="input-group bg-dark rounded-2 p-2">
                                                        <input type="password" class="form-control text-white" id="password"
                                                            placeholder="Password">
                                                        <button class="btn btn-outline-light" type="button"
                                                            id="togglePassword">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Themes Section -->
                                                <div class="mb-5">
                                                    <label class="form-label text-white">Themes</label>
                                                    <div class="d-flex justify-content-center gap-4 flex-wrap">
                                                        <!-- Theme Items -->
                                                        <div class="text-center">
                                                            <div class="rounded-circle"
                                                                style="width: 50px; height: 50px; background: purple;">
                                                            </div>
                                                            <small class="text-white d-block mt-2">Purple</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="rounded-circle"
                                                                style="width: 50px; height: 50px; background: #4B0082;">
                                                            </div>
                                                            <small class="text-white d-block mt-2">Dark Purple</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="rounded-circle"
                                                                style="width: 50px; height: 50px; background: brown;"></div>
                                                            <small class="text-white d-block mt-2">Brown</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="rounded-circle"
                                                                style="width: 50px; height: 50px; background: gray;"></div>
                                                            <small class="text-white d-block mt-2">Dark</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="rounded-circle"
                                                                style="width: 50px; height: 50px; background: white; border:1px solid #ccc;">
                                                            </div>
                                                            <small class="text-white d-block mt-2">Light</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Profile Info Section -->
                                                <div class="text-center mb-4">
                                                    <h5 class="text-white">Update Profile Info</h5>
                                                </div>
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
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const dateRangeButton = document.getElementById('dateRangeButton');
    const selectedDateRange = document.getElementById('selectedDateRange');

    const today = new Date();
    const fiveDaysAgo = new Date();
    fiveDaysAgo.setDate(today.getDate() - 5);

    const picker = flatpickr(document.createElement('input'), {
        mode: 'range',
        dateFormat: 'd M Y',
        defaultDate: [fiveDaysAgo, today],
        theme: 'dark',
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                const startFormatted = formatDate(selectedDates[0]);
                const endFormatted = formatDate(selectedDates[1]);

                selectedDateRange.textContent = `${startFormatted} - ${endFormatted}`;

                // Call loadActivityLog2 with selected dates in backend format (Y-m-d)
                const startForBackend = formatDateForBackend(selectedDates[0]);
                const endForBackend = formatDateForBackend(selectedDates[1]);
                loadActivityLog2(startForBackend, endForBackend);
            }
        }
    });

    dateRangeButton.addEventListener('click', function() {
        picker.open();
    });

    // Helper functions
    function formatDate(date) {
        const options = { day: '2-digit', month: 'short' };
        return date.toLocaleDateString('en-GB', options);
    }

    function formatDateForBackend(date) {
        return date.toISOString().split('T')[0]; // YYYY-MM-DD
    }

    // Auto-load logs for default 5 days range
    loadActivityLog2(formatDateForBackend(fiveDaysAgo), formatDateForBackend(today));
});

</script>

    <?php require_once 'components/externalScripts.php'; ?>
    <script src="./assets/js/custom.js"></script>
    <?php require_once 'components/specific_pages.php'; ?>
    <?php require_once 'components/global.php'; ?>
    <!-- for logoIcon -->
</body>

</html>