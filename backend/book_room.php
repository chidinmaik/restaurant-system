<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Ensure room ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$roomId = (int)$_GET['id'];
$room = getProductById($roomId);

// Validate room exists and is in "Rooms" category
$roomsCategoryId = getCategoryIdByName('Rooms');
if (!$room || $room['category_id'] != $roomsCategoryId || !$room['in_stock']) {
    header("Location: index.php");
    exit;
}

// Handle form submission
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['user_name'] ?? '');
    $userEmail = trim($_POST['user_email'] ?? '');
    $checkInDate = $_POST['check_in_date'] ?? '';
    $checkOutDate = $_POST['check_out_date'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    // Basic validation
    if (empty($userName)) {
        $errors[] = "Name is required.";
    }
    if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($checkInDate)) {
        $errors[] = "Check-in date is required.";
    }
    if (empty($checkOutDate)) {
        $errors[] = "Check-out date is required.";
    }
    if (!in_array($paymentMethod, ['stripe', 'bank_transfer', 'kora_card', 'kora_bank', 'flutterwave'])) {
        $errors[] = "Invalid payment method selected.";
    }
    
    // Validate dates
    $today = date('Y-m-d');
    $checkIn = strtotime($checkInDate);
    $checkOut = strtotime($checkOutDate);
    if ($checkIn < strtotime($today)) {
        $errors[] = "Check-in date cannot be in the past.";
    }
    if ($checkOut <= $checkIn) {
        $errors[] = "Check-out date must be after check-in date.";
    }
    
    // Calculate total amount (price per night * number of nights)
    $nights = ($checkOut - $checkIn) / (60 * 60 * 24);
    $totalAmount = $room['price'] * $nights;
    
    if (empty($errors)) {
        // Create booking
        $bookingData = [
            'room_id' => $roomId,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod
        ];
        $bookingId = createBooking($bookingData);
        
        if ($bookingId) {
            if ($paymentMethod === 'stripe') {
                // Stripe payment
                require 'vendor/autoload.php';
                \Stripe\Stripe::setApiKey('sk_test_51O6GqXFoJ9z5Yg3h...'); // Replace with your Stripe secret key
                
                try {
                    $checkoutSession = \Stripe\Checkout\Session::create([
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'NGN',
                                'product_data' => [
                                    'name' => $room['name'] . ' Booking',
                                ],
                                'unit_amount' => $totalAmount * 100, // Amount in cents
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => 'http://localhost/rest/booking_success.php?booking_id=' . $bookingId,
                        'cancel_url' => 'http://localhost/rest/book_room.php?id=' . $roomId,
                        'metadata' => ['booking_id' => $bookingId]
                    ]);
                    header("Location: " . $checkoutSession->url);
                    exit;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $errors[] = "Payment processing failed: " . $e->getMessage();
                }
            } elseif ($paymentMethod === 'bank_transfer') {
                // Bank transfer instructions
                $_SESSION['booking_id'] = $bookingId;
                header("Location: bank_transfer.php?booking_id=$bookingId");
                exit;
            } elseif ($paymentMethod === 'kora_card' || $paymentMethod === 'kora_bank') {
                // Kora payment (card or bank transfer)
                $koraApiKey = 'your_kora_api_key'; // Replace with your Kora API key
                $currency = 'NGN';
                $totalAmountNgn = $totalAmount * 1600; // Convert USD to NGN (approx.)
                
                $koraData = [
                    'amount' => $totalAmountNgn * 100, // Kora expects kobo
                    'currency' => $currency,
                    'description' => $room['name'] . ' Booking',
                    'customer' => [
                        'name' => $userName,
                        'email' => $userEmail
                    ],
                    'redirect_url' => 'http://localhost/rest/booking_success.php?booking_id=' . $bookingId,
                    'metadata' => ['booking_id' => $bookingId]
                ];
                
                $endpoint = $paymentMethod === 'kora_card' ? 'charges/card' : 'charges/bank';
                $response = makeKoraApiRequest($endpoint, $koraData, $koraApiKey);
                
                if ($response && isset($response['data']['checkout_url'])) {
                    header("Location: " . $response['data']['checkout_url']);
                    exit;
                } else {
                    $errors[] = "Kora payment initiation failed. Please try again.";
                }
            } elseif ($paymentMethod === 'flutterwave') {
                // Flutterwave payment
                require 'vendor/autoload.php';
                $flutterwave = new \Flutterwave\Flutterwave([
                    'publicKey' => 'FLWPUBK_TEST-c153e9c97003c9a11627adcdc1e46805-X', // Replace with your Flutterwave public key
                    'secretKey' => 'FLWSECK_TEST-9eb4faf11ab305e90366db8f5b0af83d-X', // Replace with your Flutterwave secret key
                    'encryptionKey' => 'FLWSECK_TEST178b436f0444' // Replace with your Flutterwave encryption key
                ]);
                
                try {
                    $paymentData = [
                        'tx_ref' => 'BOOKING_' . $bookingId . '_' . time(),
                        'amount' => $totalAmount * 1600, // Convert USD to NGN (approx.)
                        'currency' => 'NGN',
                        'payment_options' => 'card,banktransfer,ussd,mobilemoney',
                        'redirect_url' => 'http://localhost/rest/booking_success.php?booking_id=' . $bookingId,
                        'meta' => ['booking_id' => $bookingId],
                        'customer' => [
                            'email' => $userEmail,
                            'name' => $userName
                        ],
                        'customizations' => [
                            'title' => $room['name'] . ' Booking',
                            'description' => 'Payment for room booking'
                        ]
                    ];
                    
                    $response = \Flutterwave\Flutterwave::createPayment($paymentData);
                    if ($response['status'] === 'success' && isset($response['data']['link'])) {
                        header("Location: " . $response['data']['link']);
                        exit;
                    } else {
                        $errors[] = "Flutterwave payment initiation failed: " . ($response['message'] ?? 'Unknown error');
                    }
                } catch (Exception $e) {
                    $errors[] = "Flutterwave payment processing failed: " . $e->getMessage();
                }
            }
        } else {
            $errors[] = "Failed to create booking. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container py-4">
        <h2 class="mb-4">Book Room: <?php echo htmlspecialchars($room['name']); ?></h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo getRoomImageUrl($room['image']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($room['name']); ?>" style="max-height: 300px; object-fit: cover;">
                <h5 class="mt-3 text-orange">$<?php echo number_format($room['price'], 2); ?> / night</h5>
            </div>
            <div class="col-md-6">
                <form method="POST" class="bg-dark p-4 rounded shadow-sm">
                    <div class="mb-3">
                        <label for="user_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control bg-dark text-light border-gray" id="user_name" name="user_name" value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email</label>
                        <input type="email" class="form-control bg-dark text-light border-gray" id="user_email" name="user_email" value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="check_in_date" class="form-label">Check-In Date</label>
                        <input type="date" class="form-control bg-dark text-light border-gray" id="check_in_date" name="check_in_date" value="<?php echo htmlspecialchars($_POST['check_in_date'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="check_out_date" class="form-label">Check-Out Date</label>
                        <input type="date" class="form-control bg-dark text-light border-gray" id="check_out_date" name="check_out_date" value="<?php echo htmlspecialchars($_POST['check_out_date'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-control bg-dark text-light border-gray" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="stripe">Credit/Debit Card (Stripe)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="kora_card">Kora Card (Nigeria)</option>
                            <option value="kora_bank">Kora Bank Transfer (Nigeria)</option>
                            <option value="flutterwave">Flutterwave (Card, Bank, Mobile Money, USSD)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-orange w-100">Proceed to Payment</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>