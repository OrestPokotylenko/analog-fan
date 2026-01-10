<?php

namespace App\Features\User;

use App\Core\BaseModel;
use PDOException;

class UserModel extends BaseModel {
    public function userExists($username, $email) {
        $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array('username' => $username, 'email' => $email));
        $userData = $stmt->fetch();

        return $userData ? true : false;
    }

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
            $sql = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (:first_name, :last_name, :username, :email, :password)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
                'username' => $user->username,
                'email' => $user->email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
        } catch (PDOException $e) {
            return null;            
        }

        return $user;
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

    public function resetPassword($userId, $password) {
        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    public function authenticateUser($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($username, $username));
        $userData = $stmt->fetch();
    
        if ($userData && password_verify($password, $userData['password'])) {
            return new UserDTO(
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                $userData['user_id']
            );
        }
    
        return null;
    }

    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $userData = $stmt->fetch();

        if ($userData) {
            return new UserDTO(
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                $userData['user_id']
            );
        } 

        return null;
    }
}