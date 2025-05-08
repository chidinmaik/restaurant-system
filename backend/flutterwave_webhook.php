<?php
include 'includes/config.php';
include 'includes/functions.php';

// Verify webhook signature
$secretHash = 'wpf_flutterwave_listener'; // Replace with your Flutterwave webhook secret hash
$signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';
if ($signature !== $secretHash) {
    http_response_code(401);
    exit;
}

// Get payload
$payload = @file_get_contents('php://input');
$data = json_decode($payload, true);

if ($data['event'] === 'charge.completed' && $data['data']['status'] === 'successful') {
    $bookingId = $data['data']['meta']['booking_id'] ?? null;
    if ($bookingId) {
        updateBookingPaymentStatus($bookingId, 'completed');
    }
}

http_response_code(200);
?>