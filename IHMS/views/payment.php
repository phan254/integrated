<?php
// Set page title
$page_title = "Pay Now | IHMS";

// Check if user is logged in as student
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Check if booking exists and belongs to the student
$booking_query = mysqli_query($conn, "SELECT b.*, h.hostel_name, rt.room_type 
                                    FROM bookings b 
                                    JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    WHERE b.booking_id = $booking_id 
                                    AND b.student_id = $student_id 
                                    AND b.payment_status != 'paid'");

if(mysqli_num_rows($booking_query) == 0) {
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}

$booking = mysqli_fetch_assoc($booking_query);
$amount = $booking['amount'];

// Get student details
$student_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $student_id");
$student = mysqli_fetch_assoc($student_query);

// Process payment form
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $phone_number = trim($_POST['phone_number']);
    
    if(empty($phone_number)) {
        $error_message = "Please enter your phone number.";
    } else {
        // Include payment handler
        require_once 'payment_handler.php';
        
        // Initiate payment
        $payment_result = initiate_mpesa_payment($phone_number, $amount, $booking_id, $student_id);
        
        if($payment_result['status'] == 'success') {
            $success_message = "Payment initiated successfully. You will receive an M-Pesa prompt on your phone.";
            
            // For simulation purposes, we'll mark the payment as successful immediately
            // In a production environment, this would be handled by the callback
            $update_payment = update_payment_status($payment_result['transaction_ref'], 'completed', $booking_id);
            
            if($update_payment) {
                $redirect_message = "Payment completed successfully. Redirecting to dashboard...";
                header("refresh:3;url=" . BASE_URL . "?page=dashboard");
            }
        } else {
            $error_message = $payment_result['message'];
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Make Payment</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($redirect_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $redirect_message; ?>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h5>Booking Details</h5>
                            <p><strong>Hostel:</strong> <?php echo $booking['hostel_name']; ?></p>
                            <p><strong>Room Type:</strong> <?php echo $booking['room_type']; ?></p>
                            <p><strong>Check-in Date:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                            <p><strong>Amount:</strong> <span class="text-primary fw-bold">KSh <?php echo number_format($amount, 2); ?></span></p>
                        </div>
                        
                        <h5 class="mb-3">Payment Method</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="mpesa" checked>
                                        <label class="form-check-label" for="mpesa">
                                            M-Pesa
                                        </label>
                                    </div>
                                    <div class="ms-3">
                                        <img src="<?php echo BASE_URL; ?>assets/img/mpesa-logo.png" alt="M-Pesa" height="30">
                                    </div>
                                </div>
                                <p class="small text-muted">You will receive an M-Pesa prompt on your phone to complete the payment.</p>
                            </div>
                        </div>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">+254</span>
                                    <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="7XXXXXXXX" value="<?php echo isset($student['phone']) ? ltrim($student['phone'], '+254') : ''; ?>" required>
                                </div>
                                <div class="form-text">Enter your M-Pesa registered phone number (format: 7XXXXXXXX)</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="make_payment" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i> Pay Now (KSh <?php echo number_format($amount, 2); ?>)
                                </button>
                                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Cancel Payment
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>