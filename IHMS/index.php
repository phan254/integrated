<?php
// Start output buffering at the very beginning
ob_start();

// Start session
session_start();

// Include database connection
require_once 'config/db_config.php';

// Define base URL - adjusted for localhost
define('BASE_URL', 'http://localhost/IHMS/');
require_once 'validation_functions.php';
require_once 'error_handler.php';
require_once 'form_generator.php';
// Set page title default
$page_title = "Integrated Hostel Management System";

// Handle page routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Include header
include 'includes/header.php';

// Include navigation
include 'includes/nav.php';

// Load appropriate page content
switch($page) {
    case 'home':
        include 'views/home.php';
        break;
    case 'login':
        include 'views/login.php';
        break;
    case 'register':
        include 'views/register.php';
        break;
    case 'hostels':
        include 'views/hostels.php';
        break;
    case 'hostel_details':
        include 'views/hostel_details.php';
        break;
    case 'dashboard':
        // Check if user is logged in
        if(isset($_SESSION['user_id'])) {
            // Load appropriate dashboard based on user type
            switch($_SESSION['user_type']) {
                case 'student':
                    include 'views/student_dashboard.php';
                    break;
                case 'hostel_manager':
                    include 'views/manager_dashboard.php';
                    break;
                case 'university_admin':
                    include 'views/admin_dashboard.php';
                    break;
                default:
                    include 'views/home.php';
            }
        } else {
            // Redirect to login if not logged in
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'add_hostel':
        // Check if user is logged in as hostel manager
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'hostel_manager') {
            include 'views/add_hostel.php';
        } else {
            // Redirect to login if not logged in as hostel manager
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'manage_hostel':
        // Check if user is logged in as hostel manager
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'hostel_manager') {
            include 'views/manage_hostel.php';
        } else {
            // Redirect to login if not logged in as hostel manager
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'payment':
        // Check if user is logged in as student
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student') {
            include 'views/payment.php';
        } else {
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'payment_status':
        // Check if user is logged in as student
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student') {
            include 'views/payment_status.php';
        } else {
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'booking_detail':
        // Check if user is logged in as student
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student') {
            include 'views/booking_detail.php';
        } else {
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'manage_users':
        // Check if user is logged in as university admin
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'university_admin') {
            include 'views/manage_users.php';
        } else {
            // Redirect to login if not logged in as university admin
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'manage_hostels':
        // Check if user is logged in as university admin
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'university_admin') {
            include 'views/manage_hostels.php';
        } else {
            // Redirect to login if not logged in as university admin
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'reports':
        // Check if user is logged in as university admin
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'university_admin') {
            include 'views/reports.php';
        } else {
            // Redirect to login if not logged in as university admin
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'settings':
        // Check if user is logged in as university admin
        if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'university_admin') {
            include 'views/settings.php';
        } else {
            // Redirect to login if not logged in as university admin
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'profile':
        // Check if user is logged in
        if(isset($_SESSION['user_id'])) {
            include 'views/profile.php';
        } else {
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'messages':
        // Check if user is logged in
        if(isset($_SESSION['user_id'])) {
            include 'views/messages.php';
        } else {
            header("Location: " . BASE_URL . "?page=login");
            exit;
        }
        break;
    case 'logout':
        // Set a logout flag first
        $_SESSION['logout'] = true;
        // Then destroy session and redirect to home
        session_destroy();
        header("Location: " . BASE_URL . "?page=login&logout=1");
        exit;
        break;
    default:
        // 404 page
        include 'views/404.php';
}

// Include footer
include 'includes/footer.php';

// End output buffering and send the buffered content to the browser
ob_end_flush();
?>