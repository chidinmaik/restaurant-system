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
if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    $_SESSION['error_msg'] = 'Invalid room ID';
    header('Location: rooms.php');
    exit;
}

$roomId = intval($_GET['room_id']);
$room = getRoomById($roomId);

// Check if room exists
if (!$room) {
    $_SESSION['error_msg'] = 'Room not found';
    header('Location: rooms.php');
    exit;
}

// Handle image actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $imageId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($action === 'delete' && $imageId > 0) {
        if (deleteRoomImage($imageId)) {
            $_SESSION['success_msg'] = 'Image deleted successfully';
        } else {
            $_SESSION['error_msg'] = 'Failed to delete image';
        }
        header("Location: room-images.php?room_id=$roomId");
        exit;
    }
    
    if ($action === 'set_primary' && $imageId > 0) {
        if (setRoomImageAsPrimary($imageId)) {
            $_SESSION['success_msg'] = 'Primary image updated successfully';
        } else {
            $_SESSION['error_msg'] = 'Failed to update primary image';
        }
        header("Location: room-images.php?room_id=$roomId");
        exit;
    }
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate images
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        $errors[] = 'Please select at least one image to upload';
    } else {
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
    
    // If no errors, upload images
    if (empty($errors)) {
        $uploadDir = '../uploads/rooms/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Check if there are existing images
        $existingImages = getRoomImages($roomId);
        $isPrimary = empty($existingImages) ? 1 : 0;
        
        $uploadedCount = 0;
        
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $tmpName = $_FILES['images']['tmp_name'][$key];
                $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $fileName = 'room_' . $roomId . '_' . time() . '_' . $key . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetFile)) {
                    // Insert image record
                    $conn = getDbConnection();
                    $stmt = $conn->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $roomId, $fileName, $isPrimary);
                    
                    if ($stmt->execute()) {
                        $uploadedCount++;
                    }
                    
                    // Only first image is primary (if no existing images)
                    $isPrimary = 0;
                }
            }
        }
        
        if ($uploadedCount > 0) {
            $_SESSION['success_msg'] = $uploadedCount . ' image(s) uploaded successfully';
        } else {
            $_SESSION['error_msg'] = 'Failed to upload images';
        }
        
        header("Location: room-images.php?room_id=$roomId");
        exit;
    }
}

// Get room images
$roomImages = getRoomImages($roomId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Room Images - Admin Dashboard</title>
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
                    <h1 class="h2">Manage Room Images</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="edit-room.php?id=<?php echo $roomId; ?>" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-edit"></i> Edit Room
                        </a>
                        <a href="rooms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
                        </a>
                    </div>
                </div>
                
                <!-- Room Info -->
                <div class="alert alert-info">
                    <h5>Room: <?php echo $room['name']; ?></h5>
                    <p class="mb-0">Type: <?php echo $room['type_name']; ?> | Price: $<?php echo number_format($room['price'], 2); ?> per night</p>
                </div>
                
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_msg']; 
                        unset($_SESSION['success_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_msg']; 
                        unset($_SESSION['error_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
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
                
                <!-- Upload Images Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Upload New Images</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="room-images.php?room_id=<?php echo $roomId; ?>" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="images" class="form-label">Select Images</label>
                                <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg, image/png, image/jpg" multiple required>
                                <div class="form-text">You can select multiple images. Maximum file size: 5MB per image.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Images</button>
                        </form>
                    </div>
                </div>
                
                <!-- Current Images -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (empty($roomImages)): ?>
                                <div class="col-12">
                                    <p class="text-muted">No images available for this room</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($roomImages as $image): ?>
                                    <div class="col-md-3 mb-4">
                                        <div class="card h-100">
                                            <img src="<?php echo '../uploads/rooms/' . $image['image_path']; ?>" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
                                            <div class="card-body text-center">
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
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>