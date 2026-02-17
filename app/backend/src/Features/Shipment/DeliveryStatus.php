<?php

namespace App\Features\Shipment;

enum DeliveryStatus: string {
    case LABEL_CREATED = 'label_created';
    case PRE_TRANSIT = 'pre_transit';
    case TRANSIT = 'transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case FAILURE = 'failure';
    case UNKNOWN = 'unknown';

    public static function fromString(string $status): self {
        return match(strtolower($status)) {
            'label_created' => self::LABEL_CREATED,
            'pre_transit' => self::PRE_TRANSIT,
            'transit' => self::TRANSIT,
            'out_for_delivery' => self::OUT_FOR_DELIVERY,
            'delivered' => self::DELIVERED,
            'returned' => self::RETURNED,
            'failure' => self::FAILURE,
            default => self::UNKNOWN
        };
    }

    public static function getAllValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public function getDisplayName(): string {
        return match($this) {
            self::LABEL_CREATED => 'Label Created',
            self::PRE_TRANSIT => 'Pre-Transit',
            self::TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::RETURNED => 'Returned',
            self::FAILURE => 'Delivery Failed',
            self::UNKNOWN => 'Unknown',
        };
    }
}
