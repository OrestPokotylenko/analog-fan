<?php

require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

require_once(__DIR__ . '/../src/Core/Route.php');

use App\Core\Route;

require_once(__DIR__ . '/../routes/user-api.php');
require_once(__DIR__ . '/../routes/item-api.php');
require_once(__DIR__ . '/../routes/liked-items-api.php');

Route::run();