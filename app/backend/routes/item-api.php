<?php

use App\Features\Item\ItemController;
use App\Features\User\UserController;
use App\Core\Route;

header('Content-Type: application/json');

$itemController = new ItemController();
$userController = new UserController();

function http_status_from_exception($e): int {
    $code = $e->getCode();
    return (is_numeric($code) && (int)$code >= 100 && (int)$code <= 599)
        ? (int)$code
        : 500;
}

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
                
                if (strpos($name, '[]') !== false) {
                    $name = str_replace('[]', '', $name);
                    $_PUT_FILES[$name]['name'][] = $filename;
                    $_PUT_FILES[$name]['tmp_name'][] = $tmpName;
                    $_PUT_FILES[$name]['size'][] = filesize($tmpName);
                } else {
                    $_PUT_FILES[$name] = [
                        'name' => $filename,
                        'tmp_name' => $tmpName,
                        'size' => filesize($tmpName)
                    ];
                }
            } else {
                if (strpos($name, '[]') !== false) {
                    $name = str_replace('[]', '', $name);
                    $_PUT[$name][] = $content;
                } else {
                    $_PUT[$name] = $content;
                }
            }
        }
    }
}

Route::add('/api/items/type/([0-9]+)', function ($typeId) use ($itemController) {
    try {
        echo json_encode($itemController->getItemsByType((int)$typeId));
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/items', function () use ($itemController, $userController) {
    try {
        // Try to get authenticated user, but allow null for guests
        try {
            $userId = $userController->getAuthenticatedUser();
        } catch (\Throwable $e) {
            $userId = null;
        }
        
        echo json_encode($itemController->getItems($userId));
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/items/my-items', function () use ($itemController, $userController) {
    try {
        $userId = $userController->getAuthenticatedUser();
        echo json_encode($itemController->fetchUserItems($userId));
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/items/{id}', function ($id) use ($itemController) {
    try {
        $item = $itemController->fetchItemById((int)$id);
        echo json_encode($item);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/items', function () use ($itemController, $userController) {
    try {
        $userId = $userController->getAuthenticatedUser();
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $type = $_POST['type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);
        $condition = $_POST['condition'] ?? 'good';
        $genre = $_POST['genre'] ?? null;
        $files = $_FILES['images'] ?? null;

        $item = $itemController->postItem($userId, $title, $description, $price, $type, $files, $quantity, $condition, $genre);
        echo json_encode($item);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'post');

Route::add('/api/items/{id}', function ($id) use ($itemController, $userController) {
    global $_PUT, $_PUT_FILES;
    
    try {
        parsePutFormData();
        
        $id = (int)$id;
        $userId = $userController->getAuthenticatedUser();
        $existingItem = $itemController->fetchItemById($id);
        $existingItemArray = is_array($existingItem) ? $existingItem : (array)$existingItem;

        if (($existingItemArray['userId'] ?? null) !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        $title = $_PUT['title'] ?? '';
        $description = $_PUT['description'] ?? '';
        $price = (float)($_PUT['price'] ?? 0);
        $type = $_PUT['type'] ?? '';
        $quantity = isset($_PUT['quantity']) ? (int)$_PUT['quantity'] : null;
        $condition = $_PUT['condition'] ?? null;
        $genre = $_PUT['genre'] ?? null;
        $files = $_PUT_FILES['images'] ?? null;

        $existingImages = $_PUT['existing_images'] ?? [];
        if (!is_array($existingImages)) {
            $existingImages = [];
        }

        $item = $itemController->updateItemWithFiles($id, $userId, $title, $description, $price, $type, $files, $existingImages, $quantity, $condition, $genre);

        echo json_encode([
            'success' => true,
            'message' => 'Item updated successfully',
            'item' => $item
        ]);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}, 'put');

Route::add('/api/items/{id}', function ($id) use ($itemController, $userController) {
    try {
        $id = (int)$id;
        $userId = $userController->getAuthenticatedUser();
        $itemController->deleteItem($id, $userId);

        echo json_encode([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}, 'delete');

Route::add('/api/items/{id}/images/{imageIndex}', function ($id, $imageIndex) use ($itemController, $userController) {
    try {
        $id = (int)$id;
        $imageIndex = (int)$imageIndex;
        $userId = $userController->getAuthenticatedUser();

        $item = $itemController->deleteImageFromItem($id, $userId, $imageIndex);

        echo json_encode([
            'success' => true,
            'message' => 'Image deleted successfully',
            'item' => $item
        ]);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}, 'delete');

