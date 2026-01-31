<?php

namespace App\Features\ProductType;

class ProductTypeController {
    private ProductTypeModel $model;

    public function __construct() {
        $this->model = new ProductTypeModel();
    }

    public function getProductTypes() {
        $productTypes = $this->model->getProductTypes();
        echo json_encode(array_map([ProductTypeDto::class, 'toDTO'], $productTypes));
    }

    public function getProductTypeById(int $productTypeId) {
        $productType = $this->model->fetchProductTypeById($productTypeId);
        if ($productType) {
            echo json_encode(ProductTypeDto::toDTO($productType));
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product type not found']);
        }
    }

    public function postProductType($name) {
        $newProductType = $this->model->postProductType($name);
        echo json_encode(ProductTypeDto::toDTO($newProductType));
    }
}