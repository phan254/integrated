<?php
// Start session
session_start();

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: index.php?page=login");
    exit;
}

// Include database connection
require_once 'config/db_config.php';

// Set file name
$backup_file = 'ihms_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backup_file . '"');

// Get all tables
$tables_query = mysqli_query($conn, "SHOW TABLES");
$tables = array();

while($table = mysqli_fetch_row($tables_query)) {
    $tables[] = $table[0];
}

// Start output buffering
ob_start();

// Add database header info
echo "-- IHMS Database Backup\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Host: " . DB_SERVER . "\n";
echo "-- Database: " . DB_NAME . "\n\n";

echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "SET time_zone = \"+00:00\";\n\n";

// Process each table
foreach($tables as $table) {
    // Get create table statement
    $create_table_query = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    $create_table = mysqli_fetch_row($create_table_query);
    
    echo "\n\n-- Table structure for table `$table`\n\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $create_table[1] . ";\n\n";
    
    // Get table data
    $data_query = mysqli_query($conn, "SELECT * FROM `$table`");
    $num_fields = mysqli_num_fields($data_query);
    $num_rows = mysqli_num_rows($data_query);
    
    if($num_rows > 0) {
        echo "-- Dumping data for table `$table`\n";
        echo "INSERT INTO `$table` VALUES\n";
        
        $row_count = 0;
        while($row = mysqli_fetch_row($data_query)) {
            $row_count++;
            echo "(";
            
            for($i = 0; $i < $num_fields; $i++) {
                if(is_null($row[$i])) {
                    echo "NULL";
                } else {
                    echo "'" . mysqli_real_escape_string($conn, $row[$i]) . "'";
                }
                
                if($i < ($num_fields - 1)) {
                    echo ", ";
                }
            }
            
            if($row_count == $num_rows) {
                echo ");\n";
            } else {
                echo "),\n";
            }
        }
    }
}

echo "\nSET FOREIGN_KEY_CHECKS=1;\n";

// Output the buffer contents
$output = ob_get_clean();
echo $output;

// Log backup activity
$log_dir = 'logs';
if(!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/system_' . date('Y-m-d') . '.log';
$log_entry = date('Y-m-d H:i:s') . " - Database backup created by admin ID: " . $_SESSION['user_id'] . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

exit;
?>