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

// Handle booking status update
if (isset($_POST['update_status'])) {
    $bookingId = intval($_POST['booking_id']);
    $status = $_POST['status'];
    
    if (updateBookingStatus($bookingId, $status)) {
        $_SESSION['success_msg'] = 'Booking status updated successfully';
    } else {
        $_SESSION['error_msg'] = 'Failed to update booking status';
    }
    
    header('Location: room-bookings.php');
    exit;
}

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $bookingId = intval($_POST['booking_id']);
    $paymentStatus = $_POST['payment_status'];
    
    if (updateBookingPaymentStatus($bookingId, $paymentStatus)) {
        $_SESSION['success_msg'] = 'Payment status updated successfully';
    } else {
        $_SESSION['error_msg'] = 'Failed to update payment status';
    }
    
    header('Location: room-bookings.php');
    exit;
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get bookings
$bookings = getAllBookings($statusFilter, $searchQuery, $dateFrom, $dateTo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Room Bookings - Admin Dashboard</title>
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
                    <h1 class="h2">Manage Room Bookings</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="rooms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
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
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filter Bookings</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="room-bookings.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                    <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Check-in Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Check-in Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Name, Email, Phone" value="<?php echo $searchQuery; ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="room-bookings.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Room Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Total</th>
                                        <th>Payment Method</th>
                                        <th>Payment Status</th>
                                        <th>Booking Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No bookings found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?php echo $booking['id']; ?></td>
                                                <td>
                                                    <?php echo $booking['customer_name']; ?><br>
                                                    <small class="text-muted"><?php echo $booking['customer_email']; ?></small>
                                                </td>
                                                <td><?php echo $booking['room_name']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                                <td>
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
                                                </td>
                                                <td>
                                                    <form method="post" action="room-bookings.php">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <select class="form-select form-select-sm" name="payment_status" onchange="this.form.submit()">
                                                            <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                            <option value="failed" <?php echo $booking['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                        </select>
                                                        <input type="hidden" name="update_payment" value="1">
                                                    </form>
                                                </td>
                                                <td>
                                                    <form method="post" action="room-bookings.php">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                            <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                                            <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                </td>
                                                <td>
                                                    <a href="room-booking-detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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