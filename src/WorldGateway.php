<?php

class WorldGateway
{
  private PDO $conn;
  public function __construct(Database $database)
  {
    $this->conn = $database->getConnection();
  }

  public function getAllForUser(int $user_id): array | false
  {
    $sql = "SELECT *
            FROM worlds
            WHERE user_id = :user_id
            ORDER BY name";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $row['shared'] = (bool) $row['shared'];

      $data[] = $row;
    }

    return $data;
  }

  public function getForUser(string $id, int $user_id): array | false
  {
    $sql = "SELECT * 
            FROM worlds
            WHERE id = :id
            AND user_id = :user_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data !== false) {
      $data['shared'] = (bool) $data['shared'];
    }

    return $data;
  }

  public function checkForShareID($share_id): array | false
  {
    $sql = "SELECT * 
            FROM worlds
            WHERE share_id = :share_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":share_id", $share_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function joinSharedWorld(string $share_id, int $user_id): bool
  {
    return false;
  }

  public function createForUser(array $data, string $user_id): string
  {
    $sql = "INSERT INTO worlds (user_id, name, image_url, shared, share_id)
            VALUES (:user_id, :name, :image_url, :shared, :share_id)";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
    $stmt->bindValue(":image_url", $data["image_url"], PDO::PARAM_INT);
    if (empty($data["shared"])) {
      $stmt->bindValue(":shared", false, PDO::PARAM_BOOL);
    } else {
      $stmt->bindValue(":shared", $data["shared"], PDO::PARAM_BOOL);
    }

    do {
      $share_id = rand(10000, 99999);
    } while ($this->checkForShareID($share_id));

    $stmt->bindValue(":share_id", $share_id, PDO::PARAM_INT);

    $stmt->execute();

    return $this->conn->lastInsertId();
  }

  public function updateForUser(string $id, array $data, string $user_id): int
  {
    $fields = [];

    if (!empty($data["name"])) {
      $fields["name"] = [
        $data["name"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["image_url"])) {
      $fields["image_url"] = [
        $data["image_url"],
        PDO::PARAM_STR
      ];
    }

    if (array_key_exists("shared", $data)) {
      $fields["shared"] = [
        $data["shared"],
        PDO::PARAM_BOOL
      ];
    }

    if (empty($fields)) {
      return 0;
    } else {
      $sets = array_map(function ($value) {
        return "$value = :$value";
      }, array_keys($fields));

      $sql = "UPDATE worlds"
        . " SET " . implode(", ", $sets)
        . " WHERE id = :id"
        . " AND user_id = :user_id";

      $stmt = $this->conn->prepare($sql);

      $stmt->bindValue(":id", $id, PDO::PARAM_INT);
      $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

      foreach ($fields as $name => $values) {
        $stmt->bindValue(":$name", $values[0], $values[1]);
      }

      $stmt->execute();

      return $stmt->rowCount();
    }
  }

  public function deleteForUser(string $id, int $user_id): int
  {
    $sql = "DELETE FROM worlds
            WHERE id = :id
            AND user_id = :user_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->rowCount();
  }
}
