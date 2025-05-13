<?php
/**
 * M-Pesa Payment Callback Handler
 * This file receives callbacks from M-Pesa API after payment is processed
 */

// Include database connection and necessary files
require_once 'config/db_config.php';
define('BASE_URL', 'http://localhost/IHMS/');
require_once 'payment_handler.php';

// Create logs directory if it doesn't exist
$log_dir = dirname(__FILE__) . '/logs';
if(!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log callback data for debugging
$callback_data = file_get_contents('php://input');
file_put_contents($log_dir . '/mpesa_callback_' . date('Y-m-d') . '.log', 
                 date('Y-m-d H:i:s') . ': ' . $callback_data . PHP_EOL, FILE_APPEND);

// Parse the JSON response
$callback = json_decode($callback_data, true);

// Process callback data if present
if(isset($callback['Body']) && isset($callback['Body']['stkCallback'])) {
    $result = $callback['Body']['stkCallback'];
    $result_code = $result['ResultCode'];
    $checkout_request_id = $result['CheckoutRequestID'];
    
    // Get payment request from database
    $request_query = mysqli_query($conn, "SELECT * FROM payment_requests WHERE transaction_ref = '$checkout_request_id'");
    
    if(mysqli_num_rows($request_query) > 0) {
        $request = mysqli_fetch_assoc($request_query);
        $booking_id = $request['booking_id'];
        $student_id = $request['student_id'];
        $amount = $request['amount'];
        
        // Update payment request status and response data
        $status = ($result_code === 0) ? 'completed' : 'failed';
        $escaped_callback_data = mysqli_real_escape_string($conn, $callback_data);
        mysqli_query($conn, "UPDATE payment_requests SET status = '$status', response_data = '$escaped_callback_data' WHERE transaction_ref = '$checkout_request_id'");
        
        if($result_code === 0) {
            // Payment successful
            // Extract the payment details from callback data
            $transaction_details = $result['CallbackMetadata']['Item'];
            $mpesa_receipt = '';
            $mpesa_amount = 0;
            $mpesa_date = '';
            $phone_number = '';
            
            foreach($transaction_details as $item) {
                if($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesa_receipt = $item['Value'];
                }
                if($item['Name'] === 'Amount') {
                    $mpesa_amount = $item['Value'];
                }
                if($item['Name'] === 'TransactionDate') {
                    $mpesa_date = $item['Value'];
                }
                if($item['Name'] === 'PhoneNumber') {
                    $phone_number = $item['Value'];
                }
            }
            
            // Check if payment already exists
            $existing_payment = mysqli_query($conn, "SELECT * FROM payments WHERE transaction_ref = '$mpesa_receipt'");
            
            if(mysqli_num_rows($existing_payment) == 0) {
                // Insert into payments table
                $insert_query = "INSERT INTO payments 
                                (booking_id, student_id, amount, payment_method, transaction_ref, status) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $insert_query);
                
                if($stmt) {
                    $payment_method = 'mpesa';
                    $payment_status = 'completed';
                    
                    mysqli_stmt_bind_param($stmt, "iidsss", 
                                          $booking_id, 
                                          $student_id, 
                                          $mpesa_amount, 
                                          $payment_method, 
                                          $mpesa_receipt, 
                                          $payment_status);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        // Update booking payment status
                        mysqli_query($conn, "UPDATE bookings SET payment_status = 'paid' WHERE booking_id = $booking_id");
                        
                        // Log successful payment
                        file_put_contents($log_dir . '/payment_success_' . date('Y-m-d') . '.log', 
                                         date('Y-m-d H:i:s') . ': Payment completed for booking #' . $booking_id . 
                                         ' - Amount: ' . $mpesa_amount . ' - Receipt: ' . $mpesa_receipt . PHP_EOL, 
                                         FILE_APPEND);
                    } else {
                        // Log payment insertion error
                        file_put_contents($log_dir . '/payment_error_' . date('Y-m-d') . '.log', 
                                         date('Y-m-d H:i:s') . ': Failed to insert payment - ' . 
                                         mysqli_error($conn) . PHP_EOL, FILE_APPEND);
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            } else {
                // Payment already recorded, just log it
                file_put_contents($log_dir . '/payment_duplicate_' . date('Y-m-d') . '.log', 
                                 date('Y-m-d H:i:s') . ': Duplicate payment callback for transaction: ' . 
                                 $mpesa_receipt . PHP_EOL, FILE_APPEND);
            }
        } else {
            // Payment failed
            $result_desc = isset($result['ResultDesc']) ? $result['ResultDesc'] : 'Unknown error';
            
            // Log payment failure
            file_put_contents($log_dir . '/payment_failure_' . date('Y-m-d') . '.log', 
                             date('Y-m-d H:i:s') . ': Payment failed for booking #' . $booking_id . 
                             ' - Code: ' . $result_code . ' - Desc: ' . $result_desc . PHP_EOL, 
                             FILE_APPEND);
        }
    } else {
        // Log unknown transaction
        file_put_contents($log_dir . '/payment_unknown_' . date('Y-m-d') . '.log', 
                         date('Y-m-d H:i:s') . ': Callback received for unknown transaction: ' . 
                         $checkout_request_id . PHP_EOL, FILE_APPEND);
    }
} else {
    // Log invalid callback format
    file_put_contents($log_dir . '/payment_invalid_' . date('Y-m-d') . '.log', 
                     date('Y-m-d H:i:s') . ': Invalid callback format received' . PHP_EOL, 
                     FILE_APPEND);
}

// Send response to M-Pesa - always acknowledge receipt to prevent retries
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
?>