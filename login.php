<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

// If the request method isn't POST, exit the script
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  header("Allow: POST");
  exit;
}

// Decode the data sent as JSON
$data = (array) json_decode(file_get_contents("php://input"), true);

// If email or password isn't present, exit with a bad request
if (
  !array_key_exists("email", $data) ||
  !array_key_exists("password", $data)
) {
  http_response_code(400);
  echo json_encode(["message" => "Missing login credentials"]);
  exit;
}


// Create a new database object, giving it the .env variables
$database = new Database(
  $_ENV["DB_HOST"],
  $_ENV["DB_NAME"],
  $_ENV["DB_USER"],
  $_ENV["DB_PASS"]);

$user_gateway = new UserGateway($database);

// Get the user requested via email
$user = $user_gateway->getByEmail($data["email"]);

// If user doesn't exist, exit
if ($user === false) {
  http_response_code(401);
  echo json_encode(["message" => "Invalid authentication"]);
  exit;
}


// If the password is incorrect, exit
if ( ! password_verify($data["password"], $user["password_hash"])) {
  http_response_code(401);
  echo json_encode(["message" => "Invalid authentication"]);
  exit;
}

// Successfully logged in, pass in api key which the frontend will extract
echo json_encode(["message" => "Successfully logged in", "api_key" => $user["api_key"]]);
exit;
