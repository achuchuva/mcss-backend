<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

// If the request method isn't POST, exit the script
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  header("Allow: POST");
  exit;
}

// Create a new database object, giving it the .env variables
$database = new Database(
  $_ENV["DB_HOST"],
  $_ENV["DB_NAME"],
  $_ENV["DB_USER"],
  $_ENV["DB_PASS"]
);

// Establish a DB connection
$conn = $database->getConnection();

$data = (array) json_decode(file_get_contents("php://input"), true);


// If email or password isn't present, exit with a bad request
if (empty($data["email"])) {
  http_response_code(400);
  echo json_encode(["message" => "Email is required"]);
  exit;
}

if (empty($data["password"])) {
  http_response_code(400);
  echo json_encode(["message" => "Password is required"]);
  exit;
}

$user_gateway = new UserGateway($database);

// Check if a user with given email is present
// If there is, exit with bad request
if ($user_gateway->getByEmail($data["email"]) !== false) {
  http_response_code(400);
  echo json_encode(["message" => "User already exists with given email"]);
  exit;
}

// Create a sql statement, prepare it and bind values to the statement
$sql = "INSERT INTO users (email, password_hash, api_key)
          VALUES (:email, :password_hash, :api_key)";

$stmt = $conn->prepare($sql);

// Convert the password to a hash
$password_hash = password_hash($data["password"], PASSWORD_DEFAULT);

// Generate a random api key
$api_key = bin2hex(random_bytes(16));

$stmt->bindValue(":email", $data["email"], PDO::PARAM_STR);
$stmt->bindValue(":password_hash", $password_hash, PDO::PARAM_STR);
$stmt->bindValue(":api_key", $api_key, PDO::PARAM_STR);

$stmt->execute();

// Successfully registered in, pass in api key which the frontend will extract
echo json_encode(["message" => "Successfully registered in", "api_key" => $api_key]);
exit;
