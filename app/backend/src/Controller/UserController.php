<?php

require_once(__DIR__ . '/../DTO/UserDTO.php');
require_once(__DIR__ . '/../Model/UserModel.php');
require_once(__DIR__ . '/../Service/JWTService.php');

class UserController {
    private UserModel $userModel;
    private JWTService $jwtService;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->jwtService = new JWTService();
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
            $userData['email']
        );
        
        return $this->userModel->createUser($user, $userData['password']);
    }

    public function updateUser(int $userId, $userData) {
        $user = new UserDTO(
            $userData['firstName'],
            $userData['lastName'],
            $userData['username'],
            $userData['email'],
            $userId
        );
        
        $this->userModel->updateUser($user);
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