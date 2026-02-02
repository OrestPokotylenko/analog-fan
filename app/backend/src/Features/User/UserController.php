<?php

namespace App\Features\User;

use App\External\JWTService;
use App\External\CloudinaryService;
use Exception;

class UserController {
    private UserModel $userModel;
    private JWTService $jwtService;
    private CloudinaryService $cloudinaryService;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->jwtService = new JWTService();
        $this->cloudinaryService = new CloudinaryService();
    }

    public function userExists($username, $email) {
        return $this->userModel->userExists($username, $email);
    }

    public function getUsers() {
        return $this->userModel->getUsers();
    }

    public function getUserById(int $userId) {
        return $this->userModel->getUserById($userId);
    }

    public function createUser($userData) {
        if ($userData['password'] !== $userData['repeatPassword']) {
            return null;   
        }

        $user = new UserDTO(
            $userData['firstName'],
            $userData['lastName'],
            $userData['username'],
            $userData['email'],
            $userData['phoneNumber'] ?? null
        );
        
        return $this->userModel->createUser($user, $userData['password']);
    }

    public function updateUser(int $userId, $userData, $imageFile = null) {
        $existingUser = $this->userModel->getUserById($userId);
        if (!$existingUser) {
            throw new Exception('User not found', 404);
        }
        
        // Use existing imageUrl by default
        $imageUrl = $existingUser['imageUrl'];
        
        // Check if user wants to remove image
        $removeImage = isset($userData['removeImage']) && $userData['removeImage'] === 'true';
        
        if ($removeImage) {
            // Delete existing image from Cloudinary if it exists
            if (!empty($existingUser['imageUrl'])) {
                $this->cloudinaryService->deleteImage($existingUser['imageUrl']);
            }
            $imageUrl = null;
        } elseif ($imageFile && isset($imageFile['tmp_name']) && !empty($imageFile['tmp_name'])) {
            // Delete old image from Cloudinary before uploading new one
            if (!empty($existingUser['imageUrl'])) {
                $this->cloudinaryService->deleteImage($existingUser['imageUrl']);
            }
            
            // Upload new image
            $tmpName = $imageFile['tmp_name'];
            if (file_exists($tmpName)) {
                $imageUrl = $this->cloudinaryService->uploadImage($tmpName);
            }
        }

        $user = new UserDTO(
            $userData['firstName'],
            $userData['lastName'],
            $userData['username'],
            $userData['email'],
            $userData['phoneNumber'] ?? null,
            $userId,
            $imageUrl,
            new \DateTime($existingUser['createdAt'])
        );
        
        return $this->userModel->updateUser($user);
    }

    public function authenticateUser($username, $password) {
        $user = $this->userModel->authenticateUser($username, $password);

        if ($user) {
            $jwt = $this->jwtService->generateJWT($user);
            return ['token' => $jwt, 'user' => $user];
        }

        return null;
    }

    public function getAuthenticatedUser() {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            throw new Exception('Authorization header not found.', 401);
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        try {
            $userId = $this->jwtService->validateJWT($token);
            return $userId;
        } catch (Exception $e) {
            throw new Exception('Invalid token.', 401);
        }
    }
}