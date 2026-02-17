<?php

use App\Features\Shipment\ShipmentController;
use App\Features\Shipment\DeliveryStatus;
use App\Core\Route;

$shipmentController = new ShipmentController();

// Get available delivery statuses
Route::add('/api/shipments/statuses', function () {
    echo json_encode([
        'deliveryStatuses' => DeliveryStatus::getAllValues()
    ]);
});

// Get available carriers
Route::add('/api/shipments/carriers', function () use ($shipmentController) {
    $shipmentController->getCarriers();
});

// Get all shipments (admin only)
Route::add('/api/shipments', function () use ($shipmentController) {
    $shipmentController->getAllShipments();
});

// Get all shipments with order details (admin view)
Route::add('/api/shipments/admin', function () use ($shipmentController) {
    $shipmentController->getAllShipmentsWithOrders();
});

// Get shipping rates for an order
Route::add('/api/shipments/rates/order/([0-9]+)', function ($orderId) use ($shipmentController) {
    $shipmentController->getShippingRates((int)$orderId);
});

// Create shipping label for an order
Route::add('/api/shipments/label', function () use ($shipmentController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $shipmentController->createShippingLabel($data);
}, 'post');

// Download shipping label PDF
Route::add('/api/shipments/([0-9]+)/label', function ($shipmentId) use ($shipmentController) {
    $shipmentController->downloadLabel((int)$shipmentId);
});

// Get shipment by order ID
Route::add('/api/shipments/order/([0-9]+)', function ($orderId) use ($shipmentController) {
    $shipmentController->getShipmentByOrderId((int)$orderId);
});

// Update tracking information for a shipment
Route::add('/api/shipments/([0-9]+)/tracking', function ($shipmentId) use ($shipmentController) {
    $shipmentController->updateTracking((int)$shipmentId);
}, 'put');

// Update shipment status manually (for school project demo)
Route::add('/api/shipments/([0-9]+)/status', function ($shipmentId) use ($shipmentController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $shipmentController->updateShipmentStatus((int)$shipmentId, $data);
}, 'put');

// Public tracking endpoint - track by tracking number
Route::add('/api/track/([A-Za-z0-9]+)', function ($trackingNumber) use ($shipmentController) {
    $shipmentController->trackShipment($trackingNumber);
});

// Webhook endpoint for Shippo tracking updates
Route::add('/api/webhooks/shippo/tracking', function () use ($shipmentController) {
    $shipmentController->handleTrackingWebhook();
}, 'post');

// Webhook endpoint for Sendcloud status updates
Route::add('/api/webhooks/sendcloud', function () use ($shipmentController) {
    $shipmentController->handleSendcloudWebhook();
}, 'post');

// Mock webhook for testing - simulates Sendcloud webhook
// Usage: POST /api/webhooks/sendcloud/mock with JSON: {"tracking_number": "3SABCD...", "status": "delivered"}
Route::add('/api/webhooks/sendcloud/mock', function () use ($shipmentController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $trackingNumber = $data['tracking_number'] ?? '';
    $status = $data['status'] ?? 'in_transit';
    $shipmentController->mockSendcloudWebhook($trackingNumber, $status);
}, 'post');

