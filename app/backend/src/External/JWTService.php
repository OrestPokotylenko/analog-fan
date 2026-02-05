<?php 

namespace App\External;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService {
    private const TOKEN_EXPARATION = 3600;

    public function generateJWT($user) {
        $payload = [
            'iat' => time(),
            'exp' => time() + self::TOKEN_EXPARATION,
            'userId' => is_array($user) ? $user['userId'] : $user->userId,
            'role' => is_array($user) ? $user['role'] : $user->role->value
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public function validateJWT($token) {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            return $decoded->userId;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}