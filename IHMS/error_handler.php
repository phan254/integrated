<?php
// Prevent direct access or ensure BASE_URL is defined
if(!defined('BASE_URL')) {
    if(file_exists('config/db_config.php')) {
        require_once 'config/db_config.php';
        define('BASE_URL', 'http://localhost/IHMS/');
    } else {
        exit("Configuration file not found.");
    }
}

// Rest of the file code...

// Define debug mode (set to false for production)
define('DEBUG_MODE', false);

// Error types
define('ERROR_TYPE_INFO', 'info');
define('ERROR_TYPE_WARNING', 'warning');
define('ERROR_TYPE_ERROR', 'error');
define('ERROR_TYPE_SUCCESS', 'success');

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param string $type Error type (info, warning, error)
 * @param array $context Additional context information
 * @return void
 */
function log_error($message, $type = ERROR_TYPE_ERROR, $context = []) {
    // Create logs directory if it doesn't exist
    $log_dir = dirname(__FILE__) . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Determine log file
    $log_file = $log_dir . '/system_' . date('Y-m-d') . '.log';
    
    // Format log entry
    $log_entry = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($type),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    // Write to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Display error message to user
 * 
 * @param string $message Error message
 * @param string $type Error type (info, warning, error, success)
 * @return string HTML for error message
 */
function display_error($message, $type = ERROR_TYPE_ERROR) {
    $alert_class = 'alert-info';
    $icon_class = 'fa-info-circle';
    
    switch($type) {
        case ERROR_TYPE_WARNING:
            $alert_class = 'alert-warning';
            $icon_class = 'fa-exclamation-triangle';
            break;
        case ERROR_TYPE_ERROR:
            $alert_class = 'alert-danger';
            $icon_class = 'fa-exclamation-circle';
            break;
        case ERROR_TYPE_SUCCESS:
            $alert_class = 'alert-success';
            $icon_class = 'fa-check-circle';
            break;
    }
    
    return sprintf(
        '<div class="alert %s alert-dismissible fade show" role="alert">
            <i class="fas %s me-2"></i> %s
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>',
        $alert_class,
        $icon_class,
        $message
    );
}

/**
 * Handle database errors
 * 
 * @param mysqli $conn MySQL connection
 * @param string $query SQL query that failed
 * @return string Error message
 */
function handle_db_error($conn, $query = '') {
    $error_message = mysqli_error($conn);
    $error_code = mysqli_errno($conn);
    
    // Log the error
    log_error('Database Error: ' . $error_message, ERROR_TYPE_ERROR, [
        'code' => $error_code,
        'query' => $query
    ]);
    
    // Return user-friendly message
    if(DEBUG_MODE === true) {
        return 'Database Error: ' . $error_message . ' (Code: ' . $error_code . ')';
    } else {
        return 'A database error occurred. Please try again later.';
    }
}
/**
 * Custom error handler
 * 
 * @param int $errno Error level
 */
