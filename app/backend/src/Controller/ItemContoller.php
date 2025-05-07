<?php

require_once(__DIR__ . '/../Model/ItemModel.php');
require_once(__DIR__ . '/../Enum/ItemType.php');
require_once(__DIR__ . '/../Service/CloudinaryService.php');

class ItemController {
    private $itemModel;

    public function __construct() {
        $this->itemModel = new ItemModel();
    }

    public function getItems() {
        return $this->itemModel->getItems();
    }

    public function postItem($userId, $title, $description, $price, $type, $images) {
        $cloudinaryService = new CloudinaryService();
        $imagesPath = $cloudinaryService->uploadImages($images);
        return $this->itemModel->postItem($userId, $title, $description, $price, $type, $imagesPath);
    }
}