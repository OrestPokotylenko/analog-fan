<?php

namespace App\Features\Item;

class LikedItemController {
    private $model;

    public function __construct() {
        $this->model = new LikedItemModel();
    }

    public function fetchLikedItems($userId) {
        return $this->model->fetchLikedItems($userId);
    }

    public function postLikedItem($userId, $itemId) {
        $dto = new LikedItemDTO($userId, $itemId);
        return $this->model->postLikedItem($dto);
    }

    public function deleteLikedItem($userId, $itemId) {
        $dto = new LikedItemDTO($userId, $itemId);
        return $this->model->deleteLikedItem($dto);
    }
}