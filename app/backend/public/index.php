<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

session_start();

require_once(__DIR__ . '/../src/Core/Route.php');
require_once(__DIR__ . '/../routes/user-api.php');

Route::run();