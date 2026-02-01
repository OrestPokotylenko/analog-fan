<?php

namespace App\Features\ShoppingCart;

use DateTime;

class ShoppingCartItemDto
{
    public int $id;
    public int $shoppingCartId;
    public int $itemId;
    public int $quantity;
    public DateTime $addedAt;
    
    // Item details (populated when joined)
    public string $title = '';
    public string $description = '';
    public float $price = 0.0;
    public array $images = [];
    public int $sellerId = 0;

    public function __construct(int $id, int $shoppingCartId, int $itemId, int $quantity, DateTime $addedAt)
    {
        $this->id = $id;
        $this->shoppingCartId = $shoppingCartId;
        $this->itemId = $itemId;
        $this->quantity = $quantity;
        $this->addedAt = $addedAt;
    }

    public static function toDTO(array $cartItemData): self
    {
        return new self(
            (int)$cartItemData['id'],
            (int)$cartItemData['shopping_cart_id'],
            (int)$cartItemData['item_id'],
            (int)$cartItemData['quantity'],
            new DateTime($cartItemData['added_at'])
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'shoppingCartId' => $this->shoppingCartId,
            'itemId' => $this->itemId,
            'quantity' => $this->quantity,
            'addedAt' => $this->addedAt->format('Y-m-d H:i:s'),
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'images' => $this->images,
            'sellerId' => $this->sellerId
        ];
    }
}