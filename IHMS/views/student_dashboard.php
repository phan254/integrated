<?php
// Set page title
$page_title = "Student Dashboard | IHMS";

// Check if user is logged in as student
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student bookings
$bookings_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.address, h.city, h.hostel_id,
                                      rt.room_type, rt.price, u.full_name as manager_name 
                                      FROM bookings b 
                                      JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                      JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                      JOIN users u ON h.manager_id = u.user_id 
                                      WHERE b.student_id = $student_id 
                                      ORDER BY b.booking_date DESC");

// Handle booking cancellation
if(isset($_GET['cancel_booking']) && !empty($_GET['cancel_booking'])) {
    $booking_id = (int)$_GET['cancel_booking'];
    
    // Check if booking belongs to student
    $check_booking = mysqli_query($conn, "SELECT b.*, rt.room_type_id, rt.hostel_id 
                                        FROM bookings b 
                                        JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                        WHERE b.booking_id = $booking_id AND b.student_id = $student_id 
                                        AND b.status != 'cancelled'");
    
    if(mysqli_num_rows($check_booking) > 0) {
        $booking_data = mysqli_fetch_assoc($check_booking);
        $room_type_id = $booking_data['room_type_id'];
        $hostel_id = $booking_data['hostel_id'];
        
        // Update booking status
        $cancel_booking = mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE booking_id = $booking_id");
        
        if($cancel_booking) {
            // Update available room count
            mysqli_query($conn, "UPDATE room_types SET available_count = available_count + 1 WHERE room_type_id = $room_type_id");
            mysqli_query($conn, "UPDATE hostels SET available_rooms = available_rooms + 1 WHERE hostel_id = $hostel_id");
            
            $success_message = "Booking cancelled successfully.";
            
            // Reload booking data
            $bookings_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.address, h.city, h.hostel_id,
                                                rt.room_type, rt.price, u.full_name as manager_name 
                                                FROM bookings b 
                                                JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                                JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                                JOIN users u ON h.manager_id = u.user_id 
                                                WHERE b.student_id = $student_id 
                                                ORDER BY b.booking_date DESC");
        } else {
            $error_message = "Failed to cancel booking. Please try again.";
        }
    } else {
        $error_message = "Invalid booking or booking already cancelled.";
    }
}

// Get unread messages count
$unread_messages_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $student_id AND read_status = 0");
$unread_messages = mysqli_fetch_assoc($unread_messages_query)['count'];

// Get payment information
$payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE student_id = $student_id AND status = 'completed'");
$payments_count = mysqli_fetch_assoc($payments_query)['count'];
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>?page=profile" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
                    <?php if($unread_messages > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>?page=hostels" class="list-group-item list-group-item-action">
                    <i class="fas fa-search me-2"></i> Find Hostels
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
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> Student Dashboard</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo $_SESSION['full_name']; ?>!</h5>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <!-- Dashboard Stats -->
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Active Bookings</h6>
                                            <?php
                                            $active_bookings = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE student_id = $student_id AND (status = 'confirmed' OR status = 'pending')");
                                            $active_bookings_count = mysqli_fetch_assoc($active_bookings)['count'];
                                            ?>
                                            <h2 class="display-4"><?php echo $active_bookings_count; ?></h2>
                                        </div>
                                        <i class="fas fa-bed fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Completed Payments</h6>
                                            <h2 class="display-4"><?php echo $payments_count; ?></h2>
                                        </div>
                                        <i class="fas fa-money-bill-wave fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Messages</h6>
                                            <h2 class="display-4"><?php echo $unread_messages; ?></h2>
                                            <p class="mb-0">Unread messages</p>
                                        </div>
                                        <i class="fas fa-envelope fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Bookings -->
                    <div class="mt-4">
                        <h4>My Bookings</h4>
                        
                        <?php if(mysqli_num_rows($bookings_query) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Hostel</th>
                                            <th>Room Type</th>
                                            <th>Check-in Date</th>
                                            <th>Monthly Rent</th>
                                            <th>Booking Date</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($booking = mysqli_fetch_assoc($bookings_query)): ?>
                                            <tr>
                                                <td>#<?php echo $booking['booking_id']; ?></td>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $booking['hostel_id']; ?>">
                                                        <?php echo $booking['hostel_name']; ?>
                                                    </a>
                                                    <div class="small text-muted"><?php echo $booking['address']; ?>, <?php echo $booking['city']; ?></div>
                                                </td>
                                                <td><?php echo $booking['room_type']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                <td>KSh <?php echo number_format($booking['amount'], 2); ?></td>
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
                                                    <?php if($booking['payment_status'] == 'unpaid'): ?>
                                                        <span class="badge bg-danger">Unpaid</span>
                                                    <?php elseif($booking['payment_status'] == 'partial'): ?>
                                                        <span class="badge bg-warning text-dark">Partial</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Paid</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                                        <a href="<?php echo BASE_URL; ?>?page=dashboard&cancel_booking=<?php echo $booking['booking_id']; ?>" 
                                                           class="btn btn-sm btn-danger cancel-booking mb-1">
                                                            Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($booking['payment_status'] == 'unpaid' && $booking['status'] != 'cancelled'): ?>
                                                        <a href="<?php echo BASE_URL; ?>?page=payment&booking_id=<?php echo $booking['booking_id']; ?>" 
                                                           class="btn btn-sm btn-success mb-1">
                                                            Pay Now
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> You don't have any bookings yet.
                                <a href="<?php echo BASE_URL; ?>?page=hostels" class="alert-link">Browse hostels</a> to make a booking.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Payment History -->
                    <div class="mt-4">
                        <h4>Recent Payments</h4>
                        <?php
                        $payments_history_query = mysqli_query($conn, "SELECT p.*, b.booking_id, h.hostel_name, rt.room_type 
                                                              FROM payments p 
                                                              JOIN bookings b ON p.booking_id = b.booking_id 
                                                              JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                                              JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                                              WHERE p.student_id = $student_id 
                                                              ORDER BY p.payment_date DESC LIMIT 5");
                        
                        if(mysqli_num_rows($payments_history_query) > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Booking</th>
                                            <th>Hostel</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($payment = mysqli_fetch_assoc($payments_history_query)): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td>#<?php echo $payment['booking_id']; ?></td>
                                                <td>
                                                    <?php echo $payment['hostel_name']; ?>
                                                    <div class="small text-muted"><?php echo $payment['room_type']; ?></div>
                                                </td>
                                                <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                <td><small><?php echo $payment['transaction_ref']; ?></small></td>
                                                <td>
                                                    <?php if($payment['status'] == 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif($payment['status'] == 'completed'): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> You don't have any payment records yet.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Find Hostels</h5>
                                    <p class="card-text">Browse and filter available hostels near your university.</p>
                                    <a href="<?php echo BASE_URL; ?>?page=hostels" class="btn btn-primary">Search Now</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Messages</h5>
                                    <p class="card-text">
                                        Check your messages from hostel managers.
                                        <?php if($unread_messages > 0): ?>
                                            <span class="badge bg-danger"><?php echo $unread_messages; ?> unread</span>
                                        <?php endif; ?>
                                    </p>
                                    <a href="<?php echo BASE_URL; ?>?page=messages" class="btn btn-primary">View Messages</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Profile</h5>
                                    <p class="card-text">Update your profile information and preferences.</p>
                                    <a href="<?php echo BASE_URL; ?>?page=profile" class="btn btn-primary">Edit Profile</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>