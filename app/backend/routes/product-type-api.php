<?php

use App\Features\ProductType\ProductTypeController;
use App\Core\Route;

header('Content-Type: application/json');

$productTypeController = new ProductTypeController();

Route::add('/api/product-types/([0-9]+)', function ($productTypeId) use ($productTypeController) {
    $productTypeController->getProductTypeById((int)$productTypeId);
});

Route::add('/api/product-types', function () use ($productTypeController) {
    $productTypeController->getProductTypes();
});

Route::add('/api/product-types', function () use ($productTypeController) {
    $data = json_decode(file_get_contents('php://input'), true);
    $productTypeController->postProductType($data['name']);
}, 'post');