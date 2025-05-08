<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/admin-room-functions.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: rooms.php');
    exit;
}

$bookingId = intval($_GET['id']);
$booking = getBookingById($bookingId);

// Check if booking exists
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
    <title>Booking Confirmation - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_msg']; 
                        unset($_SESSION['success_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0"><i class="fas fa-check-circle me-2"></i>Booking Confirmation</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4>Thank you for your booking!</h4>
                            <p class="lead">Your booking has been <?php echo $booking['payment_status'] === 'paid' ? 'confirmed' : 'received'; ?>.</p>
                            <p>Booking ID: <strong><?php echo $booking['id']; ?></strong></p>
                            <p>A confirmation email has been sent to <strong><?php echo $booking['customer_email']; ?></strong></p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Booking Details</h5>
                                <p>
                                    <strong>Room:</strong> <?php echo $room['name']; ?><br>
                                    <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?><br>
                                    <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?><br>
                                    <strong>Nights:</strong> <?php echo $nights; ?><br>
                                    <strong>Guests:</strong> <?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Payment Information</h5>
                                <p>
                                    <strong>Total Amount:</strong> $<?php echo number_format($booking['total_price'], 2); ?><br>
                                    <strong>Payment Method:</strong> 
                                    <?php
                                    switch ($booking['payment_method']) {
                                        case 'flutterwave':
                                            echo 'Flutterwave';
                                            break;
                                        case 'bank_transfer':
                                            echo 'Bank Transfer';
                                            break;
                                        default:
                                            echo $booking['payment_method'];
                                    }
                                    ?>
                                    <br>
                                    <strong>Payment Status:</strong> 
                                    <?php if ($booking['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($booking['payment_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($booking['payment_method'] === 'bank_transfer' && $booking['payment_status'] === 'pending'): ?>
                                    <div class="alert alert-info">
                                        <h6>Bank Transfer Details</h6>
                                        <p class="mb-0">
                                            Bank: Your Bank Name<br>
                                            Account Name: Your Account Name<br>
                                            Account Number: Your Account Number<br>
                                            Reference: ROOM-<?php echo $booking['id']; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary">Return to Home</a>
                        </div>
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