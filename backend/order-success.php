<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if order exists
if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$orderId = $_SESSION['order_id'];
$order = getOrderById($orderId);

// Verify order exists
if (!$order) {
    header('Location: index.php');
    exit;
}

// Clear cart items
clearCart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="card-title mb-3">Thank You for Your Order!</h2>
                        <p class="card-text">Your order has been received successfully.</p>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h5>Order #<?php echo $orderId; ?></h5>
                            <p>
                                <?php if ($order['payment_method'] == 'cash_on_delivery'): ?>
                                    Your order will be delivered soon. Please prepare cash for payment upon delivery.
                                <?php elseif ($order['payment_method'] == 'payment_screenshot'): ?>
                                    Your order will be processed once your payment is verified.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="order-view.php?id=<?php echo $orderId; ?>" class="btn btn-primary me-2">View Order Details</a>
                            <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
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
    <script>
        // Clear order ID after 1 minute
        setTimeout(function() {
            <?php unset($_SESSION['order_id']); ?>
        }, 60000);
    </script>
</body>
</html>
