<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/admin-room-functions.php';
include 'includes/payment-config.php';
include 'includes/payment-functions.php';

// Check if booking data is available
if (!isset($_SESSION['booking_data'])) {
    $_SESSION['error_msg'] = 'Booking data not found. Please try again.';
    header('Location: rooms.php');
    exit;
}

$bookingData = $_SESSION['booking_data'];

// Validate booking data
if (!isset($bookingData['id']) || !isset($bookingData['room_id']) || !isset($bookingData['total_price'])) {
    $_SESSION['error_msg'] = 'Invalid booking data. Please try again.';
    header('Location: rooms.php');
    exit;
}

// Process Flutterwave payment
if (isset($_POST['pay_now'])) {
    $response = initializeFlutterwavePayment($bookingData, USE_LIVE_PAYMENT);
    
    if ($response['status'] === 'success') {
        header('Location: ' . $response['data']['link']);
        exit;
    } else {
        $_SESSION['error_msg'] = 'Payment initialization failed: ' . ($response['message'] ?? 'Unknown error');
    }
}

// Get room details
$room = getRoomById($bookingData['room_id']);

// Calculate number of nights
$checkInDate = new DateTime($bookingData['check_in_date']);
$checkOutDate = new DateTime($bookingData['check_out_date']);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flutterwave Payment - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/booking-flutterwave.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card payment-card">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0">Complete Your Payment</h3>
                    </div>
                    <div class="card-body">
                        <!-- Alert Messages -->
                        <?php if (isset($_SESSION['error_msg'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo htmlspecialchars($_SESSION['error_msg']); 
                                unset($_SESSION['error_msg']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <h4 class="mb-3">Booking Summary</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p>
                                    <strong>Room:</strong> <?php echo htmlspecialchars($room['name']); ?><br>
                                    <strong>Type:</strong> <?php echo htmlspecialchars($room['type_name']); ?><br>
                                    <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($bookingData['check_in_date'])); ?><br>
                                    <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($bookingData['check_out_date'])); ?><br>
                                    <strong>Nights:</strong> <?php echo $nights; ?><br>
                                    <strong>Guests:</strong> <?php echo $bookingData['adults']; ?> adults, <?php echo $bookingData['children']; ?> children
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($room['primary_image'])): ?>
                                    <img src="<?php echo htmlspecialchars('Uploads/rooms/' . $room['primary_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($room['name']); ?>" 
                                         class="img-fluid rounded">
                                <?php else: ?>
                                    <img src="assets/images/no-room-image.jpg" 
                                         alt="No Image Available" 
                                         class="img-fluid rounded">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body bg-light">
                                <h5 class="mb-3">Payment Details</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td>Room Rate</td>
                                        <td class="text-end">NGN<?php echo number_format($room['price'], 2); ?> x <?php echo $nights; ?> nights</td>
                                    </tr>
                                    <?php 
                                    $extraGuests = max(0, ($bookingData['adults'] + $bookingData['children']) - $room['standard_occupancy']);
                                    $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
                                    if ($extraGuestFee > 0):
                                    ?>
                                    <tr>
                                        <td>Extra Guest Fee</td>
                                        <td class="text-end">NGN<?php echo number_format($extraGuestFee, 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end">NGN<?php echo number_format($bookingData['total_price'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <form method="post" action="">
                            <div class="d-grid gap-2">
                                <button type="submit" name="pay_now" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Pay with Flutterwave
                                </button>
                                <a href="booking.php?room_id=<?php echo $bookingData['room_id']; ?>" class="btn btn-outline-secondary">Back to Booking</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>