<?php
// Set page title
$page_title = "Manage Hostel | IHMS";

// Check if user is logged in as hostel manager
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'hostel_manager') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$manager_id = $_SESSION['user_id'];

// Include image handler
require_once 'image_handler.php';

// Get hostel data for this manager
$hostel_query = mysqli_query($conn, "SELECT * FROM hostels WHERE manager_id = $manager_id");

// Check if manager has a hostel
if(mysqli_num_rows($hostel_query) == 0) {
    // Redirect to add hostel page if not found
    echo "<script>window.location.href = '" . BASE_URL . "?page=add_hostel';</script>";
exit;
    exit;
}

$hostel = mysqli_fetch_assoc($hostel_query);
$hostel_id = $hostel['hostel_id'];

// Get room types for this hostel
$room_types_query = mysqli_query($conn, "SELECT * FROM room_types WHERE hostel_id = $hostel_id ORDER BY price ASC");

// Get hostel images
$images_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id ORDER BY is_primary DESC");

// Get hostel amenities
$amenities_query = mysqli_query($conn, "SELECT * FROM hostel_amenities WHERE hostel_id = $hostel_id");
$amenities = mysqli_fetch_assoc($amenities_query);

// Get recent bookings
$bookings_query = mysqli_query($conn, "SELECT b.*, u.full_name, u.email, u.phone, rt.room_type 
                                    FROM bookings b 
                                    JOIN users u ON b.student_id = u.user_id 
                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    WHERE rt.hostel_id = $hostel_id 
                                    ORDER BY b.booking_date DESC 
                                    LIMIT 10");

// Handle updating hostel information
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_hostel'])) {
    // Get form data
    $hostel_name = trim($_POST['hostel_name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $distance = (float)$_POST['distance'];
    $price_range = trim($_POST['price_range']);
    $gender_type = trim($_POST['gender_type']);
    
    // Validate inputs
    $valid = true;
    
    if(empty($hostel_name) || empty($description) || empty($address) || empty($city) || empty($price_range) || empty($gender_type) || $distance < 0) {
        $valid = false;
        $error_message = "All required fields must be filled with valid values.";
    }
    
    if($valid) {
        // Update hostel information
        $update_hostel = mysqli_query($conn, "UPDATE hostels SET 
                                           hostel_name = '$hostel_name',
                                           description = '$description',
                                           address = '$address',
                                           city = '$city',
                                           distance_from_university = $distance,
                                           price_range = '$price_range',
                                           gender_type = '$gender_type'
                                           WHERE hostel_id = $hostel_id");
        
        if($update_hostel) {
            // Update amenities
            $wifi = isset($_POST['wifi']) ? 1 : 0;
            $hot_water = isset($_POST['hot_water']) ? 1 : 0;
            $security = isset($_POST['security']) ? 1 : 0;
            $meals = isset($_POST['meals']) ? 1 : 0;
            $laundry = isset($_POST['laundry']) ? 1 : 0;
            $study_room = isset($_POST['study_room']) ? 1 : 0;
            $parking = isset($_POST['parking']) ? 1 : 0;
            $additional_amenities = trim($_POST['additional_amenities']);
            
            mysqli_query($conn, "UPDATE hostel_amenities SET 
                              wifi = $wifi,
                              hot_water = $hot_water,
                              security = $security,
                              meals = $meals,
                              laundry = $laundry,
                              study_room = $study_room,
                              parking = $parking,
                              additional_amenities = '$additional_amenities'
                              WHERE hostel_id = $hostel_id");
            
            $success_message = "Hostel information updated successfully.";
            
            // Refresh hostel data
            $hostel_query = mysqli_query($conn, "SELECT * FROM hostels WHERE hostel_id = $hostel_id");
            $hostel = mysqli_fetch_assoc($hostel_query);
            
            // Refresh amenities data
            $amenities_query = mysqli_query($conn, "SELECT * FROM hostel_amenities WHERE hostel_id = $hostel_id");
            $amenities = mysqli_fetch_assoc($amenities_query);
        } else {
            $error_message = "Failed to update hostel information. Please try again.";
        }
    }
}

// Handle adding new room type
if(isset($_POST['add_room']) && isset($_POST['hostel_id'])) {
    $hostel_id = (int)$_POST['hostel_id'];
    
    // Check if hostel belongs to manager
    $check_hostel = mysqli_query($conn, "SELECT * FROM hostels WHERE hostel_id = $hostel_id AND manager_id = $manager_id");
    
    if(mysqli_num_rows($check_hostel) > 0) {
        // Get form data
        $room_type = trim($_POST['room_type']);
        $capacity = (int)$_POST['capacity'];
        $price = (float)$_POST['price'];
        $description = trim($_POST['description']);
        $available_count = (int)$_POST['available_count'];
        
        // Validate inputs
        $valid = true;
        
        if(empty($room_type) || $capacity < 1 || $price <= 0 || $available_count < 0) {
            $valid = false;
            $error_message = "All required fields must be filled with valid values.";
        }
        
        if($valid) {
            // Insert new room type
            $insert_room = mysqli_query($conn, "INSERT INTO room_types (hostel_id, room_type, capacity, price, description, available_count) 
                                              VALUES ($hostel_id, '$room_type', $capacity, $price, '$description', $available_count)");
            
            if($insert_room) {
                // Update hostel available rooms count
                $total_available_query = mysqli_query($conn, "SELECT SUM(available_count) as total FROM room_types WHERE hostel_id = $hostel_id");
                $total_available = mysqli_fetch_assoc($total_available_query)['total'];
                
                mysqli_query($conn, "UPDATE hostels SET available_rooms = $total_available WHERE hostel_id = $hostel_id");
                
                $success_message = "Room type added successfully.";
                
                // Refresh room types data
                $room_types_query = mysqli_query($conn, "SELECT * FROM room_types WHERE hostel_id = $hostel_id ORDER BY price ASC");
            } else {
                $error_message = "Failed to add room type. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid hostel.";
    }
}

// Handle updating room type
if(isset($_POST['update_room'])) {
    $room_type_id = (int)$_POST['room_type_id'];
    
    // Check if room type belongs to manager's hostel
    $check_room = mysqli_query($conn, "SELECT rt.* FROM room_types rt 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    WHERE rt.room_type_id = $room_type_id AND h.manager_id = $manager_id");
    
    if(mysqli_num_rows($check_room) > 0) {
        // Get form data
        $room_type = trim($_POST['room_type']);
        $capacity = (int)$_POST['capacity'];
        $price = (float)$_POST['price'];
        $description = trim($_POST['description']);
        $available_count = (int)$_POST['available_count'];
        
        // Validate inputs
        $valid = true;
        
        if(empty($room_type) || $capacity < 1 || $price <= 0 || $available_count < 0) {
            $valid = false;
            $error_message = "All required fields must be filled with valid values.";
        }
        
        if($valid) {
            // Update room type
            $update_room = mysqli_query($conn, "UPDATE room_types SET 
                                            room_type = '$room_type',
                                            capacity = $capacity,
                                            price = $price,
                                            description = '$description',
                                            available_count = $available_count
                                            WHERE room_type_id = $room_type_id");
            
            if($update_room) {
                // Update hostel available rooms count
                $total_available_query = mysqli_query($conn, "SELECT SUM(available_count) as total FROM room_types WHERE hostel_id = $hostel_id");
                $total_available = mysqli_fetch_assoc($total_available_query)['total'];
                
                mysqli_query($conn, "UPDATE hostels SET available_rooms = $total_available WHERE hostel_id = $hostel_id");
                
                $success_message = "Room type updated successfully.";
                
                // Refresh room types data
                $room_types_query = mysqli_query($conn, "SELECT * FROM room_types WHERE hostel_id = $hostel_id ORDER BY price ASC");
            } else {
                $error_message = "Failed to update room type. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid room type.";
    }
}

// Handle deleting room type
if(isset($_GET['delete_room'])) {
    $room_type_id = (int)$_GET['delete_room'];
    
    // Check if room type belongs to manager's hostel
    $check_room = mysqli_query($conn, "SELECT rt.* FROM room_types rt 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    WHERE rt.room_type_id = $room_type_id AND h.manager_id = $manager_id");
    
    if(mysqli_num_rows($check_room) > 0) {
        // Check if room type has any bookings
        $check_bookings = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE room_type_id = $room_type_id");
        $has_bookings = mysqli_fetch_assoc($check_bookings)['count'] > 0;
        
        if($has_bookings) {
            $error_message = "Cannot delete room type with existing bookings.";
        } else {
            // Delete room type
            $delete_room = mysqli_query($conn, "DELETE FROM room_types WHERE room_type_id = $room_type_id");
            
            if($delete_room) {
                // Update hostel available rooms count
                $total_available_query = mysqli_query($conn, "SELECT SUM(available_count) as total FROM room_types WHERE hostel_id = $hostel_id");
                $total_available = mysqli_fetch_assoc($total_available_query)['total'] ?: 0;
                
                mysqli_query($conn, "UPDATE hostels SET available_rooms = $total_available WHERE hostel_id = $hostel_id");
                
                $success_message = "Room type deleted successfully.";
                
                // Refresh room types data
                $room_types_query = mysqli_query($conn, "SELECT * FROM room_types WHERE hostel_id = $hostel_id ORDER BY price ASC");
            } else {
                $error_message = "Failed to delete room type. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid room type.";
    }
}

// Handle setting primary image
if(isset($_POST['set_primary']) && isset($_POST['image_id'])) {
    $image_id = (int)$_POST['image_id'];
    
    // Check if image belongs to manager's hostel
    $check_image = mysqli_query($conn, "SELECT hi.* FROM hostel_images hi 
                                     JOIN hostels h ON hi.hostel_id = h.hostel_id 
                                     WHERE hi.image_id = $image_id AND h.manager_id = $manager_id");
    
    if(mysqli_num_rows($check_image) > 0) {
        $image_data = mysqli_fetch_assoc($check_image);
        $hostel_id = $image_data['hostel_id'];
        
        // Update all images to not be primary
        mysqli_query($conn, "UPDATE hostel_images SET is_primary = 0 WHERE hostel_id = $hostel_id");
        
        // Set selected image as primary
        $update_image = mysqli_query($conn, "UPDATE hostel_images SET is_primary = 1 WHERE image_id = $image_id");
        
        if($update_image) {
            $success_message = "Primary image updated successfully.";
            
            // Refresh images data
            $images_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id ORDER BY is_primary DESC");
        } else {
            $error_message = "Failed to update primary image. Please try again.";
        }
    } else {
        $error_message = "Invalid image.";
    }
}

// Handle image deletion
if(isset($_POST['delete_image']) && isset($_POST['image_id'])) {
    $image_id = (int)$_POST['image_id'];
    
    // Check if image belongs to manager's hostel
    $check_image = mysqli_query($conn, "SELECT hi.* FROM hostel_images hi 
                                     JOIN hostels h ON hi.hostel_id = h.hostel_id 
                                     WHERE hi.image_id = $image_id AND h.manager_id = $manager_id");
    
    if(mysqli_num_rows($check_image) > 0) {
        $image_data = mysqli_fetch_assoc($check_image);
        $hostel_id = $image_data['hostel_id'];
        $image_path = $image_data['image_path'];
        $is_primary = $image_data['is_primary'];
        
        // Delete image from database
        $delete_image = mysqli_query($conn, "DELETE FROM hostel_images WHERE image_id = $image_id");
        
        if($delete_image) {
            // Delete image file from server
            $file_path = 'uploads/' . $image_path;
            if(file_exists($file_path)) {
                unlink($file_path);
            }
            
            // If deleted image was primary, set another image as primary
            if($is_primary) {
                $next_image_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id LIMIT 1");
                if(mysqli_num_rows($next_image_query) > 0) {
                    $next_image = mysqli_fetch_assoc($next_image_query);
                    mysqli_query($conn, "UPDATE hostel_images SET is_primary = 1 WHERE image_id = " . $next_image['image_id']);
                }
            }
            
            $success_message = "Image deleted successfully.";
            
            // Refresh images data
            $images_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id ORDER BY is_primary DESC");
        } else {
            $error_message = "Failed to delete image. Please try again.";
        }
    } else {
        $error_message = "Invalid image.";
    }
}

// Handle image upload
if(isset($_POST['upload_images']) && isset($_POST['hostel_id'])) {
    $hostel_id = (int)$_POST['hostel_id'];
    
    // Check if hostel belongs to manager
    $check_hostel = mysqli_query($conn, "SELECT * FROM hostels WHERE hostel_id = $hostel_id AND manager_id = $manager_id");
    
    if(mysqli_num_rows($check_hostel) > 0) {
        // Check if files were uploaded
        if(isset($_FILES['hostel_images']) && !empty($_FILES['hostel_images']['name'][0])) {
            $primary_image = isset($_POST['primary_image']) ? (int)$_POST['primary_image'] : null;
            
            if(upload_hostel_images($hostel_id, $_FILES['hostel_images'], $primary_image)) {
                $success_message = "Images uploaded successfully.";
                
                // Refresh images data
                $images_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id ORDER BY is_primary DESC");
            } else {
                $error_message = "Failed to upload images. Please try again.";
            }
        } else {
            $error_message = "Please select at least one image to upload.";
        }
    } else {
        $error_message = "Invalid hostel.";
    }
}

// Handle booking status update
if(isset($_GET['booking_id']) && isset($_GET['action'])) {
    $booking_id = (int)$_GET['booking_id'];
    $action = $_GET['action'];
    
    // Check if booking belongs to manager's hostel
    $check_booking = mysqli_query($conn, "SELECT b.* 
                                       FROM bookings b 
                                       JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                       JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                       WHERE b.booking_id = $booking_id AND h.manager_id = $manager_id");
    
    if(mysqli_num_rows($check_booking) > 0) {
        $booking_data = mysqli_fetch_assoc($check_booking);
        
        if($action == 'confirm' && $booking_data['status'] == 'pending') {
            // Confirm booking
            $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'confirmed' WHERE booking_id = $booking_id");
            
            if($update_booking) {
                $success_message = "Booking confirmed successfully.";
            } else {
                $error_message = "Failed to confirm booking. Please try again.";
            }
        } elseif($action == 'cancel' && ($booking_data['status'] == 'pending' || $booking_data['status'] == 'confirmed')) {
            // Cancel booking
            $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE booking_id = $booking_id");
            
            if($update_booking) {
                // Update available room count
                $room_type_id = $booking_data['room_type_id'];
                mysqli_query($conn, "UPDATE room_types SET available_count = available_count + 1 WHERE room_type_id = $room_type_id");
                
                // Update hostel available rooms count
                $rt_query = mysqli_query($conn, "SELECT hostel_id FROM room_types WHERE room_type_id = $room_type_id");
                $rt_data = mysqli_fetch_assoc($rt_query);
                $hostel_id = $rt_data['hostel_id'];
                
                $total_available_query = mysqli_query($conn, "SELECT SUM(available_count) as total FROM room_types WHERE hostel_id = $hostel_id");
                $total_available = mysqli_fetch_assoc($total_available_query)['total'];
                
                mysqli_query($conn, "UPDATE hostels SET available_rooms = $total_available WHERE hostel_id = $hostel_id");
                
                $success_message = "Booking cancelled successfully.";
            } else {
                $error_message = "Failed to cancel booking. Please try again.";
            }
        } elseif($action == 'complete' && $booking_data['status'] == 'confirmed') {
            // Complete booking
            $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'completed' WHERE booking_id = $booking_id");
            
            if($update_booking) {
                $success_message = "Booking marked as completed.";
            } else {
                $error_message = "Failed to complete booking. Please try again.";
            }
        } else {
            $error_message = "Invalid action or booking status.";
        }
        
        // Refresh bookings data
        $bookings_query = mysqli_query($conn, "SELECT b.*, u.full_name, u.email, u.phone, rt.room_type 
                                           FROM bookings b 
                                           JOIN users u ON b.student_id = u.user_id 
                                           JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                           WHERE rt.hostel_id = $hostel_id 
                                           ORDER BY b.booking_date DESC 
                                           LIMIT 10");
    } else {
        $error_message = "Invalid booking.";
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group mb-4">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_hostel" class="list-group-item list-group-item-action active">
                    <i class="fas fa-hotel me-2"></i> Manage Hostel
                </a>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
                </a>
                <a href="<?php echo BASE_URL; ?>?page=profile" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
                <a href="<?php echo BASE_URL; ?>?page=logout" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-hotel me-2"></i> Manage Hostel</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <ul class="nav nav-tabs" id="hostelTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                <i class="fas fa-info-circle me-1"></i> Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false">
                                <i class="fas fa-bed me-1"></i> Rooms
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images" type="button" role="tab" aria-controls="images" aria-selected="false">
                                <i class="fas fa-images me-1"></i> Images
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="false">
                                <i class="fas fa-calendar-alt me-1"></i> Bookings
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="hostelTabsContent">
                        <!-- Hostel Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="p-3">
                                <h4 class="mb-3">Hostel Information</h4>
                                
                                <form method="post" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="hostel_name" class="form-label">Hostel Name <span class="text-danger">*</span></label>
                                            <input type="text" name="hostel_name" id="hostel_name" class="form-control" value="<?php echo $hostel['hostel_name']; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="gender_type" class="form-label">Gender Type <span class="text-danger">*</span></label>
                                            <select name="gender_type" id="gender_type" class="form-select" required>
                                                <option value="male" <?php echo ($hostel['gender_type'] == 'male') ? 'selected' : ''; ?>>Male Only</option>
                                                <option value="female" <?php echo ($hostel['gender_type'] == 'female') ? 'selected' : ''; ?>>Female Only</option>
                                                <option value="mixed" <?php echo ($hostel['gender_type'] == 'mixed') ? 'selected' : ''; ?>>Mixed</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea name="description" id="description" class="form-control" rows="4" required><?php echo $hostel['description']; ?></textarea>
                                        <div class="form-text">Provide a detailed description of your hostel, including facilities and benefits.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                            <input type="text" name="address" id="address" class="form-control" value="<?php echo $hostel['address']; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                            <input type="text" name="city" id="city" class="form-control" value="<?php echo $hostel['city']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="distance" class="form-label">Distance from University (km) <span class="text-danger">*</span></label>
                                            <input type="number" name="distance" id="distance" step="0.1" min="0" class="form-control" value="<?php echo $hostel['distance_from_university']; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="price_range" class="form-label">Price Range <span class="text-danger">*</span></label>
                                            <input type="text" name="price_range" id="price_range" class="form-control" value="<?php echo $hostel['price_range']; ?>" placeholder="e.g. KSh 1,000-KSh 3,000" required>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mt-4 mb-3">Amenities</h5>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="wifi" id="wifi" value="1" <?php echo ($amenities['wifi']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="wifi">WiFi</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="hot_water" id="hot_water" value="1" <?php echo ($amenities['hot_water']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="hot_water">Hot Water</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="security" id="security" value="1" <?php echo ($amenities['security']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="security">Security</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 mb-2">
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="meals" id="meals" value="1" <?php echo ($amenities['meals']) ? 'checked' : ''; ?>>
                                                       <label class="form-check-label" for="meals">Meals</label>
                                                   </div>
                                               </div>
                                               
                                               <div class="col-md-3 mb-2">
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="laundry" id="laundry" value="1" <?php echo ($amenities['laundry']) ? 'checked' : ''; ?>>
                                                       <label class="form-check-label" for="laundry">Laundry</label>
                                                   </div>
                                               </div>
                                               
                                               <div class="col-md-3 mb-2">
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="study_room" id="study_room" value="1" <?php echo ($amenities['study_room']) ? 'checked' : ''; ?>>
                                                       <label class="form-check-label" for="study_room">Study Room</label>
                                                   </div>
                                               </div>
                                               
                                               <div class="col-md-3 mb-2">
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="parking" id="parking" value="1" <?php echo ($amenities['parking']) ? 'checked' : ''; ?>>
                                                       <label class="form-check-label" for="parking">Parking</label>
                                                   </div>
                                               </div>
                                           </div>
                                           
                                           <div class="mt-3">
                                               <label for="additional_amenities" class="form-label">Additional Amenities</label>
                                               <textarea name="additional_amenities" id="additional_amenities" class="form-control" rows="3"><?php echo $amenities['additional_amenities']; ?></textarea>
                                               <div class="form-text">List any other amenities or special features of your hostel.</div>
                                           </div>
                                       </div>
                                   </div>
                                   
                                   <div class="d-grid mt-4">
                                       <button type="submit" name="update_hostel" class="btn btn-primary">
                                           <i class="fas fa-save me-2"></i> Update Hostel Information
                                       </button>
                                   </div>
                               </form>
                           </div>
                       </div>
                       
                       <!-- Rooms Tab -->
                       <div class="tab-pane fade" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                           <div class="p-3">
                               <div class="d-flex justify-content-between align-items-center mb-3">
                                   <h4>Room Types</h4>
                                   <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                                       <i class="fas fa-plus-circle me-1"></i> Add New Room Type
                                   </button>
                               </div>
                               
                               <?php if(mysqli_num_rows($room_types_query) > 0): ?>
                                   <div class="table-responsive">
                                       <table class="table table-striped table-hover">
                                           <thead>
                                               <tr>
                                                   <th>Room Type</th>
                                                   <th>Capacity</th>
                                                   <th>Price (Monthly)</th>
                                                   <th>Description</th>
                                                   <th>Available Count</th>
                                                   <th>Actions</th>
                                               </tr>
                                           </thead>
                                           <tbody>
                                               <?php while($room = mysqli_fetch_assoc($room_types_query)): ?>
                                                   <tr>
                                                       <td><?php echo $room['room_type']; ?></td>
                                                       <td><?php echo $room['capacity']; ?> person(s)</td>
                                                       <td>KSh <?php echo number_format($room['price'], 2); ?></td>
                                                       <td><?php echo $room['description']; ?></td>
                                                       <td><?php echo $room['available_count']; ?></td>
                                                       <td>
                                                           <button class="btn btn-sm btn-primary edit-room-btn" data-bs-toggle="modal" data-bs-target="#editRoomModal" 
                                                                   data-room-id="<?php echo $room['room_type_id']; ?>"
                                                                   data-room-type="<?php echo $room['room_type']; ?>"
                                                                   data-capacity="<?php echo $room['capacity']; ?>"
                                                                   data-price="<?php echo $room['price']; ?>"
                                                                   data-description="<?php echo $room['description']; ?>"
                                                                   data-available="<?php echo $room['available_count']; ?>">
                                                               <i class="fas fa-edit"></i> Edit
                                                           </button>
                                                           
                                                           <a href="<?php echo BASE_URL; ?>?page=manage_hostel&delete_room=<?php echo $room['room_type_id']; ?>" 
                                                              class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room type?');">
                                                               <i class="fas fa-trash"></i> Delete
                                                           </a>
                                                       </td>
                                                   </tr>
                                               <?php endwhile; ?>
                                           </tbody>
                                       </table>
                                   </div>
                               <?php else: ?>
                                   <div class="alert alert-info">
                                       <i class="fas fa-info-circle me-2"></i> No room types have been added yet. Click the button above to add a new room type.
                                   </div>
                               <?php endif; ?>
                           </div>
                       </div>
                       
                       <!-- Images Tab -->
                       <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">
                           <div class="p-3">
                               <div class="d-flex justify-content-between align-items-center mb-3">
                                   <h4>Hostel Images</h4>
                               </div>
                               
                               <!-- Image Upload Form -->
                               <div class="card mb-4">
                                   <div class="card-header">
                                       <h5 class="mb-0">Upload Hostel Images</h5>
                                   </div>
                                   <div class="card-body">
                                       <form method="post" action="" enctype="multipart/form-data">
                                           <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                                           
                                           <div class="mb-3">
                                               <label for="hostel_images" class="form-label">Select Images</label>
                                               <input type="file" name="hostel_images[]" id="hostel_images" class="form-control" multiple accept="image/*" capture="camera" required>
                                               <div class="form-text">
                                                   <ul>
                                                       <li>You can select multiple images from your device gallery or take new photos</li>
                                                       <li>Maximum 5 images allowed</li>
                                                       <li>Each image should be less than 5MB</li>
                                                       <li>Supported formats: JPG, PNG, GIF</li>
                                                   </ul>
                                               </div>
                                           </div>
                                           
                                           <div class="mb-3">
                                               <label class="form-label">Set Primary Image</label>
                                               <select name="primary_image" class="form-select">
                                                   <option value="">Use first image as primary</option>
                                                   <option value="0">1st selected image</option>
                                                   <option value="1">2nd selected image</option>
                                                   <option value="2">3rd selected image</option>
                                                   <option value="3">4th selected image</option>
                                                   <option value="4">5th selected image</option>
                                               </select>
                                               <div class="form-text">The primary image will be displayed as the main image for this hostel.</div>
                                           </div>
                                           
                                           <div id="image_previews" class="row mt-3"></div>
                                           
                                           <div class="mt-3">
                                               <button type="submit" name="upload_images" class="btn btn-primary">
                                                   <i class="fas fa-upload me-1"></i> Upload Images
                                               </button>
                                           </div>
                                       </form>
                                   </div>
                               </div>
                               
                               <!-- Current Images -->
                               <h5 class="mt-4 mb-3">Current Images</h5>
                               <?php if(mysqli_num_rows($images_query) > 0): ?>
                                   <div class="row">
                                       <?php while($image = mysqli_fetch_assoc($images_query)): ?>
                                           <div class="col-md-4 col-sm-6 mb-4">
                                               <div class="card h-100">
                                                   <img src="<?php echo BASE_URL . 'uploads/' . $image['image_path']; ?>" class="card-img-top" alt="Hostel Image" style="height: 200px; object-fit: cover;">
                                                   <div class="card-body">
                                                       <?php if($image['is_primary']): ?>
                                                           <span class="badge bg-success mb-2">Primary Image</span>
                                                       <?php endif; ?>
                                                       
                                                       <div class="btn-group w-100" role="group">
                                                           <?php if(!$image['is_primary']): ?>
                                                               <form method="post" class="flex-fill">
                                                                   <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                                                   <button type="submit" name="set_primary" class="btn btn-sm btn-primary w-100">
                                                                       <i class="fas fa-star me-1"></i> Set as Primary
                                                                   </button>
                                                               </form>
                                                           <?php endif; ?>
                                                           
                                                           <form method="post" class="flex-fill ms-1">
                                                               <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                                               <button type="submit" name="delete_image" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to delete this image?');">
                                                                   <i class="fas fa-trash me-1"></i> Delete
                                                               </button>
                                                           </form>
                                                       </div>
                                                   </div>
                                               </div>
                                           </div>
                                       <?php endwhile; ?>
                                   </div>
                               <?php else: ?>
                                   <div class="alert alert-info">
                                       <i class="fas fa-info-circle me-2"></i> No images have been uploaded yet. Use the form above to upload images of your hostel.
                                   </div>
                               <?php endif; ?>
                           </div>
                       </div>
                       
                       <!-- Bookings Tab -->
                       <div class="tab-pane fade" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                           <div class="p-3">
                               <h4 class="mb-3">Booking Requests</h4>
                               
                               <?php if(mysqli_num_rows($bookings_query) > 0): ?>
                                   <div class="table-responsive">
                                       <table class="table table-striped table-hover">
                                           <thead>
                                               <tr>
                                                   <th>Booking ID</th>
                                                   <th>Student</th>
                                                   <th>Room Type</th>
                                                   <th>Check-in Date</th>
                                                   <th>Amount</th>
                                                   <th>Status</th>
                                                   <th>Payment Status</th>
                                                   <th>Actions</th>
                                               </tr>
                                           </thead>
                                           <tbody>
                                               <?php while($booking = mysqli_fetch_assoc($bookings_query)): ?>
                                                   <tr>
                                                       <td>#<?php echo $booking['booking_id']; ?></td>
                                                       <td>
                                                           <?php echo $booking['full_name']; ?>
                                                           <div>
                                                               <small class="text-muted"><?php echo $booking['email']; ?></small>
                                                               <?php if(!empty($booking['phone'])): ?>
                                                                   <br><small class="text-muted"><?php echo $booking['phone']; ?></small>
                                                               <?php endif; ?>
                                                           </div>
                                                       </td>
                                                       <td><?php echo $booking['room_type']; ?></td>
                                                       <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                       <td>KSh <?php echo number_format($booking['amount'], 2); ?></td>
                                                       <td>
                                                           <span class="badge <?php 
                                                               if($booking['status'] == 'pending') echo 'bg-warning text-dark';
                                                               elseif($booking['status'] == 'confirmed') echo 'bg-success';
                                                               elseif($booking['status'] == 'cancelled') echo 'bg-danger';
                                                               else echo 'bg-info';
                                                           ?>">
                                                               <?php echo ucfirst($booking['status']); ?>
                                                           </span>
                                                       </td>
                                                       <td>
                                                           <span class="badge <?php 
                                                               if($booking['payment_status'] == 'unpaid') echo 'bg-danger';
                                                               elseif($booking['payment_status'] == 'partial') echo 'bg-warning text-dark';
                                                               else echo 'bg-success';
                                                           ?>">
                                                               <?php echo ucfirst($booking['payment_status']); ?>
                                                           </span>
                                                       </td>
                                                       <td>
                                                           <?php if($booking['status'] == 'pending'): ?>
                                                               <a href="<?php echo BASE_URL; ?>?page=manage_hostel&booking_id=<?php echo $booking['booking_id']; ?>&action=confirm" class="btn btn-sm btn-success">
                                                                   <i class="fas fa-check"></i> Confirm
                                                               </a>
                                                               <a href="<?php echo BASE_URL; ?>?page=manage_hostel&booking_id=<?php echo $booking['booking_id']; ?>&action=cancel" class="btn btn-sm btn-danger">
                                                                   <i class="fas fa-times"></i> Cancel
                                                               </a>
                                                           <?php elseif($booking['status'] == 'confirmed'): ?>
                                                               <a href="<?php echo BASE_URL; ?>?page=manage_hostel&booking_id=<?php echo $booking['booking_id']; ?>&action=cancel" class="btn btn-sm btn-warning">
                                                                   <i class="fas fa-times"></i> Cancel
                                                               </a>
                                                               <a href="<?php echo BASE_URL; ?>?page=manage_hostel&booking_id=<?php echo $booking['booking_id']; ?>&action=complete" class="btn btn-sm btn-info">
                                                                   <i class="fas fa-check-double"></i> Complete
                                                               </a>
                                                           <?php endif; ?>
                                                           <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $booking['student_id']; ?>" class="btn btn-sm btn-primary">
                                                               <i class="fas fa-envelope"></i> Message
                                                           </a>
                                                       </td>
                                                   </tr>
                                               <?php endwhile; ?>
                                           </tbody>
                                       </table>
                                   </div>
                               <?php else: ?>
                                   <div class="alert alert-info">
                                       <i class="fas fa-info-circle me-2"></i> No booking requests found.
                                   </div>
                               <?php endif; ?>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
   <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="addRoomModalLabel">Add New Room Type</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <form method="post">
               <div class="modal-body">
                   <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                   
                   <div class="mb-3">
                       <label for="room_type" class="form-label">Room Type <span class="text-danger">*</span></label>
                       <input type="text" name="room_type" id="room_type" class="form-control" placeholder="e.g. Single, Double, Ensuite" required>
                   </div>
                   
                   <div class="row">
                       <div class="col-md-6 mb-3">
                           <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                           <input type="number" name="capacity" id="capacity" class="form-control" min="1" placeholder="Number of people" required>
                       </div>
                       
                       <div class="col-md-6 mb-3">
                           <label for="price" class="form-label">Monthly Price <span class="text-danger">*</span></label>
                           <div class="input-group">
                               <span class="input-group-text">KSh</span>
                               <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" placeholder="Price per month" required>
                           </div>
                       </div>
                   </div>
                   
                   <div class="mb-3">
                       <label for="description" class="form-label">Description</label>
                       <textarea name="description" id="description" class="form-control" rows="3" placeholder="Room features and details"></textarea>
                   </div>
                   
                   <div class="mb-3">
                       <label for="available_count" class="form-label">Available Count <span class="text-danger">*</span></label>
                       <input type="number" name="available_count" id="available_count" class="form-control" min="0" placeholder="Number of available rooms" required>
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                   <button type="submit" name="add_room" class="btn btn-primary">Add Room Type</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
   <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="editRoomModalLabel">Edit Room Type</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <form method="post">
               <div class="modal-body">
                   <input type="hidden" name="room_type_id" id="edit_room_type_id">
                   
                   <div class="mb-3">
                       <label for="edit_room_type" class="form-label">Room Type <span class="text-danger">*</span></label>
                       <input type="text" name="room_type" id="edit_room_type" class="form-control" required>
                   </div>
                   
                   <div class="row">
                       <div class="col-md-6 mb-3">
                           <label for="edit_capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                           <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" required>
                       </div>
                       
                       <div class="col-md-6 mb-3">
                           <label for="edit_price" class="form-label">Monthly Price <span class="text-danger">*</span></label>
                           <div class="input-group">
                               <span class="input-group-text">KSh</span>
                               <input type="number" name="price" id="edit_price" class="form-control" min="0" step="0.01" required>
                           </div>
                       </div>
                   </div>
                   
                   <div class="mb-3">
                       <label for="edit_description" class="form-label">Description</label>
                       <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                   </div>
                   
                   <div class="mb-3">
                       <label for="edit_available_count" class="form-label">Available Count <span class="text-danger">*</span></label>
                       <input type="number" name="available_count" id="edit_available_count" class="form-control" min="0" required>
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                   <button type="submit" name="update_room" class="btn btn-primary">Update Room Type</button>
               </div>
           </form>
       </div>
   </div>
</div>

<script>
// Add this JavaScript for image preview before upload
document.addEventListener('DOMContentLoaded', function() {
   const fileInput = document.getElementById('hostel_images');
   const previewContainer = document.getElementById('image_previews');
   
   if (fileInput) {
       fileInput.addEventListener('change', function() {
           previewContainer.innerHTML = '';
           
           if (this.files) {
               // Limit to 5 files
               const filesToPreview = Array.from(this.files).slice(0, 5);
               
               filesToPreview.forEach((file, index) => {
                   // Check if file is an image
                   if (!file.type.match('image.*')) {
                       return;
                   }
                   
                   const reader = new FileReader();
                   
                   reader.onload = function(e) {
                       const col = document.createElement('div');
                       col.className = 'col-md-4 col-6 mb-3';
                       
                       const card = document.createElement('div');
                       card.className = 'card h-100';
                       
                       const img = document.createElement('img');
                       img.src = e.target.result;
                       img.className = 'card-img-top';
                       img.style.height = '150px';
                       img.style.objectFit = 'cover';
                       
                       const cardBody = document.createElement('div');
                       cardBody.className = 'card-body p-2';
                       
                       const text = document.createElement('p');
                       text.className = 'card-text small text-center mb-0';
                       text.textContent = 'Image ' + (index + 1);
                       
                       cardBody.appendChild(text);
                       card.appendChild(img);
                       card.appendChild(cardBody);
                       col.appendChild(card);
                       previewContainer.appendChild(col);
                   };
                   
                   reader.readAsDataURL(file);
               });
               
               // Update file input if more than 5 files selected
               if (this.files.length > 5) {
                   alert('Maximum 5 images allowed. Only the first 5 will be processed.');
               }
           }
       });
   }
   
   // Edit room modal data binding
   const editRoomButtons = document.querySelectorAll('.edit-room-btn');
   if (editRoomButtons) {
       editRoomButtons.forEach(button => {
           button.addEventListener('click', function() {
               const roomId = this.getAttribute('data-room-id');
               const roomType = this.getAttribute('data-room-type');
               const capacity = this.getAttribute('data-capacity');
               const price = this.getAttribute('data-price');
               const description = this.getAttribute('data-description');
               const available = this.getAttribute('data-available');
               
               document.getElementById('edit_room_type_id').value = roomId;
               document.getElementById('edit_room_type').value = roomType;
               document.getElementById('edit_capacity').value = capacity;
               document.getElementById('edit_price').value = price;
               document.getElementById('edit_description').value = description;
               document.getElementById('edit_available_count').value = available;
           });
       });
   }
});
</script>