<?php

use App\External\StripeService;
use App\Features\User\UserController;
use App\Core\Route;

// Get Stripe publishable key
Route::add('/api/payment/config', function () {
    try {
        $config = require __DIR__ . '/../config/env.php';
        echo json_encode([
            'publishableKey' => $config['stripe']['publishable_key'] ?? ''
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
});

// Create payment intent
Route::add('/api/payment/create-intent', function () {
    try {
        $stripeService = new StripeService();
        $userController = new UserController();
        
        // Authenticate user
        $userId = $userController->getAuthenticatedUser();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Invalid amount', 400);
        }
        
        $amount = (float)$data['amount'];
        $currency = $data['currency'] ?? 'eur';
        $metadata = [
            'user_id' => $userId,
            'order_id' => $data['order_id'] ?? null,
        ];
        
        $paymentIntent = $stripeService->createPaymentIntent($amount, $currency, $metadata);
        
        echo json_encode($paymentIntent);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'post');

// Get payment status
Route::add('/api/payment/status/([a-zA-Z0-9_]+)', function ($paymentIntentId) {
    try {
        $stripeService = new StripeService();
        $userController = new UserController();
        
        // Authenticate user
        $userController->getAuthenticatedUser();
        
        $status = $stripeService->getPaymentStatus($paymentIntentId);
        
        echo json_encode(['status' => $status]);
    } catch (\Throwable $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});
