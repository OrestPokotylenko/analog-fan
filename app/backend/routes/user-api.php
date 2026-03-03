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
    
    if (empty($userData['username']) || empty($userData['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
        return;
    }
    
    $userExists = $userController->userExists($userData['username'], $userData['email']);

    if (!$userExists) {
        $createdUser = $userController->createUser($userData, Role::USER);

        if ($createdUser) {
            // Generate JWT token for automatic login after registration
            $token = $jwtService->generateJWT($createdUser);
            http_response_code(201);
            echo json_encode(['success' => true, 'token' => $token, 'user' => $createdUser]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        }
    } else {
        http_response_code(409);
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

Route::add('/api/users/{id}', function($id) use ($userController) {
    try {
        $result = $userController->deleteUser($id);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } catch (\Throwable $e) {
        $code = $e->getCode() === 404 ? 404 : 500;
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}, 'delete');

Route::add('/api/authenticate', function () use ($userController) {
    $body = json_decode(file_get_contents('php://input'), true);
    $username = $body['username'] ?? '';
    $password = $body['password'] ?? '';

    $result = $userController->authenticateUser($username, $password);
    
    if ($result) {
        echo json_encode(['success' => true, 'token' => $result['token'], 'user' => $result['user']]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
}, 'post');

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
    $email = $_GET['email'] ?? '';
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    $result = $passwordResetService->requestPasswordReset($email);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Reset link sent']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}, 'get');

Route::add('/api/validate-token', function () use ($linkController) {
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['isValid' => false]);
        return;
    }
    $result = $linkController->validLink($token);
    echo json_encode(['isValid' => $result]);
});

Route::add('/api/reset-password', function () use ($passwordResetService) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['token']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token and password are required']);
        return;
    }
    $passwordResetService->resetPassword($data['token'], $data['password']);
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
}, 'put');