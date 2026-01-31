<?php

namespace App\Features\ProductType;

class ProductTypeDto {
    public readonly int $productTypeId;
    public readonly string $name;
    public readonly ?string $imageUrl;

    public function __construct(int $productTypeId, string $name, ?string $imageUrl = null) {
        $this->productTypeId = $productTypeId;
        $this->name = $name;
        $this->imageUrl = $imageUrl;
    }

    public static function toDTO(array $productTypeData): self {
        return new self(
            $productTypeData['product_type_id'],
            $productTypeData['name'],
            $productTypeData['image_url'] ?? null
        );
    }
}