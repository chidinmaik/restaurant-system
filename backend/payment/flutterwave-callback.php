<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../includes/config.php';
include '../includes/functions.php';
include '../includes/admin-room-functions.php';
include '../includes/payment-config.php';
include '../includes/payment-functions.php';

// Define SITE_NAME if it's not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Restaurant Management');
}

// Log the callback data
error_log('Flutterwave Callback Data: ' . json_encode($_GET));

// Check if transaction ID is provided
if (!isset($_GET['transaction_id']) || !isset($_GET['tx_ref']) || !isset($_GET['status'])) {
    $_SESSION['error_msg'] = 'Invalid payment callback data.';
    header('Location: ../rooms.php');
    exit;
}

$transactionId = $_GET['transaction_id'];
$txRef = $_GET['tx_ref'];
$status = $_GET['status'];

// Extract booking ID from transaction reference
$bookingId = null;
if (preg_match('/ROOM-\d+-(\d+)/', $txRef, $matches)) {
    $bookingId = $matches[1];
} else {
    $_SESSION['error_msg'] = 'Invalid transaction reference.';
    header('Location: ../rooms.php');
    exit;
}

// Always use test API for now
$useTestAPI = true;

// Check payment status
if ($status === 'successful') {
    // Verify payment
    $verificationResponse = verifyFlutterwavePayment($transactionId, !$useTestAPI);
    
    if ($verificationResponse['status'] === 'success' && 
        isset($verificationResponse['data']['status']) && 
        $verificationResponse['data']['status'] === 'successful') {
        
        // Process payment
        $success = processFlutterwavePayment([
            'tx_ref' => $txRef,
            'transaction_id' => $transactionId,
            'amount' => $verificationResponse['data']['amount'] ?? 0,
            'currency' => $verificationResponse['data']['currency'] ?? 'USD'
        ]);
        
        if ($success) {
            $_SESSION['success_msg'] = 'Payment successful! Your booking has been confirmed.';
        } else {
            $_SESSION['error_msg'] = 'Payment was successful, but we could not update your booking. Please contact support.';
        }
    } else {
        $_SESSION['error_msg'] = 'Payment verification failed. Please contact support.';
    }
} else {
    // Payment failed or was cancelled
    $_SESSION['error_msg'] = 'Payment was not successful. Please try again.';
    
    // Update booking status
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE room_bookings SET payment_status = 'failed' WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
}

// Redirect to booking confirmation page
header('Location: ../booking/confirmation.php?id=' . $bookingId);
exit;