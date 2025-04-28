<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

session_start();

require_once(__DIR__ . '/../src/Core/Route.php');
require_once(__DIR__ . '/../routes/user-api.php');
require_once(__DIR__ . '/../routes/item-api.php');
require_once(__DIR__ . '/../routes/liked-items-api.php');

Route::run();