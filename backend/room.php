<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/room-functions.php';

// Get room ID from URL
$roomId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get booking parameters
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Get room details
$room = getRoomById($roomId);

// If room not found, redirect to rooms page
if (!$room) {
    header('Location: rooms.php');
    exit;
}

// Check if room is available for the selected dates
$isAvailable = checkRoomAvailability($roomId, $checkIn, $checkOut);

// Calculate total price
$totalPrice = calculateBookingTotal($roomId, $checkIn, $checkOut, $adults, $children);

// Calculate number of nights
$checkInDate = new DateTime($checkIn);
$checkOutDate = new DateTime($checkOut);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;

// Parse amenities
$amenities = explode(',', $room['amenities']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/rooms.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-5">
        <div class="row g-4">
            <!-- Room Details Section -->
            <div class="col-lg-8">
                <!-- Room Images Slider -->
                <div class="room-gallery mb-4">
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            <?php if (!empty($room['images'])): ?>
                                <?php foreach ($room['images'] as $image): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars(getRoomImageUrl($image['image_path'])); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="swiper-slide">
                                    <img src="assets/images/no-room-image.jpg" class="img-fluid" alt="No Image Available">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>

                <!-- Room Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h1>
                        <p class="text-muted"><?php echo htmlspecialchars($room['type_name']); ?></p>

                        <!-- Room Specs -->
                        <div class="d-flex flex-wrap mb-4">
                            <span class="badge bg-secondary me-2 mb-2">
                                <i class="fas fa-user-friends me-1"></i> Max: <?php echo $room['max_occupancy']; ?> guests
                            </span>
                            <span class="badge bg-secondary me-2 mb-2">
                                <i class="fas fa-bed me-1"></i> <?php echo htmlspecialchars($room['beds']); ?>
                            </span>
                            <span class="badge bg-secondary me-2 mb-2">
                                <i class="fas fa-vector-square me-1"></i> <?php echo $room['size_sqft']; ?> sq ft
                            </span>
                        </div>

                        <!-- Description -->
                        <h5>About This Room</h5>
                        <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>

                        <!-- Amenities -->
                        <h5 class="mt-4">Amenities</h5>
                        <div class="row mb-4">
                            <?php foreach ($amenities as $amenity): ?>
                                <?php if (!empty(trim($amenity))): ?>
                                    <div class="col-md-6 mb-2">
                                        <i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars(trim($amenity)); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Policies -->
                        <h5>Policies</h5>
                        <ul class="policies-list">
                            <li>Check-in: 2:00 PM</li>
                            <li>Check-out: 11:00 AM</li>
                            <li>Extra person fee: NGN<?php echo number_format($room['extra_person_fee'], 2); ?> per night</li>
                            <li>Standard occupancy: <?php echo $room['standard_occupancy']; ?> guests</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Booking Card -->
            <div class="col-lg-4">
                <div class="card sticky-top mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Reserve Your Stay</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="text-primary mb-3">NGN<?php echo number_format($room['price'], 2); ?> <small class="text-muted">/ night</small></h5>

                        <form method="get" action="booking.php" id="booking-form">
                            <input type="hidden" name="room_id" value="<?php echo $roomId; ?>">

                            <div class="mb-3">
                                <label for="check_in" class="form-label">Check-in Date</label>
                                <input type="text" class="form-control flatpickr" id="check_in" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="text" class="form-control flatpickr" id="check_out" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="adults" class="form-label">Adults</label>
                                    <select class="form-select" id="adults" name="adults">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php if ($adults == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="children" class="form-label">Children</label>
                                    <select class="form-select" id="children" name="children">
                                        <?php for ($i = 0; $i <= 4; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php if ($children == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="alert alert-info mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>NGN<?php echo number_format($room['price'], 2); ?> x <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                                    <span>NGN<?php echo number_format($room['price'] * $nights, 2); ?></span>
                                </div>
                                <?php 
                                $extraGuests = max(0, ($adults + $children) - $room['standard_occupancy']);
                                $extraGuestFee = $extraGuests * $room['extra_person_fee'] * $nights;
                                if ($extraGuestFee > 0):
                                ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Extra guest fee (<?php echo $extraGuests; ?> guest<?php echo $extraGuests > 1 ? 's' : ''; ?>)</span>
                                    <span>NGN<?php echo number_format($extraGuestFee, 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <span>NGN<?php echo number_format($totalPrice, 2); ?></span>
                                </div>
                            </div>

                            <!-- Booking Button -->
                            <?php if ($isAvailable): ?>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Book Now</button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger mb-3">
                                    This room is not available for the selected dates. Please choose different dates.
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" disabled>Not Available</button>
                                </div>
                            <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Swiper
            const swiper = new Swiper('.room-gallery .swiper', {
                slidesPerView: 1,
                spaceBetween: 10,
                loop: true,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });

            // Initialize Flatpickr
            flatpickr(".flatpickr", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (instance.element.id === 'check_in') {
                        const checkoutPicker = document.getElementById('check_out')._flatpickr;
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
            const checkInDate = new Date(document.getElementById('check_in').value);
            const minCheckOut = new Date(checkInDate);
            minCheckOut.setDate(checkInDate.getDate() + 1);
            flatpickr("#check_out", {
                dateFormat: "Y-m-d",
                minDate: minCheckOut,
            });
        });
    </script>
</body>
</html>