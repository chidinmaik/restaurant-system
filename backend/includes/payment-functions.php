<?php
// Payment integration functions

/**
 * Initialize Flutterwave payment
 * 
 * @param array $bookingData Booking data
 * @param bool $isLive Whether to use live or test environment
 * @return array Payment initialization response
 */
function initializeFlutterwavePayment($bookingData, $isLive = false) {
    // Set API keys based on environment
    $publicKey = $isLive ? FLUTTERWAVE_LIVE_PUBLIC_KEY : FLUTTERWAVE_TEST_PUBLIC_KEY;
    $secretKey = $isLive ? FLUTTERWAVE_LIVE_SECRET_KEY : FLUTTERWAVE_TEST_SECRET_KEY;
    
    // Log the environment and keys (mask the secret key for security)
    error_log('Flutterwave Environment: ' . ($isLive ? 'LIVE' : 'TEST'));
    error_log('Flutterwave Public Key: ' . $publicKey);
    error_log('Flutterwave Secret Key: ' . substr($secretKey, 0, 4) . '...' . substr($secretKey, -4));
    
    // Check if API keys are set
    if (empty($secretKey) || empty($publicKey)) {
        error_log('Flutterwave API keys are not configured');
        return [
            'status' => 'error',
            'message' => 'Flutterwave API keys are not configured'
        ];
    }
    
    // Generate unique transaction reference
    $txRef = 'ROOM-' . time() . '-' . $bookingData['id'];
    
    // Prepare payment data
    $paymentData = [
        'tx_ref' => $txRef,
        'amount' => $bookingData['total_price'],
        'currency' => 'NGN', // Change to your preferred currency
        'payment_options' => 'card,banktransfer',
        'redirect_url' => SITE_URL . '/payment/flutterwave-callback.php',
        'customer' => [
            'email' => $bookingData['customer_email'],
            'name' => $bookingData['customer_name'],
            'phone_number' => $bookingData['customer_phone'] ?? ''
        ],
        'meta' => [
            'booking_id' => $bookingData['id'],
            'room_id' => $bookingData['room_id'],
            'check_in' => $bookingData['check_in_date'],
            'check_out' => $bookingData['check_out_date']
        ],
        'customizations' => [
            'title' => SITE_NAME . ' - Room Booking',
            'description' => 'Payment for room booking',
            'logo' => SITE_URL . '/assets/images/logo.png' // Update with your logo path
        ]
    ];
    
    // Log the payment data
    error_log('Flutterwave Payment Data: ' . json_encode($paymentData));
    
    // Initialize cURL session
    $curl = curl_init();
    
    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($paymentData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true, // Set to false only for testing if you have SSL issues
    ]);
    
    // Execute cURL request
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    // Log cURL info
    error_log('Flutterwave API HTTP Code: ' . $httpCode);
    
    // Close cURL session
    curl_close($curl);
    
    if ($err) {
        error_log('Flutterwave cURL Error: ' . $err);
        return [
            'status' => 'error',
            'message' => 'cURL Error: ' . $err
        ];
    }
    
    // Log the raw response
    error_log('Flutterwave API Raw Response: ' . $response);
    
    // Decode response
    $responseData = json_decode($response, true);
    
    // Check for API errors
    if ($httpCode >= 400 || !isset($responseData['status'])) {
        $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown API error';
        error_log('Flutterwave API Error: ' . $errorMessage);
        return [
            'status' => 'error',
            'message' => $errorMessage,
            'http_code' => $httpCode,
            'response' => $responseData
        ];
    }
    
    // Update booking with transaction reference
    if ($responseData['status'] === 'success') {
        error_log('Flutterwave payment initialized successfully. Transaction ID: ' . $txRef);
        $conn = getDbConnection();
        $stmt = $conn->prepare("UPDATE room_bookings SET transaction_id = ? WHERE id = ?");
        $stmt->bind_param("si", $txRef, $bookingData['id']);
        $stmt->execute();
    }
    
    return $responseData;
}

// Rest of the functions remain the same...