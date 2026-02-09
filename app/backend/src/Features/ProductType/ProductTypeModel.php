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

    public function updateProductType(int $productTypeId, $name, $imageUrl = null) {
        if ($imageUrl !== null) {
            $sql = "UPDATE {$this->table} SET name = ?, image_url = ? WHERE product_type_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $imageUrl, $productTypeId]);
        } else {
            $sql = "UPDATE {$this->table} SET name = ? WHERE product_type_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $productTypeId]);
        }
        return $this->fetchProductTypeById($productTypeId);
    }

    public function deleteProductType(int $productTypeId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE product_type_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$productTypeId]);
            return ['success' => true, 'message' => 'Product type deleted'];
        } catch (\PDOException $e) {
            // Check if it's a foreign key constraint error
            if ($e->getCode() == '23000') {
                return ['success' => false, 'error' => 'constraint', 'message' => 'Cannot delete product type because it is being used by items'];
            }
            throw $e;
        }
    }

    private function normalizeRow(array $row): array {
        return [
            'product_type_id' => (int)$row['product_type_id'],
            'name' => $row['name'],
            'image_url' => $row['image_url']
        ];
    }
}