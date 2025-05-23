<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';
include '../includes/admin-room-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if room ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = 'Invalid room ID';
    header('Location: rooms.php');
    exit;
}

$roomId = intval($_GET['id']);
$room = getRoomById($roomId);

// Check if room exists
if (!$room) {
    $_SESSION['error_msg'] = 'Room not found';
    header('Location: rooms.php');
    exit;
}

// Get all room types for dropdown
$roomTypes = getAllRoomTypes();

// Get room images
$roomImages = getRoomImages($roomId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate form data
    if (empty($_POST['name'])) {
        $errors[] = 'Room name is required';
    }
    
    if (empty($_POST['room_type_id'])) {
        $errors[] = 'Room type is required';
    }
    
    if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        $errors[] = 'Valid price is required';
    }
    
    if (empty($_POST['standard_occupancy']) || !is_numeric($_POST['standard_occupancy']) || $_POST['standard_occupancy'] <= 0) {
        $errors[] = 'Valid standard occupancy is required';
    }
    
    if (empty($_POST['max_occupancy']) || !is_numeric($_POST['max_occupancy']) || $_POST['max_occupancy'] <= 0) {
        $errors[] = 'Valid maximum occupancy is required';
    }
    
    // Check if max occupancy is greater than or equal to standard occupancy
    if ($_POST['max_occupancy'] < $_POST['standard_occupancy']) {
        $errors[] = 'Maximum occupancy must be greater than or equal to standard occupancy';
    }
    
    // Validate new images if uploaded
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === 0) {
                if (!in_array($_FILES['images']['type'][$key], $allowedTypes)) {
                    $errors[] = 'Only JPG, JPEG, and PNG files are allowed';
                    break;
                }
                
                if ($_FILES['images']['size'][$key] > $maxFileSize) {
                    $errors[] = 'File size must be less than 5MB';
                    break;
                }
            } else if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Error uploading file: ' . $name;
                break;
            }
        }
    }
    
    // If no errors, update the room
    if (empty($errors)) {
        // Prepare amenities string
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        
        $roomData = [
            'id' => $roomId,
            'name' => $_POST['name'],
            'room_type_id' => $_POST['room_type_id'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'standard_occupancy' => $_POST['standard_occupancy'],
            'max_occupancy' => $_POST['max_occupancy'],
            'extra_person_fee' => $_POST['extra_person_fee'] ?? 0,
            'size_sqft' => $_POST['size_sqft'] ?? null,
            'beds' => $_POST['beds'] ?? null,
            'amenities' => $amenities,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'status' => $_POST['status']
        ];
        
        $updated = updateRoom($roomData, $_FILES['images']);
        
        if ($updated) {
            $_SESSION['success_msg'] = 'Room updated successfully';
            header('Location: rooms.php');
            exit;
        } else {
            $errors[] = 'Failed to update room. Please try again.';
        }
    }
}

// Parse amenities
$roomAmenities = explode(',', $room['amenities']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room - Admin Dashboard</title>
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
                    <h1 class="h2">Edit Room</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="rooms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
                        </a>
                    </div>
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Edit Room Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="edit-room.php?id=<?php echo $roomId; ?>" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Room Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : $room['name']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="room_type_id" class="form-label">Room Type *</label>
                                    <select class="form-select" id="room_type_id" name="room_type_id" required>
                                        <option value="">Select Room Type</option>
                                        <?php foreach ($roomTypes as $type): ?>
                                            <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['room_type_id']) ? $_POST['room_type_id'] : $room['room_type_id']) == $type['id'] ? 'selected' : ''; ?>>
                                                <?php echo $type['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : $room['description']; ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="price" class="form-label">Price per Night *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? $_POST['price'] : $room['price']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="standard_occupancy" class="form-label">Standard Occupancy *</label>
                                    <input type="number" class="form-control" id="standard_occupancy" name="standard_occupancy" min="1" value="<?php echo isset($_POST['standard_occupancy']) ? $_POST['standard_occupancy'] : $room['standard_occupancy']; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="max_occupancy" class="form-label">Maximum Occupancy *</label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" min="1" value="<?php echo isset($_POST['max_occupancy']) ? $_POST['max_occupancy'] : $room['max_occupancy']; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="extra_person_fee" class="form-label">Extra Person Fee</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="extra_person_fee" name="extra_person_fee" step="0.01" min="0" value="<?php echo isset($_POST['extra_person_fee']) ? $_POST['extra_person_fee'] : $room['extra_person_fee']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="size_sqft" class="form-label">Size (sq ft)</label>
                                    <input type="number" class="form-control" id="size_sqft" name="size_sqft" min="0" value="<?php echo isset($_POST['size_sqft']) ? $_POST['size_sqft'] : $room['size_sqft']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="beds" class="form-label">Beds</label>
                                    <input type="text" class="form-control" id="beds" name="beds" value="<?php echo isset($_POST['beds']) ? $_POST['beds'] : $room['beds']; ?>" placeholder="e.g., 1 King Bed or 2 Queen Beds">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Amenities</label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_wifi" value="Free WiFi" <?php echo (isset($_POST['amenities']) && in_array('Free WiFi', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Free WiFi', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_wifi">Free WiFi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_tv" value="Flat-screen TV" <?php echo (isset($_POST['amenities']) && in_array('Flat-screen TV', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Flat-screen TV', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_tv">Flat-screen TV</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_ac" value="Air conditioning" <?php echo (isset($_POST['amenities']) && in_array('Air conditioning', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Air conditioning', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_ac">Air conditioning</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_fridge" value="Refrigerator" <?php echo (isset($_POST['amenities']) && in_array('Refrigerator', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Refrigerator', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_fridge">Refrigerator</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_safe" value="In-room safe" <?php echo (isset($_POST['amenities']) && in_array('In-room safe', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('In-room safe', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_safe">In-room safe</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_coffee" value="Coffee maker" <?php echo (isset($_POST['amenities']) && in_array('Coffee maker', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Coffee maker', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_coffee">Coffee maker</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_desk" value="Work desk" <?php echo (isset($_POST['amenities']) && in_array('Work desk', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Work desk', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_desk">Work desk</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_iron" value="Iron/ironing board" <?php echo (isset($_POST['amenities']) && in_array('Iron/ironing board', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Iron/ironing board', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_iron">Iron/ironing board</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_hairdryer" value="Hair dryer" <?php echo (isset($_POST['amenities']) && in_array('Hair dryer', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Hair dryer', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_hairdryer">Hair dryer</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_bathtub" value="Bathtub" <?php echo (isset($_POST['amenities']) && in_array('Bathtub', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Bathtub', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_bathtub">Bathtub</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_toiletries" value="Free toiletries" <?php echo (isset($_POST['amenities']) && in_array('Free toiletries', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Free toiletries', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_toiletries">Free toiletries</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" id="amenity_balcony" value="Balcony" <?php echo (isset($_POST['amenities']) && in_array('Balcony', $_POST['amenities'])) || (!isset($_POST['amenities']) && in_array('Balcony', $roomAmenities)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity_balcony">Balcony</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="images" class="form-label">Add More Images</label>
                                <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg, image/png, image/jpg" multiple>
                                <div class="form-text">Upload additional images. Maximum file size: 5MB.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Images</label>
                                <div class="row">
                                    <?php if (empty($roomImages)): ?>
                                        <div class="col-12">
                                            <p class="text-muted">No images available</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($roomImages as $image): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="card">
                                                    <img src="<?php echo '../uploads/rooms/' . $image['image_path']; ?>" class="card-img-top" alt="Room Image" style="height: 150px; object-fit: cover;">
                                                    <div class="card-body p-2 text-center">
                                                        <?php if ($image['is_primary']): ?>
                                                            <span class="badge bg-primary mb-2">Primary Image</span>
                                                        <?php else: ?>
                                                            <a href="room-images.php?action=set_primary&id=<?php echo $image['id']; ?>&room_id=<?php echo $roomId; ?>" class="btn btn-sm btn-outline-primary mb-2">Set as Primary</a>
                                                        <?php endif; ?>
                                                        <a href="room-images.php?action=delete&id=<?php echo $image['id']; ?>&room_id=<?php echo $roomId; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this image?');">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo (isset($_POST['status']) ? $_POST['status'] : $room['status']) == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (isset($_POST['status']) ? $_POST['status'] : $room['status']) == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?php echo (isset($_POST['is_featured']) || (!isset($_POST['is_featured']) && $room['is_featured'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            Feature this room on homepage
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="rooms.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Room</button>
                            </div>
                        </form>
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