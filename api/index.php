<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[3];

$id = $parts[4] ?? null;

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

$user_gateway = new UserGateway($database);

$auth = new Auth($user_gateway);

if (!$auth->authenticateAPIKey()) {
  exit;
}

$user_id = $auth->getUserID();

switch ($resource) {
  case "worlds":
    $world_gateway = new WorldGateway($database);

    $world_controller = new WorldController($world_gateway, $user_id);

    $world_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  case "destinations":
    $destination_gateway = new DestinationGateway($database);

    if (empty($_GET["world_id"])) {
      http_response_code(400);
      echo json_encode(["message" => "World ID query string missing"]);
      exit;
    }

    $world_id = $_GET["world_id"];

    $world_gateway = new WorldGateway($database);

    if ( ! $world_gateway->getForUser($world_id, $user_id)) {
      http_response_code(401);
      echo json_encode(["message" => "You don't have access to this world"]);
      exit; 
    }

    $world_id = intval($world_id);

    $destination_controller = new DestinationController($destination_gateway, $world_id);

    $destination_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  case "objects":
    $object_gateway = new ObjectGateway($database);

    if (empty($_GET["destination_id"])) {
      http_response_code(400);
      echo json_encode(["message" => "Destination ID query string missing"]);
      exit;   
    }

    $destination_id = intval($_GET["destination_id"]);

    $destination_gateway = new DestinationGateway($database);

    if ( ! $destination_gateway->getDestinationByID($destination_id)) {
      http_response_code(401);
      echo json_encode(["message" => "This destination doesn't exist"]);
      exit;
    }

    $object_controller = new ObjectController($object_gateway, $destination_id);

    $object_controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
    break;
  default:
    http_response_code(404);
    echo json_encode(["message" => "The requested resource wasn't found"]);
}
