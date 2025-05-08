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

// Verify order exists and is the correct payment type
if (!$order || $order['payment_method'] != 'payment_screenshot') {
    header('Location: index.php');
    exit;
}

// Handle payment upload
if (isset($_POST['submit_payment'])) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] != 0) {
        $errors[] = 'Please select a payment proof image to upload.';
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['payment_proof']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed.';
        }
        
        // Validate file size (max 2MB)
        if ($_FILES['payment_proof']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'File size must be less than 2MB.';
        }
        
        // Process upload if no errors
        if (empty($errors)) {
            $target_dir = "uploads/payments/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . $orderId . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
                // Update order with payment proof
                if (updateOrderPaymentProof($orderId, $target_file)) {
                    $_SESSION['success_message'] = 'Payment proof uploaded successfully. Your order will be processed once payment is verified.';
                    header('Location: order-success.php');
                    exit;
                } else {
                    $errors[] = 'Failed to update order information. Please try again.';
                }
            } else {
                $errors[] = 'Failed to upload file. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment Proof - Restaurant Management System</title>
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
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Upload Payment Proof</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p class="mb-0">
                                <strong>Order #<?php echo $orderId; ?></strong><br>
                                Please upload a screenshot or image of your payment receipt to complete your order.
                            </p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="payment-upload.php" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="payment_proof" class="form-label">Payment Screenshot</label>
                                <input class="form-control" type="file" id="payment_proof" name="payment_proof" accept="image/*" required>
                                <div class="form-text">Accepted formats: JPG, JPEG, PNG. Maximum file size: 2MB.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="order-view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary">View Order</a>
                                <button type="submit" name="submit_payment" class="btn btn-success">Upload Payment</button>
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
</body>
</html>
