<?php
// Start session
session_start();

// Include database connection
require_once '../config/db_config.php';

// Define base URL
define('BASE_URL', 'http://localhost/IHMS/');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Include notification handler
require_once '../notification_handler.php';

// Check if notification belongs to user
$notification_query = mysqli_query($conn, "SELECT * FROM notifications WHERE notification_id = $notification_id AND user_id = $user_id");

if(mysqli_num_rows($notification_query) > 0) {
    // Mark notification as read
    mark_notification_as_read($notification_id);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid notification']);
}