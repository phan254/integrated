<?php
// Prevent direct access
if(!defined('BASE_URL')) {
    // If directly accessed, check if it's included from another file
    $included = get_included_files();
    if (count($included) <= 1) {
        // Load the essential files
        require_once 'config/db_config.php';
        define('BASE_URL', 'http://localhost/IHMS/');
    }
}

/**
 * Upload hostel images to the server
 * 
 * @param int $hostel_id The ID of the hostel
 * @param array $files The $_FILES array containing the uploaded images
 * @param int|null $primary_image_index The index of the primary image (optional)
 * @return bool Whether any images were successfully uploaded
 */
function upload_hostel_images($hostel_id, $files, $primary_image_index = null) {
    global $conn;
    
    $uploaded = false;
    $upload_dir = 'uploads/';
    
    // Create upload directory if it doesn't exist
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Check if we received a proper file array
    if(!isset($files['name']) || !is_array($files['name'])) {
        return false;
    }
    
    // Process each uploaded file
    foreach($files['name'] as $key => $file_name) {
        // Skip if no file uploaded at this index
        if(empty($file_name) || empty($files['tmp_name'][$key]) || $files['error'][$key] != 0) {
            continue;
        }
        
        $file_size = $files['size'][$key];
        $file_tmp = $files['tmp_name'][$key];
        $file_type = $files['type'][$key];
        
        // Check file size (limit to 5MB)
        if($file_size > 5242880) {
            continue;
        }
        
        // Generate unique file name
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = 'hostel_' . $hostel_id . '_' . uniqid() . '.' . $file_extension;
        
        // Check if file is an image
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        if(in_array($file_type, $allowed_types)) {
            // Move file to upload directory
            if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // Set primary flag based on selection
                $is_primary = ($primary_image_index !== null && $primary_image_index == $key) ? 1 : 0;
                
                // If this is set as primary, update other images to not be primary
                if($is_primary) {
                    mysqli_query($conn, "UPDATE hostel_images SET is_primary = 0 WHERE hostel_id = $hostel_id");
                }
                
                // Insert file info into database
                $insert_query = "INSERT INTO hostel_images (hostel_id, image_path, is_primary) VALUES (?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $insert_query)) {
                    mysqli_stmt_bind_param($stmt, "isi", $hostel_id, $new_file_name, $is_primary);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $uploaded = true;
                }
            }
        }
    }
    
    // If no primary image was set but we uploaded images, set the first one as primary
    if($uploaded && $primary_image_index === null) {
        $check_primary = mysqli_query($conn, "SELECT COUNT(*) as count FROM hostel_images WHERE hostel_id = $hostel_id AND is_primary = 1");
        $has_primary = mysqli_fetch_assoc($check_primary)['count'] > 0;
        
        if(!$has_primary) {
            mysqli_query($conn, "UPDATE hostel_images SET is_primary = 1 WHERE hostel_id = $hostel_id ORDER BY image_id ASC LIMIT 1");
        }
    }
    
    return $uploaded;
}

/**
 * Upload a profile picture for a user
 * 
 * @param int $user_id The ID of the user
 * @param array $file The $_FILES['profile_picture'] array
 * @return bool Whether the image was successfully uploaded
 */
function upload_profile_picture($user_id, $file) {
    global $conn;
    
    $upload_dir = 'uploads/profiles/';
    
    // Create upload directory if it doesn't exist
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Check if file exists and no errors
    if(empty($file['name']) || $file['error'] != 0) {
        return false;
    }
    
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = $file['type'];
    
    // Check file size (limit to 2MB)
    if($file_size > 2097152) {
        return false;
    }
    
    // Generate unique file name
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_file_name = 'profile_' . $user_id . '_' . uniqid() . '.' . $file_extension;
    
    // Check if file is an image
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
    if(in_array($file_type, $allowed_types)) {
        // Move file to upload directory
        if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            // Get current profile picture
            $query = mysqli_query($conn, "SELECT profile_picture FROM users WHERE user_id = $user_id");
            $user = mysqli_fetch_assoc($query);
            
            // If user already has a profile picture, delete the old one
            if(!empty($user['profile_picture'])) {
                $old_file = $upload_dir . $user['profile_picture'];
                if(file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            // Update user's profile picture in database
            $update_query = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            if($stmt = mysqli_prepare($conn, $update_query)) {
                mysqli_stmt_bind_param($stmt, "si", $new_file_name, $user_id);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                return $result;
            }
        }
    }
    
    return false;
}