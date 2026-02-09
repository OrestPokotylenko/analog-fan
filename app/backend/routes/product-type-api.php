<?php

use App\Features\ProductType\ProductTypeController;
use App\Core\Route;

$productTypeController = new ProductTypeController();

Route::add('/api/product-types/([0-9]+)', function ($productTypeId) use ($productTypeController) {
    $productTypeController->getProductTypeById((int)$productTypeId);
});

Route::add('/api/product-types', function () use ($productTypeController) {
    $productTypeController->getProductTypes();
});

Route::add('/api/product-types', function () use ($productTypeController) {
    $name = $_POST['name'] ?? null;
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        return;
    }
    $productTypeController->postProductType($name);
}, 'post');

Route::add('/api/product-types/{id}', function ($id) use ($productTypeController) {
    $name = $_POST['name'] ?? null;
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        return;
    }
    $productTypeController->updateProductType((int)$id, $name);
}, 'put');

Route::add('/api/product-types/{id}', function ($id) use ($productTypeController) {
    $productTypeController->deleteProductType((int)$id);
}, 'delete');
