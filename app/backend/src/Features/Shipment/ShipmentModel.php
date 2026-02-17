<?php

namespace App\Features\Shipment;

use App\Core\BaseModel;
use Exception;

class ShipmentModel extends BaseModel {
    private string $table = 'shipments';

    public function getAllShipments() {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getAllShipmentsWithOrders() {
        $stmt = $this->pdo->prepare("
            SELECT 
                s.*,
                o.order_number,
                o.email,
                o.street,
                o.house_number,
                o.city,
                o.zip_code,
                o.country,
                o.total_amount,
                o.created_at as order_created_at
            FROM {$this->table} s
            INNER JOIN orders o ON s.order_id = o.id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getShipmentById(int $shipmentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$shipmentId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function getShipmentByOrderId(int $orderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function getShipmentByTrackingNumber(string $trackingNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE tracking_number = ? LIMIT 1");
        $stmt->execute([$trackingNumber]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function createShipment(array $shipmentData) {
        $sql = "INSERT INTO {$this->table} (
            order_id, shippo_shipment_id, shippo_transaction_id, carrier, 
            service, tracking_number, tracking_url, label_url, shipping_cost, 
            currency, delivery_status, estimated_delivery_date, tracking_history
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $trackingHistory = isset($shipmentData['tracking_history']) ? 
            json_encode($shipmentData['tracking_history']) : null;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $shipmentData['order_id'],
            $shipmentData['shippo_shipment_id'] ?? null,
            $shipmentData['shippo_transaction_id'] ?? null,
            $shipmentData['carrier'],
            $shipmentData['service'],
            $shipmentData['tracking_number'],
            $shipmentData['tracking_url'] ?? null,
            $shipmentData['label_url'] ?? null,
            $shipmentData['shipping_cost'],
            $shipmentData['currency'] ?? 'USD',
            $shipmentData['delivery_status'] ?? 'label_created',
            $shipmentData['estimated_delivery_date'] ?? null,
            $trackingHistory,
        ]);
        
        return $this->getShipmentById((int)$this->pdo->lastInsertId());
    }

    public function updateShipment(int $shipmentId, array $shipmentData) {
        $fields = [];
        $values = [];

        $allowedFields = [
            'shippo_shipment_id', 'shippo_transaction_id', 'carrier', 'service',
            'tracking_number', 'tracking_url', 'label_url', 'shipping_cost',
            'currency', 'delivery_status', 'estimated_delivery_date', 
            'shipped_at', 'delivered_at'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $shipmentData)) {
                $fields[] = "$field = ?";
                $values[] = $shipmentData[$field];
            }
        }

        // Handle tracking_history separately due to JSON encoding
        if (array_key_exists('tracking_history', $shipmentData)) {
            $fields[] = "tracking_history = ?";
            $values[] = json_encode($shipmentData['tracking_history']);
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $values[] = $shipmentId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return $this->getShipmentById($shipmentId);
    }

    public function deleteShipment(int $shipmentId) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$shipmentId]);
        return $stmt->rowCount() > 0;
    }

    private function normalizeRow(array $row): array {
        // Convert snake_case to camelCase for consistency
        return [
            'id' => (int)$row['id'],
            'order_id' => (int)$row['order_id'],
            'shippo_shipment_id' => $row['shippo_shipment_id'] ?? null,
            'shippo_transaction_id' => $row['shippo_transaction_id'] ?? null,
            'carrier' => $row['carrier'],
            'service' => $row['service'],
            'tracking_number' => $row['tracking_number'],
            'tracking_url' => $row['tracking_url'] ?? null,
            'label_url' => $row['label_url'] ?? null,
            'shipping_cost' => (float)$row['shipping_cost'],
            'currency' => $row['currency'] ?? 'USD',
            'delivery_status' => $row['delivery_status'],
            'estimated_delivery_date' => $row['estimated_delivery_date'] ?? null,
            'shipped_at' => $row['shipped_at'] ?? null,
            'delivered_at' => $row['delivered_at'] ?? null,
            'tracking_history' => isset($row['tracking_history']) ? 
                json_decode($row['tracking_history'], true) : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
