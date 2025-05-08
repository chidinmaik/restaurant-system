<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Handle cart updates
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        updateCartItemQuantity($id, $quantity);
    }
    $_SESSION['success_message'] = 'Cart updated successfully.';
    header('Location: cart.php');
    exit;
}

// Handle cart removal
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    removeFromCart($_GET['remove']);
    $_SESSION['success_message'] = 'Item removed from cart.';
    header('Location: cart.php');
    exit;
}

// Get cart items
$cartItems = getCartItems();
$totalAmount = calculateCartTotal();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Restaurant Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-dark text-light">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-4">
        <h1 class="mb-4 text-light">Shopping Cart</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show bg-dark text-orange border-gray" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info bg-dark text-gray border-gray">
                Your cart is empty. <a href="index.php" class="alert-link text-orange">Continue shopping</a>.
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <div class="card bg-dark border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover text-gray">
                                <thead class="bg-orange text-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): 
                                        $imageName = basename($item['image']);
                                        $imagePath = 'Uploads/products/' . $imageName;
                                        if (empty($imageName) || !file_exists($imagePath)) {
                                            $imagePath = 'assets/images/no-image.jpg';
                                        }
                                    ?>
                                        <tr class="align-middle">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-3 rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0 text-light"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-orange">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" class="form-control bg-dark text-light border-gray text-center" style="width: 80px;" value="<?php echo $item['quantity']; ?>" min="1">
                                            </td>
                                            <td class="text-orange">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td>
                                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-red"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end text-light">Total:</th>
                                        <th class="text-orange">$<?php echo number_format($totalAmount, 2); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <a href="menu.php?category=1" class="btn btn-outline-light btn-lg w-100 w-md-auto">Continue Shopping</a>
                    <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                        <button type="submit" name="update_cart" class="btn btn-outline-light btn-lg w-100">Update Cart</button>
                        <a href="checkout.php" class="btn btn-orange btn-lg w-100">Proceed to Checkout</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>