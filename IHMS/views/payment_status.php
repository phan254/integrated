<?php
// Set page title
$page_title = "Payment Status | IHMS";

// Check if user is logged in as student
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$student_id = $_SESSION['user_id'];

// Include payment handler
require_once 'payment_handler.php';

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Check if booking belongs to student
$booking_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.hostel_id, rt.room_type, u.phone 
                                    FROM bookings b 
                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    JOIN users u ON b.student_id = u.user_id
                                    WHERE b.booking_id = $booking_id AND b.student_id = $student_id");

if(mysqli_num_rows($booking_query) == 0) {
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}

$booking = mysqli_fetch_assoc($booking_query);

// Process payment
if(isset($_POST['make_payment'])) {
    $phone = trim($_POST['phone']);
    $amount = $booking['amount'];
    
    // Call payment handler function
    $payment_result = initiate_mpesa_payment($phone, $amount, $booking_id, $student_id);
    
    if($payment_result['status'] == 'success') {
        $success_message = "Payment has been initiated. Please check your phone for an M-Pesa prompt.";
        
        // Refresh booking data
        $booking_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, h.hostel_id, rt.room_type 
                                            FROM bookings b 
                                            JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                            JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                            WHERE b.booking_id = $booking_id AND b.student_id = $student_id");
        $booking = mysqli_fetch_assoc($booking_query);
    } else {
        $error_message = $payment_result['message'];
    }
}

// Get payment history for this booking
$payments_query = mysqli_query($conn, "SELECT * FROM payments WHERE booking_id = $booking_id ORDER BY payment_date DESC");
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Payment Status</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Booking Details</h5>
                            <p><strong>Hostel:</strong> <?php echo $booking['hostel_name']; ?></p>
                            <p><strong>Room Type:</strong> <?php echo $booking['room_type']; ?></p>
                            <p><strong>Check-in Date:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Information</h5>
                            <p><strong>Amount Due:</strong> KSh <?php echo number_format($booking['amount'], 2); ?></p>
                            <p><strong>Status:</strong> 
                                <?php if($booking['payment_status'] == 'unpaid'): ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php elseif($booking['payment_status'] == 'partial'): ?>
                                    <span class="badge bg-warning text-dark">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if($booking['payment_status'] != 'paid'): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Make Payment</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">M-Pesa Phone Number</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $booking['phone']; ?>" required>
                                        <div class="form-text">Enter the phone number to receive M-Pesa payment request</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">KSh</span>
                                            <input type="text" id="amount" class="form-control" value="<?php echo number_format($booking['amount'], 2); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="make_payment" class="btn btn-success">Pay with M-Pesa</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-4">Payment History</h5>
                    <?php if(mysqli_num_rows($payments_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
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