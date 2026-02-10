<?php

namespace App\Features\Order;

enum PaymentStatus: string {
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public static function fromString(string $status): self {
        return match(strtolower($status)) {
            'pending' => self::PENDING,
            'paid' => self::PAID,
            'failed' => self::FAILED,
            'refunded' => self::REFUNDED,
            default => throw new \InvalidArgumentException("Invalid payment status: $status")
        };
    }

    public static function getAllValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
