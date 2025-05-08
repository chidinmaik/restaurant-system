<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Ensure booking ID is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: index.php");
    exit;
}

$bookingId = (int)$_GET['booking_id'];

// Fetch booking details
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT payment_method, payment_status FROM bookings WHERE id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: index.php");
    exit;
}

// Update payment status (except for bank transfer, which requires manual verification)
if ($booking['payment_method'] !== 'bank_transfer' && $booking['payment_status'] !== 'completed') {
    updateBookingPaymentStatus($bookingId, 'completed');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container py-4">
        <h2 class="mb-4">Booking Confirmed</h2>
        <?php if ($booking['payment_method'] === 'bank_transfer'): ?>
            <div class="alert alert-info">
                Your booking (ID: <?php echo $bookingId; ?>) has been received. Please complete the bank transfer and upload payment proof to confirm your booking. You’ll receive a confirmation email once verified.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                Your booking (ID: <?php echo $bookingId; ?>) has been successfully confirmed. A confirmation email will be sent to you shortly.
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-orange">Return to Home</a>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>