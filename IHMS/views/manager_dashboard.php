<?php
// Set page title
$page_title = "Hostel Manager Dashboard | IHMS";

// Check if user is logged in as hostel manager
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'hostel_manager') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$manager_id = $_SESSION['user_id'];

// Get manager's hostel(s)
$hostels_query = mysqli_query($conn, "SELECT * FROM hostels WHERE manager_id = $manager_id");
$has_hostel = mysqli_num_rows($hostels_query) > 0;

// Get unread messages count
$unread_messages_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $manager_id AND read_status = 0");
$unread_messages = mysqli_fetch_assoc($unread_messages_query)['count'];

// Get pending bookings count
$pending_bookings_query = mysqli_query($conn, "SELECT COUNT(*) as count 
                                            FROM bookings b 
                                            JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                            JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                            WHERE h.manager_id = $manager_id AND b.status = 'pending'");
$pending_bookings = mysqli_fetch_assoc($pending_bookings_query)['count'];

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
        if($action == 'confirm') {
            $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'confirmed' WHERE booking_id = $booking_id");
            if($update_booking) {
                $success_message = "Booking confirmed successfully.";
            } else {
                $error_message = "Failed to confirm booking. Please try again.";
            }
        } elseif($action == 'cancel') {
            $booking_data = mysqli_fetch_assoc($check_booking);
            $room_type_id = $booking_data['room_type_id'];
            
            // Get hostel id for updating available rooms
            $hostel_query = mysqli_query($conn, "SELECT hostel_id FROM room_types WHERE room_type_id = $room_type_id");
            $hostel_id = mysqli_fetch_assoc($hostel_query)['hostel_id'];
            
            $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE booking_id = $booking_id");
            
            if($update_booking) {
                // Update available room count
                mysqli_query($conn, "UPDATE room_types SET available_count = available_count + 1 WHERE room_type_id = $room_type_id");
                mysqli_query($conn, "UPDATE hostels SET available_rooms = available_rooms + 1 WHERE hostel_id = $hostel_id");
                
                $success_message = "Booking cancelled successfully.";
            } else {
                $error_message = "Failed to cancel booking. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid booking.";
    }
}

// Get all bookings for manager's hostels
$bookings_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.hostel_id, u.full_name as student_name, u.email as student_email, 
                                    u.phone as student_phone, rt.room_type 
                                    FROM bookings b 
                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    JOIN users u ON b.student_id = u.user_id 
                                    WHERE h.manager_id = $manager_id 
                                    ORDER BY b.booking_date DESC");

// Check if there's a file upload for hostel images
if(isset($_POST['upload_images']) && isset($_POST['hostel_id'])) {
    $hostel_id = (int)$_POST['hostel_id'];
    
    // Check if hostel belongs to manager
    $check_hostel = mysqli_query($conn, "SELECT * FROM hostels WHERE hostel_id = $hostel_id AND manager_id = $manager_id");
    
    if(mysqli_num_rows($check_hostel) > 0) {
        // Check if files were uploaded
        if(isset($_FILES['hostel_images']) && !empty($_FILES['hostel_images']['name'][0])) {
            $uploaded = false;
            $upload_dir = 'uploads/';
            
            // Create upload directory if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Process each uploaded file
            foreach($_FILES['hostel_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['hostel_images']['name'][$key];
                $file_size = $_FILES['hostel_images']['size'][$key];
                $file_tmp = $_FILES['hostel_images']['tmp_name'][$key];
                $file_type = $_FILES['hostel_images']['type'][$key];
                
                // Generate unique file name
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = 'hostel_' . $hostel_id . '_' . uniqid() . '.' . $file_extension;
                
                // Check if file is an image
                $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
                if(in_array($file_type, $allowed_types)) {
                    // Move file to upload directory
                    if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                        // Insert file info into database
                        $is_primary = (isset($_POST['primary_image']) && $_POST['primary_image'] == $key) ? 1 : 0;
                        
                        // If this is set as primary, update other images to not be primary
                        if($is_primary) {
                            mysqli_query($conn, "UPDATE hostel_images SET is_primary = 0 WHERE hostel_id = $hostel_id");
                        }
                        
                        mysqli_query($conn, "INSERT INTO hostel_images (hostel_id, image_path, is_primary) VALUES ($hostel_id, '$new_file_name', $is_primary)");
                        $uploaded = true;
                    }
                }
            }
            
            if($uploaded) {
                $success_message = "Images uploaded successfully.";
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
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <?php if($has_hostel): ?>
                    <a href="<?php echo BASE_URL; ?>?page=manage_hostel" class="list-group-item list-group-item-action">
                        <i class="fas fa-hotel me-2"></i> Manage Hostel
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>?page=add_hostel" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Add Hostel
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
                    <?php if($unread_messages > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
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
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> Hostel Manager Dashboard</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo $_SESSION['full_name']; ?>!</h5>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!$has_hostel): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i> You haven't added a hostel yet. 
                            <a href="<?php echo BASE_URL; ?>?page=add_hostel" class="alert-link">Add your hostel now</a> to start receiving bookings.
                        </div>
                    <?php else: ?>
                        <!-- Hostels Summary -->
                        <div class="row mt-4">
                            <?php 
                            mysqli_data_seek($hostels_query, 0); // Reset query pointer
                            while($hostel = mysqli_fetch_assoc($hostels_query)): 
                                
                                // Get hostel stats
                                $hostel_id = $hostel['hostel_id'];
                                
                                // Get confirmed bookings count
                                $confirmed_query = mysqli_query($conn, "SELECT COUNT(*) as count 
                                                                    FROM bookings b 
                                                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                                                    WHERE rt.hostel_id = $hostel_id AND b.status = 'confirmed'");
                                $confirmed_count = mysqli_fetch_assoc($confirmed_query)['count'];
                                
                                // Get hostel image
                                $image_query = mysqli_query($conn, "SELECT image_path FROM hostel_images WHERE hostel_id = $hostel_id AND is_primary = 1 LIMIT 1");
                                $hostel_image = mysqli_num_rows($image_query) > 0 ? mysqli_fetch_assoc($image_query)['image_path'] : null;
                            ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <img src="<?php echo $hostel_image ? BASE_URL . 'uploads/' . $hostel_image : BASE_URL . 'assets/img/no-image.jpg'; ?>" 
                                                     class="img-fluid rounded-start h-100" alt="<?php echo $hostel['hostel_name']; ?>" style="object-fit: cover;">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo $hostel['hostel_name']; ?></h5>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> <?php echo $hostel['city']; ?>
                                                        </small>
                                                    </p>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="border-start border-4 border-success ps-2 mb-2">
                                                                <small class="text-muted">Available Rooms</small>
                                                                <h5 class="mb-0"><?php echo $hostel['available_rooms']; ?> / <?php echo $hostel['total_rooms']; ?></h5>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="border-start border-4 border-primary ps-2 mb-2">
                                                                <small class="text-muted">Total Bookings</small>
                                                                <h5 class="mb-0"><?php echo $confirmed_count; ?></h5>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostel&id=<?php echo $hostel_id; ?>" class="btn btn-primary btn-sm mt-2">Manage Hostel</a>
                                                    
                                                    <!-- Upload Images Button -->
                                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#uploadImagesModal<?php echo $hostel_id; ?>">
                                                        <i class="fas fa-images me-1"></i> Upload Images
                                                    </button>
                                                    
                                                    <!-- Upload Images Modal -->
                                                    <div class="modal fade" id="uploadImagesModal<?php echo $hostel_id; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Upload Hostel Images</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="post" enctype="multipart/form-data">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="hostel_images" class="form-label">Select Images</label>
                                                                            <input type="file" name="hostel_images[]" id="hostel_images" class="form-control" multiple accept="image/*" required>
                                                                            <div class="form-text">You can select multiple images. Max 5MB per image.</div>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Primary Image</label>
                                                                            <div class="form-text mb-2">Select which image should be the main image for this hostel.</div>
                                                                            <select name="primary_image" class="form-select">
                                                                                <option value="">Use existing primary image</option>
                                                                                <option value="0">First uploaded image</option>
                                                                                <option value="1">Second uploaded image</option>
                                                                                <option value="2">Third uploaded image</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div id="image_previews" class="row mt-3"></div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="upload_images" class="btn btn-primary">Upload Images</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Bookings Section -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4>Booking Requests</h4>
                                <?php if($pending_bookings > 0): ?>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo $pending_bookings; ?> Pending Requests
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(mysqli_num_rows($bookings_query) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Student</th>
                                                <th>Hostel</th>
                                                <th>Room Type</th>
                                                <th>Check-in Date</th>
                                                <th>Monthly Rent</th>
                                                <th>Booking Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($booking = mysqli_fetch_assoc($bookings_query)): ?>
                                                <tr>
                                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                                    <td>
                                                        <?php echo $booking['student_name']; ?>
                                                        <div class="small">
                                                            <a href="mailto:<?php echo $booking['student_email']; ?>"><?php echo $booking['student_email']; ?></a>
                                                            <?php if(!empty($booking['student_phone'])): ?>
                                                                <br><?php echo $booking['student_phone']; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $booking['hostel_id']; ?>">
                                                            <?php echo $booking['hostel_name']; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $booking['room_type']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                    <td>$<?php echo number_format($booking['amount'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                    <td>
                                                        <?php if($booking['status'] == 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php elseif($booking['status'] == 'confirmed'): ?>
                                                            <span class="badge bg-success">Confirmed</span>
                                                        <?php elseif($booking['status'] == 'cancelled'): ?>
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        <?php elseif($booking['status'] == 'completed'): ?>
                                                            <span class="badge bg-info">Completed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($booking['status'] == 'pending'): ?>
                                                            <a href="<?php echo BASE_URL; ?>?page=dashboard&booking_id=<?php echo $booking['booking_id']; ?>&action=confirm" class="btn btn-sm btn-success">Confirm</a>
                                                            <a href="<?php echo BASE_URL; ?>?page=dashboard&booking_id=<?php echo $booking['booking_id']; ?>&action=cancel" class="btn btn-sm btn-danger">Reject</a>
                                                        <?php elseif($booking['status'] == 'confirmed'): ?>
                                                            <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $booking['student_id']; ?>" class="btn btn-sm btn-primary">Message</a>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You don't have any booking requests yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Messages</h6>
                                            <h2 class="mb-0"><?php echo $unread_messages; ?></h2>
                                            <small>Unread messages</small>
                                        </div>
                                        <i class="fas fa-envelope fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <a href="<?php echo BASE_URL; ?>?page=messages" class="text-white text-decoration-none small">
                                        View Messages <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Pending Bookings</h6>
                                            <h2 class="mb-0"><?php echo $pending_bookings; ?></h2>
                                            <small>Waiting for your approval</small>
                                        </div>
                                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <a href="#" class="text-white text-decoration-none small">
                                        View Pending Bookings <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Available Rooms</h6>
                                            <?php
                                            // Calculate total available rooms across all hostels
                                            $total_available_query = mysqli_query($conn, "SELECT SUM(available_rooms) as total FROM hostels WHERE manager_id = $manager_id");
                                            $total_available = mysqli_fetch_assoc($total_available_query)['total'];
                                            
                                            // Calculate total rooms across all hostels
                                            $total_rooms_query = mysqli_query($conn, "SELECT SUM(total_rooms) as total FROM hostels WHERE manager_id = $manager_id");
                                            $total_rooms = mysqli_fetch_assoc($total_rooms_query)['total'];
                                            ?>
                                            <h2 class="mb-0"><?php echo $total_available; ?></h2>
                                            <small>Out of <?php echo $total_rooms; ?> total rooms</small>
                                        </div>
                                        <i class="fas fa-bed fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostel" class="text-white text-decoration-none small">
                                        Manage Rooms <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Preview images before upload
    document.addEventListener('DOMContentLoaded', function() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const previewContainer = this.parentElement.parentElement.querySelector('#image_previews');
                previewContainer.innerHTML = '';
                
                if (this.files) {
                    for (let i = 0; i < this.files.length; i++) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const imgDiv = document.createElement('div');
                            imgDiv.className = 'col-4 mb-2';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'img-thumbnail';
                            img.style.height = '100px';
                            img.style.objectFit = 'cover';
                            
                            imgDiv.appendChild(img);
                            previewContainer.appendChild(imgDiv);
                        }
                        
                        reader.readAsDataURL(this.files[i]);
                    }
                }
            });
        });
    });
</script>