<?php
// Set page title
$page_title = "University Admin Dashboard | IHMS";

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Get unread messages count
$unread_messages_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $admin_id AND read_status = 0");
$unread_messages = mysqli_fetch_assoc($unread_messages_query)['count'];

// Get total users count by type
$students_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'");
$students_count = mysqli_fetch_assoc($students_query)['count'];

$managers_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_type = 'hostel_manager' AND status = 'active'");
$managers_count = mysqli_fetch_assoc($managers_query)['count'];

// Get total hostels count
$hostels_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM hostels WHERE status = 'active'");
$hostels_count = mysqli_fetch_assoc($hostels_query)['count'];

// Get total bookings count
$bookings_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings");
$bookings_count = mysqli_fetch_assoc($bookings_query)['count'];

// Get pending hostel approvals
$pending_hostels_query = mysqli_query($conn, "SELECT h.*, u.full_name as manager_name, u.email as manager_email 
                                           FROM hostels h 
                                           JOIN users u ON h.manager_id = u.user_id 
                                           WHERE h.status = 'pending'");
$pending_hostels_count = mysqli_num_rows($pending_hostels_query);

// Get pending review approvals
$pending_reviews_query = mysqli_query($conn, "SELECT r.*, h.hostel_name, u.full_name as student_name 
                                           FROM reviews r 
                                           JOIN hostels h ON r.hostel_id = h.hostel_id 
                                           JOIN users u ON r.student_id = u.user_id 
                                           WHERE r.status = 'pending'");
$pending_reviews_count = mysqli_num_rows($pending_reviews_query);

// Handle hostel approval
if(isset($_GET['approve_hostel']) && !empty($_GET['approve_hostel'])) {
    $hostel_id = (int)$_GET['approve_hostel'];
    $update_hostel = mysqli_query($conn, "UPDATE hostels SET status = 'active' WHERE hostel_id = $hostel_id");
    
    if($update_hostel) {
        $success_message = "Hostel approved successfully.";
        
        // Reload pending hostels
        $pending_hostels_query = mysqli_query($conn, "SELECT h.*, u.full_name as manager_name, u.email as manager_email 
                                                 FROM hostels h 
                                                 JOIN users u ON h.manager_id = u.user_id 
                                                 WHERE h.status = 'pending'");
        $pending_hostels_count = mysqli_num_rows($pending_hostels_query);
    } else {
        $error_message = "Failed to approve hostel. Please try again.";
    }
}

// Handle hostel rejection
if(isset($_GET['reject_hostel']) && !empty($_GET['reject_hostel'])) {
    $hostel_id = (int)$_GET['reject_hostel'];
    $update_hostel = mysqli_query($conn, "UPDATE hostels SET status = 'deregistered' WHERE hostel_id = $hostel_id");
    
    if($update_hostel) {
        $success_message = "Hostel rejected successfully.";
        
        // Reload pending hostels
        $pending_hostels_query = mysqli_query($conn, "SELECT h.*, u.full_name as manager_name, u.email as manager_email 
                                                 FROM hostels h 
                                                 JOIN users u ON h.manager_id = u.user_id 
                                                 WHERE h.status = 'pending'");
        $pending_hostels_count = mysqli_num_rows($pending_hostels_query);
    } else {
        $error_message = "Failed to reject hostel. Please try again.";
    }
}

// Handle review approval
if(isset($_GET['approve_review']) && !empty($_GET['approve_review'])) {
    $review_id = (int)$_GET['approve_review'];
    $update_review = mysqli_query($conn, "UPDATE reviews SET status = 'approved' WHERE review_id = $review_id");
    
    if($update_review) {
        $success_message = "Review approved successfully.";
        
        // Reload pending reviews
        $pending_reviews_query = mysqli_query($conn, "SELECT r.*, h.hostel_name, u.full_name as student_name 
                                                 FROM reviews r 
                                                 JOIN hostels h ON r.hostel_id = h.hostel_id 
                                                 JOIN users u ON r.student_id = u.user_id 
                                                 WHERE r.status = 'pending'");
        $pending_reviews_count = mysqli_num_rows($pending_reviews_query);
    } else {
        $error_message = "Failed to approve review. Please try again.";
    }
}

// Handle review rejection
if(isset($_GET['reject_review']) && !empty($_GET['reject_review'])) {
    $review_id = (int)$_GET['reject_review'];
    $update_review = mysqli_query($conn, "UPDATE reviews SET status = 'rejected' WHERE review_id = $review_id");
    
    if($update_review) {
        $success_message = "Review rejected successfully.";
        
        // Reload pending reviews
        $pending_reviews_query = mysqli_query($conn, "SELECT r.*, h.hostel_name, u.full_name as student_name 
                                                 FROM reviews r 
                                                 JOIN hostels h ON r.hostel_id = h.hostel_id 
                                                 JOIN users u ON r.student_id = u.user_id 
                                                 WHERE r.status = 'pending'");
        $pending_reviews_count = mysqli_num_rows($pending_reviews_query);
    } else {
        $error_message = "Failed to reject review. Please try again.";
    }
}

// Handle user suspension/activation
if(isset($_GET['suspend_user']) && !empty($_GET['suspend_user'])) {
    $user_id = (int)$_GET['suspend_user'];
    $update_user = mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE user_id = $user_id");
    
    if($update_user) {
        $success_message = "User suspended successfully.";
    } else {
        $error_message = "Failed to suspend user. Please try again.";
    }
}

if(isset($_GET['activate_user']) && !empty($_GET['activate_user'])) {
    $user_id = (int)$_GET['activate_user'];
    $update_user = mysqli_query($conn, "UPDATE users SET status = 'active' WHERE user_id = $user_id");
    
    if($update_user) {
        $success_message = "User activated successfully.";
    } else {
        $error_message = "Failed to activate user. Please try again.";
    }
}

// Handle hostel deregistration
if(isset($_GET['deregister_hostel']) && !empty($_GET['deregister_hostel'])) {
    $hostel_id = (int)$_GET['deregister_hostel'];
    $update_hostel = mysqli_query($conn, "UPDATE hostels SET status = 'deregistered' WHERE hostel_id = $hostel_id");
    
    if($update_hostel) {
        $success_message = "Hostel deregistered successfully.";
    } else {
        $error_message = "Failed to deregister hostel. Please try again.";
    }
}

// Get recent bookings
$recent_bookings_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, u.full_name as student_name, rt.room_type 
                                          FROM bookings b 
                                          JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                          JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                          JOIN users u ON b.student_id = u.user_id 
                                          ORDER BY b.booking_date DESC LIMIT 5");

// Get university settings
$settings_query = mysqli_query($conn, "SELECT * FROM university_settings");
$settings = [];
while($setting = mysqli_fetch_assoc($settings_query)) {
    $settings[$setting['setting_name']] = $setting['setting_value'];
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
                <a href="<?php echo BASE_URL; ?>?page=manage_users" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="list-group-item list-group-item-action">
                    <i class="fas fa-hotel me-2"></i> Manage Hostels
                </a>
                <a href="<?php echo BASE_URL; ?>?page=reports" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <a href="<?php echo BASE_URL; ?>?page=settings" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
                    <?php if($unread_messages > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
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
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>  Admin Dashboard</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo $_SESSION['full_name']; ?>!</h5>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <!-- Overview Stats -->
                    <div class="row mt-4">
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Students</h6>
                                            <h2 class="mb-0"><?php echo $students_count; ?></h2>
                                            <small>Registered students</small>
                                        </div>
                                        <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-success h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Hostel Managers</h6>
                                            <h2 class="mb-0"><?php echo $managers_count; ?></h2>
                                            <small>Active managers</small>
                                        </div>
                                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-info h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Hostels</h6>
                                            <h2 class="mb-0"><?php echo $hostels_count; ?></h2>
                                            <small>Active hostels</small>
                                        </div>
                                        <i class="fas fa-building fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-warning h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Bookings</h6>
                                            <h2 class="mb-0"><?php echo $bookings_count; ?></h2>
                                            <small>Total bookings</small>
                                        </div>
                                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Approvals -->
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Pending Hostel Approvals</h5>
                                    <?php if($pending_hostels_count > 0): ?>
                                        <span class="badge bg-danger"><?php echo $pending_hostels_count; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if($pending_hostels_count > 0): ?>
                                        <div class="list-group">
                                            <?php while($hostel = mysqli_fetch_assoc($pending_hostels_query)): ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between mb-1">
                                                        <h6 class="mb-1"><?php echo $hostel['hostel_name']; ?></h6>
                                                        <small><?php echo date('M d, Y', strtotime($hostel['created_at'])); ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <small>
                                                            <i class="fas fa-map-marker-alt text-secondary"></i> <?php echo $hostel['address']; ?>, <?php echo $hostel['city']; ?>
                                                        </small>
                                                    </p>
                                                    <p class="mb-1">
                                                        <small>
                                                            <i class="fas fa-user text-secondary"></i> Manager: <?php echo $hostel['manager_name']; ?> (<?php echo $hostel['manager_email']; ?>)
                                                        </small>
                                                    </p>
                                                    <div class="d-flex mt-2">
                                                        <a href="<?php echo BASE_URL; ?>?page=dashboard&approve_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-success me-2">Approve</a>
                                                        <a href="<?php echo BASE_URL; ?>?page=dashboard&reject_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i> No pending hostel approvals.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Pending Review Approvals</h5>
                                    <?php if($pending_reviews_count > 0): ?>
                                        <span class="badge bg-danger"><?php echo $pending_reviews_count; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if($pending_reviews_count > 0): ?>
                                        <div class="list-group">
                                            <?php while($review = mysqli_fetch_assoc($pending_reviews_query)): ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between mb-1">
                                                        <h6 class="mb-1">Review for <?php echo $review['hostel_name']; ?></h6>
                                                        <small><?php echo date('M d, Y', strtotime($review['review_date'])); ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <small>
                                                            <i class="fas fa-user text-secondary"></i> By: <?php echo $review['student_name']; ?>
                                                        </small>
                                                    </p>
                                                    <div class="mb-1">
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <p class="mb-1"><?php echo nl2br($review['review_text']); ?></p>
                                                    <div class="d-flex mt-2">
                                                        <a href="<?php echo BASE_URL; ?>?page=dashboard&approve_review=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-success me-2">Approve</a>
                                                        <a href="<?php echo BASE_URL; ?>?page=dashboard&reject_review=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i> No pending review approvals.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Bookings</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(mysqli_num_rows($recent_bookings_query) > 0): ?>
                                        <div class="list-group">
                                            <?php while($booking = mysqli_fetch_assoc($recent_bookings_query)): ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between mb-1">
                                                        <h6 class="mb-1"><?php echo $booking['student_name']; ?></h6>
                                                        <small><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <small>
                                                            <i class="fas fa-hotel text-secondary"></i> <?php echo $booking['hostel_name']; ?> - <?php echo $booking['room_type']; ?>
                                                        </small>
                                                    </p>
                                                    <p class="mb-1">
                                                        <small>
                                                            <i class="fas fa-calendar-alt text-secondary"></i> Check-in: <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                                                        </small>
                                                    </p>
                                                    <p class="mb-0">
                                                        <span class="badge <?php 
                                                            if($booking['status'] == 'pending') echo 'bg-warning text-dark';
                                                            elseif($booking['status'] == 'confirmed') echo 'bg-success';
                                                            elseif($booking['status'] == 'cancelled') echo 'bg-danger';
                                                            else echo 'bg-info';
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i> No recent bookings.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">University Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="<?php echo BASE_URL; ?>?page=settings">
                                        <div class="mb-3">
                                            <label class="form-label">University Name</label>
                                            <input type="text" class="form-control" name="university_name" value="<?php echo isset($settings['university_name']) ? $settings['university_name'] : ''; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" name="contact_email" value="<?php echo isset($settings['contact_email']) ? $settings['contact_email'] : ''; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Minimum Hostel Standards</label>
                                            <textarea class="form-control" name="min_hostel_standards" rows="3" readonly><?php echo isset($settings['min_hostel_standards']) ? $settings['min_hostel_standards'] : ''; ?></textarea>
                                        </div>
                                        
                                        <a href="<?php echo BASE_URL; ?>?page=settings" class="btn btn-primary">Edit Settings</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Manage Users</h5>
                                    <p class="card-text">View, edit, or suspend user accounts. Add new administrators.</p>
                                    <a href="<?php echo BASE_URL; ?>?page=manage_users" class="btn btn-primary">Go to Users</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-hotel fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Manage Hostels</h5>
                                    <p class="card-text">View, approve, or deregister hostels. Set minimum standards.</p>
                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="btn btn-primary">Go to Hostels</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Reports</h5>
                                    <p class="card-text">Generate reports on bookings, occupancy rates, and user activity.</p>
                                    <a href="<?php echo BASE_URL; ?>?page=reports" class="btn btn-primary">View Reports</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>