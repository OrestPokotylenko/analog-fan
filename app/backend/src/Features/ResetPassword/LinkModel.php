<?php

namespace App\Features\ResetPassword;

use App\Core\BaseModel;

class LinkModel extends BaseModel {
    public function addLink($linkDto) {
        $sql = "INSERT INTO links (user_id, token, expired) VALUES (:user_id, :token, :expired)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $linkDto->userId,
            'token' => $linkDto->token,
            'expired' => $linkDto->expired
        ]);
    }

    public function getUserIdByToken($token) {
        $sql = "SELECT user_id FROM links WHERE token = :token AND expired = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $userId = $stmt->fetchColumn();

        return $userId;
    }

    public function validLink($token) {
        $sql = "SELECT * FROM links WHERE token = :token AND expired = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);

        return $stmt->fetch() ? true : false;
    }

    public function expireLink($token) {
        $sql = "UPDATE links SET expired = 1 WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
    }
}