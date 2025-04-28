<?php

require_once(__DIR__ . '/ItemType.php');

class ItemDTO {
    public readonly ?int $itemId;
    public readonly string $title;
    public readonly string $description;
    public readonly float $price;
    public readonly ItemType $type;
    public readonly DateTime $creationDate;
    public readonly array $imagesPath;
    
    public function __construct(string $title, string $description, float $price, ItemType $type, DateTime $creationDate, array $imagesPath, ?int $itemId = null) {
        $this->itemId = $itemId;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->type = $type;
        $this->creationDate = $creationDate;
        $this->imagesPath = $imagesPath;
    }
}