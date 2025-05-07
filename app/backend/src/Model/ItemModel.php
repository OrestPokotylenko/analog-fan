<?php

require_once(__DIR__ . '/BaseModel.php');
require_once(__DIR__ . '/../Enum/ItemType.php');
require_once(__DIR__ . '/../DTO/ItemDTO.php');

class ItemModel extends BaseModel
{
    public function getItems(): array
    {
        $query = 'SELECT * FROM items';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemsData as $itemData) {
            $items[] = ItemDTO::toDTO($itemData);
        }

        return $items;
    }

    public function postItem(int $userId, string $title, string $description, float $price, string $type, array $imagesPath): ItemDTO
    {
        try {
            $this->pdo->beginTransaction();

            $query = 'INSERT INTO items (user_id, title, description, price, type, images) VALUES (:userId, :title, :description, :price, :type, :images)';
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([':userId' => $userId, ':title' => $title, ':description' => $description, ':price' => $price, ':type' => $type, ':images' => json_encode($imagesPath)]);

            if ($success) {
                $lastInsertId = $this->pdo->lastInsertId();

                $query = 'SELECT * FROM items WHERE item_id = :id';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([':id' => $lastInsertId]);
                $itemData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($itemData) {
                    $this->pdo->commit();
                    return ItemDTO::toDTO($itemData);
                } else {
                    $this->pdo->rollBack();
                    throw new Exception('Failed to fetch the inserted item.');
                }
            } else {
                $this->pdo->rollBack();
                throw new Exception('Failed to insert item into database.');
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception('Error inserting item: ' . $e->getMessage());
        }
    }
}
