<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/admin-room-functions.php';

// Check if room ID is provided
if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header('Location: rooms.php');
    exit;
}

$roomId = intval($_GET['room_id']);
$room = getRoomById($roomId);

// Check if room exists and is active
if (!$room || $room['status'] !== 'active') {
    $_SESSION['error_msg'] = 'Room not available for booking.';
    header('Location: rooms.php');
    exit;
}

// Process booking form
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $requiredFields = ['customer_name', 'customer_email', 'customer_phone', 'check_in_date', 'check_out_date', 'adults'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate dates
    if (!empty($_POST['check_in_date']) && !empty($_POST['check_out_date'])) {
        $checkInDate = new DateTime($_POST['check_in_date']);
        $checkOutDate = new DateTime($_POST['check_out_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($checkInDate < $today) {
            $errors[] = 'Check-in date cannot be in the past.';
        }
        
        if ($checkOutDate <= $checkInDate) {
            $errors[] = 'Check-out date must be after check-in date.';
        }
    }
    
    // Validate adults
    if (isset($_POST['adults']) && (!is_numeric($_POST['adults']) || $_POST['adults'] < 1)) {
        $errors[] = 'Number of adults must be at least 1.';
    }
    
    // Validate children
    if (isset($_POST['children']) && (!is_numeric($_POST['children']) || $_POST['children'] < 0)) {
        $errors[] = 'Number of children cannot be negative.';
    }
    
    // Validate email
    if (!empty($_POST['customer_email']) && !filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // If no errors, process booking
    if (empty($errors)) {
        $checkInDate = new DateTime($_POST['check_in_date']);
        $checkOutDate = new DateTime($_POST['check_out_date']);
        $interval = $checkInDate->diff($checkOutDate);
        $nights = $interval->days;
        
        $adults = intval($_POST['adults']);
        $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
        
        $totalGuests = $adults + $children;
        $extraGuests = max(0, $totalGuests - $room['standard_occupancy']);
        $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
        
        $totalPrice = ($room['price'] * $nights) + $extraGuestFee;
        
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            INSERT INTO room_bookings (
                room_id, customer_name, customer_email, customer_phone,
                check_in_date, check_out_date, adults, children,
                total_price, payment_status, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
        ");
        
        $stmt->bind_param(
            "isssssidi",
            $roomId,
            $_POST['customer_name'],
            $_POST['customer_email'],
            $_POST['customer_phone'],
            $_POST['check_in_date'],
            $_POST['check_out_date'],
            $adults,
            $children,
            $totalPrice
        );
        
        if ($stmt->execute()) {
            $bookingId = $conn->insert_id;
            $_SESSION['booking_data'] = [
                'id' => $bookingId,
                'room_id' => $roomId,
                'customer_name' => $_POST['customer_name'],
                'customer_email' => $_POST['customer_email'],
                'customer_phone' => $_POST['customer_phone'],
                'check_in_date' => $_POST['check_in_date'],
                'check_out_date' => $_POST['check_out_date'],
                'adults' => $adults,
                'children' => $children,
                'total_price' => $totalPrice
            ];
            header('Location: booking-flutterwave.php');
            exit;
        } else {
            $errors[] = 'Failed to create booking. Please try again landscaping.';
        }
    }
}

// Get minimum check-in date (today)
$minCheckInDate = date('Y-m-d');
// Get minimum check-out date (tomorrow)
$minCheckOutDate = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/booking.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-5">
        <div class="row g-4">
            <!-- Booking Form -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0">Book Your Stay</h3>
                    </div>
                    <div class="card-body">
                        <!-- Display errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="" id="booking-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="customer_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required
                                           value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="customer_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" required
                                           value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required
                                       value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="check_in_date" class="form-label">Check-in Date *</label>
                                    <input type="text" class="form-control flatpickr" id="check_in_date" name="check_in_date" required
                                           value="<?php echo isset($_POST['check_in_date']) ? htmlspecialchars($_POST['check_in_date']) : $minCheckInDate; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="check_out_date" class="form-label">Check-out Date *</label>
                                    <input type="text" class="form-control flatpickr" id="check_out_date" name="check_out_date" required
                                           value="<?php echo isset($_POST['check_out_date']) ? htmlspecialchars($_POST['check_out_date']) : $minCheckOutDate; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="adults" class="form-label">Adults *</label>
                                    <input type="number" class="form-control" id="adults" name="adults" min="1" max="<?php echo $room['max_occupancy']; ?>" required
                                           value="<?php echo isset($_POST['adults']) ? intval($_POST['adults']) : 1; ?>">
                                    <small class="text-muted">Maximum: <?php echo $room['max_occupancy']; ?> guests</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="children" class="form-label">Children</label>
                                    <input type="number" class="form-control" id="children" name="children" min="0"
                                           value="<?php echo isset($_POST['children']) ? intval($_POST['children']) : 0; ?>">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Continue to Payment</button>
                                <a href="room.php?id=<?php echo $roomId; ?>" class="btn btn-outline-secondary">Back to Room Details</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Room Details Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Room Summary</h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($room['type_name']); ?></p>
                        
                        <?php if (!empty($room['primary_image'])): ?>
                            <img src="<?php echo htmlspecialchars('Uploads/rooms/' . $room['primary_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($room['name']); ?>" 
                                 class="img-fluid rounded mb-3">
                        <?php else: ?>
                            <img src="assets/images/no-room-image.jpg" 
                                 alt="No Image Available" 
                                 class="img-fluid rounded mb-3">
                        <?php endif; ?>
                        
                        <p><?php echo htmlspecialchars(substr($room['description'], 0, 150)) . '...'; ?></p>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Price per night:</span>
                            <span class="fw-bold">NGN<?php echo number_format($room['price'], 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Max occupancy:</span>
                            <span><?php echo $room['max_occupancy']; ?> guests</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Extra person fee:</span>
                            <span>NGN<?php echo number_format($room['extra_person_fee'], 2); ?> per night</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Room size:</span>
                            <span><?php echo $room['size_sqft']; ?> sq ft</span>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr
            flatpickr(".flatpickr", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (instance.element.id === 'check_in_date') {
                        const checkoutPicker = document.getElementById('check_out_date')._flatpickr;
                        const nextDay = new Date(selectedDates[0]);
                        nextDay.setDate(nextDay.getDate() + 1);
                        checkoutPicker.set('minDate', nextDay);
                        if (checkoutPicker.selectedDates[0] <= selectedDates[0]) {
                            checkoutPicker.setDate(nextDay);
                        }
                    }
                }
            });

            // Set checkout min date
            const checkInDate = new Date(document.getElementById('check_in_date').value);
            const minCheckOut = new Date(checkInDate);
            minCheckOut.setDate(checkInDate.getDate() + 1);
            flatpickr("#check_out_date", {
                dateFormat: "Y-m-d",
                minDate: minCheckOut,
            });
        });
    </script>
</body>
</html>