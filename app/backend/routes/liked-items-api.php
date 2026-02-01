<?php

use App\Features\Item\LikedItemController;
use App\Core\Route;

$likedItemController = new LikedItemController();

Route::add('/api/liked-items/([0-9]+)', function ($userId) use ($likedItemController) {
    $likedItems = $likedItemController->fetchLikedItems($userId);
    echo json_encode($likedItems);
});

Route::add('/api/liked-items', function () use ($likedItemController) {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $likedItemController->postLikedItem($data['userId'], $data['itemId']);
    echo json_encode($result);
}, 'post');

Route::add('/api/liked-items', function () use ($likedItemController) {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $likedItemController->deleteLikedItem($data['userId'], $data['itemId']);
    echo json_encode($result);
}, 'delete');

