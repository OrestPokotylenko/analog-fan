<?php

namespace App\Features\Order;

use App\Core\BaseModel;
use Exception;

class OrderModel extends BaseModel {
    private string $table = 'orders';

    public function getAllOrders() {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getOrdersByUserId(int $userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getOrderById(int $orderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function getOrderWithUser(int $orderId) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.first_name, u.last_name, u.email as user_email
            FROM {$this->table} o
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE o.id = ? LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    // Get orders containing items sold by a specific user (seller)
    public function getOrdersBySellerId(int $sellerId) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT o.*
            FROM {$this->table} o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN items i ON oi.item_id = i.item_id
            WHERE i.user_id = ?
            AND o.payment_status = 'paid'
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$sellerId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getOrderByOrderNumber(string $orderNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function createOrder(array $orderData) {
        $sql = "INSERT INTO {$this->table} (
            user_id, order_number, email, phone_number, street, house_number, 
            city, zip_code, country, subtotal, tax_amount, 
            shipping_cost, total_amount, payment_method, payment_status, 
            transaction_id, order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $orderData['user_id'],
            $orderData['order_number'],
            $orderData['email'],
            $orderData['phone_number'] ?? null,
            $orderData['street'],
            $orderData['house_number'],
            $orderData['city'],
            $orderData['zip_code'],
            $orderData['country'],
            $orderData['subtotal'],
            $orderData['tax_amount'] ?? 0,
            $orderData['shipping_cost'] ?? 0,
            $orderData['total_amount'],
            $orderData['payment_method'] ?? null,
            $orderData['payment_status'] ?? 'pending',
            $orderData['transaction_id'] ?? null,
            $orderData['order_status'] ?? 'pending',
        ]);
        
        return $this->getOrderById((int)$this->pdo->lastInsertId());
    }

    public function updateOrder(int $orderId, array $orderData) {
        $fields = [];
        $values = [];

        $allowedFields = [
            'email', 'phone_number', 'street', 'house_number', 'city', 
            'zip_code', 'country', 'subtotal', 'tax_amount', 'shipping_cost', 
            'total_amount', 'payment_method', 'payment_status', 'transaction_id', 
            'order_status', 'tracking_number', 'shipped_at', 'delivered_at'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $orderData)) {
                $fields[] = "$field = ?";
                $values[] = $orderData[$field];
            }
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $values[] = $orderId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return $this->getOrderById($orderId);
    }

    public function deleteOrder(int $orderId) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$orderId]);
        return ['success' => true, 'message' => 'Order deleted'];
    }

    public function generateOrderNumber(): string {
        // Generate unique order number (e.g., ORD-20260209-1234)
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $orderNumber = "ORD-{$date}-{$random}";
        
        // Check if it exists, regenerate if needed
        if ($this->getOrderByOrderNumber($orderNumber)) {
            return $this->generateOrderNumber();
        }
        
        return $orderNumber;
    }

    private function normalizeRow(array $row): array {
        $normalized = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'order_number' => $row['order_number'],
            'email' => $row['email'],
            'phone_number' => $row['phone_number'],
            'street' => $row['street'],
            'house_number' => $row['house_number'],
            'city' => $row['city'],
            'zip_code' => $row['zip_code'],
            'country' => $row['country'],
            'subtotal' => (float)$row['subtotal'],
            'tax_amount' => (float)$row['tax_amount'],
            'shipping_cost' => (float)$row['shipping_cost'],
            'total_amount' => (float)$row['total_amount'],
            'payment_method' => $row['payment_method'],
            'payment_status' => $row['payment_status'],
            'transaction_id' => $row['transaction_id'],
            'order_status' => $row['order_status'],
            'tracking_number' => $row['tracking_number'],
            'shipped_at' => $row['shipped_at'],
            'delivered_at' => $row['delivered_at'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];

        // Include user fields if they exist (from JOIN queries)
        if (isset($row['first_name'])) {
            $normalized['first_name'] = $row['first_name'];
        }
        if (isset($row['last_name'])) {
            $normalized['last_name'] = $row['last_name'];
        }
        if (isset($row['user_email'])) {
            $normalized['user_email'] = $row['user_email'];
        }

        return $normalized;
    }
}
