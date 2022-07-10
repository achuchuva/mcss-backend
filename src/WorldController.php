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
        echo json_encode($this->gateway->getWorlds($this->user_id));
      } else if ($method == "POST") {
        $data = (array) json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        // $id = $this->gateway->createForUser($data, $this->user_id);

        $this->respondCreated($id);
      } else {
        $this->respondMethodNotAllowed("GET, POST");
      }
    } else {

      // $task = $this->gateway->getForUser($id, $this->user_id);

      // if ($task === false) {
      //   $this->respondNotFound($id);
      //   return;
      // }

      // switch ($method) {
      //   case "GET":
      //     echo json_encode($this->gateway->getForUser($id, $this->user_id));
      //     break;
      //   case "PATCH":
      //     $data = (array) json_decode(file_get_contents("php://input"), true);

      //     $errors = $this->getValidationErrors($data, false);

      //     if (!empty($errors)) {
      //       $this->respondUnprocessableEntity($errors);
      //       return;
      //     }
      //     $rows = $this->gateway->updateForUser($id, $data, $this->user_id);
      //     echo json_encode(["message" => "Task updated successfully", "rows" => $rows]);
      //     break;
      //     case "DELETE":
      //       $rows = $this->gateway->deleteForUser($id, $this->user_id);
      //       echo json_encode(["message" => "Task deleted successfully", "rows" => $rows]);
      //     break;
      //   default:
      //     $this->respondMethodNotAllowed("GET, PATCH, DELETE");
      //     break;
      // }
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
    echo json_encode(["message" => "Task with ID $id not found"]);
  }

  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "Task created", "id" => $id]);
  }

  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    $errors = [];

    if ($is_new && empty($data["name"])) {
      $errors[] = "Name is required";
    }

    if (!empty($data["priority"])) {
      if (filter_var($data["priority"], FILTER_VALIDATE_INT) === false) {
        $errors[] = "Priority must be an integer";
      }
    }

    return $errors;
  }
}
