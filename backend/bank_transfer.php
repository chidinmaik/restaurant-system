<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Ensure booking ID is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id']) || !isset($_SESSION['booking_id']) || $_SESSION['booking_id'] != $_GET['booking_id']) {
    header("Location: index.php");
    exit;
}

$bookingId = (int)$_GET['booking_id'];

// Fetch booking details
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT b.*, p.name as room_name, p.price FROM bookings b JOIN products p ON b.room_id = p.id WHERE b.id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking || $booking['payment_method'] !== 'bank_transfer') {
    header("Location: index.php");
    exit;
}

// Handle payment proof upload
$uploadSuccess = false;
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $uploadDir = 'Uploads/payments/';
    $fileName = $bookingId . '_' . time() . '_' . basename($_FILES['payment_proof']['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if ($_FILES['payment_proof']['size'] > 5000000) { // 5MB limit
        $uploadError = "File is too large. Maximum 5MB.";
    } elseif (!in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'pdf'])) {
        $uploadError = "Invalid file type. Only JPG, PNG, or PDF allowed.";
    } elseif (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadPath)) {
        // Update booking with payment proof path
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'pending', payment_proof = ? WHERE id = ?");
        $stmt->bind_param("si", $uploadPath, $bookingId);
        if ($stmt->execute()) {
            $uploadSuccess = true;
            // TODO: Notify admin to verify payment proof
        } else {
            $uploadError = "Failed to save payment proof.";
        }
        $stmt->close();
    } else {
        $uploadError = "Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Instructions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container py-4">
        <h2 class="mb-4">Bank Transfer Instructions</h2>
        
        <?php if ($uploadSuccess): ?>
            <div class="alert alert-success">
                Payment proof uploaded successfully. Our team will verify it, and you’ll receive a confirmation soon.
            </div>
        <?php elseif ($uploadError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($uploadError); ?>
            </div>
        <?php endif; ?>
        
        <p>Please transfer <strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong> for your booking of <strong><?php echo htmlspecialchars($booking['room_name']); ?></strong> (Booking ID: <?php echo $bookingId; ?>) to the following bank account:</p>
        <ul>
            <li><strong>Bank Name:</strong> Your Bank Name</li>
            <li><strong>Account Name:</strong> Your Business Name</li>
            <li><strong>Account Number:</strong> Your Account Number</li>
            <li><strong>Routing Number:</strong> Your Routing Number (for US) or IBAN (for Europe)</li>
            <li><strong>SWIFT Code:</strong> Your SWIFT Code (for international transfers)</li>
        </ul>
        <p><strong>Important:</strong> Include the Booking ID (<?php echo $bookingId; ?>) in the transfer reference to help us identify your payment.</p>
        
        <h4>Upload Payment Proof</h4>
        <form method="POST" enctype="multipart/form-data" class="bg-dark p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="payment_proof" class="form-label">Upload Proof of Payment (JPG, PNG, or PDF)</label>
                <input type="file" class="form-control bg-dark text-light border-gray" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <button type="submit" class="btn btn-orange">Submit Payment Proof</button>
        </form>
        
        <a href="index.php" class="btn btn-outline-light mt-3">Return to Home</a>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>