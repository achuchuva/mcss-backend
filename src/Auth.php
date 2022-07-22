<?php

class Auth
{
  private int $user_id;

  public function __construct(
    private UserGateway $user_gateway
  )
  {
    
  }

  public function authenticateAPIKey(): bool
  {
    // Check if the api key is empty, exit if it is
    if (empty($_SERVER["HTTP_X_API_KEY"])) {
      http_response_code(400);
      echo json_encode(["message" => "Missing API key"]);
      return false;
    }
    
    $api_key = $_SERVER["HTTP_X_API_KEY"];

    // If the user with the api key isn't found, exit with an unauthorized error
    $user = $this->user_gateway->getByAPIKey($api_key); 
    if ($user === false)
    {
      http_response_code(401);
      echo json_encode(["message" => "Invalid API key"]);
      return false;
    }
    
    // Set the user id to retrieved user id
    $this->user_id = $user["id"];
    
    return true;
  }

  // Return the user id
  public function getUserID(): int
  {
    return $this->user_id;
  }
}
