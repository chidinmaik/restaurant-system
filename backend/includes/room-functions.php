<?php
// Room-related functions

// Get all room types
function getRoomTypes() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM room_types ORDER BY name");
    
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
    
    return $types;
}

// Get room type by ID
function getRoomTypeById($typeId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM room_types WHERE id = ?");
    $stmt->bind_param("i", $typeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get all rooms
function getRooms($typeId = null, $limit = null) {
    $conn = getDbConnection();
    
    $query = "SELECT r.*, rt.name as type_name, rt.description as type_description 
              FROM rooms r 
              JOIN room_types rt ON r.room_type_id = rt.id 
              WHERE r.status = 'active'";
    
    if ($typeId) {
        $query .= " AND r.room_type_id = " . intval($typeId);
    }
    
    $query .= " ORDER BY r.price ASC";
    
    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($query);
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        // Get room images
        $row['images'] = getRoomImages($row['id']);
        $rooms[] = $row;
    }
    
    return $rooms;
}

// Get room by ID
function getRoomById($roomId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT r.*, rt.name as type_name, rt.description as type_description 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        // Get room images
        $room['images'] = getRoomImages($roomId);
        return $room;
    }
    
    return null;
}

// Get room images
function getRoomImages($roomId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    
    return $images;
}

// Get room image URL
function getRoomImageUrl($imagePath) {
    // Extract just the filename from the stored path
    $imageName = basename($imagePath);
    
    // Construct the path relative to the web root
    $imagePath = 'uploads/rooms/' . $imageName;
    
    // Fallback to a default image if no image is available
    if (empty($imageName) || !file_exists($imagePath)) {
        return 'assets/images/no-room-image.jpg';
    }
    
    return $imagePath;
}

// Check room availability for a date range
function checkRoomAvailability($roomId, $checkIn, $checkOut) {
    $conn = getDbConnection();
    
    // Convert dates to MySQL format
    $checkInDate = date('Y-m-d', strtotime($checkIn));
    $checkOutDate = date('Y-m-d', strtotime($checkOut));
    
    // Check if there are any overlapping bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) as booking_count 
        FROM room_bookings 
        WHERE room_id = ? 
        AND status != 'cancelled' 
        AND (
            (check_in_date <= ? AND check_out_date > ?) OR
            (check_in_date < ? AND check_out_date >= ?) OR
            (check_in_date >= ? AND check_out_date <= ?)
        )
    ");
    
    $stmt->bind_param("issssss", $roomId, $checkOutDate, $checkInDate, $checkOutDate, $checkInDate, $checkInDate, $checkOutDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // If booking_count is 0, the room is available
    return ($row['booking_count'] == 0);
}

// Get available rooms for a date range
function getAvailableRooms($checkIn, $checkOut, $roomTypeId = null, $adults = 1, $children = 0) {
    $conn = getDbConnection();
    
    // Convert dates to MySQL format
    $checkInDate = date('Y-m-d', strtotime($checkIn));
    $checkOutDate = date('Y-m-d', strtotime($checkOut));
    
    // Get all active rooms
    $query = "SELECT r.*, rt.name as type_name, rt.description as type_description 
              FROM rooms r 
              JOIN room_types rt ON r.room_type_id = rt.id 
              WHERE r.status = 'active'";
    
    if ($roomTypeId) {
        $query .= " AND r.room_type_id = " . intval($roomTypeId);
    }
    
    // Filter by capacity if specified
    $totalGuests = $adults + $children;
    if ($totalGuests > 0) {
        $query .= " AND r.max_occupancy >= " . intval($totalGuests);
    }
    
    $query .= " ORDER BY r.price ASC";
    
    $result = $conn->query($query);
    
    $availableRooms = [];
    while ($row = $result->fetch_assoc()) {
        // Check if this room is available for the date range
        if (checkRoomAvailability($row['id'], $checkInDate, $checkOutDate)) {
            // Get room images
            $row['images'] = getRoomImages($row['id']);
            $availableRooms[] = $row;
        }
    }
    
    return $availableRooms;
}

// Calculate total price for a booking
function calculateBookingTotal($roomId, $checkIn, $checkOut, $adults, $children) {
    $room = getRoomById($roomId);
    
    if (!$room) {
        return 0;
    }
    
    // Calculate number of nights
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    $interval = $checkInDate->diff($checkOutDate);
    $nights = $interval->days;
    
    // Base price is room price * nights
    $basePrice = $room['price'] * $nights;
    
    // Additional charges for extra guests if applicable
    $extraGuestCharge = 0;
    $totalGuests = $adults + $children;
    $standardOccupancy = $room['standard_occupancy'];
    
    if ($totalGuests > $standardOccupancy) {
        $extraGuests = $totalGuests - $standardOccupancy;
        $extraGuestCharge = $extraGuests * $room['extra_person_fee'] * $nights;
    }
    
    // Total price
    $totalPrice = $basePrice + $extraGuestCharge;
    
    return $totalPrice;
}

// Create a new booking
function createBooking($bookingData) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO room_bookings (
                room_id, customer_name, customer_email, customer_phone, 
                check_in_date, check_out_date, adults, children, 
                total_price, payment_method, special_requests, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = 'pending'; // Default status
        
        $stmt->bind_param(
            "isssssiiisss", 
            $bookingData['room_id'], 
            $bookingData['customer_name'], 
            $bookingData['customer_email'], 
            $bookingData['customer_phone'], 
            $bookingData['check_in_date'], 
            $bookingData['check_out_date'], 
            $bookingData['adults'], 
            $bookingData['children'], 
            $bookingData['total_price'], 
            $bookingData['payment_method'], 
            $bookingData['special_requests'], 
            $status
        );
        
        $stmt->execute();
        $bookingId = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        return $bookingId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

// Get booking by ID
function getBookingById($bookingId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT rb.*, r.name as room_name, r.price as room_price, 
               rt.name as room_type 
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE rb.id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Update booking payment status
function updateBookingPaymentStatus($bookingId, $paymentStatus, $transactionId = null) {
    $conn = getDbConnection();
    
    $query = "UPDATE room_bookings SET payment_status = ?";
    $params = [$paymentStatus];
    $types = "s";
    
    if ($transactionId) {
        $query .= ", transaction_id = ?";
        $params[] = $transactionId;
        $types .= "s";
    }
    
    $query .= " WHERE id = ?";
    $params[] = $bookingId;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    return $stmt->execute();
}

// Update booking status
function updateBookingStatus($bookingId, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE room_bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $bookingId);
    
    return $stmt->execute();
}

// Get bookings by email
function getBookingsByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT rb.*, r.name as room_name, r.price as room_price, 
               rt.name as room_type 
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE rb.customer_email = ?
        ORDER BY rb.check_in_date DESC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// Get featured rooms for homepage
function getFeaturedRooms($limit = 3) {
    $conn = getDbConnection();
    $result = $conn->query("
        SELECT r.*, rt.name as type_name, rt.description as type_description 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE r.status = 'active' AND r.is_featured = 1
        ORDER BY r.price ASC
        LIMIT " . intval($limit)
    );
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        // Get room images
        $row['images'] = getRoomImages($row['id']);
        $rooms[] = $row;
    }
    
    return $rooms;
}
