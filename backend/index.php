<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/room-functions.php'; // For getRoomImageUrl()
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/rooms-list.css"> 
    <link rel="stylesheet" href="assets/css/footer-1.css"><!-- For UI consistency with rooms.php -->
    <style>
       
    </style>
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section - Adding this as Guardsman design feature -->
    <div class="hero-section">
        <img src="assets/images/hero-image.jpeg" alt="Guardsman Style" class="w-100 h-100" style="object-fit: cover;">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.6;"></div>
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-3">Welcome to Our Restaurant</h1>
            <p class="lead mb-4">Experience exceptional dining in elegant surroundings</p>
            <a href="menu.php" class="btn btn-lg btn-orange px-4 py-2">
                <i class="fas fa-utensils me-2"></i>View Full Menu
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-4 mb-5">
        <!-- Search Bar - Modern Version -->
        <div class="mb-4">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search for items...">
            </div>
        </div>

        <!-- Categories (Horizontal Scroll) - Modern Version -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="section-title text-light mb-0">Categories</h5>
                <a href="menu.php" class="see-all-link">
                    See All <i class="fas fa-arrow-right fs-6"></i>
                </a>
            </div>
            <div class="d-flex overflow-auto gap-2 pb-2">
                <?php
                $categories = getCategories();
                if ($categories) {
                    foreach ($categories as $category) {
                        echo '<a href="menu.php?category=' . $category['id'] . '" class="category-pill">' . htmlspecialchars($category['name']) . '</a>';
                    }
                } else {
                    echo '<p class="text-gray">No categories available.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Featured Items - Modern Version -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title text-light mb-0">Featured Items</h5>
                <a href="menu.php" class="see-all-link">
                    See All <i class="fas fa-arrow-right fs-6"></i>
                </a>
            </div>
            <?php
            $featuredItems = getFeaturedItems();
            if ($featuredItems && count($featuredItems) > 0) {
            ?>
                <div class="row row-cols-1 row-cols-2 g-4">
                    <?php
                    foreach ($featuredItems as $item) {
                        $imagePath = getProductImageUrl($item['image']);
                    ?>
                        <div class="col">
                            <div class="custom-card">
                                <div class="card-badge">Featured</div>
                                <div class="card-img-container">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-content">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="card-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="d-flex gap-2 mt-auto">
                                        <a href="product.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-light btn-sm flex-fill">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        <button onclick="showCartModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $imagePath; ?>')" class="btn btn-orange btn-sm flex-fill">
                                            <i class="fas fa-shopping-bag"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            <?php
            } else {
                echo '<p class="text-gray">No featured items available.</p>';
            }
            ?>
        </div>

        <!-- Available Rooms - Modern Version -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title text-light mb-0">Available Rooms</h5>
                <a href="rooms.php" class="see-all-link">
                    See All <i class="fas fa-arrow-right fs-6"></i>
                </a>
            </div>
            <?php
            $conn = getDbConnection();
            $query = "SELECT r.*, ri.image_path 
          FROM rooms r 
          LEFT JOIN room_images ri ON r.id = ri.room_id AND ri.is_primary = 1
          WHERE r.status = 'active' 
          ORDER BY r.id DESC 
          LIMIT 4";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
            ?>
                <div class="row row-cols-2 row-cols-2 g-5">
                    <?php
                    while ($room = $result->fetch_assoc()) {
                        $imagePath = !empty($room['image_path']) ? 
                            getRoomImageUrl($room['image_path']) : 
                            'assets/images/room-placeholder.jpg';
                    ?>
                        <div class="col">
                            <div class="custom-card">
                                <div class="card-badge">Available</div>
                                <div class="card-img-container">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" loading="lazy">
                                </div>
                                <div class="card-content">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($room['name']); ?></h6>
                                    <div class="card-price">$<?php echo number_format($room['price'], 2); ?> / night</div>
                                    <div class="d-flex gap-2 mt-auto">
                                        <a href="room.php?id=<?php echo $room['id']; ?>" class="btn btn-outline-light btn-sm flex-fill">
                                            <i class="fas fa-info-circle"></i> Details
                                        </a>
                                        <a href="booking/booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-orange btn-sm flex-fill">
                                            <i class="fas fa-calendar-check"></i> Book
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            <?php
            } else {
                echo '<p class="text-gray">No rooms available at the moment.</p>';
            }
            $conn->close();
            ?>
        </div>
    </main>

    <!-- Cart Modal - Modern Version -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-gray modal-modern">
                <div class="modal-header bg-orange text-dark">
                    <h5 class="modal-title fw-bold" id="cartModalLabel">Add to Cart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-4">
                        <img id="modalImage" src="/placeholder.svg" class="modal-product-image me-3" alt="Item Image">
                        <div>
                            <h6 id="modalItemName" class="mb-1 fw-bold"></h6>
                            <p id="modalItemPrice" class="mb-0 text-orange fw-bold"></p>
                        </div>
                    </div>
                    
                    <label class="form-label mb-3">Quantity</label>
                    <div class="quantity-control mb-4">
                        <button type="button" class="quantity-btn" onclick="decrementQuantity()">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" id="modalQuantity" value="1" min="1" max="10" readonly>
                        <button type="button" class="quantity-btn" onclick="incrementQuantity()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-orange" onclick="addToCartFromModal()">
                        <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Footer -->
    <?php include 'includes/footer-1.php'; ?> <!-- Use footer-1.php instead of footer.php -->

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Quantity control functions for the cart modal
        function incrementQuantity() {
            const input = document.getElementById('modalQuantity');
            const currentValue = parseInt(input.value);
            if (currentValue < 10) {
                input.value = currentValue + 1;
            }
        }
        
        function decrementQuantity() {
            const input = document.getElementById('modalQuantity');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        }
        
        // Modern toast notification function
        function showModernToast(message) {
            const toastContainer = document.querySelector('.toast-container');
            const toastEl = document.createElement('div');
            toastEl.classList.add('toast', 'toast-modern', 'show');
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            
            toastEl.innerHTML = `
                <div class="toast-header bg-dark text-light border-0">
                    <i class="fas fa-check-circle text-orange me-2"></i>
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            
            toastContainer.appendChild(toastEl);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                toastEl.remove();
            }, 3000);
        }
        
        // You might want to update your existing addToCartFromModal function 
        // to use the new showModernToast function
        const originalAddToCartFromModal = window.addToCartFromModal;
        window.addToCartFromModal = function() {
            if (originalAddToCartFromModal) {
                originalAddToCartFromModal();
            }
            // Show the modern toast notification
            showModernToast('Item added to your cart successfully!');
        };
    </script>
</body>
</html>