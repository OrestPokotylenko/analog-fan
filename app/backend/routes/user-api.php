<?php

require_once(__DIR__ . '/../src/Controller/UserController.php');
require_once(__DIR__ . '/../src/Service/JWTService.php');

$userController = new UserController();
$jwtService = new JWTService();

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

Route::add('/api/authenticate', function () use ($userController) {
    $username = $_GET['username'];
    $password = $_GET['password'];

    $result = $userController->authenticateUser($username, $password);
    
    if ($result) {
        echo json_encode(['success' => true, 'token' => $result['token'], 'user' => $result['user']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
}, 'get');

Route::add('/api/protected', function () use ($jwtService) {
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $decoded = $jwtService->validateJWT($token);
        if ($decoded) {
            echo json_encode(['success' => true, 'message' => 'Access granted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No token provided']);
    }
}, 'get');