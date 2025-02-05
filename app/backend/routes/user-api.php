<?php

require_once(__DIR__ . '/../src/Controller/UserController.php');
$userController = new UserController();

Route::add('/api/users', function() use ($userController) {
    echo json_encode($userController->getUsers());
});

Route::add('/api/users/{id}', function($id) use ($userController) {
    echo json_encode($userController->getUserById($id));
});

Route::add('/api/users', function() use ($userController) {
    $userData = json_decode(file_get_contents('php://input'), true);
    $success = $userController->createUser($userData);
    json_encode(['success' => $success]);
}, 'post');

Route::add('/api/users/{id}', function($id) use ($userController) {
    $userData = json_decode(file_get_contents('php://input'), true);
    $userController->updateUser($id, $userData);
}, 'put');