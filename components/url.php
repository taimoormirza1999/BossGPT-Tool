<?php
require_once 'config/constants.php';
switch ($page) {
    case 'login':
        include_login_page();
        break;
    case 'register':
        include_register_page();
        break;
    case 'dashboard':
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
    // case 'garden_stats':
    //     header('Location: ?page=dashboard');
    //     exit;
    case 'profile':
        require_once 'profile.php';
        include_profile($images);
        break;
    default:
        echo "<h1>404 - Page Not Found</h1>";
}
