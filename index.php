<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

// Get the URL path of the request
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$parts = explode("/", $path);

// Store the resource as the second element
$resource = $parts[2];

// If id is present, store it
$id = $parts[3] ?? null;


// Create database and a user gateway
$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

$user_gateway = new UserGateway($database);

// Create a new Auth object to authenticate the user
$auth = new Auth($user_gateway);

// If the user doesn't have a valid API key, discontinue the request
if (!$auth->authenticateAPIKey()) {
  exit;
}

// Extract user id using the api key
$user_id = $auth->getUserID();

// Depending on the resource, different requests are sent through
switch ($resource) {
  case "worlds":
    $world_gateway = new WorldGateway($database);

    // Create a world controller
    $world_controller = new WorldController($world_gateway, $user_id);

    // Get the world controller to process the request based on the method and id
    $world_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  case "destinations":
    $destination_gateway = new DestinationGateway($database);

    // If world_id isn't present, exit the script with a bad request
    if (empty($_GET["world_id"])) {
      http_response_code(400);
      echo json_encode(["message" => "World ID query string missing"]);
      exit;
    }

    $world_id = $_GET["world_id"];

    $world_gateway = new WorldGateway($database);

    // If the world_id passed in doesn't return a world based on the user id
    // exit the script with an unauthorized error
    if ( ! $world_gateway->getForUser($world_id, $user_id)) {
      http_response_code(401);
      echo json_encode(["message" => "You don't have access to this world"]);
      exit; 
    }

    // Convert the world id to an integer
    $world_id = intval($world_id);

    // Create a destination controller
    $destination_controller = new DestinationController($destination_gateway, $world_id);

    // Get the destinaton controller to process the request based on the method and id
    $destination_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  case "objects":
    $object_gateway = new ObjectGateway($database);

    // If destination_id isn't present, exit the script with a bad request 
    if (empty($_GET["destination_id"])) {
      http_response_code(400);
      echo json_encode(["message" => "Destination ID query string missing"]);
      exit;   
    }

    $destination_id = intval($_GET["destination_id"]);

    $destination_gateway = new DestinationGateway($database);

    // If the destination gateway doesn't find a destination, exit the script with a bad request
    if ( ! $destination_gateway->getDestinationByID($destination_id)) {
      http_response_code(400);
      echo json_encode(["message" => "This destination doesn't exist"]);
      exit;
    }

    // Create an object controller
    $object_controller = new ObjectController($object_gateway, $destination_id);

    // Get the object controller to process the request based on the method and id
    $object_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  default:
    // The resource isn't part of the api request, return not found
    http_response_code(404);
    echo json_encode(["message" => "The requested resource wasn't found"]);
}
