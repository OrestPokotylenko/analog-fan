<?php

namespace App\Features\ProductType;

use App\Core\BaseModel;

class ProductTypeModel extends BaseModel {
    private string $table = 'product_types';

    public function getProductTypes() {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function fetchProductTypeById(int $productTypeId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE product_type_id = ? LIMIT 1");
        $stmt->execute([$productTypeId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function postProductType($name, $imageUrl) {
        $sql = "INSERT INTO {$this->table} (name, image_url) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $imageUrl]);
        return $this->fetchProductTypeById((int)$this->pdo->lastInsertId());
    }

    private function normalizeRow(array $row): array {
        return [
            'product_type_id' => (int)$row['product_type_id'],
            'name' => $row['name'],
            'image_url' => $row['image_url']
        ];
    }
}