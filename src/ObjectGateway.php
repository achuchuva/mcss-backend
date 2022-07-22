<?php

class ObjectGateway
{
  private PDO $conn;
  public function __construct(Database $database)
  {
    $this->conn = $database->getConnection();
  }

  // All functions follow a similar structure
  // A sql statement is constructed as a string
  // The statment is prepared via the database connection
  // Any values that are passed as parameters are binded to the statement
  // The statement is executed and returns either an associative array or false
  // For non-fetching statments, either ID or amount of rows affected is returned

  // Get all the objects for a given user based on destination_id
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

  // Get a single object for a user based on id and destination_id
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

  // Create a single object for a given user with a specified name
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

  // Update a single object for a given user
  public function updateForUser(string $id, array $data, string $destination_id): int
  {
    $fields = [];

    // Check which fields are present and need to be updated
    if (!empty($data["name"])) {
      $fields["name"] = [
        $data["name"],
        PDO::PARAM_STR
      ];
    }

    // Check if fields are empty, if they are return that zero rows were affected
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

      // Return the amount of rows that were affected
      return $stmt->rowCount();
    }
  }

  // Delete a single object for a given user
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

  // Delete all objects for a given user
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
