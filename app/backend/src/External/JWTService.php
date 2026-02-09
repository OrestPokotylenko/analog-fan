<?php 

namespace App\External;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class JWTService {
    private const TOKEN_EXPIRATION = 3600; // 1 hour

    public function generateJWT($user) {
        $payload = [
            'iat' => time(),
            'exp' => time() + self::TOKEN_EXPIRATION,
            'userId' => is_array($user) ? $user['userId'] : $user->userId,
            'role' => is_array($user) ? $user['role'] : $user->role->value
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public function validateJWT($token) {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            return $decoded->userId;
        } catch (ExpiredException $e) {
            http_response_code(401);
            throw new Exception('Token has expired');
        } catch (Exception $e) {
            http_response_code(401);
            throw new Exception('Invalid token');
        }
    }
}