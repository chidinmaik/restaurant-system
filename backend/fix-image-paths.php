<?php
include 'includes/config.php';

// Connect to database
$conn = getDbConnection();

// Get all products
$result = $conn->query("SELECT id, image FROM products");

echo "<h2>Updating Image Paths</h2>";
echo "<pre>";

while ($row = $result->fetch_assoc()) {
    $oldPath = $row['image'];
    
    // Extract just the filename
    $filename = basename($oldPath);
    
    // Update the path in the database
    $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
    $stmt->bind_param("si", $filename, $row['id']);
    
    if ($stmt->execute()) {
        echo "Updated product {$row['id']}: {$oldPath} -> {$filename}\n";
    } else {
        echo "Failed to update product {$row['id']}\n";
    }
}

echo "</pre>";
echo "<p>Done updating image paths.</p>";
?>