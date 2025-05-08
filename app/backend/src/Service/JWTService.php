<?php 

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../config/env.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService {
    private const TOKEN_EXPARATION = 3600;

    public function generateJWT($user) {
        $payload = [
            'iat' => time(),
            'exp' => time() + self::TOKEN_EXPARATION,
            'userId' => $user->userId
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