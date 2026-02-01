<?php

namespace App\Features\ShoppingCart;

use App\Core\BaseModel;

class ShoppingCartModel extends BaseModel {
    private string $table = 'shopping_carts';

    public function getCartByUserId($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function createCart($userId) {
        $sql = "INSERT INTO {$this->table} (user_id)
                VALUES (?)";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$userId])) {
            throw new \Exception('Failed to create shopping cart');
        }

        return $this->getCartByUserId($userId);
    }

    public function deleteCart($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    private function normalizeRow(array $row): array {
        return [
            'cartId'    => (int)($row['id'] ?? 0),
            'userId'    => (int)($row['user_id'] ?? 0),
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        ];
    }
}