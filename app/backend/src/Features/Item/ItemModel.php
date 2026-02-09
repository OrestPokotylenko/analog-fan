<?php

namespace App\Features\Item;

use App\Core\BaseModel;
use Exception;

class ItemModel extends BaseModel {
    private string $table = 'items';

    public function getItems($userId = null) {
        if ($userId === null) {
            // Guest user - show all items
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table}");
            $stmt->execute();
        } else {
            // Logged-in user - exclude their own items
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id != ?");
            $stmt->execute([$userId]);
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function fetchItemById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE item_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function fetchUserItems($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function postItem($userId, $title, $description, $price, $type, $imagesPath) {
        $imageJson = json_encode(is_array($imagesPath) ? $imagesPath : []);
        $sql = "INSERT INTO {$this->table} (user_id, title, description, price, product_type_id, images)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$userId, $title, $description, $price, $type, $imageJson])) {
            throw new Exception('Failed to create item');
        }

        return $this->fetchItemById((int)$this->pdo->lastInsertId());
    }

    public function updateItem($id, $userId, $title, $description, $price, $type, $imagesPath) {
        $imageJson = json_encode(is_array($imagesPath) ? $imagesPath : []);
        $sql = "UPDATE {$this->table}
                SET title = ?, description = ?, price = ?, product_type_id = ?, images = ?
                WHERE item_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$title, $description, $price, $type, $imageJson, $id, $userId])) {
            throw new Exception('Failed to update item');
        }

        return $this->fetchItemById($id);
    }

    public function deleteItem($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE item_id = ?");
        if (!$stmt->execute([$id])) {
            throw new Exception('Failed to delete item');
        }
        return ['success' => true, 'message' => 'Item deleted'];
    }

    public function getItemsByType($type) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE product_type_id = ?");
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    private function normalizeRow(array $row): array {
        return [
            'itemId'      => (int)($row['item_id'] ?? 0),
            'userId'      => (int)($row['user_id'] ?? 0),
            'title'       => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'price'       => is_numeric($row['price'] ?? 0) ? (float)$row['price'] : 0.0,
            'type'        => $row['type'] ?? '',
            'createdAt'   => $row['creation_date'] ?? null,
            'imagesPath'  => $this->decodeImages($row['images'] ?? '[]'),
        ];
    }

    private function decodeImages($value): array {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}