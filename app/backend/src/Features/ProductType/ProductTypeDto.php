<?php

namespace App\Features\ProductType;

class ProductTypeDto {
    public readonly int $productTypeId;
    public readonly string $name;
    public readonly ?string $imageUrl;
    public readonly bool $supportsGenre;

    public function __construct(int $productTypeId, string $name, ?string $imageUrl = null, bool $supportsGenre = false) {
        $this->productTypeId  = $productTypeId;
        $this->name           = $name;
        $this->imageUrl       = $imageUrl;
        $this->supportsGenre  = $supportsGenre;
    }

    public static function toDTO(array $productTypeData): self {
        return new self(
            $productTypeData['product_type_id'],
            $productTypeData['name'],
            $productTypeData['image_url'] ?? null,
            (bool)($productTypeData['supports_genre'] ?? false)
        );
    }

    public function toArray(): array {
        return [
            'productTypeId' => $this->productTypeId,
            'typeName'      => $this->name,
            'imageUrl'      => $this->imageUrl,
            'supportsGenre' => $this->supportsGenre,
        ];
    }
}
