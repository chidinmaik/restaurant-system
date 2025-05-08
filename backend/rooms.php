<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/room-functions.php';

// Get filter parameters
$roomTypeId = isset($_GET['type']) ? intval($_GET['type']) : null;
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Get room types for filter
$roomTypes = getRoomTypes();

// Get available rooms
$rooms = getAvailableRooms($checkIn, $checkOut, $roomTypeId, $adults, $children);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/rooms-list.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mt-5">
        <div class="row g-4">
            <!-- Search Filters -->
            <div class="col-lg-3">
                <div class="card sticky-top mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Search Rooms</h4>
                    </div>
                    <div class="card-body">
                        <form method="get" action="rooms.php" id="search-form">
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
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Room Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Room Types</option>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php if ($roomTypeId == $type['id']) echo 'selected'; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Search Availability</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Room Listings -->
            <div class="col-lg-9">
                <h2 class="mb-4">Available Rooms</h2>
                
                <?php if (empty($rooms)): ?>
                    <div class="alert alert-info">
                        No rooms available for the selected dates. Please try different dates or filters.
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="card room-card mb-4">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <?php if (!empty($room['images'])): ?>
                                        <img src="<?php echo htmlspecialchars(getRoomImageUrl($room['images'][0]['image_path'])); ?>" class="img-fluid rounded-start room-image" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                    <?php else: ?>
                                        <img src="assets/images/no-room-image.jpg" class="img-fluid rounded-start room-image" alt="No Image Available">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                                        <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($room['type_name']); ?></small></p>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($room['description'], 0, 150)); ?>...</p>
                                        
                                        <div class="d-flex flex-wrap mb-3">
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
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="text-primary mb-0">NGN<?php echo number_format($room['price'], 2); ?> <small class="text-muted">/ night</small></h4>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="room.php?id=<?php echo $room['id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" class="btn btn-outline-primary">View Details</a>
                                                <a href="booking.php?room_id=<?php echo $room['id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" class="btn btn-primary">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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