<?php

namespace App\Features\Item;

use App\Core\BaseModel;
use Exception;

class ItemModel extends BaseModel {
    private string $table = 'items';

    /** All columns — used for single-item detail responses */
    private const COLUMNS = 'item_id, user_id, title, description, price, product_type_id, images, quantity, item_condition, genre, creation_date';

    /** Minimal columns — used for list/grid responses (home, category, my-items) */
    private const LIST_COLUMNS = 'item_id, user_id, title, price, product_type_id, item_condition, genre, images';

    public function getItems($userId = null) {
        $p = $this->getPaginationParams();

        if ($userId === null) {
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE quantity > 0");
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT " . self::LIST_COLUMNS . " FROM {$this->table} WHERE quantity > 0 ORDER BY creation_date DESC LIMIT ? OFFSET ?");
            $stmt->execute([$p['limit'], $p['offset']]);
        } else {
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id != ? AND quantity > 0");
            $countStmt->execute([$userId]);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT " . self::LIST_COLUMNS . " FROM {$this->table} WHERE user_id != ? AND quantity > 0 ORDER BY creation_date DESC LIMIT ? OFFSET ?");
            $stmt->execute([$userId, $p['limit'], $p['offset']]);
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->paginatedResponse(
            array_map([$this, 'normalizeSummaryRow'], $rows),
            $total, $p['page'], $p['limit']
        );
    }

    public function fetchItemById($id) {
        $stmt = $this->pdo->prepare("SELECT " . self::COLUMNS . " FROM {$this->table} WHERE item_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function fetchUserItems($userId) {
        $p = $this->getPaginationParams();

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT " . self::LIST_COLUMNS . " FROM {$this->table} WHERE user_id = ? ORDER BY creation_date DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, $p['limit'], $p['offset']]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->paginatedResponse(
            array_map([$this, 'normalizeSummaryRow'], $rows),
            $total, $p['page'], $p['limit']
        );
    }

    public function postItem($userId, $title, $description, $price, $type, $imagesPath, $quantity = 1, $condition = 'good', $genre = null) {
        $imageJson = json_encode(is_array($imagesPath) ? $imagesPath : []);
        $sql = "INSERT INTO {$this->table}
                    (user_id, title, description, price, product_type_id, images, quantity, item_condition, genre)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$userId, $title, $description, $price, $type, $imageJson, $quantity, $condition, $genre])) {
            throw new Exception('Failed to create item');
        }

        return $this->fetchItemById((int)$this->pdo->lastInsertId());
    }

    public function updateItem($id, $userId, $title, $description, $price, $type, $imagesPath, $quantity = null, $condition = null, $genre = null) {
        $imageJson = json_encode(is_array($imagesPath) ? $imagesPath : []);

        if ($quantity !== null) {
            $sql = "UPDATE {$this->table}
                    SET title = ?, description = ?, price = ?, product_type_id = ?, images = ?,
                        quantity = ?, item_condition = ?, genre = ?
                    WHERE item_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt->execute([$title, $description, $price, $type, $imageJson, $quantity, $condition, $genre, $id, $userId])) {
                throw new Exception('Failed to update item');
            }
        } else {
            $sql = "UPDATE {$this->table}
                    SET title = ?, description = ?, price = ?, product_type_id = ?, images = ?,
                        item_condition = ?, genre = ?
                    WHERE item_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt->execute([$title, $description, $price, $type, $imageJson, $condition, $genre, $id, $userId])) {
                throw new Exception('Failed to update item');
            }
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
        $p = $this->getPaginationParams();

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE product_type_id = ? AND quantity > 0");
        $countStmt->execute([$type]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT " . self::LIST_COLUMNS . " FROM {$this->table} WHERE product_type_id = ? AND quantity > 0 ORDER BY creation_date DESC LIMIT ? OFFSET ?");
        $stmt->execute([$type, $p['limit'], $p['offset']]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->paginatedResponse(
            array_map([$this, 'normalizeSummaryRow'], $rows),
            $total, $p['page'], $p['limit']
        );
    }

    public function decrementQuantity($itemId, $quantity) {
        $sql = "UPDATE {$this->table} SET quantity = quantity - ? WHERE item_id = ? AND quantity >= ?";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$quantity, $itemId, $quantity])) {
            throw new Exception('Failed to decrement quantity');
        }

        if ($stmt->rowCount() === 0) {
            throw new Exception('Insufficient quantity available');
        }

        return $this->fetchItemById($itemId);
    }

    /**
     * Minimal payload for list/grid views — drops description, quantity, createdAt.
     * Callers: getItems(), getItemsByType(), fetchUserItems()
     */
    private function normalizeSummaryRow(array $row): array {
        return [
            'itemId'        => (int)($row['item_id'] ?? 0),
            'userId'        => (int)($row['user_id'] ?? 0),
            'title'         => $row['title'] ?? '',
            'price'         => is_numeric($row['price'] ?? 0) ? (float)$row['price'] : 0.0,
            'productTypeId' => (int)($row['product_type_id'] ?? 0),
            'condition'     => $row['item_condition'] ?? 'good',
            'genre'         => $row['genre'] ?? null,
            'imagesPath'    => $this->decodeImages($row['images'] ?? '[]'),
        ];
    }

    /**
     * Full payload for single-item detail views.
     * Callers: fetchItemById(), decrementQuantity()
     */
    private function normalizeRow(array $row): array {
        return [
            'itemId'        => (int)($row['item_id'] ?? 0),
            'userId'        => (int)($row['user_id'] ?? 0),
            'title'         => $row['title'] ?? '',
            'description'   => $row['description'] ?? '',
            'price'         => is_numeric($row['price'] ?? 0) ? (float)$row['price'] : 0.0,
            'productTypeId' => (int)($row['product_type_id'] ?? 0),
            'quantity'      => (int)($row['quantity'] ?? 1),
            'condition'     => $row['item_condition'] ?? 'good',
            'genre'         => $row['genre'] ?? null,
            'createdAt'     => $row['creation_date'] ?? null,
            'imagesPath'    => $this->decodeImages($row['images'] ?? '[]'),
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
