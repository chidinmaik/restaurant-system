<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle category delete
if (isset($_GET['delete_category'])) {
    $categoryId = intval($_GET['delete_category']);
    
    if (deleteCategory($categoryId)) {
        $_SESSION['success_message'] = 'Category deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete category. Make sure it has no products or subcategories.';
    }
    
    header('Location: categories.php');
    exit;
}

// Handle subcategory delete
if (isset($_GET['delete_subcategory'])) {
    $subcategoryId = intval($_GET['delete_subcategory']);
    
    if (deleteSubcategory($subcategoryId)) {
        $_SESSION['success_message'] = 'Subcategory deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete subcategory. Make sure it has no products.';
    }
    
    header('Location: categories.php');
    exit;
}

// Handle category add
if (isset($_POST['add_category'])) {
    $categoryName = trim($_POST['category_name']);
    
    if (empty($categoryName)) {
        $_SESSION['error_message'] = 'Category name is required.';
    } else {
        if (addCategory($categoryName)) {
            $_SESSION['success_message'] = 'Category added successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to add category.';
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Handle subcategory add
if (isset($_POST['add_subcategory'])) {
    $subcategoryName = trim($_POST['subcategory_name']);
    $categoryId = intval($_POST['category_id']);
    
    if (empty($subcategoryName)) {
        $_SESSION['error_message'] = 'Subcategory name is required.';
    } else if (empty($categoryId)) {
        $_SESSION['error_message'] = 'Please select a parent category.';
    } else {
        if (addSubcategory($subcategoryName, $categoryId)) {
            $_SESSION['success_message'] = 'Subcategory added successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to add subcategory.';
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Get categories and subcategories
$categories = getCategoriesWithSubcategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Restaurant Management System</title>
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
                    <h1 class="h2">Manage Categories</h1>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Add Category Form -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Add New Category</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="categories.php">
                                    <div class="mb-3">
                                        <label for="category_name" class="form-label">Category Name</label>
                                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                                    </div>
                                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Subcategory Form -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Add New Subcategory</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="categories.php">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Parent Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subcategory_name" class="form-label">Subcategory Name</label>
                                        <input type="text" class="form-control" id="subcategory_name" name="subcategory_name" required>
                                    </div>
                                    <button type="submit" name="add_subcategory" class="btn btn-primary">Add Subcategory</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Categories and Subcategories List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Categories and Subcategories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Parent Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="table-primary">
                                            <td><?php echo $category['id']; ?></td>
                                            <td><strong><?php echo $category['name']; ?></strong></td>
                                            <td>Category</td>
                                            <td>-</td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $category['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                
                                                <!-- Delete Category Modal -->
                                                <div class="modal fade" id="deleteCategoryModal<?php echo $category['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirm Delete</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete the category: <strong><?php echo $category['name']; ?></strong>?
                                                                <p class="text-danger mt-2">Note: You cannot delete a category that has products or subcategories.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <a href="categories.php?delete_category=<?php echo $category['id']; ?>" class="btn btn-danger">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                                            <tr>
                                                <td><?php echo $subcategory['id']; ?></td>
                                                <td class="ps-4"><?php echo $subcategory['name']; ?></td>
                                                <td>Subcategory</td>
                                                <td><?php echo $category['name']; ?></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteSubcategoryModal<?php echo $subcategory['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    
                                                    <!-- Delete Subcategory Modal -->
                                                    <div class="modal fade" id="deleteSubcategoryModal<?php echo $subcategory['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete the subcategory: <strong><?php echo $subcategory['name']; ?></strong>?
                                                                    <p class="text-danger mt-2">Note: You cannot delete a subcategory that has products.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <a href="categories.php?delete_subcategory=<?php echo $subcategory['id']; ?>" class="btn btn-danger">Delete</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No categories found</td>
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