<?php

namespace App\Features\Order;

use App\Core\BaseModel;
use Exception;

class OrderItemModel extends BaseModel {
    private string $table = 'order_items';

    public function getItemsByOrderId(int $orderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY id");
        $stmt->execute([$orderId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getOrderItemById(int $itemId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function addOrderItem(array $itemData) {
        $sql = "INSERT INTO {$this->table} (order_id, item_id, quantity, price_at_purchase) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $itemData['order_id'],
            $itemData['item_id'],
            $itemData['quantity'],
            $itemData['price_at_purchase']
        ]);
        
        return $this->getOrderItemById((int)$this->pdo->lastInsertId());
    }

    public function updateOrderItem(int $itemId, array $itemData) {
        $fields = [];
        $values = [];

        if (isset($itemData['quantity'])) {
            $fields[] = "quantity = ?";
            $values[] = $itemData['quantity'];
        }

        if (isset($itemData['price_at_purchase'])) {
            $fields[] = "price_at_purchase = ?";
            $values[] = $itemData['price_at_purchase'];
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $values[] = $itemId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return $this->getOrderItemById($itemId);
    }

    public function deleteOrderItem(int $itemId) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$itemId]);
        return ['success' => true, 'message' => 'Order item deleted'];
    }

    public function deleteItemsByOrderId(int $orderId) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return ['success' => true, 'message' => 'Order items deleted'];
    }

    public function calculateOrderSubtotal(int $orderId): float {
        $stmt = $this->pdo->prepare("SELECT SUM(quantity * price_at_purchase) as total FROM {$this->table} WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }

    private function normalizeRow(array $row): array {
        return [
            'id' => (int)$row['id'],
            'order_id' => (int)$row['order_id'],
            'item_id' => (int)$row['item_id'],
            'quantity' => (int)$row['quantity'],
            'price_at_purchase' => (float)$row['price_at_purchase']
        ];
    }
}
