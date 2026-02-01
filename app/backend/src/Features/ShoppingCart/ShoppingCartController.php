<?php

namespace App\Features\ShoppingCart;

class ShoppingCartController {
    private ShoppingCartModel $model;

    public function __construct() {
        $this->model = new ShoppingCartModel();
    }

    public function getCart($userId) {
        return $this->model->getCartByUserId($userId);
    }

    public function createCart($userId) {
        return $this->model->createCart($userId);
    }

    public function deleteCart($userId) {
        return $this->model->deleteCart($userId);
    }
}