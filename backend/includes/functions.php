<?php
// Get all categories
function getCategories() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Get category by ID
function getCategoryById($categoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get subcategories by category ID
function getSubcategoriesByCategory($categoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    
    return $subcategories;
}

// Get subcategory by ID
function getSubcategoryById($subcategoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->bind_param("i", $subcategoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get featured products for homepage
function getFeaturedItems($limit = 6) {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM products WHERE in_stock = 1 ORDER BY RAND() LIMIT $limit");
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Count products by category (for pagination)
function countProductsByCategory($categoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND in_stock = 1");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Get products by category with pagination
function getProductsByCategory($categoryId, $offset = 0, $limit = 8) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND in_stock = 1 ORDER BY name LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $categoryId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Count products by subcategory (for pagination)
function countProductsBySubcategory($subcategoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE subcategory_id = ? AND in_stock = 1");
    $stmt->bind_param("i", $subcategoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Get products by subcategory with pagination
function getProductsBySubcategory($subcategoryId, $offset = 0, $limit = 8) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE subcategory_id = ? AND in_stock = 1 ORDER BY name LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $subcategoryId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Get related products
function getRelatedProducts($categoryId, $currentProductId, $limit = 4) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND in_stock = 1 ORDER BY RAND() LIMIT ?");
    $stmt->bind_param("iii", $categoryId, $currentProductId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Add item to cart
function addToCart($productId, $quantity = 1) {
    $product = getProductById($productId);
    
    if (!$product || !$product['in_stock']) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already in cart
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    return true;
}

// Update cart item quantity
function updateCartItemQuantity($productId, $quantity) {
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$productId])) {
        return false;
    }
    
    if ($quantity <= 0) {
        removeFromCart($productId);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    return true;
}

// Remove item from cart
function removeFromCart($productId) {
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$productId])) {
        return false;
    }
    
    unset($_SESSION['cart'][$productId]);
    
    return true;
}

// Get cart items
function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $cartItems = [];
    
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $product = getProductById($productId);
        
        if ($product) {
            $product['quantity'] = $quantity;
            $cartItems[] = $product;
        }
    }
    
    return $cartItems;
}

// Calculate cart total
function calculateCartTotal() {
    $cartItems = getCartItems();
    $total = 0;
    
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

// Clear cart
function clearCart() {
    unset($_SESSION['cart']);
}

// Place order
function placeOrder($orderData) {
    $conn = getDbConnection();
    
    // Get cart items
    $cartItems = getCartItems();
    
    if (empty($cartItems)) {
        return false;
    }
    
    // Calculate total
    $totalAmount = calculateCartTotal();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, notes, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", 
            $orderData['full_name'], 
            $orderData['email'], 
            $orderData['phone'], 
            $orderData['address'], 
            $orderData['notes'], 
            $totalAmount, 
            $orderData['payment_method']
        );
        $stmt->execute();
        
        $orderId = $conn->insert_id;
        
        // Insert order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($cartItems as $item) {
            $stmt->bind_param("iiid", $orderId, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        return $orderId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

/**
 * Get product by ID
 * @param int $productId The product ID
 * @return array|null Product data or null if not found
 */
function getProductById($productId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        return $product;
    }
    
    return null;
}

// Get order by ID
function getOrderById($orderId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get order items
function getOrderItems($orderId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

// Update order payment proof
function updateOrderPaymentProof($orderId, $proofPath) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE orders SET payment_proof = ? WHERE id = ?");
    $stmt->bind_param("si", $proofPath, $orderId);
    
    return $stmt->execute();
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Get status label
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'completed':
            return 'Completed';
        case 'cancelled':
            return 'Cancelled';
        default:
            return 'Unknown';
    }
}

// Get payment method label
function getPaymentMethodLabel($method) {
    switch ($method) {
        case 'cash_on_delivery':
            return 'Cash on Delivery';
        case 'payment_screenshot':
            return 'Payment Screenshot';
        default:
            return 'Unknown';
    }
}

// Get payment status badge class
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'paid':
            return 'success';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Get payment status label
function getPaymentStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'paid':
            return 'Paid';
        case 'failed':
            return 'Failed';
        default:
            return 'Unknown';
    }
}

// Helper function to fix image paths
function getProductImageUrl($imagePath) {
    // Extract just the filename from the stored path
    $imageName = basename($imagePath);
    
    // Construct the path relative to the web root
    $imagePath = 'Uploads/products/' . $imageName;
    
    // Fallback to a default image if no image is available
    if (empty($imageName) || !file_exists($imagePath)) {
        return 'assets/images/no-image.jpg';
    }
    
    return $imagePath;
}

// Get category ID by name
function getCategoryIdByName($categoryName) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        error_log("Found category '$categoryName' with ID: " . $row['id']);
        return $row['id'];
    }
    
    error_log("Category '$categoryName' not found in categories table.");
    return null;
}

// Get top rooms by category (actually products in the "Rooms" category)
function getTopRooms($limit = 3) {
    $conn = getDbConnection();
    
    // Get the "Rooms" category ID
    $categoryId = getCategoryIdByName('Rooms');
    if (!$categoryId) {
        error_log("No category ID found for 'Rooms'. Check if the category exists in the categories table.");
        return [];
    }
    
    // Fetch products from the "Rooms" category
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND in_stock = 1 ORDER BY name LIMIT ?");
    $stmt->bind_param("ii", $categoryId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    
    if (empty($rooms)) {
        error_log("No products found for Rooms category ID: $categoryId. Check if products exist in the products table with this category_id.");
    } else {
        error_log("Found " . count($rooms) . " products for Rooms category ID: $categoryId.");
    }
    
    return $rooms;
}


?>