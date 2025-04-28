<?php

require_once(__DIR__ . '/../Model/ItemModel.php');

class ItemController {
    private $itemModel;

    public function __construct() {
        $this->itemModel = new ItemModel();
    }

    public function getItems() {
        return $this->itemModel->getItems();
    }
}