<?php

namespace App\Features\ProductType;

use App\External\CloudinaryService;

class ProductTypeController {
    private ProductTypeModel $model;
    private CloudinaryService $cloudinary;

    public function __construct() {
        $this->model = new ProductTypeModel();
        $this->cloudinary = new CloudinaryService();
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
        $imageUrl = null;
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = $this->cloudinary->uploadImage($_FILES['image']['tmp_name']);
        }
        
        $newProductType = $this->model->postProductType($name, $imageUrl);
        echo json_encode(ProductTypeDto::toDTO($newProductType)->toArray());
    }

    public function updateProductType(int $productTypeId, $name) {
        $imageUrl = null;
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Get existing product type to delete old image if exists
            $existingType = $this->model->fetchProductTypeById($productTypeId);
            if ($existingType && $existingType['image_url']) {
                $this->cloudinary->deleteImage($existingType['image_url']);
            }
            
            $imageUrl = $this->cloudinary->uploadImage($_FILES['image']['tmp_name']);
        }
        
        $updatedProductType = $this->model->updateProductType($productTypeId, $name, $imageUrl);
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