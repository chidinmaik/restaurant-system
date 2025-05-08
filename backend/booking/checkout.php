<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';
include '../includes/admin-room-functions.php';
include '../includes/payment-config.php';

// Define SITE_NAME if it's not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Restaurant Management');
}

// Check if booking data is available
if (!isset($_SESSION['booking_data'])) {
    header('Location: ../rooms.php');
    exit;
}

$bookingData = $_SESSION['booking_data'];
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
    <title>Room Booking Checkout - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Room Booking Checkout</h3>
                    </div>
                    <div class="card-body">
                        <!-- Alert Messages -->
                        <?php if (isset($_SESSION['error_msg'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error_msg']; 
                                unset($_SESSION['error_msg']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <h4>Booking Summary</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p>
                                    <strong>Room:</strong> <?php echo $room['name']; ?><br>
                                    <strong>Type:</strong> <?php echo $room['type_name']; ?><br>
                                    <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($bookingData['check_in_date'])); ?><br>
                                    <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($bookingData['check_out_date'])); ?><br>
                                    <strong>Nights:</strong> <?php echo $nights; ?><br>
                                    <strong>Guests:</strong> <?php echo $bookingData['adults']; ?> adults, <?php echo $bookingData['children']; ?> children
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($room['primary_image'])): ?>
                                    <img src="../uploads/rooms/<?php echo $room['primary_image']; ?>" 
                                         alt="<?php echo $room['name']; ?>" 
                                         class="img-fluid rounded">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <h4>Payment Method</h4>
                        <form action="../payment/process-payment.php" method="post">
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="flutterwave" value="flutterwave" checked>
                                    <label class="form-check-label" for="flutterwave">
                                        Pay with Flutterwave (Credit/Debit Card, Bank Transfer)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        Bank Transfer (Manual)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                                <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-outline-secondary">Back to Booking</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Price Details</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td>Room Rate</td>
                                <td class="text-end">$<?php echo number_format($room['price'], 2); ?> x <?php echo $nights; ?> nights</td>
                            </tr>
                            <?php 
                            $extraGuests = max(0, ($bookingData['adults'] + $bookingData['children']) - $room['standard_occupancy']);
                            $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
                            if ($extraGuestFee > 0):
                            ?>
                            <tr>
                                <td>Extra Guest Fee</td>
                                <td class="text-end">$<?php echo number_format($extraGuestFee, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end">$<?php echo number_format($bookingData['total_price'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>