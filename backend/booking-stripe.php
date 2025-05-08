v<?php
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
if (!$booking || $booking['payment_method'] != 'stripe') {
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

// Handle Stripe payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real implementation, you would process the Stripe payment here
    // For this example, we'll just simulate a successful payment
    
    // Update booking payment status
    updateBookingPaymentStatus($bookingId, 'paid', 'stripe_' . time());
    
    $_SESSION['success_message'] = 'Payment processed successfully.';
    header('Location: booking-confirmation.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Payment - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
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
                        <h4 class="mb-0">Complete Your Payment</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h5>Booking Summary</h5>
                            <p>
                                <strong>Room:</strong> <?php echo $room['name']; ?><br>
                                <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?><br>
                                <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?><br>
                                <strong>Nights:</strong> <?php echo $nights; ?><br>
                                <strong>Guests:</strong> <?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children<br>
                                <strong>Total Amount:</strong> $<?php echo number_format($booking['total_price'], 2); ?>
                            </p>
                        </div>
                        
                        <form id="payment-form" method="post" action="booking-stripe.php">
                            <div class="mb-3">
                                <label for="card-element" class="form-label">Credit or Debit Card</label>
                                <div id="card-element" class="form-control" style="height: 2.4em; padding-top: .7em;"></div>
                                <div id="card-errors" class="invalid-feedback d-block"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn btn-outline-secondary">Back to Booking</a>
                                <button type="submit" class="btn btn-primary" id="submit-button">
                                    Pay $<?php echo number_format($booking['total_price'], 2); ?>
                                </button>
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
    <script>
        // Create a Stripe client
        var stripe = Stripe('pk_test_your_stripe_publishable_key');
        var elements = stripe.elements();

        // Create an instance of the card Element
        var card = elements.create('card');

        // Add an instance of the card Element into the `card-element` div
        card.mount('#card-element');

        // Handle real-time validation errors from the card Element
        card.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            var submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // In a real implementation, you would create a payment method and confirm the payment
            // For this example, we'll just submit the form directly
            form.submit();
        });
    </script>
</body>
</html>
