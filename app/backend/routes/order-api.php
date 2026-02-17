<?php

use App\Features\Order\OrderController;
use App\Features\Order\OrderStatus;
use App\Features\Order\PaymentStatus;
use App\Core\Route;

$orderController = new OrderController();

// Get available order statuses
Route::add('/api/orders/statuses', function () {
    echo json_encode([
        'orderStatuses' => OrderStatus::getAllValues(),
        'paymentStatuses' => PaymentStatus::getAllValues()
    ]);
});

// Get all orders (admin only - you might want to add auth middleware)
Route::add('/api/orders', function () use ($orderController) {
    $orderController->getAllOrders();
});

// Get orders by user ID
Route::add('/api/orders/user/([0-9]+)', function ($userId) use ($orderController) {
    $orderController->getOrdersByUserId((int)$userId);
});

// Get orders by seller ID (orders containing items sold by this user)
Route::add('/api/orders/seller/([0-9]+)', function ($sellerId) use ($orderController) {
    $orderController->getOrdersBySellerId((int)$sellerId);
});

// Get specific order by ID
Route::add('/api/orders/([0-9]+)', function ($orderId) use ($orderController) {
    $orderController->getOrderById((int)$orderId);
});

// Create new order
Route::add('/api/orders', function () use ($orderController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderController->createOrder($data);
}, 'post');

// Update order
Route::add('/api/orders/([0-9]+)', function ($orderId) use ($orderController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderController->updateOrder((int)$orderId, $data);
}, 'put');

// Delete order
Route::add('/api/orders/([0-9]+)', function ($orderId) use ($orderController) {
    $orderController->deleteOrder((int)$orderId);
}, 'delete');
