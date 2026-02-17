<?php

namespace App\Features\Order;

use App\Features\Item\ItemModel;
use App\Features\Shipment\ShipmentModel;
use App\Features\Shipment\ShipmentDTO;
use App\External\Mailer;
use App\External\SellerMailer;
use App\External\SendcloudService;
use Exception;

class OrderController {
    private OrderModel $model;
    private OrderItemModel $orderItemModel;
    private ItemModel $itemModel;
    private Mailer $mailer;
    private ?SellerMailer $sellerMailer;
    private ?ShipmentModel $shipmentModel;
    private ?SendcloudService $sendcloudService;

    public function __construct() {
        $this->model = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->itemModel = new ItemModel();
        $this->mailer = new Mailer();
        
       // Initialize shipment services (optional - only if configured)
        try {
            $this->sendcloudService = new SendcloudService();
            $this->shipmentModel = new ShipmentModel();
            $this->sellerMailer = new SellerMailer();
        } catch (Exception $e) {
            // Sendcloud not configured - delivery features disabled
            $this->sendcloudService = null;
            $this->shipmentModel = null;
            $this->sellerMailer = null;
            error_log("Sendcloud initialization in OrderController failed: " . $e->getMessage());
        }
    }

    public function getAllOrders() {
        try {
            $orders = $this->model->getAllOrders();
            $dtos = array_map(function($order) {
                return OrderDTO::toDTO($order)->toArray();
            }, $orders);
            echo json_encode($dtos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOrdersByUserId(int $userId) {
        try {
            $orders = $this->model->getOrdersByUserId($userId);
            $dtos = array_map(function($order) {
                return OrderDTO::toDTO($order)->toArray();
            }, $orders);
            echo json_encode($dtos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Get orders containing items sold by this user
    public function getOrdersBySellerId(int $sellerId) {
        try {
            $orders = $this->model->getOrdersBySellerId($sellerId);
            $dtos = array_map(function($order) {
                return OrderDTO::toDTO($order)->toArray();
            }, $orders);
            echo json_encode($dtos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOrderById(int $orderId) {
        try {
            $order = $this->model->getOrderById($orderId);
            if ($order) {
                echo json_encode(OrderDTO::toDTO($order)->toArray());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function createOrder(array $data) {
        try {
            // Validate required fields
            $required = ['user_id', 'email', 'street', 'house_number', 'city', 'zip_code', 'country', 'subtotal', 'total_amount'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Validate order_status if provided
            if (!empty($data['order_status'])) {
                OrderStatus::fromString($data['order_status']); // Throws exception if invalid
            }

            // Validate payment_status if provided
            if (!empty($data['payment_status'])) {
                PaymentStatus::fromString($data['payment_status']); // Throws exception if invalid
            }

            // Generate order number if not provided
            if (empty($data['order_number'])) {
                $data['order_number'] = $this->model->generateOrderNumber();
            }

            $order = $this->model->createOrder($data);
            http_response_code(201);
            echo json_encode(OrderDTO::toDTO($order)->toArray());
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateOrder(int $orderId, array $data) {
        try {
            // Validate order_status if provided
            if (!empty($data['order_status'])) {
                OrderStatus::fromString($data['order_status']); // Throws exception if invalid
            }

            // Validate payment_status if provided
            if (!empty($data['payment_status'])) {
                PaymentStatus::fromString($data['payment_status']); // Throws exception if invalid
            }

            // Get the current order to check previous payment status
            $currentOrder = $this->model->getOrderById($orderId);
            
            // Update the order
            $order = $this->model->updateOrder($orderId, $data);
            
            // If payment status changed to 'paid' or 'completed', decrement item quantities
            if ($order && !empty($data['payment_status'])) {
                $newStatus = strtolower($data['payment_status']);
                $oldStatus = strtolower($currentOrder['payment_status'] ?? '');
                
                // Only decrement if status changed to paid/completed from a non-paid status
                if (($newStatus === 'paid' || $newStatus === 'completed') && 
                    $oldStatus !== 'paid' && $oldStatus !== 'completed') {
                    
                    // Get all order items
                    $orderItems = $this->orderItemModel->getItemsByOrderId($orderId);
                    
                    // Decrement quantity for each item
                    foreach ($orderItems as $orderItem) {
                        try {
                            $this->itemModel->decrementQuantity(
                                $orderItem['item_id'], 
                                $orderItem['quantity']
                            );
                        } catch (Exception $e) {
                            // Log error but continue processing other items
                            error_log("Failed to decrement quantity for item {$orderItem['item_id']}: {$e->getMessage()}");
                        }
                    }
                    
                    // Send order confirmation email
                    try {
                        $this->mailer->sendOrderConfirmation($order);
                    } catch (Exception $e) {
                        // Log error but don't fail the request
                        error_log("Failed to send order confirmation email: {$e->getMessage()}");
                    }
                    
                    // Automatically get shipping rates when order is paid  
                    if ($this->sendcloudService && $this->shipmentModel) {
                        try {
                            // Check if shipment already exists
                            $existingShipment = $this->shipmentModel->getShipmentByOrderId($orderId);
                            if (!$existingShipment) {
                                error_log("Creating shipment for order {$orderId}...");
                                $this->createShipmentForOrder($orderId, $order);
                                error_log("Shipment created successfully for order {$orderId}");
                            } else {
                                error_log("Shipment already exists for order {$orderId}");
                            }
                        } catch (Exception $e) {
                            // Log error but don't fail the order update
                            error_log("Failed to create shipment for order {$orderId}: {$e->getMessage()}");
                            error_log("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        error_log("Shipment creation skipped for order {$orderId}: SendcloudService=" . ($this->sendcloudService ? 'OK' : 'NULL') . ", ShipmentModel=" . ($this->shipmentModel ? 'OK' : 'NULL'));
                    }
                }
            }
            
            if ($order) {
                echo json_encode(OrderDTO::toDTO($order)->toArray());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Automatically create shipment for an order when payment is confirmed
     */
    private function createShipmentForOrder(int $orderId, array $order) {
        try {
            error_log("Auto-creating shipment for order #{$order['order_number']}");
            
            // Get order with user data for customer name
            $orderWithUser = $this->model->getOrderWithUser($orderId);
            if (!$orderWithUser) {
                throw new Exception('Order not found');
            }
            
            // Get customer name
            $customerName = trim(($orderWithUser['first_name'] ?? '') . ' ' . ($orderWithUser['last_name'] ?? ''));
            if (empty($customerName)) {
                $customerName = explode('@', $orderWithUser['email'])[0] ?? 'Customer';
            }
            
            // Prepare address for Sendcloud
            $toAddress = [
                'name' => $customerName,
                'email' => $orderWithUser['email'],
                'street1' => $orderWithUser['street'] . ' ' . $orderWithUser['house_number'],
                'city' => $orderWithUser['city'],
                'zip' => $orderWithUser['zip_code'],
                'country' => $orderWithUser['country'],
            ];
            
            // Get shipping rates to determine the default rate
            $ratesResult = $this->sendcloudService->getShippingRates($toAddress);
            $rates = $ratesResult['rates'] ?? [];
            
            if (empty($rates)) {
                throw new Exception('No shipping rates available');
            }
            
            // Use first available rate (PostNL standard)
            $selectedRate = $rates[0];
            $rateId = $selectedRate['rate_id'];
            
            error_log("Using shipping rate: {$rateId} - {$selectedRate['service']}");
            
            // Create the label
            $label = $this->sendcloudService->createLabel($toAddress, $rateId, [
                'order_number' => $orderWithUser['order_number'],
                'total_value' => $orderWithUser['total_amount'],
            ]);
            
            // Save shipment to database
            $shipmentData = [
                'order_id' => $orderId,
                'shippo_transaction_id' => $label['transaction_id'],
                'carrier' => $label['carrier'],
                'service' => $label['service'],
                'tracking_number' => $label['tracking_number'],
                'tracking_url' => $label['tracking_url_provider'],
                'label_url' => $label['label_url'],
                'shipping_cost' => $orderWithUser['shipping_cost'],
                'currency' => 'EUR',
                'delivery_status' => 'label_created',
                'estimated_delivery_date' => $label['eta'],
            ];
            
            $shipment = $this->shipmentModel->createShipment($shipmentData);
            
            // Update order with tracking number
            $this->model->updateOrder($orderId, [
                'tracking_number' => $label['tracking_number'],
                'order_status' => 'processing',
            ]);
            
            // Send label to seller via email
            if ($this->sellerMailer) {
                try {
                    $pdfContent = $this->sendcloudService->downloadLabel($label['label_url']);
                    $this->sellerMailer->sendShippingLabel($orderWithUser, $shipment, $pdfContent);
                    error_log("✅ Shipment created and seller notified for order #{$orderWithUser['order_number']}");
                } catch (Exception $e) {
                    error_log("⚠️ Shipment created but failed to send seller notification: " . $e->getMessage());
                }
            }
            
            return $shipment;
            
        } catch (Exception $e) {
            error_log("❌ Failed to auto-create shipment for order {$orderId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteOrder(int $orderId) {
        try {
            $result = $this->model->deleteOrder($orderId);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
