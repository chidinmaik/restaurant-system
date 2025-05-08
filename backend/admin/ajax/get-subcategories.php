<?php
session_start();
include '../../includes/config.php';
include '../../includes/admin-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if category ID is provided
if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Category ID is required']);
    exit;
}

$categoryId = intval($_POST['category_id']);
$subcategories = getSubcategoriesByCategory($categoryId);

// Return subcategories as JSON
header('Content-Type: application/json');
echo json_encode($subcategories);
exit;
