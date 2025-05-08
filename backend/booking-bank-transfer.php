<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/room-functions.php';

// Check if booking exists
if (!isset($_SESSION['booking_id'])) {
    header('Location: rooms.php');
    exit;
}

$bookingId = $_SESSION['booking_id'];
$booking = getBookingById($bookingId);

// Verify booking exists and is the correct payment type
if (!$booking || $booking['payment_method'] != 'bank_transfer') {
    header('Location: rooms.php');
    exit;
}

// Get room details
$room = getRoomById($booking['room_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Instructions - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Bank Transfer Instructions</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-4">
                            <h5><i class="fas fa-check-circle me-2"></i> Booking Received</h5>
                            <p>Your booking has been received and is pending payment confirmation.</p>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <h5>Booking Summary</h5>
                            <p>
                                <strong>Booking ID:</strong> #<?php echo $bookingId; ?><br>
                                <strong>Room:</strong> <?php echo $room['name']; ?><br>
                                <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?><br>
                                <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?><br>
                                <strong>Total Amount:</strong> $<?php echo number_format($booking['total_price'], 2); ?>
                            </p>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Bank Transfer Details</h5>
                            </div>
                            <div class="card-body">
                                <p>Please transfer the total amount to the following bank account:</p>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Bank Name</th>
                                        <td>Example Bank</td>
                                    </tr>
                                    <tr>
                                        <th>Account Name</th>
                                        <td>Restaurant Management</td>
                                    </tr>
                                    <tr>
                                        <th>Account Number</th>
                                        <td>1234567890</td>
                                    </tr>
                                    <tr>
                                        <th>Sort Code / Routing Number</th>
                                        <td>123456</td>
                                    </tr>
                                    <tr>
                                        <th>Reference</th>
                                        <td>BOOKING-<?php echo $bookingId; ?></td>
                                    </tr>
                                </table>
                                <div class="alert alert-warning mt-3">
                                    <strong>Important:</strong> Please include the reference number in your payment to help us identify your booking.
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <p>After making the payment, you can check your booking status at any time.</p>
                            <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn btn-primary">View Booking Details</a>
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
