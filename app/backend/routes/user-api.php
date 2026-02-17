<?php

use App\Features\User\UserController;
use App\Features\ResetPassword\LinkController;
use App\External\JWTService;
use App\Features\ResetPassword\PasswordResetService;
use App\Core\Route;
use App\Features\User\Role;

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

Route::add('/api/users', function() use ($userController, $jwtService) {
    $userData = json_decode(file_get_contents('php://input'), true);
    $userExists = $userController->userExists($userData['username'], $userData['email']);

    if (!$userExists) {
        $createdUser = $userController->createUser($userData, Role::USER);

        if ($createdUser) {
            // Generate JWT token for automatic login after registration
            $token = $jwtService->generateJWT($createdUser);
            echo json_encode(['success' => true, 'token' => $token, 'user' => $createdUser]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User already exists']);
    }
}, 'post');

Route::add('/api/users/{id}', function($id) use ($userController) {
    global $_PUT, $_PUT_FILES;
    
    try {
        parsePutFormData();
        
        // Check if we have multipart data
        if (!empty($_PUT) || !empty($_PUT_FILES)) {
            $userData = $_PUT;
            $imageFile = $_PUT_FILES['image'] ?? null;
        } else {
            // Handle JSON data (without image)
            $userData = json_decode(file_get_contents('php://input'), true);
            $imageFile = null;
        }
        
        $result = $userController->updateUser($id, $userData, Role::USER, $imageFile);
        echo json_encode(['success' => true, 'user' => $result]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
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

