// Global variable to store current item ID for modal
let currentItemId = null;

// Show cart modal with item details
function showCartModal(id, name, price, image) {
    console.log('Opening modal for item:', { id, name, price, image });
    currentItemId = id;
    $('#modalItemName').text(name);
    $('#modalItemPrice').text(`$${price}`);
    $('#modalImage').attr('src', image);
    $('#modalQuantity').val(1);
    $('#cartModal').modal('show');
}

// Add to cart from modal
function addToCartFromModal() {
    const quantity = parseInt($('#modalQuantity').val());
    const confirmBtn = $('.modal-footer .btn-orange');
    console.log('Adding to cart:', { itemId: currentItemId, quantity });
    if (quantity > 0 && currentItemId) {
        confirmBtn.prop('disabled', true).text('Adding...');
        // Get servings from the product page if available
        const servings = parseInt($('#quantity').val()) || 1;
        addToCart(currentItemId, quantity * servings);
        $('#cartModal').modal('hide');
        confirmBtn.prop('disabled', false).text('Confirm');
    } else {
        showToast('Please enter a valid quantity.', 'bg-danger');
    }
}

// Add to cart function
function addToCart(productId, quantity = 1) {
    console.log('AJAX call to add-to-cart.php:', { productId, quantity });
    $.ajax({
        url: "ajax/add-to-cart.php",
        type: "POST",
        data: {
            product_id: productId,
            quantity: quantity
        },
        dataType: "json",
        success: function(response) {
            console.log('Add to cart response:', response);
            if (response.success) {
                showToast('Product added to cart successfully!', 'bg-orange');
                updateCartCount();
            } else {
                showToast(response.message || 'Failed to add product to cart.', 'bg-danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Add to cart error:', { status, error });
            showToast('An error occurred. Please try again.', 'bg-danger');
        }
    });
}

// Update cart count in header
function updateCartCount() {
    console.log('Updating cart count...');
    $.ajax({
        url: "ajax/get-cart-count.php",
        type: "GET",
        dataType: "json",
        success: function(response) {
            console.log('Cart count response:', response);
            var cartCountElement = $(".fa-shopping-cart").siblings(".badge");
            if (response.count > 0) {
                if (cartCountElement.length) {
                    cartCountElement.text(response.count);
                } else {
                    $(".fa-shopping-cart").after(
                        '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange text-dark">' +
                        response.count +
                        '</span>'
                    );
                }
            } else {
                cartCountElement.remove();
            }
        },
        error: function(xhr, status, error) {
            console.error('Cart count error:', { status, error });
            showToast('Failed to update cart count.', 'bg-danger');
        }
    });
}

// Show toast notification
function showToast(message, bgClass = 'bg-orange') {
    const toastHtml = `
        <div class="toast align-items-center ${bgClass} text-light border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    $('.toast-container').append(toastHtml);
    $('.toast').last().toast({ delay: 3000 }).toast('show');
    $('.toast').on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Document ready
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize toast container if not present
    if (!$('.toast-container').length) {
        $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }

    // Update cart count on page load
    updateCartCount();
});