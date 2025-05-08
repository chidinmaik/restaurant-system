<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/room-functions.php';

// Check if booking ID is provided
if (!isset($_GET['id']) && !isset($_SESSION['booking_id'])) {
    header('Location: rooms.php');
    exit;
}

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['booking_id'];
$booking = getBookingById($bookingId);

// Verify booking exists
if (!$booking) {
    header('Location: rooms.php');
    exit;
}

// Get room details
$room = getRoomById($booking['room_id']);

// Calculate number of nights
$checkInDate = new DateTime($booking['check_in_date']);
$checkOutDate = new DateTime($booking['check_out_date']);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $bookingId; ?> - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Booking #<?php echo $bookingId; ?></h1>
            <a href="rooms.php" class="btn btn-outline-primary">Browse Rooms</a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Booking Details -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Booking Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Room Information</h5>
                                <div class="d-flex mb-3">
                                    <?php if (!empty($room['images'])): ?>
                                        <img src="<?php echo getRoomImageUrl($room['images'][0]['image_path']); ?>" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="<?php echo $room['name']; ?>">
                                    <?php else: ?>
                                        <img src="assets/images/no-room-image.jpg" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="No Image Available">
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="mb-1"><?php echo $room['name']; ?></h5>
                                        <p class="text-muted mb-0"><?php echo $room['type_name']; ?></p>
                                    </div>
                                </div>
                                <p><a href="room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary">View Room Details</a></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Stay Information</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Check-in</span>
                                        <span><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Check-out</span>
                                        <span><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Nights</span>
                                        <span><?php echo $nights; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Guests</span>
                                        <span><?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <h5>Price Details</h5>
                        <div class="alert alert-info mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>$<?php echo number_format($room['price'], 2); ?> x <?php echo $nights; ?> nights</span>
                                <span>$<?php echo number_format($room['price'] * $nights, 2); ?></span>
                            </div>
                            
                            <?php 
                            $extraGuests = max(0, ($booking['adults'] + $booking['children']) - $room['standard_occupancy']);
                            $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
                            if ($extraGuestFee > 0):
                            ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Extra guest fee (<?php echo $extraGuests; ?> guests)</span>
                                <span>$<?php echo number_format($extraGuestFee, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['special_requests'])): ?>
                            <h5>Special Requests</h5>
                            <p><?php echo nl2br($booking['special_requests']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Booking Status -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Booking Status</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Status
                                <span class="badge bg-<?php echo getStatusBadgeClass($booking['status']); ?>"><?php echo getStatusLabel($booking['status']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Payment Method
                                <span><?php echo getPaymentMethodLabel($booking['payment_method']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Payment Status
                                <span class="badge bg-<?php echo getPaymentStatusBadgeClass($booking['payment_status']); ?>"><?php echo getPaymentStatusLabel($booking['payment_status']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Booking Date
                                <span><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></span>
                            </li>
                        </ul>
                        
                        <?php if ($booking['payment_method'] == 'bank_transfer' && $booking['payment_status'] == 'pending'): ?>
                            <div class="alert alert-warning mt-3">
                                <h5>Bank Transfer Information</h5>
                                <p>Please make your payment to the following bank account:</p>
                                <p>
                                    <strong>Bank Name:</strong> Example Bank<br>
                                    <strong>Account Name:</strong> Restaurant Management<br>
                                    <strong>Account Number:</strong> 1234567890<br>
                                    <strong>Reference:</strong> Booking #<?php echo $bookingId; ?>
                                </p>
                                <p>Please include your booking number as reference.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Guest Information -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Guest Information</h4>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>Name:</strong> <?php echo $booking['customer_name']; ?><br>
                            <strong>Email:</strong> <?php echo $booking['customer_email']; ?><br>
                            <strong>Phone:</strong> <?php echo $booking['customer_phone']; ?>
                        </p>
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
