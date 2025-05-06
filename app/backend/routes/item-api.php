<?php

require_once(__DIR__ . '/../src/Controller/ItemContoller.php');

$itemController = new ItemController();

Route::add('/api/items', function () use ($itemController) {
    echo json_encode($itemController->getItems());
});

Route::add('/api/items', function () use ($itemController) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $type = $_POST['type'] ?? '';
    $images = [];

    // Process uploaded files
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $images[] = $tmpName; // Add the temporary file path to the images array
            }
        }
    }

    $item = $itemController->postItem($title, $description, $price, $type, $images);
    echo json_encode($item);
}, 'post');
