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
        echo json_encode($this->gateway->getAllForUser($this->user_id));
      } else if ($method == "POST") {
        $data = (array) json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data);

        if (!empty($data["share_id"])) {
          $sharingErrors = $this->getSharingErrors($data["share_id"]);

          if (!empty($sharingErrors)) {
            $this->respondUnprocessableEntity($errors);
            return;            
          }

          $this->gateway->joinSharedWorld($data["share_id"], $this->user_id);
          return;
        }

        if (!empty($errors)) {
          $this->respondUnprocessableEntity($errors);
          return;
        }

        $id = $this->gateway->createForUser($data, $this->user_id);

        $this->respondCreated($id);
      } else {
        $this->respondMethodNotAllowed("GET, POST");
      }
    } else {

      $world = $this->gateway->getForUser($id, $this->user_id);

      if ($world === false) {
        $this->respondNotFound($id);
        return;
      }

      switch ($method) {
        case "GET":
          echo json_encode($this->gateway->getForUser($id, $this->user_id));
          break;
        case "PATCH":
          $data = (array) json_decode(file_get_contents("php://input"), true);

          $errors = $this->getValidationErrors($data, false);

          if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
          }

          $rows = $this->gateway->updateForUser($id, $data, $this->user_id);

          echo json_encode(["message" => "World updated successfully", "rows" => $rows]);
          break;
        case "DELETE":
          $rows = $this->gateway->deleteForUser($id, $this->user_id);
          echo json_encode(["message" => "World deleted successfully", "rows" => $rows]);
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
    echo json_encode(["message" => "World with ID $id not found"]);
  }

  private function respondCreated(string $id): void
  {
    http_response_code(201);
    echo json_encode(["message" => "World created!", "id" => $id]);
  }

  private function getValidationErrors(array $data, bool $is_new = true): array
  {
    $errors = [];

    if ($is_new && empty($data["name"])) {
      $errors[] = "World name is required";
    }

    if ($is_new && empty($data["image_url"])) {
      $errors[] = "Image url is required";
    }

    if (!$is_new && empty($data)) {
      $errors[] = "To update the world, the data arrays needs to be not empty";
    }

    return $errors;
  }

  private function getSharingErrors($share_id): array
  {
    $errors = [];
    $sharedWorld = $this->gateway->checkForShareID($share_id);

    if ( ! $sharedWorld) {
      $errors[] = "Requested world doesn't exist";
      return $errors;
    }

    if ($sharedWorld["user_id"] === $this->user_id) {
      $errors[] = "This world is already your own";
      return $errors;
    }

    if ($sharedWorld["shared"] !== true) {
      $errors[] = "The requested world doesn't have sharing enabled";
    }

    return $errors;
  }
}
