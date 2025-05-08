<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details
$product = getProductById($productId);

// If product not found, redirect to home
if (!$product) {
    header('Location: index.php');
    exit;
}

// Get related products
$relatedProducts = getRelatedProducts($product['category_id'], $productId);

// Handle image path
$imageName = basename($product['image']);
$imagePath = 'Uploads/products/' . $imageName;
if (empty($imageName) || !file_exists($imagePath)) {
    $imagePath = 'assets/images/no-image.jpg';
}

// Mock additional images (replace with actual data if available)
$additionalImages = [$imagePath, $imagePath, $imagePath, $imagePath];

// Mock preparation time and cuisine (replace with actual data if available)
$prepTime = '15 Min';
$cuisine = getCategoryById($product['category_id'])['name'];

// Mock ingredients (replace with actual data if available)
$ingredients = ['1 cup Pasta', '200g Tomato', '2 tsp Olive Oil'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Restaurant Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-4">
        <!-- Hero Image -->
        <div class="mb-3">
            <img src="<?php echo $imagePath; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" style="width: 100%; height: 250px; object-fit: cover;">
        </div>

        <!-- Additional Images -->
        <div class="d-flex overflow-auto gap-2 mb-3 pb-2">
            <?php
            foreach ($additionalImages as $img) {
                echo '<img src="' . $img . '" class="rounded" style="width: 80px; height: 80px; object-fit: cover;" alt="Additional Image">';
            }
            ?>
        </div>

        <!-- Item Details -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="text-light mb-0"><?php echo htmlspecialchars($product['name']); ?></h4>
                <span class="text-orange"><i class="fas fa-star"></i> 4.9</span>
            </div>
            <p class="text-gray mb-1">
                Category: <a href="menu.php?category=<?php echo $product['category_id']; ?>" class="text-orange text-decoration-none"><?php echo htmlspecialchars(getCategoryById($product['category_id'])['name']); ?></a>
                <?php if ($product['subcategory_id']): ?>
                    | Subcategory: <a href="menu.php?category=<?php echo $product['category_id']; ?>&subcategory=<?php echo $product['subcategory_id']; ?>" class="text-orange text-decoration-none"><?php echo htmlspecialchars(getSubcategoryById($product['subcategory_id'])['name']); ?></a>
                <?php endif; ?>
            </p>
            <p class="text-gray"><?php echo htmlspecialchars($product['description']); ?></p>
            <?php if ($product['in_stock']): ?>
                <span class="badge bg-success mb-3">In Stock</span>
            <?php else: ?>
                <span class="badge bg-danger mb-3">Out of Stock</span>
            <?php endif; ?>
        </div>

        <!-- Additional Info -
        <div class="d-flex gap-3 mb-3">
            <div class="text-center">
                <i class="fas fa-clock text-orange"></i>
                <p class="text-gray mb-0"><?php echo $prepTime; ?></p>
            </div>
            <div class="text-center">
                <i class="fas fa-utensils text-orange"></i>
                <p class="text-gray mb-0"><?php echo $cuisine; ?></p>
            </div>
        </div>

         Ingredients -
        <div class="mb-3">
            <h6 class="text-light">Ingredients</h6>
            <ul class="list-unstyled text-gray">
                <?php foreach ($ingredients as $ingredient): ?>
                    <li><?php echo htmlspecialchars($ingredient); ?></li>
                <?php endforeach; ?>
            </ul>
        </div> Serving Selector -
        <div class="mb-3">
            <h6 class="text-light">Servings</h6>
            <select class="form-select bg-dark text-light border-gray" id="quantity" style="width: auto;">
                <option value="1">1 Serving</option>
                <option value="2">2 Servings</option>
                <option value="3">3 Servings</option>
                <option value="4">4 Servings</option>
                <option value="5">5 Servings</option>
            </select>
        </div>

        Add to Cart Button -->
        <button onclick="showCartModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $imagePath; ?>')" class="btn btn-orange w-100 py-3">Add to Cart</button>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-light mb-0">Related Items</h5>
                    <a href="menu.php?category=<?php echo $product['category_id']; ?>" class="text-orange text-decoration-none">See All</a>
                </div>
                <div class="d-flex overflow-auto gap-3 pb-2">
                    <?php foreach ($relatedProducts as $relProduct): 
                        $relImageName = basename($relProduct['image']);
                        $relImagePath = 'Uploads/products/' . $relImageName;
                        if (empty($relImageName) || !file_exists($relImagePath)) {
                            $relImagePath = 'assets/images/no-image.jpg';
                        }
                    ?>
                        <div class="card bg-dark border-0 shadow-sm" style="min-width: 200px;">
                            <img src="<?php echo $relImagePath; ?>" class="card-img-top rounded-top" alt="<?php echo htmlspecialchars($relProduct['name']); ?>" loading="lazy">
                            <div class="card-body p-2">
                                <h6 class="card-title text-light mb-1"><?php echo htmlspecialchars($relProduct['name']); ?></h6>
                                <p class="text-orange mb-1">$<?php echo number_format($relProduct['price'], 2); ?></p>
                                <a href="product.php?id=<?php echo $relProduct['id']; ?>" class="btn btn-outline-light btn-sm w-100">Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-gray">
                <div class="modal-header bg-orange text-dark">
                    <h5 class="modal-title" id="cartModalLabel">Add to Cart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <img id="modalImage" src="" class="img-thumbnail me-3" style="width: 80px;" alt="Item Image">
                        <div>
                            <h6 id="modalItemName" class="mb-1"></h6>
                            <p id="modalItemPrice" class="mb-0 text-orange"></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modalQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control bg-dark text-light border-gray" id="modalQuantity" value="1" min="1" max="10">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-orange" onclick="addToCartFromModal()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>