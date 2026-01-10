<?php

namespace App\Features\ResetPassword;

class LinkDTO {
    public readonly ?int $linkId;
    public readonly int $userId;
    public readonly string $token;
    public readonly int $expired;

    public function __construct(int $userId, string $token, int $expired, ?int $linkId = null) {
        $this->userId = $userId;
        $this->token = $token;
        $this->expired = $expired;
        $this->linkId = $linkId;
    }
}