<?php
// Admin room management functions

// Include config to get database connection
include_once __DIR__ . '/config.php';

// Get all rooms for admin
function getAllRooms() {
    $conn = getDbConnection();
    $result = $conn->query("
        SELECT r.*, rt.name as type_name 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        ORDER BY r.id DESC
    ");
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        // Get primary image
        $stmt = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $imgResult = $stmt->get_result();
        
        if ($imgResult->num_rows > 0) {
            $row['primary_image'] = $imgResult->fetch_assoc()['image_path'];
        } else {
            $row['primary_image'] = '';
        }
        
        $rooms[] = $row;
    }
    
    return $rooms;
}

// Get room by ID
function getRoomById($roomId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT r.*, rt.name as type_name
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Add a new room
function addRoom($roomData, $images) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert room data
        $stmt = $conn->prepare("
            INSERT INTO rooms (
                name, room_type_id, description, price, 
                standard_occupancy, max_occupancy, extra_person_fee, 
                size_sqft, beds, amenities, is_featured, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Handle size_sqft: Convert empty string to 0.0
        $size_sqft = !empty($roomData['size_sqft']) ? floatval($roomData['size_sqft']) : 0.0;

        // Bind 12 parameters
        $stmt->bind_param(
            "sisdiidsssis",
            $roomData['name'],                  // s: string
            $roomData['room_type_id'],          // i: integer
            $roomData['description'],           // s: string
            $roomData['price'],                 // d: float
            $roomData['standard_occupancy'],    // i: integer
            $roomData['max_occupancy'],         // i: integer
            $roomData['extra_person_fee'],      // d: float
            $size_sqft,                         // d: float
            $roomData['beds'],                  // s: string
            $roomData['amenities'],             // s: string
            $roomData['is_featured'],           // i: integer
            $roomData['status']                 // s: string
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $roomId = $conn->insert_id;
        
        // Upload images
        if ($roomId && isset($images) && !empty($images['name'][0])) {
            $uploadDir = '../Uploads/rooms/'; // Original path
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // First image is primary
            $isPrimary = 1;
            
            foreach ($images['name'] as $key => $name) {
                if ($images['error'][$key] === UPLOAD_ERR_OK) {
                    $tmpName = $images['tmp_name'][$key];
                    $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $fileName = 'room_' . $roomId . '_' . time() . '_' . $key . '.' . $fileExt;
                    $targetFile = $uploadDir . $fileName;
                    
                    // Validate image
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($fileExt, $allowedTypes)) {
                        error_log("Invalid image type for file: $name");
                        continue;
                    }
                    
                    if (move_uploaded_file($tmpName, $targetFile)) {
                        // Store filename only in database
                        $imagePath = $fileName;
                        
                        // Insert image record
                        $imgStmt = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $imgStmt->bind_param("isi", $roomId, $imagePath, $isPrimary);
                        if (!$imgStmt->execute()) {
                            error_log("Failed to insert image record for room $roomId: " . $imgStmt->error);
                        }
                        $imgStmt->close();
                        
                        // Only first image is primary
                        $isPrimary = 0;
                    } else {
                        error_log("Failed to move uploaded file: $name to $targetFile");
                    }
                } else {
                    error_log("Upload error for file $name: " . $images['error'][$key]);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        return $roomId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error adding room: " . $e->getMessage());
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Update a room
function updateRoom($roomData, $images) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update room data
        $stmt = $conn->prepare("
            UPDATE rooms SET
                name = ?, room_type_id = ?, description = ?, price = ?, 
                standard_occupancy = ?, max_occupancy = ?, extra_person_fee = ?, 
                size_sqft = ?, beds = ?, amenities = ?, is_featured = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sisiiidsssisi",
            $roomData['name'],
            $roomData['room_type_id'],
            $roomData['description'],
            $roomData['price'],
            $roomData['standard_occupancy'],
            $roomData['max_occupancy'],
            $roomData['extra_person_fee'],
            $roomData['size_sqft'],
            $roomData['beds'],
            $roomData['amenities'],
            $roomData['is_featured'],
            $roomData['status'],
            $roomData['id']
        );
        
        $stmt->execute();
        
        // Upload new images if provided
        if (isset($images) && !empty($images['name'][0])) {
            $uploadDir = '../Uploads/rooms/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Check if there are existing images
            $existingImages = getRoomImages($roomData['id']);
            $isPrimary = empty($existingImages) ? 1 : 0;
            
            foreach ($images['name'] as $key => $name) {
                if ($images['error'][$key] === UPLOAD_ERR_OK) {
                    $tmpName = $images['tmp_name'][$key];
                    $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $fileName = 'room_' . $roomData['id'] . '_' . time() . '_' . $key . '.' . $fileExt;
                    $targetFile = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmpName, $targetFile)) {
                        // Store filename only
                        $imagePath = $fileName;
                        
                        // Insert image record
                        $imgStmt = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $imgStmt->bind_param("isi", $roomData['id'], $imagePath, $isPrimary);
                        $imgStmt->execute();
                        $imgStmt->close();
                        
                        // Only first image is primary if no existing images
                        $isPrimary = 0;
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating room: " . $e->getMessage());
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Delete a room
function deleteRoom($roomId) {
    $conn = getDbConnection();
    
    // Check if room has active bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM room_bookings WHERE room_id = ? AND status IN ('pending', 'confirmed', 'checked_in')");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return false; // Cannot delete room with active bookings
    }
    
    // Get all images to delete files
    $stmt = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $imagePaths = [];
    while ($row = $result->fetch_assoc()) {
        $imagePaths[] = $row['image_path'];
    }
    
    // Delete room (will cascade delete images from database)
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $success = $stmt->execute();
    
    if ($success) {
        // Delete image files
        foreach ($imagePaths as $path) {
            $filePath = '../Uploads/rooms/' . $path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    return $success;
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

// Delete room image
function deleteRoomImage($imageId) {
    $conn = getDbConnection();
    
    // Get image info
    $stmt = $conn->prepare("SELECT room_id, image_path, is_primary FROM room_images WHERE id = ?");
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $image = $result->fetch_assoc();
    $roomId = $image['room_id'];
    $isPrimary = $image['is_primary'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete image from database
        $stmt = $conn->prepare("DELETE FROM room_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        
        // Delete image file
        $filePath = '../Uploads/rooms/' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // If primary image was deleted, set another image as primary
        if ($isPrimary) {
            $stmt = $conn->prepare("SELECT id FROM room_images WHERE room_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->bind_param("i", $roomId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $newPrimaryId = $result->fetch_assoc()['id'];
                $stmt = $conn->prepare("UPDATE room_images SET is_primary = 1 WHERE id = ?");
                $stmt->bind_param("i", $newPrimaryId);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting room image: " . $e->getMessage());
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Set room image as primary
function setRoomImageAsPrimary($imageId) {
    $conn = getDbConnection();
    
    // Get room ID
    $stmt = $conn->prepare("SELECT room_id FROM room_images WHERE id = ?");
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $roomId = $result->fetch_assoc()['room_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Reset all images for this room to non-primary
        $stmt = $conn->prepare("UPDATE room_images SET is_primary = 0 WHERE room_id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        
        // Set selected image as primary
        $stmt = $conn->prepare("UPDATE room_images SET is_primary = 1 WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error setting primary image: " . $e->getMessage());
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Get all room types
function getAllRoomTypes() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM room_types ORDER BY name ASC");
    
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
    
    return $types;
}

// Add room type
function addRoomType($name, $description) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO room_types (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    return $stmt->execute();
}

// Update room type
function updateRoomType($id, $name, $description) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE room_types SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    return $stmt->execute();
}

// Delete room type
function deleteRoomType($id) {
    $conn = getDbConnection();
    
    // Check if room type is in use
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_type_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return false; // Cannot delete room type that is in use
    }
    
    $stmt = $conn->prepare("DELETE FROM room_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Get all bookings
function getAllBookings($status = '', $search = '', $dateFrom = '', $dateTo = '') {
    $conn = getDbConnection();
    
    $query = "
        SELECT rb.*, r.name as room_name
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if (!empty($status)) {
        $query .= " AND rb.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $search = "%$search%";
        $query .= " AND (rb.customer_name LIKE ? OR rb.customer_email LIKE ? OR rb.customer_phone LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND rb.check_in_date >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    
    if (!empty($dateTo)) {
        $query .= " AND rb.check_in_date <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    
    $query .= " ORDER BY rb.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// Get booking by ID
function getBookingById($bookingId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT rb.*, r.name as room_name
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        WHERE rb.id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Update booking status
function updateBookingStatus($bookingId, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE room_bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $bookingId);
    return $stmt->execute();
}

// Update booking payment status
function updateBookingPaymentStatus($bookingId, $paymentStatus, $transactionId = null) {
    $conn = getDbConnection();
    
    if ($transactionId !== null) {
        $stmt = $conn->prepare("UPDATE room_bookings SET payment_status = ?, transaction_id = ? WHERE id = ?");
        $stmt->bind_param("ssi", $paymentStatus, $transactionId, $bookingId);
    } else {
        $stmt = $conn->prepare("UPDATE room_bookings SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $paymentStatus, $bookingId);
    }
    
    return $stmt->execute();
}