<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';
include '../includes/admin-room-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = 'Invalid booking ID';
    header('Location: room-bookings.php');
    exit;
}

$bookingId = intval($_GET['id']);
$booking = getBookingById($bookingId);

// Check if booking exists
if (!$booking) {
    $_SESSION['error_msg'] = 'Booking not found';
    header('Location: room-bookings.php');
    exit;
}

// Get room details
$room = getRoomById($booking['room_id']);

// Calculate number of nights
$checkInDate = new DateTime($booking['check_in_date']);
$checkOutDate = new DateTime($booking['check_out_date']);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;

// Handle booking status update
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    if (updateBookingStatus($bookingId, $status)) {
        $_SESSION['success_msg'] = 'Booking status updated successfully';
        header("Location: room-booking-detail.php?id=$bookingId");
        exit;
    } else {
        $_SESSION['error_msg'] = 'Failed to update booking status';
    }
}

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $paymentStatus = $_POST['payment_status'];
    $transactionId = $_POST['transaction_id'] ?? null;
    
    if (updateBookingPaymentStatus($bookingId, $paymentStatus, $transactionId)) {
        $_SESSION['success_msg'] = 'Payment status updated successfully';
        header("Location: room-booking-detail.php?id=$bookingId");
        exit;
    } else {
        $_SESSION['error_msg'] = 'Failed to update payment status';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Booking Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="room-bookings.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Bookings
                        </a>
                    </div>
                </div>
                
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
                
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_msg']; 
                        unset($_SESSION['error_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Booking Information -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Booking #<?php echo $booking['id']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Room Information</h6>
                                        <p>
                                            <strong>Room:</strong> <?php echo $room['name']; ?><br>
                                            <strong>Type:</strong> <?php echo $room['type_name']; ?><br>
                                            <strong>Price:</strong> $<?php echo number_format($room['price'], 2); ?> per night
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Stay Information</h6>
                                        <p>
                                            <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?><br>
                                            <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?><br>
                                            <strong>Nights:</strong> <?php echo $nights; ?><br>
                                            <strong>Guests:</strong> <?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children
                                        </p>
                                    </div>
                                </div>
                                
                                <h6>Price Details</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <td>Room Rate</td>
                                        <td>$<?php echo number_format($room['price'], 2); ?> x <?php echo $nights; ?> nights</td>
                                        <td class="text-end">$<?php echo number_format($room['price'] * $nights, 2); ?></td>
                                    </tr>
                                    <?php 
                                    $extraGuests = max(0, ($booking['adults'] + $booking['children']) - $room['standard_occupancy']);
                                    $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
                                    if ($extraGuestFee > 0):
                                    ?>
                                    <tr>
                                        <td>Extra Guest Fee</td>
                                        <td>$<?php echo number_format($room['extra_person_fee'], 2); ?> x <?php echo $extraGuests; ?> guests x <?php echo $nights; ?> nights</td>
                                        <td class="text-end">$<?php echo number_format($extraGuestFee, 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Total</th>
                                        <td></td>
                                        <th class="text-end">$<?php echo number_format($booking['total_price'], 2); ?></th>
                                    </tr>
                                </table>
                                
                                <?php if (!empty($booking['special_requests'])): ?>
                                    <h6>Special Requests</h6>
                                    <p><?php echo nl2br($booking['special_requests']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guest and Status Information -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Guest Information</h5>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Name:</strong> <?php echo $booking['customer_name']; ?><br>
                                    <strong>Email:</strong> <?php echo $booking['customer_email']; ?><br>
                                    <strong>Phone:</strong> <?php echo $booking['customer_phone']; ?><br>
                                    <strong>Booking Date:</strong> <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Booking Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="room-booking-detail.php?id=<?php echo $bookingId; ?>">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                            <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Payment Method:</strong>
                                    <?php
                                    switch ($booking['payment_method']) {
                                        case 'stripe':
                                            echo '<span class="badge bg-info">Stripe</span>';
                                            break;
                                        case 'flutterwave':
                                            echo '<span class="badge bg-primary">Flutterwave</span>';
                                            break;
                                        case 'bank_transfer':
                                            echo '<span class="badge bg-secondary">Bank Transfer</span>';
                                            break;
                                        default:
                                            echo $booking['payment_method'];
                                    }
                                    ?>
                                    <br>
                                    <strong>Transaction ID:</strong> <?php echo $booking['transaction_id'] ?? 'N/A'; ?>
                                </p>
                                
                                <form method="post" action="room-booking-detail.php?id=<?php echo $bookingId; ?>">
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status</label>
                                        <select class="form-select" id="payment_status" name="payment_status">
                                            <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="failed" <?php echo $booking['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="transaction_id" class="form-label">Transaction ID</label>
                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="<?php echo $booking['transaction_id'] ?? ''; ?>">
                                    </div>
                                    <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>