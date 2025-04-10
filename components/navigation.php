<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid sides-padding" style="overflow: visible;">
        <a class="navbar-brand" href="?page=dashboard">
            <?php echo getLogoImage($bottomMargin = '0.4rem', $topMargin = "0.4rem", $width = "11rem", $height = "auto", $positionClass = " ", $positionStyle = " ", $src = "https://res.cloudinary.com/da6qujoed/image/upload/v1742651528/bossgpt-transparent_n4axv7.png"); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"><?php echo getMenuIcon(); ?></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- <li class="nav-item">
                            <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>"
                                href="?page=dashboard">Dashboard</a>
                        </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="garden.php">
                        <i class="bi bi-tree"></i> My Garden
                    </a>
                </li>
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
                        <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"
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
                <!-- <button id="toggleDarkModeBtn" class="btn btn-icon-only mx-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Switch Theme">
                    <svg width="24" id="light-icon" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 18.5C15.5899 18.5 18.5 15.5899 18.5 12C18.5 8.41015 15.5899 5.5 12 5.5C8.41015 5.5 5.5 8.41015 5.5 12C5.5 15.5899 8.41015 18.5 12 18.5Z"
                            stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path
                            d="M19.14 19.14L19.01 19.01M19.01 4.99L19.14 4.86L19.01 4.99ZM4.86 19.14L4.99 19.01L4.86 19.14ZM12 2.08V2V2.08ZM12 22V21.92V22ZM2.08 12H2H2.08ZM22 12H21.92H22ZM4.99 4.99L4.86 4.86L4.99 4.99Z"
                            stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>

                    <svg id="dark-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white"
                        viewBox="0 0 28 30">
                        <path d="
            M 23, 5
            A 12 12 0 1 0 23, 25
            A 12 12 0 0 1 23, 5
        "></path>
                    </svg>
                </button> -->
                <button class="btn btn-icon-only mx-2" id="btn-theme" onclick="toggleThemeClick()" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Switch Theme">
            <?php echo getThemeIcon(); ?>
            <div class="theme-icon-container">
                <h6>Theme</h6>
                <div class="theme-icon-content-container">
                    <div class="theme-icon-content-item">
                    <div class="theme-icon-color" onclick="changeTheme('purple-mode')"></div>
                    <span>Purple</span>
                    </div>
                
                   <div class="theme-icon-content-item">
                   <div class="theme-icon-color" onclick="changeTheme('black-mode')"></div>
                   <span>Black</span>
                   </div>
                   <div class="theme-icon-content-item">
                   <div class="theme-icon-color" onclick="changeTheme('brown-mode')"></div>
                   <span>Brown</span>
                   </div>
                   <div class="theme-icon-content-item">
                   <div class="theme-icon-color" onclick="changeTheme('system-mode')"></div>
                   <span>Default</span>
                   </div>
                </div>
            </div>
        </button>
                <!-- Logout Form -->
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-light btn-logout" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Logout"><?php echo getLogoutIcon(); ?></button>
                </form>
            </div>
        </div>
    </div>
</nav>