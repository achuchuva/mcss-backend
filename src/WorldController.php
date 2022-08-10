<?php

class WorldController
{
  public function __construct(private WorldGateway $gateway, private int $user_id)
  {
  }

  public function processRequest(string $method, ?string $id): void
  {
    if ($id == null) {
      if ($method == "GET") {
        // Get all worlds for a given user
        echo json_encode($this->gateway->getAllForUser($this->user_id));
      } else if ($method == "POST") {
        // Create a new world for given user
        $data = (array) json_decode(file_get_contents("php://input"), true);

        // Check for errors
        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        // Create the world
        $id = $this->gateway->createForUser($data, $this->user_id);

        // Respond with a created status to the frontend
        $this->respondCreated($id);
      } else {
        // All other methods are not allowed
        $this->respondMethodNotAllowed("GET, POST");
      }
    } else {

      $world = $this->gateway->getForUser($id, $this->user_id);

      // If the requested id is join, the user wants to join a world
      if ($world === false && $id != "join") {
        $this->respondNotFound($id);
        return;
      }

      switch ($method) {
        case "GET":
          // Get a single world based on id and user_id
          echo json_encode($this->gateway->getForUser($id, $this->user_id));
          break;
        case "PATCH":
          // Update a world for a given user
          $data = (array) json_decode(file_get_contents("php://input"), true);

          // Check for errors
          $errors = $this->getValidationErrors($data, false);

          if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
          }

          // Update the world
          $rows = $this->gateway->updateForUser($id, $data, $this->user_id);

          // Return a successful message to the frontend
          echo json_encode(["message" => "World updated successfully", "rows" => $rows]);
          break;
        case "POST":
          $data = (array) json_decode(file_get_contents("php://input"), true);

          // If the share id is not empty, continue
          if (!empty($data["share_id"])) {
            $share_id = $data["share_id"];

            // Check for errors
            $sharingErrors = $this->getSharingErrors($share_id);

            if (!empty($sharingErrors)) {
              $this->respondUnprocessableEntity($sharingErrors);
              return;
            }

            // Join the shared world base on the share_id and user_id
            $world_access_id = $this->gateway->joinSharedWorld($share_id, $this->user_id);

            // Return a successful message to the frontend
            echo json_encode(["message" => "World joined successfully", "id" => $world_access_id]);            
            return;
          } else {
            // The share id wasn't present, return a 422 status code
            http_response_code(422);
            $errors[] = "Share ID is required";
            echo json_encode(["errors" => $errors]);
          }
          break;
        case "DELETE":
          // Delete a world for a given user
          $rows = $this->gateway->deleteForUser($id, $this->user_id);
          echo json_encode(["message" => "World deleted successfully", "rows" => $rows]);
          break;
        default:
          // All other methods are not allowed
          $this->respondMethodNotAllowed("GET, PATCH, DELETE");
          break;
      }
    }
  }

  // Return a unprocessable entity to the frontend
  // The frontend has some sort of error in their request
  private function respondUnprocessableEntity(array $errors): void
  {
    http_response_code(422);
    echo json_encode(["errors" => $errors]);
  }

  // Return a method request is not allowed response
  private function respondMethodNotAllowed(string $allowed_methods): void
  {
    http_response_code(405);
    header("Allow: $allowed_methods");
  }

  // Return a resource not found response
  private function respondNotFound(string $id): void
  {
    http_response_code(404);
    echo json_encode(["message" => "World with ID $id not found"]);
  }

  // Return a 201 status that the destination was created
  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "World created!", "id" => $id]);
  }

  // Validate the frontend request
  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    $errors = [];

    // If any of the required fields are empty, add to the errors array
    if ($is_new && empty($data["name"])) {
      $errors[] = "World name is required";
    }

    // If the world update data is empty, add to the errors array
    if (!$is_new && empty($data)) {
      $errors[] = "To update the world, the data arrays needs to be not empty";
    }

    return $errors;
  }

  // Validate the frontend request for joining a world
  private function getSharingErrors($share_id): array
  {
    $errors = [];
    // Return a world based on share_id
    $sharedWorld = $this->gateway->findByShareID($share_id);

    // If it doesn't exist, add to error array
    if (!$sharedWorld) {
      $errors[] = "Requested world doesn't exist";
      return $errors;
    }

    // If the user_id of the world is equal to the user idea, add to error array
    if ($sharedWorld["user_id"] === $this->user_id) {
      $errors[] = "This world is already your own";
      return $errors;
    }

    // If the world doesn't have sharing enabled, add to error array
    if ($sharedWorld["shared"] !== 1) {
      $errors[] = "The requested world doesn't have sharing enabled";
    }

    // If the world access table already has a matching entry, add to error array
    if ($this->gateway->findWorldAccess($sharedWorld["id"], $this->user_id) !== false) {
      $errors[] = "You already joined this world";
    }

    return $errors;
  }
}
