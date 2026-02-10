<?php

use App\Features\Order\OrderItemController;
use App\Core\Route;

$orderItemController = new OrderItemController();

// Get all items for a specific order
Route::add('/api/order-items/order/([0-9]+)', function ($orderId) use ($orderItemController) {
    $orderItemController->getItemsByOrderId((int)$orderId);
});

// Get specific order item by ID
Route::add('/api/order-items/([0-9]+)', function ($itemId) use ($orderItemController) {
    $orderItemController->getOrderItemById((int)$itemId);
});

// Add item to order
Route::add('/api/order-items', function () use ($orderItemController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderItemController->addOrderItem($data);
}, 'post');

// Update order item
Route::add('/api/order-items/([0-9]+)', function ($itemId) use ($orderItemController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderItemController->updateOrderItem((int)$itemId, $data);
}, 'put');

// Delete order item
Route::add('/api/order-items/([0-9]+)', function ($itemId) use ($orderItemController) {
    $orderItemController->deleteOrderItem((int)$itemId);
}, 'delete');
