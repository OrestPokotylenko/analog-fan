<?php

namespace App\Features\Order;

class OrderItemDTO {
    public readonly int $id;
    public readonly int $orderId;
    public readonly int $itemId;
    public readonly int $quantity;
    public readonly float $priceAtPurchase;

    public function __construct(
        int $id,
        int $orderId,
        int $itemId,
        int $quantity,
        float $priceAtPurchase
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->itemId = $itemId;
        $this->quantity = $quantity;
        $this->priceAtPurchase = $priceAtPurchase;
    }

    public static function toDTO(array $itemData): self {
        return new self(
            $itemData['id'],
            $itemData['order_id'],
            $itemData['item_id'],
            $itemData['quantity'],
            (float)$itemData['price_at_purchase']
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'orderId' => $this->orderId,
            'itemId' => $this->itemId,
            'quantity' => $this->quantity,
            'priceAtPurchase' => $this->priceAtPurchase
        ];
    }
}
