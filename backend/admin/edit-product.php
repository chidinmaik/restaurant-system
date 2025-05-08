<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = intval($_GET['id']);
$product = getProductById($productId);

// Verify product exists
if (!$product) {
    header('Location: products.php');
    exit;
}

// Get categories
$categories = getCategories();

// Get subcategories for the product's category
$subcategories = getSubcategoriesByCategory($product['category_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate form data
    if (empty($_POST['name'])) {
        $errors[] = 'Product name is required.';
    }
    
    if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        $errors[] = 'Valid price is required.';
    }
    
    if (empty($_POST['category_id'])) {
        $errors[] = 'Category is required.';
    }
    
    // Handle image upload if a new image is provided
    $imagePath = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed.';
        }
        
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'File size must be less than 2MB.';
        }
        
        if (empty($errors)) {
            $target_dir = "../uploads/products/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {

                $imagePath = $filename;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // If no errors, update product
    if (empty($errors)) {
        $productData = [
            'id' => $productId,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category_id' => $_POST['category_id'],
            'subcategory_id' => !empty($_POST['subcategory_id']) ? $_POST['subcategory_id'] : null,
            'in_stock' => isset($_POST['in_stock']) ? 1 : 0,
            'image' => $imagePath
        ];
        
        if (updateProduct($productData)) {
            $_SESSION['success_message'] = 'Product updated successfully.';
            header('Location: products.php');
            exit;
        } else {
            $errors[] = 'Failed to update product. Please try again.';
        }
    }
    
    // If category is changed, get new subcategories
    if (!empty($_POST['category_id']) && $_POST['category_id'] != $product['category_id']) {
        $subcategories = getSubcategoriesByCategory($_POST['category_id']);
        $product['category_id'] = $_POST['category_id'];
        $product['subcategory_id'] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Restaurant Management System</title>
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
                    <h1 class="h2">Edit Product</h1>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Product Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="edit-product.php?id=<?php echo $productId; ?>" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo $product['price']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required onchange="loadSubcategories(this.value)">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php if ($product['category_id'] == $category['id']) echo 'selected'; ?>>
                                                <?php echo $category['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="subcategory_id" class="form-label">Subcategory</label>
                                    <select class="form-select" id="subcategory_id" name="subcategory_id" <?php if (empty($subcategories)) echo 'disabled'; ?>>
                                        <option value="">Select Subcategory</option>
                                        <?php foreach ($subcategories as $subcategory): ?>
                                            <option value="<?php echo $subcategory['id']; ?>" <?php if ($product['subcategory_id'] == $subcategory['id']) echo 'selected'; ?>>
                                                <?php echo $subcategory['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input class="form-control" type="file" id="image" name="image" accept="image/*">
                                <div class="form-text">Leave empty to keep the current image. Accepted formats: JPG, JPEG, PNG. Maximum file size: 2MB.</div>
                                
                                <?php if (!empty($product['image'])): ?>
                                    <div class="mt-2">
                                        <p>Current Image:</p>
                                        <img src="<?php echo str_replace('/uploads/', '../uploads/', $product['image']); ?>" alt="<?php echo $product['name']; ?>" width="50" class="img-thumbnail">
                                                
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="in_stock" name="in_stock" <?php if ($product['in_stock']) echo 'checked'; ?>>
                                <label class="form-check-label" for="in_stock">In Stock</label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        function loadSubcategories(categoryId) {
            if (categoryId) {
                $.ajax({
                    url: 'ajax/get-subcategories.php',
                    type: 'POST',
                    data: {category_id: categoryId},
                    dataType: 'json',
                    success: function(data) {
                        var subcategorySelect = $('#subcategory_id');
                        subcategorySelect.empty();
                        subcategorySelect.append('<option value="">Select Subcategory</option>');
                        
                        if (data.length > 0) {
                            $.each(data, function(index, subcategory) {
                                subcategorySelect.append('<option value="' + subcategory.id + '">' + subcategory.name + '</option>');
                            });
                            subcategorySelect.prop('disabled', false);
                        } else {
                            subcategorySelect.prop('disabled', true);
                        }
                    },
                    error: function() {
                        alert('Failed to load subcategories. Please try again.');
                    }
                });
            } else {
                $('#subcategory_id').empty().append('<option value="">Select Subcategory</option>').prop('disabled', true);
            }
        }
    </script>
</body>
</html>
