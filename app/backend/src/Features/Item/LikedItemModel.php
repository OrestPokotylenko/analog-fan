<?php

namespace App\Features\Item;

use App\Core\BaseModel;
use PDO;
use PDOException;

class LikedItemModel extends BaseModel {
    public function fetchLikedItems($userId): array {
        $query = "SELECT * FROM liked_items WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dtos = [];
        foreach ($data as $likedItem) {
            $dto = LikedItemDTO::toDTO($likedItem);
            $dtos[] = $dto;
        }

        return $dtos;
    }

    public function postLikedItem(LikedItemDTO $dto) {
        try {
            $this->pdo->beginTransaction();

            $query = "INSERT INTO liked_items(user_id, item_id) VALUES (:userId, :itemId)";
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([':userId' => $dto->userId, ':itemId' => $dto->itemId]);

            if (!$success) {
                self::$pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteLikedItem($dto) {
        try {
            $this->pdo->beginTransaction();

            $query = "DELETE FROM liked_items WHERE user_id = :userId AND item_id = :itemId";
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([':userId' => $dto->userId, ':itemId' => $dto->itemId]);

            if (!$success) {
                self::$pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}