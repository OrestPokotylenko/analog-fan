<?php

namespace App\Features\Order;

use Exception;

class OrderController {
    private OrderModel $model;

    public function __construct() {
        $this->model = new OrderModel();
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

            $order = $this->model->updateOrder($orderId, $data);
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
