<?php
// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

include_once __DIR__ . '/functions.php';
// Admin login
function adminLogin($username, $password) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            // Update last login time
            $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $admin['id']);
            $stmt->execute();
            
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            
            return true;
        }
    }
    
    return false;
}

// Admin logout
function adminLogout() {
    unset($_SESSION['admin_id']);
    session_destroy();
}

// Get dashboard statistics
function getDashboardStats() {
    $conn = getDbConnection();
    
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $result->fetch_assoc()['count'];
    
    // Total revenue
    $result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
    $totalRevenue = $result->fetch_assoc()['total'] ?? 0;
    
    // Total products
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $totalProducts = $result->fetch_assoc()['count'];
    
    // Pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pendingOrders = $result->fetch_assoc()['count'];
    
    return [
        'total_orders' => $totalOrders,
        'total_revenue' => $totalRevenue,
        'total_products' => $totalProducts,
        'pending_orders' => $pendingOrders
    ];
}

// Get recent orders
function getRecentOrders($limit = 5) {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT $limit");
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

// Get latest products
function getLatestProducts($limit = 5) {
    $conn = getDbConnection();
    $result = $conn->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT $limit
    ");
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Get orders with filters
function getOrders($status = '', $search = '', $dateFrom = '', $dateTo = '') {
    $conn = getDbConnection();
    
    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $search = "%$search%";
        $query .= " AND (id LIKE ? OR customer_name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    
    if (!empty($dateTo)) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

// Update order status
function updateOrderStatus($orderId, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    
    return $stmt->execute();
}

// Update order payment status
function updateOrderPaymentStatus($orderId, $paymentStatus) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $paymentStatus, $orderId);
    
    return $stmt->execute();
}

// Get products with filters
function getProducts($categoryId = '', $subcategoryId = '', $search = '') {
    $conn = getDbConnection();
    
    $query = "
        SELECT p.*, c.name as category_name, s.name as subcategory_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN subcategories s ON p.subcategory_id = s.id 
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if (!empty($categoryId)) {
        $query .= " AND p.category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    }
    
    if (!empty($subcategoryId)) {
        $query .= " AND p.subcategory_id = ?";
        $params[] = $subcategoryId;
        $types .= "i";
    }
    
    if (!empty($search)) {
        $search = "%$search%";
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    $query .= " ORDER BY p.name";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Add product
function addProduct($productData) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO products (name, description, price, category_id, subcategory_id, in_stock, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("ssdiiss", 
        $productData['name'], 
        $productData['description'], 
        $productData['price'], 
        $productData['category_id'], 
        $productData['subcategory_id'], 
        $productData['in_stock'], 
        $productData['image']
    );
    
    return $stmt->execute();
}

// Update product
function updateProduct($productData) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, category_id = ?, subcategory_id = ?, in_stock = ?, image = ? 
        WHERE id = ?
    ");
    
    $stmt->bind_param("ssdiiisi", 
        $productData['name'], 
        $productData['description'], 
        $productData['price'], 
        $productData['category_id'], 
        $productData['subcategory_id'], 
        $productData['in_stock'], 
        $productData['image'], 
        $productData['id']
    );
    
    return $stmt->execute();
}

// Delete product
function deleteProduct($productId) {
    $conn = getDbConnection();
    
    // Check if product exists in any order
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Product is in orders, don't delete
        return false;
    }
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    
    return $stmt->execute();
}

// Get categories with subcategories
function getCategoriesWithSubcategories() {
    $conn = getDbConnection();
    
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categoryId = $row['id'];
        
        // Get subcategories
        $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $subcatResult = $stmt->get_result();
        
        $subcategories = [];
        while ($subcat = $subcatResult->fetch_assoc()) {
            $subcategories[] = $subcat;
        }
        
        $row['subcategories'] = $subcategories;
        $categories[] = $row;
    }
    
    return $categories;
}

// Add category
function addCategory($name) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    return $stmt->execute();
}

// Add subcategory
function addSubcategory($name, $categoryId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $categoryId);
    
    return $stmt->execute();
}

// Delete category
function deleteCategory($categoryId) {
    $conn = getDbConnection();
    
    // Check if category has products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Category has products, don't delete
        return false;
    }
    
    // Check if category has subcategories
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subcategories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Category has subcategories, don't delete
        return false;
    }
    
    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    
    return $stmt->execute();
}

// Delete subcategory
function deleteSubcategory($subcategoryId) {
    $conn = getDbConnection();
    
    // Check if subcategory has products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE subcategory_id = ?");
    $stmt->bind_param("i", $subcategoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Subcategory has products, don't delete
        return false;
    }
    
    // Delete subcategory
    $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
    $stmt->bind_param("i", $subcategoryId);
    
    return $stmt->execute();
}