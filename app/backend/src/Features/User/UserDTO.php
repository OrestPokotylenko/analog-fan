<?php

namespace App\Features\User;

class UserDTO {
    public readonly ?int $userId;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $username;
    public readonly string $email;

    public function __construct(string $firstName, string $lastName, string $username, string $email, ?int $userId = null) {
        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->email = $email;
    }
}