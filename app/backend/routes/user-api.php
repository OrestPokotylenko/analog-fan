<?php

use App\Features\User\UserController;
use App\Features\ResetPassword\LinkController;
use App\External\JWTService;
use App\Features\ResetPassword\PasswordResetService;
use App\Core\Route;

$userController = new UserController();
$jwtService = new JWTService();
$passwordResetService = new PasswordResetService();
$linkController = new LinkController();

Route::add('/api/users', function() use ($userController) {
    echo json_encode($userController->getUsers());
});

Route::add('/api/users/{id}', function($id) use ($userController) {
    echo json_encode($userController->getUserById($id));
});

Route::add('/api/users', function() use ($userController) {
    $userData = json_decode(file_get_contents('php://input'), true);
    $userExists = $userController->userExists($userData['username'], $userData['email']);

    if (!$userExists) {
        $createdUser = $userController->createUser($userData);

        if ($createdUser) {
            echo json_encode(['success' => true, 'user' => $createdUser]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User already exists']);
    }
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

Route::add('/api/reset-password', function () use ($passwordResetService) {
    $email = $_GET['email'];
    $result = $passwordResetService->requestPasswordReset($email);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Reset link sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}, 'get');

Route::add('/api/validate-token', function () use ($linkController) {
    $token = $_GET['token'];
    $result = $linkController->validLink($token);
    echo json_encode(['isValid' => $result]);
});

Route::add('/api/reset-password', function () use ($passwordResetService) {
    $data = json_decode(file_get_contents('php://input'), true);
    $passwordResetService->resetPassword($data['token'], $data['password']);
}, 'put');

