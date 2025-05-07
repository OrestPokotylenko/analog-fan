<?php

require_once(__DIR__ . '/../Enum/ItemType.php');

class ItemDTO {
    public readonly int $itemId;
    public readonly int $userId;
    public readonly string $title;
    public readonly string $description;
    public readonly float $price;
    public readonly ItemType $type;
    public readonly DateTime $creationDate;
    public readonly array $imagesPath;
    
    public function __construct(int $itemId, int $userId, string $title, string $description, float $price, ItemType $type, DateTime $creationDate, array $imagesPath) {
        $this->itemId = $itemId;
        $this->userId = $userId;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->type = $type;
        $this->creationDate = $creationDate;
        $this->imagesPath = $imagesPath;
    }

    public static function toDTO(array $itemData): self {
        return new self(
            $itemData['item_id'],
            $itemData['user_id'],
            $itemData['title'],
            $itemData['description'],
            (float)$itemData['price'],
            ItemType::from($itemData['type']),
            new DateTime($itemData['creation_date']),
            json_decode($itemData['images'], true)
        );
    }
}