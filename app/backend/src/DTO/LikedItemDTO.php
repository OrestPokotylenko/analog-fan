<?php

class LikedItemDTO {
    public readonly int $userId;
    public readonly int $itemId;

    public function __construct(int $userId, int $itemId) {
        $this->userId = $userId;
        $this->itemId = $itemId;
    }

    public static function toDTO(array $data): self {
        return new self(
            $data['user_id'],
            $data['item_id']
        );
    }
}