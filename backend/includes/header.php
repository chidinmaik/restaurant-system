<header class="bg-dark text-white">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <a class="navbar-brand" href="index.php">Restaurant Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Menu
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php
                            $menuCategories = getCategories();
                            foreach ($menuCategories as $category) {
                                echo '<li><a class="dropdown-item" href="menu.php?category=' . $category['id'] . '">' . $category['name'] . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php
                    $cartCount = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $quantity) {
                            $cartCount += $quantity;
                        }
                    }
                    ?>
                    <a href="cart.php" class="btn btn-outline-light position-relative me-2">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                   <!-- <?php if (isset($_SESSION['admin_id'])): ?>
                        <a href="admin/index.php" class="btn btn-primary">Admin Panel</a>
                    <?php else: ?>
                        <a href="admin/login.php" class="btn btn-primary">Admin Login</a>
                    <?php endif; ?>-->
                </div>
            </div>
        </nav>
    </div>
</header>
