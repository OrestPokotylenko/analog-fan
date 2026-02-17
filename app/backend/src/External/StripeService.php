<?php

namespace App\External;

use Exception;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService {
    private string $secretKey;

    public function __construct() {
        $config = require __DIR__ . '/../../config/env.php';
        $this->secretKey = $config['stripe']['secret_key'] ?? '';
        
        if (empty($this->secretKey)) {
            throw new Exception('Stripe secret key not configured');
        }
        
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Create a payment intent
     * 
     * @param float $amount Amount in currency (e.g., 10.50 for €10.50)
     * @param string $currency Currency code (default: 'eur')
     * @param array $metadata Optional metadata
     * @return array Payment intent data
     */
    public function createPaymentIntent(float $amount, string $currency = 'eur', array $metadata = []): array {
        try {
            // Convert amount to cents
            $amountInCents = (int)($amount * 100);
            
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'payment_method_types' => ['card', 'ideal'],
                'metadata' => $metadata,
            ]);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'id' => $paymentIntent->id,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent
     * 
     * @param string $paymentIntentId The payment intent ID
     * @return object Payment intent object
     */
    public function retrievePaymentIntent(string $paymentIntentId) {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception('Failed to retrieve payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Confirm payment status
     * 
     * @param string $paymentIntentId The payment intent ID
     * @return string Payment status (succeeded, processing, requires_payment_method, etc.)
     */
    public function getPaymentStatus(string $paymentIntentId): string {
        $paymentIntent = $this->retrievePaymentIntent($paymentIntentId);
        return $paymentIntent->status;
    }
}
