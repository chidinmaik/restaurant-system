<?php
// This file contains SQL statements to create the necessary tables for the room booking system
// Run this file once to set up the database structure

include '../includes/config.php';

// Get database connection
$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
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
    
    // Commit transaction
    $conn->commit();
    
    echo "Room booking tables created successfully!";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error creating tables: " . $e->getMessage();
}
?>
