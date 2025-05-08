<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Get category ID from URL
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 1;
$subcategoryId = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : null;

// Get category details
$category = getCategoryById($categoryId);

// Get subcategories
$subcategories = getSubcategoriesByCategory($categoryId);

// Pagination settings
$itemsPerPage = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get products with pagination
if ($subcategoryId) {
    $products = getProductsBySubcategory($subcategoryId, $offset, $itemsPerPage);
    $totalProducts = countProductsBySubcategory($subcategoryId);
    $subcategory = getSubcategoryById($subcategoryId);
    $pageTitle = $subcategory['name'] . ' - ' . $category['name'];
} else {
    $products = getProductsByCategory($categoryId, $offset, $itemsPerPage);
    $totalProducts = countProductsByCategory($categoryId);
    $pageTitle = $category['name'];
}

// Calculate total pages
$totalPages = ceil($totalProducts / $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> Menu - Restaurant Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-4 mb-5">
        <div class="row">
            <!-- Sidebar (Collapsible on Mobile) -->
            <div class="col-md-3 mb-4 mb-md-0">
                <!-- Mobile Toggle Button -->
                <button class="btn btn-orange d-md-none w-100 mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-expanded="false" aria-controls="sidebarCollapse">
                    <i class="fas fa-bars me-2"></i>Categories & Filters
                </button>

                <div class="collapse d-md-block" id="sidebarCollapse">
                    <!-- Categories -->
                    <div class="card bg-dark border-0 shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-orange text-dark">
                            <h4 class="mb-0"><i class="fas fa-list me-2"></i>Categories</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php
                                $categories = getCategories();
                                foreach ($categories as $cat) {
                                    $activeClass = ($cat['id'] == $categoryId) ? 'active' : '';
                                    echo '<li class="list-group-item bg-dark border-gray ' . $activeClass . '"><a href="menu.php?category=' . $cat['id'] . '" class="text-gray text-decoration-none">' . htmlspecialchars($cat['name']) . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Subcategories -->
                    <?php if (!empty($subcategories)): ?>
                        <div class="card bg-dark border-0 shadow-sm mt-3">
                            <div class="card-header bg-gradient-orange text-dark">
                                <h4 class="mb-0"><i class="fas fa-tags me-2"></i>Subcategories</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-dark border-gray"><a href="menu.php?category=<?php echo $categoryId; ?>" class="text-gray text-decoration-none">All <?php echo htmlspecialchars($category['name']); ?></a></li>
                                    <?php
                                    foreach ($subcategories as $subcat) {
                                        $activeClass = ($subcat['id'] == $subcategoryId) ? 'active' : '';
                                        echo '<li class="list-group-item bg-dark border-gray ' . $activeClass . '"><a href="menu.php?category=' . $categoryId . '&subcategory=' . $subcat['id'] . '" class="text-gray text-decoration-none">' . htmlspecialchars($subcat['name']) . '</a></li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products -->
            <div class="col-md-9">
                <div class="card bg-dark border-0 shadow-sm">
                    <div class="card-header bg-gradient-orange text-dark d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php echo htmlspecialchars($pageTitle); ?></h4>
                        <div class="d-flex gap-2">
                            <select class="form-select bg-dark text-light border-gray" style="width: auto;" onchange="sortProducts(this.value)">
                                <option value="">Sort by: Default</option>
                                <option value="price-asc">Price: Low to High</option>
                                <option value="price-desc">Price: High to Low</option>
                                <option value="name-asc">Name: A to Z</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info bg-dark border-gray text-gray">
                                No products found in this category. <a href="index.php" class="alert-link text-orange">Back to Home</a>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-2 g-4" id="productList">
                                <?php foreach ($products as $product): 
                                    $imagePath = getProductImageUrl($product['image']);
                                ?>
                                    <div class="col">
                                        <div class="card h-100 bg-dark border-0 shadow-sm position-relative">
                                            <img src="<?php echo $imagePath; ?>" class="card-img-top rounded-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 220px; object-fit: cover;">
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title text-light"><?php echo htmlspecialchars($product['name']); ?></h5>
                                                <p class="card-text text-gray flex-grow-1"><?php echo substr(htmlspecialchars($product['description']), 0, 80) . '...'; ?></p>
                                                <p class="text-orange fw-bold">$<?php echo number_format($product['price'], 2); ?></p>
                                                <div class="d-flex gap-2 mt-auto">
                                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-pull-up btn-outline-light btn-sm flex-fill">View Details</a>
                                                    <button onclick="showCartModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $imagePath; ?>')" class="btn btn-pull-up btn-orange btn-sm flex-fill">Add to Cart</button>
                                                </div>
                                            </div>
                                            <?php if (!$product['in_stock']): ?>
                                                <div class="position-absolute top-0 start-0 bg-red text-white p-2 rounded-bottom-right">
                                                    Out of Stock
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link bg-dark border-gray text-gray" href="menu.php?category=<?php echo $categoryId; ?><?php echo $subcategoryId ? '&subcategory=' . $subcategoryId : ''; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link bg-dark border-gray text-gray" href="menu.php?category=<?php echo $categoryId; ?><?php echo $subcategoryId ? '&subcategory=' . $subcategoryId : ''; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link bg-dark border-gray text-gray" href="menu.php?category=<?php echo $categoryId; ?><?php echo $subcategoryId ? '&subcategory=' . $subcategoryId : ''; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
    <script>
        function sortProducts(sortOption) {
            const products = <?php echo json_encode($products); ?>;
            let sortedProducts = [...products];

            if (sortOption === 'price-asc') {
                sortedProducts.sort((a, b) => a.price - b.price);
            } else if (sortOption === 'price-desc') {
                sortedProducts.sort((a, b) => b.price - a.price);
            } else if (sortOption === 'name-asc') {
                sortedProducts.sort((a, b) => a.name.localeCompare(b.name));
            }

            const productList = document.getElementById('productList');
            productList.innerHTML = '';

            sortedProducts.forEach(product => {
                const imagePath = '<?php echo addslashes(getProductImageUrl("")); ?>' + product.image.split('/').pop();
                const card = `
                    <div class="col">
                        <div class="card h-100 bg-dark border-0 shadow-sm position-relative">
                            <img src="${imagePath}" class="card-img-top rounded-top" alt="${product.name}" style="height: 220px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-light">${product.name}</h5>
                                <p class="card-text text-gray flex-grow-1">${product.description.substring(0, 80)}...</p>
                                <p class="text-orange fw-bold">$${Number(product.price).toFixed(2)}</p>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="product.php?id=${product.id}" class="btn btn-pull-up btn-outline-light btn-sm flex-fill">View Details</a>
                                    <button onclick="showCartModal(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, '${imagePath}')" class="btn btn-pull-up btn-orange btn-sm flex-fill">Add to Cart</button>
                                </div>
                            </div>
                            ${!product.in_stock ? '<div class="position-absolute top-0 start-0 bg-red text-white p-2 rounded-bottom-right">Out of Stock</div>' : ''}
                        </div>
                    </div>
                `;
                productList.innerHTML += card;
            });
        }
    </script>
</body>
</html>