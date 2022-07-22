<?php

// Using composer, all php files are loaded, allowing them to be accessible from any file
require "vendor/autoload.php";

// Allow for access from the .env file via $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("Content-type: application/json; charset=UTF-8");
