<?php
// Start session
session_start();

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: index.php?page=login");
    exit;
}

// Get requested file
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Validate file (prevent directory traversal)
if(empty($file) || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Set file path
$log_dir = 'logs';
$file_path = $log_dir . '/' . $file;

// Check if file exists and is readable
if(!file_exists($file_path) || !is_readable($file_path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Set headers for file download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($file_path));

// Output file content
readfile($file_path);
exit;
?>