<?php

use App\Features\ShoppingCart\ShoppingCartController;
use App\Features\ShoppingCart\ShoppingCartItemController;
use App\Core\Route;

$shoppingCartController = new ShoppingCartController();
$shoppingCartItemController = new ShoppingCartItemController();

// ===========================
// Shopping Cart Routes
// ===========================

// Get user's shopping cart
Route::add('/api/shopping-cart/user/([0-9]+)', function($userId) use ($shoppingCartController) {
    $cart = $shoppingCartController->getCart($userId);
    if ($cart === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Cart not found']);
        exit();
    }
    
    echo json_encode($cart);
});

// Create cart for user (auto-created on first item add, but can be explicit)
Route::add('/api/shopping-cart', function() use ($shoppingCartController) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['userId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'userId is required']);
            exit();
        }
        $cart = $shoppingCartController->createCart($data['userId']);
        echo json_encode($cart);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}, 'post');

// Delete user's cart
Route::add('/api/shopping-cart/user/([0-9]+)', function($userId) use ($shoppingCartController) {
    echo json_encode($shoppingCartController->deleteCart($userId));
}, 'delete');

// Get all items in a cart
Route::add('/api/shopping-cart/([0-9]+)/items', function($cartId) use ($shoppingCartItemController) {
    echo json_encode($shoppingCartItemController->getCartItems($cartId));
});

// Add item to cart
Route::add('/api/shopping-cart/([0-9]+)/items', function($cartId) use ($shoppingCartItemController) {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($shoppingCartItemController->addItemToCart(
        $cartId,
        $data['itemId'],
        $data['quantity'] ?? 1
    ));
}, 'post');

// Update item quantity in cart
Route::add('/api/shopping-cart/items/([0-9]+)', function($cartItemId) use ($shoppingCartItemController) {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($shoppingCartItemController->updateCartItemQuantity($cartItemId, $data['quantity']));
}, 'put');

// Remove specific item from cart
Route::add( '/api/shopping-cart/([0-9]+)/items/([0-9]+)', function($cartId, $cartItemId) use ($shoppingCartItemController) {
    echo json_encode($shoppingCartItemController->removeItemFromCart($cartItemId));
}, 'delete');

// Clear all items from cart (removes items but keeps cart)
Route::add('/api/shopping-cart/([0-9]+)/items', function($cartId) use ($shoppingCartItemController) {
    echo json_encode($shoppingCartItemController->clearCart($cartId));
}, 'delete');