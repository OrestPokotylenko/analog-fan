<?php

namespace App\Features\Shipment;

use App\External\SendcloudService;
use App\External\SellerMailer;
use App\Features\Order\OrderModel;
use Exception;

class ShipmentController {
    private ShipmentModel $shipmentModel;
    private ?SendcloudService $sendcloudService;
    private OrderModel $orderModel;
    private SellerMailer $sellerMailer;

    public function __construct() {
        $this->shipmentModel = new ShipmentModel();
        $this->orderModel = new OrderModel();
        $this->sellerMailer = new SellerMailer();
        
        // Initialize Sendcloud service (optional - only if configured)
        try {
            $this->sendcloudService = new SendcloudService();
        } catch (Exception $e) {
            // Sendcloud not configured - delivery features disabled
            $this->sendcloudService = null;
            error_log("Sendcloud initialization failed: " . $e->getMessage());
        }
    }

    /**
     * Get shipping rates for an order
     * Sendcloud provides real-time rates from carriers like PostNL, DHL, DPD
     */
    public function getShippingRates(int $orderId) {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $order = $this->orderModel->getOrderWithUser($orderId);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                return;
            }

            $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
            if (empty($customerName)) {
                $customerName = explode('@', $order['email'])[0] ?? 'Customer';
            }

            $toAddress = [
                'name' => $customerName,
                'email' => $order['email'],
                'street1' => $order['street'] . ' ' . $order['house_number'],
                'city' => $order['city'],
                'zip' => $order['zip_code'],
                'country' => $order['country'],
            ];

            $result = $this->sendcloudService->getShippingRates($toAddress);
            
            http_response_code(200);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a shipping label for an order
     */
    public function createShippingLabel(array $data) {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $orderId = $data['order_id'] ?? null;
            $rateId = $data['rate_id'] ?? null;

            if (!$orderId || !$rateId) {
                http_response_code(400);
                echo json_encode(['error' => 'order_id and rate_id are required']);
                return;
            }

            $order = $this->orderModel->getOrderWithUser($orderId);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                return;
            }

            // Check if shipment already exists
            $existingShipment = $this->shipmentModel->getShipmentByOrderId($orderId);
            if ($existingShipment) {
                http_response_code(400);
                echo json_encode(['error' => 'Shipment already exists for this order']);
                return;
            }

            $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
            if (empty($customerName)) {
                $customerName = explode('@', $order['email'])[0] ?? 'Customer';
            }

            // Prepare address for Sendcloud
            $toAddress = [
                'name' => $customerName,
                'email' => $order['email'],
                'street1' => $order['street'] . ' ' . $order['house_number'],
                'city' => $order['city'],
                'zip' => $order['zip_code'],
                'country' => $order['country'],
            ];

            // Create the label
            $label = $this->sendcloudService->createLabel($toAddress, $rateId, [
                'description' => 'Analog Fan Order #' . $order['order_number'],
            ]);

            // Save shipment to database
            $shipmentData = [
                'order_id' => $orderId,
                'shippo_transaction_id' => $label['transaction_id'], // Repurpose this field
                'carrier' => $label['carrier'],
                'service' => $label['service'],
                'tracking_number' => $label['tracking_number'],
                'tracking_url' => $label['tracking_url_provider'],
                'label_url' => $label['label_url'],
                'shipping_cost' => $order['shipping_cost'],
                'currency' => 'EUR',
                'delivery_status' => 'label_created',
                'estimated_delivery_date' => $label['eta'],
            ];

            $shipment = $this->shipmentModel->createShipment($shipmentData);

            // Update order with tracking number and status
            $this->orderModel->updateOrder($orderId, [
                'tracking_number' => $label['tracking_number'],
                'order_status' => 'processing',
            ]);

            // Send label to seller via email
            try {
                $pdfContent = $this->sendcloudService->downloadLabel($label['label_url']);
                $this->sellerMailer->sendShippingLabel($order, $shipment, $pdfContent);
                error_log("Seller notification sent for order #{$order['order_number']}");
            } catch (Exception $e) {
                error_log("Failed to send seller notification: " . $e->getMessage());
            }

            http_response_code(201);
            echo json_encode(ShipmentDTO::fromArray($shipment)->toArray());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get shipment by order ID
     */
    public function getShipmentByOrderId(int $orderId) {
        try {
            $shipment = $this->shipmentModel->getShipmentByOrderId($orderId);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Shipment not found']);
                return;
            }

            http_response_code(200);
            echo json_encode(ShipmentDTO::fromArray($shipment)->toArray());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update tracking information
     */
    public function updateTracking(int $shipmentId) {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $shipment = $this->shipmentModel->getShipmentById($shipmentId);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Shipment not found']);
                return;
            }

            // Get order to fetch postal code
            $order = $this->orderModel->getOrderById($shipment['order_id']);

            // Fetch latest tracking info from Sendcloud
            $tracking = $this->sendcloudService->getTrackingInfo(
                $shipment['tracking_number'],
                $order['zip_code'] ?? ''
            );

            // Update shipment with newtracking info
            $updateData = [
                'delivery_status' => $tracking['status'],
            ];

            // Update timestamps based on status
            if (in_array($tracking['status'], ['DELIVERED', 'delivered']) && !$shipment['delivered_at']) {
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
                
                // Update order status
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'delivered',
                    'delivered_at' => $updateData['delivered_at'],
                ]);
            } elseif (in_array($tracking['status'], ['TRANSIT', 'in_transit']) && !$shipment['shipped_at']) {
                $updateData['shipped_at'] = date('Y-m-d H:i:s');
                
                // Update order status
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'shipped',
                    'shipped_at' => $updateData['shipped_at'],
                ]);
            }

            $updatedShipment = $this->shipmentModel->updateShipment($shipmentId, $updateData);
            
            http_response_code(200);
            echo json_encode(ShipmentDTO::fromArray($updatedShipment)->toArray());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get tracking info by tracking number (public endpoint)
     */
    public function trackShipment(string $trackingNumber) {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $shipment = $this->shipmentModel->getShipmentByTrackingNumber($trackingNumber);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Tracking number not found']);
                return;
            }

            // Get order to fetch postal code
            $order = $this->orderModel->getOrderById($shipment['order_id']);

            // Fetch latest tracking info
            $tracking = $this->sendcloudService->getTrackingInfo(
                $trackingNumber,
                $order['zip_code'] ?? ''
            );

            http_response_code(200);
            echo json_encode([
                'shipment' => ShipmentDTO::fromArray($shipment)->toArray(),
                'tracking' => $tracking,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all shipments (admin)
     */
    public function getAllShipments() {
        try {
            $shipments = $this->shipmentModel->getAllShipments();
            $dtos = array_map(fn($s) => ShipmentDTO::fromArray($s)->toArray(), $shipments);
            
            http_response_code(200);
            echo json_encode($dtos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all shipments with order details for admin view
     */
    public function getAllShipmentsWithOrders() {
        try {
            $shipments = $this->shipmentModel->getAllShipmentsWithOrders();
            
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $shipments]);
        } catch (Exception $e) {
            error_log("Error fetching shipments with orders: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available carriers
     */
    public function getCarriers() {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $carriers = $this->sendcloudService->getCarriers();
            
            http_response_code(200);
            echo json_encode($carriers);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download shipping label PDF
     */
    public function downloadLabel(int $shipmentId) {
        try {
            if (!$this->sendcloudService) {
                http_response_code(503);
                echo json_encode(['error' => 'Shipping service not configured']);
                return;
            }
            
            $shipment = $this->shipmentModel->getShipmentById($shipmentId);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Shipment not found']);
                return;
            }
            
            if (empty($shipment['label_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No label URL available']);
                return;
            }
            
            // Download PDF through authenticated service
            $pdfContent = $this->sendcloudService->downloadLabel($shipment['label_url']);
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="shipping-label-' . $shipment['tracking_number'] . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            
            echo $pdfContent;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Webhook handler for Shippo tracking updates
     */
    public function handleTrackingWebhook() {
        try {
            $payload = json_decode(file_get_contents('php://input'), true);
            
            if (!$payload || !isset($payload['data'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid webhook payload']);
                return;
            }

            $trackingNumber = $payload['data']['tracking_number'] ?? null;
            $trackingStatus = $payload['data']['tracking_status']['status'] ?? null;

            if (!$trackingNumber) {
                http_response_code(400);
                echo json_encode(['error' => 'Tracking number not found in payload']);
                return;
            }

            $shipment = $this->shipmentModel->getShipmentByTrackingNumber($trackingNumber);
            
            if ($shipment) {
                // Update shipment status
                $updateData = [
                    'delivery_status' => $trackingStatus,
                    'tracking_history' => $payload['data']['tracking_history'] ?? null,
                ];

                if ($trackingStatus === 'DELIVERED') {
                    $updateData['delivered_at'] = date('Y-m-d H:i:s');
                    $this->orderModel->updateOrder($shipment['order_id'], [
                        'order_status' => 'delivered',
                        'delivered_at' => $updateData['delivered_at'],
                    ]);
                }

                $this->shipmentModel->updateShipment($shipment['id'], $updateData);
            }

            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Webhook handler for Sendcloud status updates
     * This is what Sendcloud will call when parcel status changes
     */
    public function handleSendcloudWebhook() {
        try {
            $payload = json_decode(file_get_contents('php://input'), true);
            error_log("Sendcloud webhook received: " . json_encode($payload));
            
            if (!$payload || !isset($payload['parcel'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid webhook payload']);
                return;
            }

            $parcel = $payload['parcel'];
            $trackingNumber = $parcel['tracking_number'] ?? null;
            $statusCode = $parcel['status']['id'] ?? null;
            $statusMessage = $parcel['status']['message'] ?? null;

            if (!$trackingNumber) {
                http_response_code(400);
                echo json_encode(['error' => 'Tracking number not found in payload']);
                return;
            }

            $shipment = $this->shipmentModel->getShipmentByTrackingNumber($trackingNumber);
            
            if ($shipment) {
                // Map Sendcloud status codes to our delivery statuses
                $deliveryStatus = $this->mapSendcloudStatus($statusCode, $statusMessage);
                
                $updateData = [
                    'delivery_status' => $deliveryStatus,
                ];

                // Update timestamps and order status
                if ($deliveryStatus === 'delivered' && !$shipment['delivered_at']) {
                    $updateData['delivered_at'] = date('Y-m-d H:i:s');
                    $this->orderModel->updateOrder($shipment['order_id'], [
                        'order_status' => 'delivered',
                        'delivered_at' => $updateData['delivered_at'],
                    ]);
                } elseif (in_array($deliveryStatus, ['transit', 'out_for_delivery']) && !$shipment['shipped_at']) {
                    $updateData['shipped_at'] = date('Y-m-d H:i:s');
                    $this->orderModel->updateOrder($shipment['order_id'], [
                        'order_status' => 'shipped',
                        'shipped_at' => $updateData['shipped_at'],
                    ]);
                }

                $this->shipmentModel->updateShipment($shipment['id'], $updateData);
                error_log("Shipment {$shipment['id']} updated to status: {$deliveryStatus}");
            }

            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log("Sendcloud webhook error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mock webhook for testing - simulates Sendcloud webhook call
     * Call this endpoint manually to test status updates
     */
    public function mockSendcloudWebhook(string $trackingNumber, string $status) {
        try {
            $shipment = $this->shipmentModel->getShipmentByTrackingNumber($trackingNumber);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Shipment not found with tracking: ' . $trackingNumber]);
                return;
            }

            // Create mock Sendcloud webhook payload
            $statusMap = [
                'label_created' => ['id' => 1, 'message' => 'Announced'],
                'pre_transit' => ['id' => 2, 'message' => 'En route to sorting center'],
                'transit' => ['id' => 11, 'message' => 'En route to recipient'],
                'out_for_delivery' => ['id' => 12, 'message' => 'Out for delivery'],
                'delivered' => ['id' => 13, 'message' => 'Delivered'],
                'failure' => ['id' => 15, 'message' => 'Delivery attempt failed'],
                'returned' => ['id' => 6, 'message' => 'Returned to sender'],
            ];

            if (!isset($statusMap[$status])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Invalid status. Allowed: ' . implode(', ', array_keys($statusMap))
                ]);
                return;
            }

            $mockPayload = [
                'parcel' => [
                    'id' => $shipment['transaction_id'],
                    'tracking_number' => $trackingNumber,
                    'status' => $statusMap[$status],
                    'carrier' => [
                        'code' => $shipment['carrier']
                    ]
                ],
                'action' => 'parcel_status_changed',
                'timestamp' => time()
            ];

            error_log("Mock webhook triggered for {$trackingNumber} with status {$status}");
            error_log("Mock payload: " . json_encode($mockPayload));

            // Process it as if it came from Sendcloud
            $_POST = $mockPayload; // For any code that reads from POST
            file_put_contents('php://input', json_encode($mockPayload)); // Won't work, but for clarity
            
            // Directly update the shipment
            $deliveryStatus = $this->mapSendcloudStatus($statusMap[$status]['id'], $statusMap[$status]['message']);
            
            $updateData = [
                'delivery_status' => $deliveryStatus,
            ];

            if ($deliveryStatus === 'delivered' && !$shipment['delivered_at']) {
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'delivered',
                    'delivered_at' => $updateData['delivered_at'],
                ]);
            } elseif (in_array($deliveryStatus, ['transit', 'out_for_delivery']) && !$shipment['shipped_at']) {
                $updateData['shipped_at'] = date('Y-m-d H:i:s');
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'shipped',
                    'shipped_at' => $updateData['shipped_at'],
                ]);
            }

            $result = $this->shipmentModel->updateShipment($shipment['id'], $updateData);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Mock webhook processed',
                'shipment' => $result,
                'mock_payload' => $mockPayload
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Map Sendcloud status codes to our delivery statuses
     */
    private function mapSendcloudStatus(?int $statusCode, ?string $statusMessage): string {
        // Sendcloud status codes: https://docs.sendcloud.sc/api/v2/shipping/#parcel-statuses
        return match($statusCode) {
            1, 2 => 'pre_transit',          // Announced, En route to sorting center
            3, 4, 5, 11 => 'transit',       // Delivered at sorting center, Scheduled, Sorted, En route to recipient
            12 => 'out_for_delivery',       // Out for delivery
            13 => 'delivered',              // Delivered
            14, 15 => 'failure',            // Exception, Delivery attempt failed
            6, 7 => 'returned',             // Returned to sender, Awaiting customer pickup
            default => 'unknown'
        };
    }
    
    /**
     * Manually update shipment status (for school project demo)
     */
    public function updateShipmentStatus(int $shipmentId, array $data) {
        try {
            $shipment = $this->shipmentModel->getShipmentById($shipmentId);
            
            if (!$shipment) {
                http_response_code(404);
                echo json_encode(['error' => 'Shipment not found']);
                return;
            }
            
            $allowedStatuses = ['label_created', 'pre_transit', 'transit', 'out_for_delivery', 'delivered', 'failure', 'returned'];
            
            if (!isset($data['status']) || !in_array($data['status'], $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses)]);
                return;
            }
            
            $updateData = ['delivery_status' => $data['status']];
            
            // Update timestamps based on status
            if ($data['status'] === 'delivered' && !$shipment['delivered_at']) {
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
                
                // Update order status
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'delivered',
                    'delivered_at' => $updateData['delivered_at'],
                ]);
            } elseif (in_array($data['status'], ['transit', 'out_for_delivery']) && !$shipment['shipped_at']) {
                $updateData['shipped_at'] = date('Y-m-d H:i:s');
                
                // Update order status
                $this->orderModel->updateOrder($shipment['order_id'], [
                    'order_status' => 'shipped',
                    'shipped_at' => $updateData['shipped_at'],
                ]);
            }
            
            $result = $this->shipmentModel->updateShipment($shipmentId, $updateData);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
