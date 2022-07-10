<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$parts = explode("/", $path);

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

$world_gateway = new WorldGateway($database);

$world_controller = new WorldController($world_gateway, 1);

$world_controller->processRequest($_SERVER["REQUEST_METHOD"], null);