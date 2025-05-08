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

// Handle room type add
if (isset($_POST['add_type'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error_msg'] = 'Room type name is required';
    } else {
        if (addRoomType($name, $description)) {
            $_SESSION['success_msg'] = 'Room type added successfully';
        } else {
            $_SESSION['error_msg'] = 'Failed to add room type';
        }
    }
    
    header('Location: room-types.php');
    exit;
}

// Handle room type edit
if (isset($_POST['edit_type'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error_msg'] = 'Room type name is required';
    } else {
        if (updateRoomType($id, $name, $description)) {
            $_SESSION['success_msg'] = 'Room type updated successfully';
        } else {
            $_SESSION['error_msg'] = 'Failed to update room type';
        }
    }
    
    header('Location: room-types.php');
    exit;
}

// Handle room type delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if (deleteRoomType($id)) {
        $_SESSION['success_msg'] = 'Room type deleted successfully';
    } else {
        $_SESSION['error_msg'] = 'Failed to delete room type. Make sure it has no rooms assigned.';
    }
    
    header('Location: room-types.php');
    exit;
}

// Get all room types
$roomTypes = getAllRoomTypes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Room Types - Admin Dashboard</title>
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
                    <h1 class="h2">Manage Room Types</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="rooms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
                        </a>
                    </div>
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
                
                <div class="row">
                    <!-- Add Room Type Form -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Add New Room Type</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="room-types.php">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    <button type="submit" name="add_type" class="btn btn-primary">Add Room Type</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Room Types List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Room Types</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($roomTypes)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No room types found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($roomTypes as $type): ?>
                                                    <tr>
                                                        <td><?php echo $type['id']; ?></td>
                                                        <td><?php echo $type['name']; ?></td>
                                                        <td><?php echo substr($type['description'], 0, 100) . (strlen($type['description']) > 100 ? '...' : ''); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $type['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="room-types.php?action=delete&id=<?php echo $type['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room type?');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                            
                                                            <!-- Edit Modal -->
                                                            <div class="modal fade" id="editModal<?php echo $type['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="editModalLabel">Edit Room Type</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <form method="post" action="room-types.php">
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="id" value="<?php echo $type['id']; ?>">
                                                                                <div class="mb-3">
                                                                                    <label for="edit_name<?php echo $type['id']; ?>" class="form-label">Name *</label>
                                                                                    <input type="text" class="form-control" id="edit_name<?php echo $type['id']; ?>" name="name" value="<?php echo $type['name']; ?>" required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="edit_description<?php echo $type['id']; ?>" class="form-label">Description</label>
                                                                                    <textarea class="form-control" id="edit_description<?php echo $type['id']; ?>" name="description" rows="3"><?php echo $type['description']; ?></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" name="edit_type" class="btn btn-primary">Update</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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