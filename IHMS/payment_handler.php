<?php
// Prevent direct access
if(!defined('BASE_URL')) {
    // Load the essential files
    require_once 'config/db_config.php';
    define('BASE_URL', 'http://localhost/IHMS/');
}

// Set production mode (false for sandbox/testing, true for live)
define('MPESA_PRODUCTION', false);

// API endpoints
define('MPESA_AUTH_URL', MPESA_PRODUCTION ? 
    'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 
    'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

define('MPESA_STK_URL', MPESA_PRODUCTION ? 
    'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 
    'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

// M-Pesa credentials - Replace with your actual credentials
define('MPESA_CONSUMER_KEY', 'O0Xx3kukf1MFtGwUANPeWpirNWdXwqURWT8iDeDdAox0DyD5');
define('MPESA_CONSUMER_SECRET', 'r0XXbC5AAH6V52EjhnIR9DH1rGscAZMX5PttA8a0buS1Yq2nKrCErNGJuMa7Xei4');
define('MPESA_SHORTCODE', '174379');  // Business shortcode from Safaricom
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');      // Provided by Safaricom
define('MPESA_CALLBACK_URL', BASE_URL . 'payment_callback.php');

/**
 * Get M-Pesa access token
 * 
 * @return string Access token for M-Pesa API
 */
function get_mpesa_access_token() {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    
    $ch = curl_init(MPESA_AUTH_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        // Log curl error
        error_log('CURL Error in M-Pesa Auth: ' . curl_error($ch));
        return null;
    }
    
    curl_close($ch);
    
    $result = json_decode($response);
    
    if(isset($result->access_token)) {
        return $result->access_token;
    }
    
    // Log error if no access token
    error_log('Failed to get M-Pesa access token: ' . $response);
    return null;
}

/**
 * Initiate M-Pesa payment
 * 
 * @param string $phone_number User's phone number
 * @param float $amount Amount to pay
 * @param int $booking_id Booking ID
 * @param int $student_id Student ID
 * @return array Response with status and message
 */
function initiate_mpesa_payment($phone_number, $amount, $booking_id, $student_id) {
    global $conn;
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname(__FILE__) . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Format phone number (ensure it starts with 254)
    $phone_number = preg_replace('/^0/', '254', $phone_number);
    if(!preg_match('/^254/', $phone_number)) {
        $phone_number = '254' . $phone_number;
    }
    
    // Generate timestamp
    $timestamp = date('YmdHis');
    
    // Generate password
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    // Generate a unique transaction reference
    $transaction_ref = 'IHMS' . time() . rand(100, 999);
    $account_ref = 'IHMS' . $booking_id;
    
    // Prepare request data
    $data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => round($amount),  // M-Pesa requires whole numbers
        'PartyA' => $phone_number,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone_number,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $account_ref,
        'TransactionDesc' => 'Hostel Booking Payment'
    ];
    
    // Log request data
    file_put_contents($log_dir . '/mpesa_request_' . date('Y-m-d') . '.log', 
                     date('Y-m-d H:i:s') . ': ' . json_encode($data) . PHP_EOL, FILE_APPEND);
    
    // Get access token
    $access_token = get_mpesa_access_token();
    
    if(!$access_token) {
        return [
            'status' => 'error',
            'message' => 'Failed to get access token from M-Pesa'
        ];
    }
    
    // Send request to M-Pesa
    $ch = curl_init(MPESA_STK_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        // Log curl error
        file_put_contents($log_dir . '/mpesa_error_' . date('Y-m-d') . '.log', 
                         date('Y-m-d H:i:s') . ': CURL Error - ' . curl_error($ch) . PHP_EOL, FILE_APPEND);
        
        curl_close($ch);
        
        return [
            'status' => 'error',
            'message' => 'Connection error while contacting M-Pesa'
        ];
    }
    
    curl_close($ch);
    
    // Log response
    file_put_contents($log_dir . '/mpesa_response_' . date('Y-m-d') . '.log', 
                     date('Y-m-d H:i:s') . ': ' . $response . PHP_EOL, FILE_APPEND);
    
    $result = json_decode($response, true);
    
    // Save request data to database
    $request_data = json_encode([
        'phone' => $phone_number,
        'amount' => $amount,
        'reference' => $transaction_ref,
        'request' => $data,
        'response' => $result
    ]);
    
    // Insert payment request record
    $sql = "INSERT INTO payment_requests 
            (booking_id, student_id, amount, payment_method, transaction_ref, request_data, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if($stmt) {
        $checkout_request_id = isset($result['CheckoutRequestID']) ? $result['CheckoutRequestID'] : $transaction_ref;
        $status = 'pending';
        $payment_method = 'mpesa';
        
        mysqli_stmt_bind_param($stmt, "iidssss", 
                              $booking_id, 
                              $student_id, 
                              $amount, 
                              $payment_method, 
                              $checkout_request_id, 
                              $request_data, 
                              $status);
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Check if the request was successful
        if(isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
            return [
                'status' => 'success',
                'message' => 'Payment initiated. Please check your phone for the M-Pesa prompt.',
                'transaction_ref' => $checkout_request_id
            ];
        } else {
            $error_message = isset($result['errorMessage']) ? $result['errorMessage'] : 'Failed to initiate payment';
            return [
                'status' => 'error',
                'message' => $error_message
            ];
        }
    } else {
        return [
            'status' => 'error',
            'message' => 'Database error while saving payment request'
        ];
    }
}

/**
 * Check M-Pesa payment status
 * 
 * @param string $transaction_ref Transaction reference
 * @return array Payment status information
 */
function check_payment_status($transaction_ref) {
    global $conn;
    
    $query = mysqli_query($conn, "SELECT * FROM payment_requests WHERE transaction_ref = '$transaction_ref'");
    
    if(mysqli_num_rows($query) > 0) {
        $request = mysqli_fetch_assoc($query);
        
        // Check if payment has been processed
        $payment_query = mysqli_query($conn, "SELECT * FROM payments WHERE booking_id = " . $request['booking_id'] . " ORDER BY payment_date DESC LIMIT 1");
        
        if(mysqli_num_rows($payment_query) > 0) {
            $payment = mysqli_fetch_assoc($payment_query);
            
            return [
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'transaction_ref' => $payment['transaction_ref'],
                'payment_date' => $payment['payment_date']
            ];
        }
        
        // If no payment found, return request status
        return [
            'status' => $request['status'],
            'amount' => $request['amount'],
            'transaction_ref' => $request['transaction_ref'],
            'created_at' => $request['created_at']
        ];
    }
    
    return [
        'status' => 'not_found',
        'message' => 'Transaction reference not found'
    ];
}

/**
 * Update payment status
 * 
 * @param string $transaction_ref Transaction reference
 * @param string $status New status
 * @param int $booking_id Booking ID
 * @return bool Success or failure
 */
function update_payment_status($transaction_ref, $status, $booking_id) {
    global $conn;
    
    // Update payment request status
    mysqli_query($conn, "UPDATE payment_requests SET status = '$status' WHERE transaction_ref = '$transaction_ref'");
    
    if($status == 'completed') {
        // Get payment request data
        $request_query = mysqli_query($conn, "SELECT * FROM payment_requests WHERE transaction_ref = '$transaction_ref'");
        
        if(mysqli_num_rows($request_query) > 0) {
            $request = mysqli_fetch_assoc($request_query);
            
            // Check if payment already exists
            $existing_payment = mysqli_query($conn, "SELECT * FROM payments WHERE transaction_ref = '$transaction_ref'");
            
            if(mysqli_num_rows($existing_payment) == 0) {
                // Insert into payments table
                $sql = "INSERT INTO payments 
                        (booking_id, student_id, amount, payment_method, transaction_ref, status) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $sql);
                
                if($stmt) {
                    $payment_status = 'completed';
                    
                    mysqli_stmt_bind_param($stmt, "iidsss", 
                                        $request['booking_id'], 
                                        $request['student_id'], 
                                        $request['amount'], 
                                        $request['payment_method'], 
                                        $transaction_ref, 
                                        $payment_status);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        // Update booking payment status
                        mysqli_query($conn, "UPDATE bookings SET payment_status = 'paid' WHERE booking_id = " . $request['booking_id']);
                        mysqli_stmt_close($stmt);
                        return true;
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            } else {
                // Payment already exists, just update booking status
                mysqli_query($conn, "UPDATE bookings SET payment_status = 'paid' WHERE booking_id = " . $request['booking_id']);
                return true;
            }
        }
    }
    
    return false;
}
?>