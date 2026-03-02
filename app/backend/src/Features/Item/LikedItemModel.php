<?php

namespace App\Features\Item;

use App\Core\BaseModel;
use PDO;
use PDOException;

class LikedItemModel extends BaseModel {
    public function fetchLikedItems(int $userId): array {
        $query = "SELECT item_id FROM liked_items WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => ['itemId' => (int)$row['item_id']], $data);
    }

    public function postLikedItem(LikedItemDTO $dto): bool {
        try {
            $query = "INSERT INTO liked_items(user_id, item_id) VALUES (:userId, :itemId)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([':userId' => $dto->userId, ':itemId' => $dto->itemId]);
        } catch (PDOException $e) {
            error_log('postLikedItem error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteLikedItem(LikedItemDTO $dto): bool {
        try {
            $query = "DELETE FROM liked_items WHERE user_id = :userId AND item_id = :itemId";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([':userId' => $dto->userId, ':itemId' => $dto->itemId]);
        } catch (PDOException $e) {
            error_log('deleteLikedItem error: ' . $e->getMessage());
            return false;
        }
    }
}