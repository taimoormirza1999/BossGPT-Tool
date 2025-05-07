<?php
require_once 'config/constants.php';
switch ($page) {
    case 'login':
        if(isset($_SESSION['user_id'])){
            header('Location: ?page=dashboard');
            exit;
        }
        include_login_page();
        break;
    case 'register':
        if(isset($_SESSION['user_id'])){
            header('Location: ?page=dashboard');
            exit;
        }
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
