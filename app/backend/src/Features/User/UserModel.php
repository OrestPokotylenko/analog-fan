<?php

namespace App\Features\User;

use App\Core\BaseModel;
use PDOException;

class UserModel extends BaseModel {
    /** Columns needed for UserDTO — excludes password */
    private const COLUMNS = 'user_id, first_name, last_name, username, email, role, phone_number, image_url, created_at';

    public function userExists($username, $email) {
        $sql = "SELECT 1 FROM users WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array('username' => $username, 'email' => $email));

        return $stmt->fetchColumn() !== false;
    }

    public function getUsers() {
        $sql = "SELECT user_id, first_name, last_name, username, email, role, phone_number, image_url, created_at FROM users";
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
                Role::from($userData['role']),
                $userData['phone_number'] ?? null,
                $userData['user_id'],
                $userData['image_url'] ?? null,
                new \DateTime($userData['created_at'])
            );

            $users[] = $user->toArray();
        }

        return $users;
    }

    public function getUserById(int $userId) {
        $sql = "SELECT " . self::COLUMNS . " FROM users WHERE user_id = :user_id";
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
            Role::from($userData['role']),
            $userData['phone_number'] ?? null,
            $userData['user_id'],
            $userData['image_url'] ?? null,
            new \DateTime($userData['created_at'])
        );

        return $user->toArray();
    }

    public function createUser(UserDTO $user, string $password) {
        try{
            $sql = "INSERT INTO users (first_name, last_name, username, email, password, phone_number, role) VALUES (:first_name, :last_name, :username, :email, :password, :phone_number, :role)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
                'username' => $user->username,
                'email' => $user->email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone_number' => $user->phoneNumber,
                'role' => $user->role->value
            ]);
        } catch (PDOException $e) {
            return null;            
        }

        return $user->toArray();
    }

    public function updateUser(UserDTO $user) {
        $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email, phone_number = :phone_number, image_url = :image_url WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user->userId,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'username' => $user->username,
            'email' => $user->email,
            'phone_number' => $user->phoneNumber,
            'image_url' => $user->imageUrl
        ]);
        
        return $user->toArray();
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
            $user = new UserDTO(
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                Role::from($userData['role']),
                $userData['phone_number'] ?? null,
                $userData['user_id'],
                $userData['image_url'] ?? null,
                new \DateTime($userData['created_at'])
            );
            return $user->toArray();
        }
    
        return null;
    }

    public function getUserByEmail($email) {
        $sql = "SELECT " . self::COLUMNS . " FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $userData = $stmt->fetch();

        if ($userData) {
            $user = new UserDTO(
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                Role::from($userData['role']),
                $userData['phone_number'] ?? null,
                $userData['user_id'],
                $userData['image_url'] ?? null,
                new \DateTime($userData['created_at'])
            );

            return $user;
        }

        return null;
    }
}