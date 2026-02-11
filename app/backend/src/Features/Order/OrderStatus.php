<?php

namespace App\Features\Order;

enum OrderStatus: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public static function fromString(string $status): self {
        return match(strtolower($status)) {
            'pending' => self::PENDING,
            'processing' => self::PROCESSING,
            'shipped' => self::SHIPPED,
            'delivered' => self::DELIVERED,
            'cancelled' => self::CANCELLED,
            default => throw new \InvalidArgumentException("Invalid order status: $status")
        };
    }

    public static function getAllValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
