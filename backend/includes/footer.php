<!-- Footer Navigation -->
<footer class="fixed-bottom bg-dark border-top border-gray py-2">

<?php
                    $cartCount = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $quantity) {
                            $cartCount += $quantity;
                        }
                    }
                    ?>

    <div class="container-fluid">
        <div class="d-flex justify-content-around align-items-center">
            <a href="index.php" class="text-gray text-decoration-none text-center">
              <img width="20" height="20" src="https://img.icons8.com/wired/64/FFFFFF/restaurant.png" alt="restaurant"/>

                <p class="mb-0 small">Restuarant</p>
            </a>
            <a href="cart.php" class="text-gray text-decoration-none text-center position-relative">
            <img width="20" height="20" src="https://img.icons8.com/ios/50/FFFFFF/shopping-cart--v1.png" alt="shopping-cart--v1"/>
                <p class="mb-0 small">Cart <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?></p>
            </a>
            <a href="order-view.php" class="text-gray text-decoration-none text-center">
            <img width="20" height="20" src="https://img.icons8.com/external-others-cattaleeya-thongsriphong/64/FFFFFF/external-calling-food-delivery-outline-others-cattaleeya-thongsriphong-2.png" alt="external-calling-food-delivery-outline-others-cattaleeya-thongsriphong-2"/>
                <p class="mb-0 small">Orders</p>
            </a>
            <a href="../index.html" class="text-gray text-decoration-none text-center">
                       <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20" height="20" viewBox="0 0 50 50" style="fill:#FFFFFF;">
<path d="M 24.962891 1.0546875 A 1.0001 1.0001 0 0 0 24.384766 1.2636719 L 1.3847656 19.210938 A 1.0005659 1.0005659 0 0 0 2.6152344 20.789062 L 4 19.708984 L 4 46 A 1.0001 1.0001 0 0 0 5 47 L 18.832031 47 A 1.0001 1.0001 0 0 0 19.158203 47 L 30.832031 47 A 1.0001 1.0001 0 0 0 31.158203 47 L 45 47 A 1.0001 1.0001 0 0 0 46 46 L 46 19.708984 L 47.384766 20.789062 A 1.0005657 1.0005657 0 1 0 48.615234 19.210938 L 41 13.269531 L 41 6 L 35 6 L 35 8.5859375 L 25.615234 1.2636719 A 1.0001 1.0001 0 0 0 24.962891 1.0546875 z M 25 3.3222656 L 44 18.148438 L 44 45 L 32 45 L 32 26 L 18 26 L 18 45 L 6 45 L 6 18.148438 L 25 3.3222656 z M 37 8 L 39 8 L 39 11.708984 L 37 10.146484 L 37 8 z M 20 28 L 30 28 L 30 45 L 20 45 L 20 28 z"></path>
</svg>
                <p class="mb-0 small">Frontpage</p>
            </a>
        </div>
    </div>
</footer>