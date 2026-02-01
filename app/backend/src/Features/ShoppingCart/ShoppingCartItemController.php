<?php

namespace App\Features\ShoppingCart;

class ShoppingCartItemController
{
    private ShoppingCartItemModel $shoppingCartItemModel;

    public function __construct()
    {
        $this->shoppingCartItemModel = new ShoppingCartItemModel();
    }

    public function addItemToCart(int $shoppingCartId, int $itemId, int $quantity): ?ShoppingCartItemDto
    {
        return $this->shoppingCartItemModel->addItemToCart($shoppingCartId, $itemId, $quantity);
    }

    public function removeItemFromCart(int $id): bool
    {
        return $this->shoppingCartItemModel->removeItemFromCart($id);
    }

    public function getCartItems(int $shoppingCartId): array
    {
        return $this->shoppingCartItemModel->getCartItems($shoppingCartId);
    }

    public function updateCartItemQuantity(int $id, int $quantity): ShoppingCartItemDto
    {
        return $this->shoppingCartItemModel->updateCartItemQuantity($id, $quantity);
    }

    public function clearCart(int $shoppingCartId): bool
    {
        return $this->shoppingCartItemModel->clearCart($shoppingCartId);
    }
}