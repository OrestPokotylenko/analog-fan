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
    if (empty($data['userId']) || empty($data['itemId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'userId and itemId are required']);
        return;
    }
    $result = $likedItemController->postLikedItem($data['userId'], $data['itemId']);
    if ($result) {
        http_response_code(201);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to like item']);
    }
}, 'post');

Route::add('/api/liked-items', function () use ($likedItemController) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['userId']) || empty($data['itemId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'userId and itemId are required']);
        return;
    }
    $result = $likedItemController->deleteLikedItem($data['userId'], $data['itemId']);
    echo json_encode(['success' => (bool) $result]);
}, 'delete');

