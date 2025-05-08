<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_management');


// Site configuration
define('SITE_NAME', 'Restaurant Management');
define('ADMIN_EMAIL', 'admin@example.com'); // Change to your admin email
// Establish database connection
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    
    return $conn;
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    $conn = getDbConnection();
    
    // Create categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create subcategories table
    $conn->query("CREATE TABLE IF NOT EXISTS subcategories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )");
    
    // Create products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        category_id INT(11) NOT NULL,
        subcategory_id INT(11),
        in_stock TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
    )");
    
    // Create orders table
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        customer_address TEXT NOT NULL,
        notes TEXT,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        payment_method ENUM('cash_on_delivery', 'payment_screenshot') NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_proof VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create order_items table
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    
    // Create admins table
    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100),
        email VARCHAR(100),
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create room_types table
    $conn->query("CREATE TABLE IF NOT EXISTS room_types (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create rooms table
    $conn->query("CREATE TABLE IF NOT EXISTS rooms (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        room_type_id INT(11) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        standard_occupancy INT(11) NOT NULL DEFAULT 2,
        max_occupancy INT(11) NOT NULL DEFAULT 4,
        extra_person_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
        size_sqft INT(11),
        beds VARCHAR(100),
        amenities TEXT,
        is_featured TINYINT(1) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_type_id) REFERENCES room_types(id)
    )");
    
    // Create room_images table
    $conn->query("CREATE TABLE IF NOT EXISTS room_images (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        room_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");
    
    // Create room_bookings table
    $conn->query("CREATE TABLE IF NOT EXISTS room_bookings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        room_id INT(11) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        adults INT(11) NOT NULL DEFAULT 1,
        children INT(11) NOT NULL DEFAULT 0,
        total_price DECIMAL(10,2) NOT NULL,
        payment_method ENUM('stripe', 'flutterwave', 'bank_transfer') NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        transaction_id VARCHAR(100),
        special_requests TEXT,
        status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id)
    )");
    
    // Insert default admin if not exists
    $result = $conn->query("SELECT COUNT(*) as count FROM admins");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Default admin: username = admin, password = admin123
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO admins (username, password, name, email) VALUES ('admin', '$password', 'Administrator', 'admin@example.com')");
    }
    
    // Insert default room types if not exists
    $result = $conn->query("SELECT COUNT(*) as count FROM room_types");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO room_types (name, description) VALUES 
            ('Standard Room', 'Our comfortable standard rooms offer all the essentials for a pleasant stay.'),
            ('Deluxe Room', 'Spacious deluxe rooms with premium amenities and extra comfort.'),
            ('Suite', 'Luxurious suites with separate living area and exclusive amenities.'),
            ('Family Room', 'Perfect for families, these rooms offer extra space and beds.')
        ");
    }
    
    // Insert default categories if not exists
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO categories (name) VALUES ('Food'), ('Drinks'), ('Party Drinks')");
        
        // Get category IDs
        $result = $conn->query("SELECT id, name FROM categories");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[$row['name']] = $row['id'];
        }
        
        // Insert default subcategories
        if (isset($categories['Drinks'])) {
            $conn->query("INSERT INTO subcategories (name, category_id) VALUES 
                ('Soft Drinks', {$categories['Drinks']}),
                ('Hard Drinks', {$categories['Drinks']})");
        }
        
        if (isset($categories['Party Drinks'])) {
            $conn->query("INSERT INTO subcategories (name, category_id) VALUES 
                ('Vodka', {$categories['Party Drinks']}),
                ('Whiskey', {$categories['Party Drinks']}),
                ('Cocktails', {$categories['Party Drinks']})");
        }
    }
}

// Initialize database on first load
initializeDatabase();
