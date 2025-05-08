<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if order ID is provided
if (!isset($_GET['id']) && !isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['order_id'];
$order = getOrderById($orderId);

// Verify order exists and belongs to current session
if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$orderItems = getOrderItems($orderId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Restaurant Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Base styles -->
    <link rel="stylesheet" href="assets/css/order-view.css"> <!-- Order-specific styles -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">Order #<?php echo $orderId; ?></h1>
            <a href="index.php" class="btn btn-outline-light btn-lg">Continue Shopping</a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Order Items -->
                <div class="card bg-dark border-0 shadow-sm mb-4">
                    <div class="card-header bg-teal text-dark">
                        <h4 class="mb-0"><i class="fas fa-utensils me-2"></i>Order Items</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table text-gray">
                                <thead class="bg-teal text-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): 
                                        $imageName = basename($item['image']);
                                        $imagePath = 'Uploads/products/' . $imageName;
                                        if (empty($imageName) || !file_exists($imagePath)) {
                                            $imagePath = 'assets/images/no-image.jpg';
                                        }
                                    ?>
                                        <tr class="align-middle">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-3 rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0 text-light"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-orange">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-orange">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end text-light">Total:</th>
                                        <th class="text-orange">$<?php echo number_format($order['total_amount'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="card bg-dark border-0 shadow-sm">
                    <div class="card-header bg-teal text-dark">
                        <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Order Status</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-dark border-gray d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-info-circle me-2 text-orange"></i>Status</span>
                                <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>"><?php echo getStatusLabel($order['status']); ?></span>
                            </li>
                            <li class="list-group-item bg-dark border-gray d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-credit-card me-2 text-orange"></i>Payment Method</span>
                                <span><?php echo getPaymentMethodLabel($order['payment_method']); ?></span>
                            </li>
                            <li class="list-group-item bg-dark border-gray d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-money-check-alt me-2 text-orange"></i>Payment Status</span>
                                <span class="badge bg-<?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>"><?php echo getPaymentStatusLabel($order['payment_status']); ?></span>
                            </li>
                            <li class="list-group-item bg-dark border-gray d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt me-2 text-orange"></i>Date</span>
                                <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                            </li>
                        </ul>

                        <?php if ($order['payment_method'] == 'payment_screenshot' && $order['payment_status'] == 'pending' && empty($order['payment_proof'])): ?>
                            <div class="alert alert-warning mt-3 bg-dark border-gray text-gray">
                                <p>Please upload a payment proof to complete your order.</p>
                                <a href="payment-upload.php" class="btn btn-orange btn-sm">Upload Payment</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="col-md-4">
                <div class="card bg-dark border-0 shadow-sm">
                    <div class="card-header bg-teal text-dark">
                        <h4 class="mb-0"><i class="fas fa-user me-2"></i>Order Details</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="text-light">Customer Information</h5>
                        <p class="text-gray">
                            <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?>
                        </p>

                        <h5 class="mt-4 text-light">Shipping Address</h5>
                        <p class="text-gray"><?php echo htmlspecialchars($order['customer_address']); ?></p>

                        <?php if (!empty($order['notes'])): ?>
                            <h5 class="mt-4 text-light">Order Notes</h5>
                            <p class="text-gray"><?php echo htmlspecialchars($order['notes']); ?></p>
                        <?php endif; ?>
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