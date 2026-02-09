<?php

namespace App\Features\User;

use DateTime;

class UserDTO {
    public readonly ?int $userId;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $username;
    public readonly string $email;
    public readonly ?string $phoneNumber;
    public readonly Role $role;
    public readonly ?string $imageUrl;
    public readonly ?DateTime $createdAt;

    public function __construct(string $firstName, string $lastName, string $username, string $email, Role $role, ?string $phoneNumber = null, ?int $userId = null, ?string $imageUrl = null, ?DateTime $createdAt = null) {
        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->role = $role;
        $this->imageUrl = $imageUrl;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array {
        return [
            'userId' => $this->userId,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'username' => $this->username,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'role' => $this->role->value,
            'imageUrl' => $this->imageUrl,
            'createdAt' => $this->createdAt ? $this->createdAt->format('d.m.Y') : null
        ];
    }
}