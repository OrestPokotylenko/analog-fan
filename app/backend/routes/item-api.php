<?php

require_once(__DIR__ . '/../src/Controller/ItemContoller.php');
require_once(__DIR__ . '/../src/Controller/UserController.php');

$itemController = new ItemController();
$userController = new UserController();

Route::add('/api/items', function () use ($itemController) {
    echo json_encode($itemController->getItems());
});

Route::add('/api/items', function () use ($itemController, $userController) {
    try {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $type = $_POST['type'] ?? '';
        $images = [];

        if (isset($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if (is_uploaded_file($tmpName)) {
                    $images[] = $tmpName;
                }
            }
        }

        // Get authenticated user
        $userId = $userController->getAuthenticatedUser();

        // Post the item
        $item = $itemController->postItem($userId, $title, $description, $price, $type, $images);
        echo json_encode($item);

    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 500); // Default to 500 if no code is set
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'post');
