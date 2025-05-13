<?php
// Prevent direct access
if(!defined('BASE_URL')) {
    // Load the essential files
    require_once 'config/db_config.php';
    define('BASE_URL', 'http://localhost/IHMS/');
}

// Function to get unread message count
function get_unread_message_count($user_id) {
    global $conn;
    
    $unread_messages_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND read_status = 0");
    return mysqli_fetch_assoc($unread_messages_query)['count'];
}

// Function to mark message as read
function mark_message_as_read($message_id) {
    global $conn;
    
    mysqli_query($conn, "UPDATE messages SET read_status = 1 WHERE message_id = $message_id");
}

// Function to create a notification
function create_notification($user_id, $notification_type, $message, $link = '') {
    global $conn;
    
    $message = mysqli_real_escape_string($conn, $message);
    $link = mysqli_real_escape_string($conn, $link);

    mysqli_query($conn, "INSERT INTO notifications (user_id, notification_type, message, link) VALUES ($user_id, '$notification_type', '$message', '$link')");
}

// Function to get notifications
function get_notifications($user_id, $limit = 5) {
    global $conn;
    $notifications_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit");
    
    $notifications = [];
    while($notification = mysqli_fetch_assoc($notifications_query)) {
        $notifications[] = $notification;
    }
    
    return $notifications;
}

// Function to mark notification as read
function mark_notification_as_read($notification_id) {
    global $conn;
    
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = $notification_id");
}

// Function to get unread notification count
function get_unread_notification_count($user_id) {
    global $conn;
    $unread_notifications_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    return mysqli_fetch_assoc($unread_notifications_query)['count'];
}