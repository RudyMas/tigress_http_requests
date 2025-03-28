<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Tigress\HttpRequests;

require_once 'vendor/autoload.php';

// 1. Create a logger
$logger = new Logger('tigress');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/http.log', Level::Debug));

// 2. Create the HttpRequest instance with the logger
$http = new HttpRequests('https://api.example.com', $logger);

// 3. Use as
try {
    $response = $http->get('/endpoint');
    $data = $http->getJsonBody($response);
    print_r($data);
} catch (Throwable $e) {
    echo "Fout: " . $e->getMessage();
}