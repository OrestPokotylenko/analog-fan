<?php

use App\Features\ProductType\ProductTypeController;
use App\Core\Route;

$productTypeController = new ProductTypeController();

// parsePutFormData function is defined in item-api.php
if (!function_exists('parsePutFormData')) {
    function parsePutFormData() {
        global $_PUT, $_PUT_FILES;
        
        $_PUT = [];
        $_PUT_FILES = [];
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') === false) {
            return;
        }
        
        $raw = file_get_contents('php://input');
        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1] ?? '';
        
        if (empty($boundary)) {
            return;
        }
        
        $parts = array_slice(explode("--$boundary", $raw), 1);
        
        foreach ($parts as $part) {
            if ($part == "--\r\n" || empty(trim($part))) continue;
            
            list($rawHeaders, $content) = explode("\r\n\r\n", $part, 2);
            $content = substr($content, 0, -2);
            
            preg_match('/name="([^"]*)"/', $rawHeaders, $nameMatch);
            $name = $nameMatch[1] ?? '';
            
            if (preg_match('/filename="([^"]*)"/', $rawHeaders, $fileMatch)) {
                $filename = $fileMatch[1];
                $tmpName = tempnam(sys_get_temp_dir(), 'php');
                file_put_contents($tmpName, $content);
                
                $_PUT_FILES[$name] = [
                    'name' => $filename,
                    'tmp_name' => $tmpName,
                    'size' => filesize($tmpName),
                    'error' => UPLOAD_ERR_OK
                ];
            } else {
                $_PUT[$name] = $content;
            }
        }
    }
}

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
    global $_PUT, $_PUT_FILES;
    
    parsePutFormData();
    
    // Check if we have FormData (multipart) or JSON
    $name = $_PUT['name'] ?? null;
    
    // If no FormData, try JSON body
    if (!$name) {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? null;
    }
    
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        return;
    }
    
    // Handle file from FormData if present
    if (isset($_PUT_FILES['image'])) {
        $_FILES['image'] = $_PUT_FILES['image'];
    }
    
    $productTypeController->updateProductType((int)$id, $name);
}, 'put');

Route::add('/api/product-types/{id}', function ($id) use ($productTypeController) {
    $productTypeController->deleteProductType((int)$id);
}, 'delete');
