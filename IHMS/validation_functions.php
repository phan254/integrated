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
 /** Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validate_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if the phone number has a valid length (adjust as needed for your country)
    return strlen($phone) >= 9 && strlen($phone) <= 15;
}

/**
 * Sanitize string input
 * 
 * @param string $input String to sanitize
 * @return string Sanitized string
 */
function sanitize_string($input) {
    // Remove HTML tags and encode special characters
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string] Validation result and message
 */
function validate_password($password) {
    $result = [
        'valid' => true,
        'message' => ''
    ];
    
    // Check length
    if(strlen($password) < 6) {
        $result['valid'] = false;
        $result['message'] = 'Password must have at least 6 characters.';
        return $result;
    }
    
    // Check for at least one letter and one number
    if(!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one letter and one number.';
        return $result;
    }
    
    return $result;
}

/**
 * Validate required fields
 * 
 * @param array $fields Array of field names to check
 * @param array $data Data array to check against
 * @return array ['valid' => bool, 'missing' => array] Validation result and missing fields
 */
function validate_required_fields($fields, $data) {
    $result = [
        'valid' => true,
        'missing' => []
    ];
    
    foreach($fields as $field) {
        if(!isset($data[$field]) || empty(trim($data[$field]))) {
            $result['valid'] = false;
            $result['missing'][] = $field;
        }
    }
    
    return $result;
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to validate
 * @param float $min Minimum value (optional)
 * @param float $max Maximum value (optional)
 * @return bool True if valid, false otherwise
 */
function validate_numeric($value, $min = null, $max = null) {
    if(!is_numeric($value)) {
        return false;
    }
    
    if($min !== null && $value < $min) {
        return false;
    }
    
    if($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

/**
 * Validate date format
 * 
 * @param string $date Date string to validate
 * @param string $format Expected date format (default: Y-m-d)
 * @return bool True if valid, false otherwise
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}