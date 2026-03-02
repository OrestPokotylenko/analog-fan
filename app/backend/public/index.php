<?php

require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../vendor/autoload.php';

require_once(__DIR__ . '/../src/Core/Route.php');

use App\Core\Route;

require_once(__DIR__ . '/../routes/user-api.php');
require_once(__DIR__ . '/../routes/item-api.php');
require_once(__DIR__ . '/../routes/liked-items-api.php');
require_once(__DIR__ . '/../routes/product-type-api.php');
require_once(__DIR__ . '/../routes/shopping-cart-api.php');
require_once(__DIR__ . '/../routes/order-api.php');
require_once(__DIR__ . '/../routes/order-item-api.php');
require_once(__DIR__ . '/../routes/payment-api.php');
require_once(__DIR__ . '/../routes/shipment-api.php');
require_once(__DIR__ . '/../routes/message-api.php');

Route::run();