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

// Handle room deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
  $roomId = intval($_GET['id']);
  $deleted = deleteRoom($roomId);
  
  if ($deleted) {
      $_SESSION['success_msg'] = "Room deleted successfully.";
  } else {
      $_SESSION['error_msg'] = "Cannot delete room. It has active bookings.";
  }
  
  header('Location: rooms.php');
  exit;
}

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
  $roomId = intval($_GET['id']);
  $room = getRoomById($roomId);
  
  if ($room) {
      $newStatus = ($room['status'] == 'active') ? 'inactive' : 'active';
      $conn = getDbConnection();
      $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
      $stmt->bind_param("si", $newStatus, $roomId);
      
      if ($stmt->execute()) {
          $_SESSION['success_msg'] = "Room status updated successfully.";
      } else {
          $_SESSION['error_msg'] = "Failed to update room status.";
      }
  }
  
  header('Location: rooms.php');
  exit;
}

// Get all rooms
$rooms = getAllRooms();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Rooms - Admin Dashboard</title>
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
                  <h1 class="h2">Manage Rooms</h1>
                  <div class="btn-toolbar mb-2 mb-md-0">
                      <a href="add-room.php" class="btn btn-sm btn-primary">
                          <i class="fas fa-plus"></i> Add New Room
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
              
              <!-- Rooms Table -->
              <div class="card">
                  <div class="card-body">
                      <div class="table-responsive">
                          <table class="table table-striped table-hover">
                              <thead>
                                  <tr>
                                      <th>ID</th>
                                      <th>Image</th>
                                      <th>Name</th>
                                      <th>Type</th>
                                      <th>Price</th>
                                      <th>Occupancy</th>
                                      <th>Status</th>
                                      <th>Featured</th>
                                      <th>Actions</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <?php if (!empty($rooms)): ?>
                                      <?php foreach ($rooms as $room): ?>
                                          <tr>
                                              <td><?php echo $room['id']; ?></td>
                                              <td>
                                                  <?php if (!empty($room['primary_image'])): ?>
                                                      <img src="<?php echo '../uploads/rooms/' . $room['primary_image']; ?>" 
                                                           alt="<?php echo $room['name']; ?>" 
                                                           width="50" height="50" 
                                                           class="img-thumbnail">
                                                  <?php else: ?>
                                                      <span class="text-muted">No image</span>
                                                  <?php endif; ?>
                                              </td>
                                              <td><?php echo $room['name']; ?></td>
                                              <td><?php echo $room['type_name']; ?></td>
                                              <td>$<?php echo number_format($room['price'], 2); ?></td>
                                              <td>
                                                  <?php echo $room['standard_occupancy']; ?> 
                                                  (max: <?php echo $room['max_occupancy']; ?>)
                                              </td>
                                              <td>
                                                  <a href="rooms.php?action=toggle_status&id=<?php echo $room['id']; ?>" 
                                                     class="text-decoration-none">
                                                      <?php if ($room['status'] == 'active'): ?>
                                                          <span class="badge bg-success">Active</span>
                                                      <?php else: ?>
                                                          <span class="badge bg-danger">Inactive</span>
                                                      <?php endif; ?>
                                                  </a>
                                              </td>
                                              <td>
                                                  <?php if ($room['is_featured']): ?>
                                                      <span class="badge bg-primary">Featured</span>
                                                  <?php else: ?>
                                                      <span class="badge bg-secondary">No</span>
                                                  <?php endif; ?>
                                              </td>
                                              <td>
                                                  <div class="btn-group">
                                                      <a href="edit-room.php?id=<?php echo $room['id']; ?>" 
                                                         class="btn btn-sm btn-primary" 
                                                         title="Edit">
                                                          <i class="fas fa-edit"></i>
                                                      </a>
                                                      <a href="room-images.php?room_id=<?php echo $room['id']; ?>" 
                                                         class="btn btn-sm btn-info" 
                                                         title="Manage Images">
                                                          <i class="fas fa-images"></i>
                                                      </a>
                                                      <a href="rooms.php?action=delete&id=<?php echo $room['id']; ?>" 
                                                         class="btn btn-sm btn-danger" 
                                                         title="Delete"
                                                         onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                                                          <i class="fas fa-trash"></i>
                                                      </a>
                                                  </div>
                                              </td>
                                          </tr>
                                      <?php endforeach; ?>
                                  <?php else: ?>
                                      <tr>
                                          <td colspan="9" class="text-center">No rooms found</td>
                                      </tr>
                                  <?php endif; ?>
                              </tbody>
                          </table>
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