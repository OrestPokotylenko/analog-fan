<?php

namespace App\Features\Order;

class OrderDTO {
    public readonly int $id;
    public readonly int $userId;
    public readonly string $orderNumber;
    public readonly string $email;
    public readonly ?string $phone;
    public readonly string $street;
    public readonly string $houseNumber;
    public readonly string $city;
    public readonly string $zipCode;
    public readonly string $country;
    public readonly float $subtotal;
    public readonly float $taxAmount;
    public readonly float $shippingCost;
    public readonly float $totalAmount;
    public readonly ?string $paymentMethod;
    public readonly string $paymentStatus;
    public readonly ?string $transactionId;
    public readonly string $status;
    public readonly ?string $trackingNumber;
    public readonly ?string $shippedAt;
    public readonly ?string $deliveredAt;
    public readonly string $createdAt;
    public readonly ?string $updatedAt;

    public function __construct(
        int $id,
        int $userId,
        string $orderNumber,
        string $email,
        ?string $phone,
        string $street,
        string $houseNumber,
        string $city,
        string $zipCode,
        string $country,
        float $subtotal,
        float $taxAmount,
        float $shippingCost,
        float $totalAmount,
        ?string $paymentMethod,
        string $paymentStatus,
        ?string $transactionId,
        string $status,
        ?string $trackingNumber,
        ?string $shippedAt,
        ?string $deliveredAt,
        string $createdAt,
        ?string $updatedAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->orderNumber = $orderNumber;
        $this->email = $email;
        $this->phone = $phone;
        $this->street = $street;
        $this->houseNumber = $houseNumber;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->country = $country;
        $this->subtotal = $subtotal;
        $this->taxAmount = $taxAmount;
        $this->shippingCost = $shippingCost;
        $this->totalAmount = $totalAmount;
        $this->paymentMethod = $paymentMethod;
        $this->paymentStatus = $paymentStatus;
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->trackingNumber = $trackingNumber;
        $this->shippedAt = $shippedAt;
        $this->deliveredAt = $deliveredAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function toDTO(array $orderData): self {
        return new self(
            $orderData['id'],
            $orderData['user_id'],
            $orderData['order_number'],
            $orderData['email'],
            $orderData['phone_number'] ?? null,
            $orderData['street'],
            $orderData['house_number'],
            $orderData['city'],
            $orderData['zip_code'],
            $orderData['country'],
            (float)$orderData['subtotal'],
            (float)$orderData['tax_amount'],
            (float)$orderData['shipping_cost'],
            (float)$orderData['total_amount'],
            $orderData['payment_method'] ?? null,
            $orderData['payment_status'],
            $orderData['transaction_id'] ?? null,
            $orderData['order_status'],
            $orderData['tracking_number'] ?? null,
            $orderData['shipped_at'] ?? null,
            $orderData['delivered_at'] ?? null,
            $orderData['created_at'],
            $orderData['updated_at']
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'orderNumber' => $this->orderNumber,
            'email' => $this->email,
            'phone' => $this->phone,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'city' => $this->city,
            'zipCode' => $this->zipCode,
            'country' => $this->country,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
            'shippingCost' => $this->shippingCost,
            'totalAmount' => $this->totalAmount,
            'paymentMethod' => $this->paymentMethod,
            'paymentStatus' => $this->paymentStatus,
            'transactionId' => $this->transactionId,
            'status' => $this->status,
            'trackingNumber' => $this->trackingNumber,
            'shippedAt' => $this->shippedAt,
            'deliveredAt' => $this->deliveredAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }
}