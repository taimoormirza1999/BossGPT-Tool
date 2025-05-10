<body>
    <div id="projectDropdownWrapper" style="display:none;">
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle projectDropdownButton text-decorat " type="button"
                data-bs-toggle="dropdown" aria-expanded="false" id="projectDropdownButton">
                Game 101
                <svg width="16" height="16" ...></svg>
            </button>
            <ul class="dropdown-menu" id="projectDropdown">
                <!-- ... your project list ... -->
            </ul>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid sides-padding" style="overflow: visible; padding:0!important;">
            <a class="navbar-brand" href="<?php echo $_ENV['BASE_URL'] ?>?page=dashboard">
                <?php echo getLogoImage($bottomMargin = '0.4rem', $topMargin = "0.4rem", $width = "11rem", $height = "auto", $positionClass = " ", $positionStyle = " ", $src = "https://res.cloudinary.com/da6qujoed/image/upload/v1742651528/bossgpt-transparent_n4axv7.png"); ?>
            </a>
            <div id="navbarProjectDropdownPlaceholder"></div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon bg-transparent"><?php echo getMenuIcon(); ?></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $_ENV['BASE_URL'] ?>/garden.php">
                            <i class="bi bi-tree"></i> My Garden
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center nav-btn-container">
                    <button type="button" onclick="openModal('newProjectModal')" class="btn btn-main-primary"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Create New Project"
                        data-bs-target="#newProjectModal" style="background: rgba(255, 255, 255, 0.1)!important;
                    border: 1.3px solid rgba(255, 255, 255, 0.35)!important;
                    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1)!important;">
                        <?php echo getAddSquareIcon(); ?> Create&nbsp;Project
                    </button>
                    <button onclick="openLink('<?php echo $_ENV['BASE_URL'] ?>/garden.php',false)"
                        class="btn btn-outline-light btn-logout" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="My Garden"><?php echo getTreeIcon(); ?></button>
                    <button type="button" class="btn btn-outline-light btn-logout" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" onclick="openModal('assignUserModal')"
                        title="Invite User"><?php echo getAddUserIcon(); ?></button>
                    <button type="button" class="btn btn-outline-light btn-logout " data-bs-toggle="tooltip"
                        data-bs-placement="bottom" onclick="openModal('activityLogModal')"
                        title="Project Activity"><?php echo getClockIcon(); ?></button>
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
                    <?php
                    $themes = [
                        'purple-mode' => 'Purple',
                        'black-mode' => 'Black',
                        'brown-mode' => 'Brown',
                        'system-mode' => 'Default'
                    ];
                    ?>

                    <button class="btn btn-icon-only" id="btn-theme" onclick="toggleThemeClick()"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Switch Theme">
                        <?php echo getThemeIcon(); ?>
                        <div class="theme-icon-container d-none">
                            <h6>Theme</h6>
                            <div class="theme-icon-content-container">
                                <?php foreach ($themes as $themeClass => $themeName): ?>
                                    <div class="theme-icon-content-item">
                                        <div class="theme-icon-color" onclick="changeTheme('<?php echo $themeClass; ?>')">
                                        </div>
                                        <span><?php echo htmlspecialchars($themeName); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </button>
                    <img src="<?= $_SESSION['avatar_image'] ?? $images['default-user-image'] ?>"
                        style="  object-fit: cover;object-position: center;cursor: pointer;width: 47px; height: 47px; border-radius: 50%; border: 1.6px solid rgba(248, 249, 250, 0.5);"
                        class=" btn-logout" id="avatar_image_nav" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Profile" onclick="openLink('<?php echo $_ENV['BASE_URL'] ?>?page=profile',false)" />
                    <!-- Logout Form -->
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-light btn-logout" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Logout"><?php echo getLogoutIcon(); ?></button>
                    </form>
                </div>
            </div>
    </nav>

    <style>
        @media (max-width: 768px) {

            .navbar,
            .container-fluid,
            .sides-padding,
            .nav-btn-container {
                overflow: visible !important;
            }

            #navbarProjectDropdownPlaceholder {
                /* position: relative !important;
                margin-left: 15%; */
            }
            #navbarProjectDropdownPlaceholder .dropdown-menu .dropdown-item{
                padding: 0!important;
                color: #fff!important;
            }
            #navbarProjectDropdownPlaceholder .dropdown-menu .dropdown-item.active, #navbarProjectDropdownPlaceholder .dropdown-menu .dropdown-item:active {
                background: rgba(255, 255, 255, 0.2)!important;
                border: 0!important;  
            }
            #navbarProjectDropdownPlaceholder .dropdown-menu {
                z-index: 99999 !important;
                position: fixed !important;
                left: 50% !important;
                right: 0 !important;
                transform: translateX(-50%);
                right: 0 !important;
                top: 56px !important;
                max-width: 12rem !important;
                min-width: unset !important;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(211, 211, 211, 0.5);
                backdrop-filter: blur(4.3px);
                border-radius: 16px;
            }
        }

        @keyframes bell-shake {
            0% {
                transform: rotate(0);
            }

            10% {
                transform: rotate(-15deg);
            }

            20% {
                transform: rotate(10deg);
            }

            30% {
                transform: rotate(-10deg);
            }

            40% {
                transform: rotate(6deg);
            }

            50% {
                transform: rotate(-4deg);
            }

            60% {
                transform: rotate(2deg);
            }

            70% {
                transform: rotate(-1deg);
            }

            80% {
                transform: rotate(1deg);
            }

            90% {
                transform: rotate(0);
            }

            100% {
                transform: rotate(0);
            }
        }

        .bell-animate {
            animation: bell-shake 1s cubic-bezier(.36, .07, .19, .97) both;
            animation-iteration-count: 10;
            /* 10 seconds if 1s per shake */
        }

        @media (max-width: 768px) {
            #projectDropdownWrapper {
                display: none !important;
            }

            #navbarProjectDropdownPlaceholder #projectDropdownWrapper {
                display: block !important;
            }

            #navbarProjectDropdownPlaceholder {
                position: relative !important;
            }

        }
    </style>

    <script>
        function moveProjectDropdownForMobile() {
            const dropdownWrapper = document.getElementById('projectDropdownWrapper');
            const navbarPlaceholder = document.getElementById('navbarProjectDropdownPlaceholder');
            if (!dropdownWrapper || !navbarPlaceholder) return;

            if (window.innerWidth <= 768) {
                // Move to navbar if not already there
                if (!navbarPlaceholder.contains(dropdownWrapper)) {
                    navbarPlaceholder.appendChild(dropdownWrapper);
                }
            } else {
                // Move back to card header if not already there
                const cardHeader = document.querySelector('.card-header .d-flex'); // adjust selector as needed
                if (cardHeader && !cardHeader.contains(dropdownWrapper)) {
                    cardHeader.insertBefore(dropdownWrapper, cardHeader.firstChild);
                }
            }
        }

        // Run on load and on resize
        window.addEventListener('DOMContentLoaded', moveProjectDropdownForMobile);
        window.addEventListener('resize', moveProjectDropdownForMobile);
    </script>
</body>