<?php

class DestinationController
{
  public function __construct(private DestinationGateway $gateway, private int $destination_id)
  {
  }

  public function processRequest(string $method, ?string $id): void
  {
    // The id is null
    if ($id == null) {
      if ($method == "GET") {
        // Retrieve all the destinations
        echo json_encode($this->gateway->getAllForUser($this->destination_id));
      } else if ($method == "POST") {
        // The user wants to create a new destination
        $data = (array) json_decode(file_get_contents("php://input"), true);

        // Validate the request
        $errors = $this->getValidationErrors($data);

        // If there are no errors, proceed
        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        // Create the requested destination
        $id = $this->gateway->createForUser($data, $this->destination_id);

        // Respond created back to the frontend
        $this->respondCreated($id);
      } else {
        // All other methods are not allowed
        $this->respondMethodNotAllowed("GET, POST");
      }
    } else {

      $destination = $this->gateway->getForUser($id, $this->destination_id);

      if ($destination === false) {
        $this->respondNotFound($id);
        return;
      }

      switch ($method) {
        case "GET":
          // Get a single destination based on the id
          echo json_encode($this->gateway->getForUser($id, $this->destination_id));
          break;
        case "PATCH":
          // The user wants to update a destination
          $data = (array) json_decode(file_get_contents("php://input"), true);

          // Validate the request
          $errors = $this->getValidationErrors($data, false);

          // If there are no errors, proceed
          if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
          }

          // Execute the requested destination updated
          $rows = $this->gateway->updateForUser($id, $data, $this->destination_id);

          // Return a successful status and message to the frontend
          // (200 is default code status)
          echo json_encode(["message" => "Destination updated successfully", "rows" => $rows]);
          break;
        case "DELETE":
          // User wants to delete a destination
          // Return a successful status and message to the frontend
          // (200 is default code status)
          $rows = $this->gateway->deleteForUser($id, $this->destination_id);
          echo json_encode(["message" => "Destination deleted successfully", "rows" => $rows]);
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
    echo json_encode(["message" => "Destination with ID $id not found"]);
  }

  // Return a 201 status that the destination was created
  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "Destination created!", "id" => $id]);
  }

  // Validate the frontend request
  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    // Is new checks whether the destination is new or being updated
    $errors = [];

    // If any of the required fields are empty, add to the errors array
    if ($is_new && empty($data["realm"])) {
      $errors[] = "Destination realm is required";
    }

    if ($is_new && empty($data["name"])) {
      $errors[] = "Destination name is required";
    }

    // Strlen also needs to be used as a check as 0 is considered to be empty
    if ($is_new && (
      (empty($data["coordinate_x"]) && strlen($data["coordinate_x"]) == 0) ||
      (empty($data["coordinate_y"]) && strlen($data["coordinate_y"]) == 0) ||
      (empty($data["coordinate_z"]) && strlen($data["coordinate_z"]) == 0))) {
      $errors[] = "Destination coordinates are required";
    }

    // If the destination update data is empty, add to the errors array
    if (!$is_new && empty($data)) {
      $errors[] = "To update the destination, the data arrays needs to be not empty";
    }

    return $errors;
  }
}
