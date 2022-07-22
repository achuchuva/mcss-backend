<?php

class ObjectController
{
  public function __construct(private ObjectGateway $gateway, private int $destination_id)
  {
  }

  public function processRequest(string $method, ?string $id): void
  {
    if ($id == null) {
      if ($method == "GET") {
        // Get all objects for given user
        echo json_encode($this->gateway->getAllForUser($this->destination_id));
      } else if ($method == "POST") {
        // The user wants to create a object for a destination
        $data = (array) json_decode(file_get_contents("php://input"), true);

        // Check for errors
        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        // Create the object for the user
        $id = $this->gateway->createForUser($data["name"], $this->destination_id);

        // Respond that the object was created
        $this->respondCreated($id);
      } else {
        // All other methods are not allowed
        $this->respondMethodNotAllowed("GET, POST");
      }
    } else {

      // If the world with given id wasn't found, exit the request
      $world = $this->gateway->getForUser($id, $this->destination_id);

      if ($world === false) {
        $this->respondNotFound($id);
        return;
      }

      switch ($method) {
        case "GET":
          // Get single object using the passed in id
          echo json_encode($this->gateway->getForUser($id, $this->destination_id));
          break;
        case "PATCH":
          // The user wants to update the objects in a destination
          $data = (array) json_decode(file_get_contents("php://input"), true);

          $errors = $this->getValidationErrors($data, false);

          if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
          }

          // Update the object for the user
          $rows = $this->gateway->updateForUser($id, $data, $this->destination_id);

          // Return to the user a successful update
          echo json_encode(["message" => "Object updated successfully", "rows" => $rows]);
          break;
          case "DELETE":
            // Delete the requested object
            $rows = $this->gateway->deleteForUser($id, $this->destination_id);
            echo json_encode(["message" => "Objects deleted successfully", "rows" => $rows]);
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
    echo json_encode(["message" => "Object with ID $id not found"]);
  }

  // Return a 201 status that the destination was created
  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "Object created!", "id" => $id]);
  }

  // Validate the frontend request
  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    $errors = [];

    // If the name value is empty, add to the error array
    if ($is_new && empty($data["name"])) {
      $errors[] = "Object name is required";
    }

    // If the object update data is empty, add to the errors array
    if (!$is_new && empty($data)) {
      $errors[] = "To update the objects, the data arrays needs to be not empty";
    }

    return $errors;
  }
}
