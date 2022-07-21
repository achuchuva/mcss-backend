<?php

class ObjectGateway
{
  private PDO $conn;
  public function __construct(Database $database)
  {
    $this->conn = $database->getConnection();
  }

  public function getAllForUser(int $destination_id): array | false
  {
    $sql = "SELECT *
            FROM objects
            WHERE destination_id = :destination_id
            ORDER BY name";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getForUser(string $id, int $destination_id): array | false
  {
    $sql = "SELECT * 
            FROM objects
            WHERE id = :id
            AND destination_id = :destination_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function createForUser(string $name, string $destination_id): string
  {
    $sql = "INSERT INTO objects (destination_id, name)
            VALUES (:destination_id, :name)";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);
    $stmt->bindValue(":name", $name, PDO::PARAM_STR);

    $stmt->execute();

    return $this->conn->lastInsertId();
  }

  public function updateForUser(string $id, array $data, string $destination_id): int
  {
    $fields = [];

    if (!empty($data["name"])) {
      $fields["name"] = [
        $data["name"],
        PDO::PARAM_STR
      ];
    }

    if (empty($fields)) {
      return 0;
    } else {
      $sets = array_map(function ($value) {
        return "$value = :$value";
      }, array_keys($fields));

      $sql = "UPDATE objects"
        . " SET " . implode(", ", $sets)
        . " WHERE id = :id"
        . " AND destination_id = :destination_id";

      $stmt = $this->conn->prepare($sql);

      $stmt->bindValue(":id", $id, PDO::PARAM_INT);
      $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);

      foreach ($fields as $name => $values) {
        $stmt->bindValue(":$name", $values[0], $values[1]);
      }

      $stmt->execute();

      return $stmt->rowCount();
    }
  }

  public function deleteForUser(string $id, int $destination_id): int
  {
    $sql = "DELETE FROM objects
            WHERE id = :id
            AND destination_id = :destination_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->rowCount();
  }

  public function deleteAllForUser(int $destination_id): int
  {
    $sql = "DELETE FROM objects
            WHERE destination_id = :destination_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":destination_id", $destination_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->rowCount();
  }
}
