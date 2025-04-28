<?php 

require_once(__DIR__ . '/BaseModel.php');
require_once(__DIR__ . '/../DTO/ItemType.php');
require_once(__DIR__ . '/../DTO/ItemDTO.php');

class ItemModel extends BaseModel {
    public function getItems(): array {
        $query = 'SELECT * FROM items';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemsData as $itemData) {
            $items[] = new ItemDTO(
                $itemData['title'],
                $itemData['description'],
                $itemData['price'],
                ItemType::from($itemData['type']),
                new DateTime($itemData['creation_date']),
                json_decode($itemData['images'], true),
                $itemData['item_id'],
            );
        }

        return $items;
    }
}