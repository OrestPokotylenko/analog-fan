<?php

require_once(__DIR__ . '/BaseModel.php');
require_once(__DIR__ . '/../DTO/UserDTO.php');

class UserModel extends BaseModel {
    public function getUsers() {
        $sql = "SELECT * FROM users";
        $stmt = $this->pdo->query($sql);
        $stmt->execute();
        $usersData = $stmt->fetchAll();

        $users = [];
        foreach ($usersData as $userData) {
            $user = new UserDTO(
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                $userData['user_id']
            );

            $users[] = $user;
        }

        return $users;
    }

    public function getUserById(int $userId) {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $userData = $stmt->fetch();

        if (!$userData) {
            return null;
        }

        $user = new UserDTO(
            $userData['first_name'],
            $userData['last_name'],
            $userData['username'],
            $userData['email'],
            $userData['user_id']
        );

        return $user;
    }

    public function createUser(UserDTO $user, string $password) {
        try{
            $sql = "INSERT INTO users (first_name, last_name, username, email) VALUES (:first_name, :last_name, :username, :email)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
                'username' => $user->username,
                'email' => $user->email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
        } catch (PDOException $e) {
            return false;            
        }

        return true;
    }

    public function updateUser(UserDTO $user) {
        $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user->userId,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'username' => $user->username,
            'email' => $user->email
        ]);
    }
}