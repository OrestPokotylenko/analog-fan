<?php

require_once(__DIR__ . '/../DTO/UserDTO.php');
require_once(__DIR__ . '/../Model/UserModel.php');

class UserController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function getUsers() {
        return $this->userModel->getUsers();
    }

    public function getUserById(int $userId) {
        return $this->userModel->getUserById($userId);
    }

    public function createUser($userData) {
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
}