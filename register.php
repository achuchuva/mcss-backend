<?php

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  header("Allow: POST");
  exit;
}

$database = new Database(
  $_ENV["DB_HOST"],
  $_ENV["DB_NAME"],
  $_ENV["DB_USER"],
  $_ENV["DB_PASS"]
);

$conn = $database->getConnection();

$data = (array) json_decode(file_get_contents("php://input"), true);

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

if ($user_gateway->getByEmail($data["email"]) !== false) {
  http_response_code(400);
  echo json_encode(["message" => "User already exists with given email"]);
  exit;
}

$sql = "INSERT INTO users (email, password_hash, api_key)
          VALUES (:email, :password_hash, :api_key)";

$stmt = $conn->prepare($sql);

$password_hash = password_hash($data["password"], PASSWORD_DEFAULT);
$api_key = bin2hex(random_bytes(16));

$stmt->bindValue(":email", $data["email"], PDO::PARAM_STR);
$stmt->bindValue(":password_hash", $password_hash, PDO::PARAM_STR);
$stmt->bindValue(":api_key", $api_key, PDO::PARAM_STR);

$stmt->execute();

echo json_encode(["message" => "Successfully registered in", "api_key" => $api_key]);
exit;
