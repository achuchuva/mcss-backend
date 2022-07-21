<?php

class DestinationController
{
  public function __construct(private DestinationGateway $gateway, private int $destination_id)
  {
  }

  public function processRequest(string $method, ?string $id): void
  {
    if ($id == null) {
      if ($method == "GET") {
        echo json_encode($this->gateway->getAllForUser($this->destination_id));
      } else if ($method == "POST") {
        $data = (array) json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        $id = $this->gateway->createForUser($data, $this->destination_id);

        $this->respondCreated($id);
      } else {
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
          echo json_encode($this->gateway->getForUser($id, $this->destination_id));
          break;
        case "PATCH":
          $data = (array) json_decode(file_get_contents("php://input"), true);

          $errors = $this->getValidationErrors($data, false);

          if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
          }

          $rows = $this->gateway->updateForUser($id, $data, $this->destination_id);
          
          echo json_encode(["message" => "Destination updated successfully", "rows" => $rows]);
          break;
          case "DELETE":
            $rows = $this->gateway->deleteForUser($id, $this->destination_id);
            echo json_encode(["message" => "Destination deleted successfully", "rows" => $rows]);
          break;
        default:
          $this->respondMethodNotAllowed("GET, PATCH, DELETE");
          break;
      }
    }
  }

  private function respondUnprocessableEntity(array $errors): void
  {
    http_response_code(422);
    echo json_encode(["errors" => $errors]);
  }


  private function respondMethodNotAllowed(string $allowed_methods): void
  {
    http_response_code(405);
    header("Allow: $allowed_methods");
  }

  private function respondNotFound(string $id): void
  {
    http_response_code(404);
    echo json_encode(["message" => "Destination with ID $id not found"]);
  }

  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "Destination created!", "id" => $id]);
  }

  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    $errors = [];

    if ($is_new && empty($data["realm"])) {
      $errors[] = "Destination realm is required";
    }

    if ($is_new && empty($data["name"])) {
      $errors[] = "Destination name is required";
    }

    if ($is_new && (
      (empty($data["coordinate_x"]) && strlen($data["coordinate_x"]) == 0) ||
      (empty($data["coordinate_y"]) && strlen($data["coordinate_y"]) == 0) || 
      (empty($data["coordinate_z"]) && strlen($data["coordinate_z"]) == 0))) {      
      $errors[] = "Destination coordinates are required";
    }

    if (!$is_new && empty($data)) {
      $errors[] = "To update the destination, the data arrays needs to be not empty";
    }

    return $errors;
  }
}
