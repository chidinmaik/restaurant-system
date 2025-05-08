<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = intval($_GET['id']);
$order = getOrderById($orderId);

// Verify order exists
if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$orderItems = getOrderItems($orderId);

// Handle status update
if (isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    
    if ($status) {
        updateOrderStatus($orderId, $status);
        $_SESSION['success_message'] = 'Order status updated successfully.';
        header('Location: order-detail.php?id=' . $orderId);
        exit;
    }
}

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $paymentStatus = $_POST['payment_status'] ?? '';
    
    if ($paymentStatus) {
        updateOrderPaymentStatus($orderId, $paymentStatus);
        $_SESSION['success_message'] = 'Payment status updated successfully.';
        header('Location: order-detail.php?id=' . $orderId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> Details - Restaurant Management System</title>
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
                    <h1 class="h2">Order #<?php echo $orderId; ?> Details</h1>
                    <div>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail me-3" style="width: 60px;">
                                                            <div>
                                                                <?php echo $item['name']; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>$<?php echo $item['price']; ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>$<?php echo number_format($order['total_amount'], 2); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Status Management -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6>Update Order Status</h6>
                                        <form method="post" action="order-detail.php?id=<?php echo $orderId; ?>">
                                            <div class="input-group">
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?php if ($order['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="processing" <?php if ($order['status'] == 'processing') echo 'selected'; ?>>Processing</option>
                                                    <option value="completed" <?php if ($order['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                                    <option value="cancelled" <?php if ($order['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <h6>Update Payment Status</h6>
                                        <form method="post" action="order-detail.php?id=<?php echo $orderId; ?>">
                                            <div class="input-group">
                                                <select name="payment_status" class="form-select">
                                                    <option value="pending" <?php if ($order['payment_status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="paid" <?php if ($order['payment_status'] == 'paid') echo 'selected'; ?>>Paid</option>
                                                    <option value="failed" <?php if ($order['payment_status'] == 'failed') echo 'selected'; ?>>Failed</option>
                                                </select>
                                                <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if ($order['payment_method'] == 'payment_screenshot' && !empty($order['payment_proof'])): ?>
                                <div class="mt-3">
                                    <h6>Payment Proof</h6>
                                    <div class="border p-3">
                                        <img src="<?php echo $order['payment_proof']; ?>" alt="Payment Proof" class="img-fluid" style="max-width: 300px;">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Order Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Details</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Order ID
                                        <span>#<?php echo $order['id']; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Date
                                        <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Status
                                        <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>"><?php echo getStatusLabel($order['status']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Payment Method
                                        <span><?php echo getPaymentMethodLabel($order['payment_method']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Payment Status
                                        <span class="badge bg-<?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>"><?php echo getPaymentStatusLabel($order['payment_status']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Total Amount
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Name:</strong> <?php echo $order['customer_name']; ?><br>
                                    <strong>Email:</strong> <?php echo $order['customer_email']; ?><br>
                                    <strong>Phone:</strong> <?php echo $order['customer_phone']; ?><br>
                                    <strong>Address:</strong> <?php echo $order['customer_address']; ?>
                                </p>
                                
                                <?php if (!empty($order['notes'])): ?>
                                    <hr>
                                    <h6>Order Notes</h6>
                                    <p><?php echo $order['notes']; ?></p>
                                <?php endif; ?>
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
