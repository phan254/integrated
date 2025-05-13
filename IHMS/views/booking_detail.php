<?php
// Set page title
$page_title = "Booking Details | IHMS";

// Check if user is logged in as student
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if booking belongs to student
$booking_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.address, h.city, h.hostel_id, 
                                    rt.room_type, rt.capacity, rt.price, u.full_name as manager_name, u.email as manager_email, 
                                    u.phone as manager_phone, h.manager_id
                                    FROM bookings b 
                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    JOIN users u ON h.manager_id = u.user_id 
                                    WHERE b.booking_id = $booking_id AND b.student_id = $student_id");

if(mysqli_num_rows($booking_query) == 0) {
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}

$booking = mysqli_fetch_assoc($booking_query);

// Get messages related to this booking
$messages_query = mysqli_query($conn, "SELECT m.*, u.full_name, u.user_type 
                                    FROM messages m 
                                    JOIN users u ON m.sender_id = u.user_id 
                                    WHERE (m.sender_id = $student_id OR m.receiver_id = $student_id) 
                                    AND m.subject LIKE '%Booking #$booking_id%' 
                                    ORDER BY m.sent_at DESC 
                                    LIMIT 10");

// Get payment history
$payments_query = mysqli_query($conn, "SELECT * FROM payments WHERE booking_id = $booking_id ORDER BY payment_date DESC");
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Booking Details #<?php echo $booking_id; ?></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Booking Information</h5>
                            <p><strong>Status:</strong> 
                                <?php if($booking['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($booking['status'] == 'confirmed'): ?>
                                    <span class="badge bg-success">Confirmed</span>
                                <?php elseif($booking['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php elseif($booking['status'] == 'completed'): ?>
                                    <span class="badge bg-info">Completed</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Booking Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                            <p><strong>Check-in Date:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                            <?php if($booking['check_out_date']): ?>
                                <p><strong>Check-out Date:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
                            <?php endif; ?>
                            <p><strong>Monthly Rent:</strong> KSh <?php echo number_format($booking['amount'], 2); ?></p>
                            <p><strong>Payment Status:</strong> 
                                <?php if($booking['payment_status'] == 'unpaid'): ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php elseif($booking['payment_status'] == 'partial'): ?>
                                    <span class="badge bg-warning text-dark">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Hostel Information</h5>
                            <p><strong>Hostel Name:</strong> 
                                <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $booking['hostel_id']; ?>">
                                    <?php echo $booking['hostel_name']; ?>
                                </a>
                            </p>
                            <p><strong>Address:</strong> <?php echo $booking['address']; ?>, <?php echo $booking['city']; ?></p>
                            <p><strong>Room Type:</strong> <?php echo $booking['room_type']; ?></p>
                            <p><strong>Capacity:</strong> <?php echo $booking['capacity']; ?> person(s)</p>
                            <p><strong>Manager:</strong> <?php echo $booking['manager_name']; ?></p>
                            <p><strong>Contact:</strong> 
                                <?php echo $booking['manager_email']; ?>
                                <?php if($booking['manager_phone']): ?>
                                    <br><?php echo $booking['manager_phone']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                            <a href="<?php echo BASE_URL; ?>?page=dashboard&cancel_booking=<?php echo $booking_id; ?>" 
                               class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                <i class="fas fa-times-circle me-1"></i> Cancel Booking
                            </a>
                        <?php endif; ?>
                        
                        <?php if($booking['payment_status'] == 'unpaid' && $booking['status'] != 'cancelled'): ?>
                            <a href="<?php echo BASE_URL; ?>?page=payment_status&booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                                <i class="fas fa-credit-card me-1"></i> Make Payment
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $booking['manager_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-envelope me-1"></i> Message Hostel Manager
                        </a>
                    </div>
                    
                    <!-- Payments -->
                    <h5 class="mt-4">Payment History</h5>
                    <?php if(mysqli_num_rows($payments_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Transaction Reference</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($payment = mysqli_fetch_assoc($payments_query)): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                            <td><?php echo $payment['transaction_ref']; ?></td>
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
                        <div class="alert alert-info">No payment records found.</div>
                    <?php endif; ?>
                    
                    <!-- Related Messages -->
                    <h5 class="mt-4">Related Messages</h5>
                    <?php if(mysqli_num_rows($messages_query) > 0): ?>
                        <div class="list-group">
                            <?php while($message = mysqli_fetch_assoc($messages_query)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php if($message['sender_id'] == $student_id): ?>
                                                <span class="text-primary">You</span> to <?php echo $message['full_name']; ?>
                                            <?php else: ?>
                                                <?php echo $message['full_name']; ?> to <span class="text-primary">You</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small><?php echo date('M d, Y h:i A', strtotime($message['sent_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><strong><?php echo $message['subject']; ?></strong></p>
                                    <p class="mb-1"><?php echo nl2br($message['message_text']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No related messages found.</div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?php echo BASE_URL; ?>?page=dashboard" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>