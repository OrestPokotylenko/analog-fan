<?php

namespace App\Features\ProductType;

class ProductTypeController {
    private ProductTypeModel $model;

    public function __construct() {
        $this->model = new ProductTypeModel();
    }

    public function getProductTypes() {
        $productTypes = $this->model->getProductTypes();
        $dtos = array_map(function($pt) {
            return ProductTypeDto::toDTO($pt)->toArray();
        }, $productTypes);
        echo json_encode($dtos);
    }

    public function getProductTypeById(int $productTypeId) {
        $productType = $this->model->fetchProductTypeById($productTypeId);
        if ($productType) {
            echo json_encode(ProductTypeDto::toDTO($productType)->toArray());
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product type not found']);
        }
    }

    public function postProductType($name) {
        $newProductType = $this->model->postProductType($name, null);
        echo json_encode(ProductTypeDto::toDTO($newProductType)->toArray());
    }

    public function updateProductType(int $productTypeId, $name) {
        $updatedProductType = $this->model->updateProductType($productTypeId, $name);
        if ($updatedProductType) {
            echo json_encode(ProductTypeDto::toDTO($updatedProductType)->toArray());
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product type not found']);
        }
    }

    public function deleteProductType(int $productTypeId) {
        $result = $this->model->deleteProductType($productTypeId);
        
        if (isset($result['success']) && $result['success'] === false) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => $result['message']]);
        } else {
            echo json_encode($result);
        }
    }
}