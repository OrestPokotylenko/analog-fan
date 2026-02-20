<?php

// Suppress deprecation warnings from Ratchet library
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/env.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\MessageWebSocket;

echo "Starting WebSocket server...\n";
echo "Waiting for database connection...\n";

// Wait for database to be ready with retry logic
$maxRetries = 10;
$retryCount = 0;
$connected = false;

while ($retryCount < $maxRetries && !$connected) {
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset={$_ENV['DB_CHARSET']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $connected = true;
        echo "Database connection successful!\n";
    } catch (PDOException $e) {
        $retryCount++;
        echo "Database connection attempt {$retryCount}/{$maxRetries} failed: {$e->getMessage()}\n";
        if ($retryCount < $maxRetries) {
            echo "Retrying in 3 seconds...\n";
            sleep(3);
        } else {
            echo "Failed to connect to database after {$maxRetries} attempts. Exiting.\n";
            exit(1);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MessageWebSocket()
        )
    ),
    8081
);

echo "WebSocket server started on port 8081\n";
echo "Listening for connections...\n";

$server->run();
