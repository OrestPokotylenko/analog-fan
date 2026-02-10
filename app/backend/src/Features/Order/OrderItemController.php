<?php

namespace App\Features\Order;

use Exception;

class OrderItemController {
    private OrderItemModel $model;

    public function __construct() {
        $this->model = new OrderItemModel();
    }

    public function getItemsByOrderId(int $orderId) {
        try {
            $items = $this->model->getItemsByOrderId($orderId);
            $dtos = array_map(function($item) {
                return OrderItemDTO::toDTO($item)->toArray();
            }, $items);
            echo json_encode($dtos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOrderItemById(int $itemId) {
        try {
            $item = $this->model->getOrderItemById($itemId);
            if ($item) {
                echo json_encode(OrderItemDTO::toDTO($item)->toArray());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order item not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function addOrderItem(array $data) {
        try {
            // Validate required fields
            $required = ['order_id', 'item_id', 'quantity', 'price_at_purchase'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $item = $this->model->addOrderItem($data);
            http_response_code(201);
            echo json_encode(OrderItemDTO::toDTO($item)->toArray());
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateOrderItem(int $itemId, array $data) {
        try {
            $item = $this->model->updateOrderItem($itemId, $data);
            if ($item) {
                echo json_encode(OrderItemDTO::toDTO($item)->toArray());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order item not found']);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteOrderItem(int $itemId) {
        try {
            $result = $this->model->deleteOrderItem($itemId);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
