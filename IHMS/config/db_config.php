<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');  // default XAMPP/WAMP username
define('DB_PASSWORD', '');      // default XAMPP/WAMP has no password
define('DB_NAME', 'ihms_db');      // your local database name
define('CURRENCY_SYMBOL', 'KSh ');

// Session timeout settings
// Add these lines to fix the undefined SESSION_LIFETIME error
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600); // 1 hour in seconds
}

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to ensure proper data handling
mysqli_set_charset($conn, "utf8mb4");
?>