<?php
// Include database connection
require_once 'config/db_config.php';

// Add latitude and longitude columns to hostels table if they don't exist
$check_columns_query = "SHOW COLUMNS FROM hostels LIKE 'latitude'";
$result = mysqli_query($conn, $check_columns_query);

if(mysqli_num_rows($result) == 0) {
    // Latitude and longitude columns don't exist, so add them
    $alter_query = "ALTER TABLE hostels 
                    ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL,
                    ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL";
    
    if(mysqli_query($conn, $alter_query)) {
        echo "Successfully added location columns to hostels table.";
    } else {
        echo "Error adding location columns: " . mysqli_error($conn);
    }
} else {
    echo "Location columns already exist in the hostels table.";
}
?>