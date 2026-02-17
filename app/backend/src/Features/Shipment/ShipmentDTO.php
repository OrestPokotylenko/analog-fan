<?php

namespace App\Features\Shipment;

class ShipmentDTO {
    public readonly int $id;
    public readonly int $orderId;
    public readonly ?string $shippoShipmentId;
    public readonly ?string $shippoTransactionId;
    public readonly string $carrier;
    public readonly string $service;
    public readonly string $trackingNumber;
    public readonly ?string $trackingUrl;
    public readonly ?string $labelUrl;
    public readonly float $shippingCost;
    public readonly string $currency;
    public readonly string $deliveryStatus;
    public readonly ?string $estimatedDeliveryDate;
    public readonly ?string $shippedAt;
    public readonly ?string $deliveredAt;
    public readonly ?array $trackingHistory;
    public readonly string $createdAt;
    public readonly ?string $updatedAt;

    public function __construct(
        int $id,
        int $orderId,
        ?string $shippoShipmentId,
        ?string $shippoTransactionId,
        string $carrier,
        string $service,
        string $trackingNumber,
        ?string $trackingUrl,
        ?string $labelUrl,
        float $shippingCost,
        string $currency,
        string $deliveryStatus,
        ?string $estimatedDeliveryDate,
        ?string $shippedAt,
        ?string $deliveredAt,
        ?array $trackingHistory,
        string $createdAt,
        ?string $updatedAt
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->shippoShipmentId = $shippoShipmentId;
        $this->shippoTransactionId = $shippoTransactionId;
        $this->carrier = $carrier;
        $this->service = $service;
        $this->trackingNumber = $trackingNumber;
        $this->trackingUrl = $trackingUrl;
        $this->labelUrl = $labelUrl;
        $this->shippingCost = $shippingCost;
        $this->currency = $currency;
        $this->deliveryStatus = $deliveryStatus;
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;
        $this->shippedAt = $shippedAt;
        $this->deliveredAt = $deliveredAt;
        $this->trackingHistory = $trackingHistory;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $data): self {
        return new self(
            id: (int)$data['id'],
            orderId: (int)$data['order_id'],
            shippoShipmentId: $data['shippo_shipment_id'] ?? null,
            shippoTransactionId: $data['shippo_transaction_id'] ?? null,
            carrier: $data['carrier'],
            service: $data['service'],
            trackingNumber: $data['tracking_number'],
            trackingUrl: $data['tracking_url'] ?? null,
            labelUrl: $data['label_url'] ?? null,
            shippingCost: (float)$data['shipping_cost'],
            currency: $data['currency'] ?? 'USD',
            deliveryStatus: $data['delivery_status'],
            estimatedDeliveryDate: $data['estimated_delivery_date'] ?? null,
            shippedAt: $data['shipped_at'] ?? null,
            deliveredAt: $data['delivered_at'] ?? null,
            trackingHistory: isset($data['tracking_history']) ? 
                (is_string($data['tracking_history']) ? json_decode($data['tracking_history'], true) : $data['tracking_history']) 
                : null,
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'orderId' => $this->orderId,
            'shippoShipmentId' => $this->shippoShipmentId,
            'shippoTransactionId' => $this->shippoTransactionId,
            'carrier' => $this->carrier,
            'service' => $this->service,
            'trackingNumber' => $this->trackingNumber,
            'trackingUrl' => $this->trackingUrl,
            'labelUrl' => $this->labelUrl,
            'shippingCost' => $this->shippingCost,
            'currency' => $this->currency,
            'deliveryStatus' => $this->deliveryStatus,
            'estimatedDeliveryDate' => $this->estimatedDeliveryDate,
            'shippedAt' => $this->shippedAt,
            'deliveredAt' => $this->deliveredAt,
            'trackingHistory' => $this->trackingHistory,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
