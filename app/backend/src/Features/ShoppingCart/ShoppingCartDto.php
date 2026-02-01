<?php

namespace App\Features\ShoppingCart;

use DateTime;

class ShoppingCartDto
{
    public int $id;
    public int $userId;
    public DateTime $createdAt;
    public DateTime $updatedAt;

    public function __construct(int $id, int $userId, DateTime $createdAt, DateTime $updatedAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function ToDTO(array $cartData): self
    {
        return new self(
            (int)$cartData['id'],
            (int)$cartData['user_id'],
            new DateTime($cartData['created_at']),
            new DateTime($cartData['updated_at'])
        );
    }
}