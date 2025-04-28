<?php

require_once(__DIR__ . '/../src/Controller/ItemContoller.php');

$itemController = new ItemController();

Route::add('/api/items', function() use ($itemController) {
    echo json_encode($itemController->getItems());
});