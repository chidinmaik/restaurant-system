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

// Debug session data (uncomment to debug)
// echo '<pre>Session: '; print_r($_SESSION); echo '</pre>'; exit;

// Check if booking data is available
if (!isset($_SESSION['booking_data'])) {
    $_SESSION['error_msg'] = 'Booking data not found. Please try again.';
    header('Location: ../rooms.php');
    exit;
}

$bookingData = $_SESSION['booking_data'];

// Validate booking data
if (!isset($bookingData['id']) || !isset($bookingData['room_id']) || !isset($bookingData['total_price'])) {
    $_SESSION['error_msg'] = 'Invalid booking data. Please try again.';
    header('Location: ../rooms.php');
    exit;
}

// Check if payment method is selected
if (!isset($_POST['payment_method'])) {
    $_SESSION['error_msg'] = 'Please select a payment method.';
    header('Location: ../booking/checkout.php');
    exit;
}

$paymentMethod = $_POST['payment_method'];

// Process payment based on method
switch ($paymentMethod) {
    case 'flutterwave':
        try {
            // Log the attempt
            error_log('Attempting to initialize Flutterwave payment for booking ID: ' . $bookingData['id']);
            
            // Always use test API for now
            $useTestAPI = true;
            
            // Initialize Flutterwave payment
            $response = initializeFlutterwavePayment($bookingData, !$useTestAPI);
            
            // Log the response
            error_log('Flutterwave response: ' . json_encode($response));
            
            if ($response['status'] === 'success') {
                // Redirect to Flutterwave payment page
                $redirectUrl = $response['data']['link'];
                error_log('Redirecting to Flutterwave: ' . $redirectUrl);
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                // Payment initialization failed
                $errorMessage = isset($response['message']) ? $response['message'] : 'Unknown error';
                error_log('Flutterwave initialization failed: ' . $errorMessage);
                $_SESSION['error_msg'] = 'Payment initialization failed: ' . $errorMessage;
                header('Location: ../booking/checkout.php');
                exit;
            }
        } catch (Exception $e) {
            // Log the exception
            error_log('Exception during Flutterwave initialization: ' . $e->getMessage());
            $_SESSION['error_msg'] = 'An error occurred: ' . $e->getMessage();
            header('Location: ../booking/checkout.php');
            exit;
        }
        break;
        
    case 'bank_transfer':
        // Process bank transfer
        $conn = getDbConnection();
        $stmt = $conn->prepare("UPDATE room_bookings SET payment_method = 'bank_transfer', payment_status = 'pending', status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $bookingData['id']);
        
        if ($stmt->execute()) {
            // Redirect to confirmation page
            header('Location: ../booking/confirmation.php?id=' . $bookingData['id']);
            exit;
        } else {
            $_SESSION['error_msg'] = 'Failed to process bank transfer. Please try again.';
            header('Location: ../booking/checkout.php');
            exit;
        }
        break;
        
    default:
        $_SESSION['error_msg'] = 'Invalid payment method.';
        header('Location: ../booking/checkout.php');
        exit;
}

// If we get here, something went wrong
$_SESSION['error_msg'] = 'An unexpected error occurred. Please try again.';
header('Location: ../booking/checkout.php');
exit;