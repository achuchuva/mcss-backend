<?php

class DestinationGateway
{
  private PDO $conn;
  public function __construct(private Database $database)
  {
    $this->conn = $database->getConnection();
  }

  public function getAllForUser(string $world_id): array | false
  {
    $sql = "SELECT *
            FROM destinations
            WHERE world_id = :world_id
            ORDER BY name";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getForUser(string $id, int $world_id): array | false
  {
    $sql = "SELECT * 
            FROM destinations
            WHERE id = :id
            AND world_id = :world_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getDestinationByID(int $id): array | false
  {
    $sql = "SELECT * 
            FROM destinations
            WHERE id = :id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function createForUser(array $data, string $world_id): string
  {

    $sql = "INSERT INTO destinations (world_id, realm, name, structure, coordinate_x, coordinate_y, coordinate_z, notes)
            VALUES (:world_id, :realm, :name, :structure, :coordinate_x, :coordinate_y, :coordinate_z, :notes)";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);
    $stmt->bindValue(":realm", $data["realm"], PDO::PARAM_STR);
    $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
    $stmt->bindValue(":structure", $data["structure"], PDO::PARAM_STR);
    $stmt->bindValue(":coordinate_x", $data["coordinate_x"], PDO::PARAM_STR);
    $stmt->bindValue(":coordinate_y", $data["coordinate_y"], PDO::PARAM_STR);
    $stmt->bindValue(":coordinate_z", $data["coordinate_z"], PDO::PARAM_STR);
    $stmt->bindValue(":notes", $data["notes"], PDO::PARAM_STR);

    $stmt->execute();

    $id = $this->conn->lastInsertId();

    if (!is_null($data["objects"])) {
      $object_gateway = new ObjectGateway($this->database);

      $object_gateway->deleteAllForUser($id);

      foreach ($data["objects"] as $object) {
        $object_gateway->createForUser($object, $id);
      }
    }

    return $id;
  }

  public function updateForUser(string $id, array $data, string $world_id): int
  {
    $fields = [];

    if (!empty($data["realm"])) {
      $fields["realm"] = [
        $data["realm"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["name"])) {
      $fields["name"] = [
        $data["name"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["structure"])) {
      $fields["structure"] = [
        $data["structure"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["coordinate_x"])) {
      $fields["coordinate_x"] = [
        $data["coordinate_x"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["coordinate_y"])) {
      $fields["coordinate_y"] = [
        $data["coordinate_y"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["coordinate_z"])) {
      $fields["coordinate_z"] = [
        $data["coordinate_z"],
        PDO::PARAM_STR
      ];
    }

    if (array_key_exists("notes", $data)) {
      $fields["notes"] = [
        $data["notes"],
        PDO::PARAM_STR
      ];
    }

    if (!is_null($data["objects"])) {
      $object_gateway = new ObjectGateway($this->database);

      $object_gateway->deleteAllForUser($id);

      foreach ($data["objects"] as $object) {
        $object_gateway->createForUser($object, $id);
      }
    }

    if (empty($fields)) {
      if (!is_null($data["objects"])) {
        return 1;
      } else {
        return 0;
      }
    } else {
      $sets = array_map(function ($value) {
        return "$value = :$value";
      }, array_keys($fields));

      $sql = "UPDATE destinations"
        . " SET " . implode(", ", $sets)
        . " WHERE id = :id"
        . " AND world_id = :world_id";

      $stmt = $this->conn->prepare($sql);

      $stmt->bindValue(":id", $id, PDO::PARAM_INT);
      $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);

      foreach ($fields as $name => $values) {
        $stmt->bindValue(":$name", $values[0], $values[1]);
      }

      $stmt->execute();

      return $stmt->rowCount();
    }
  }

  public function deleteForUser(string $id, int $world_id): int
  {
    $sql = "DELETE FROM destinations
            WHERE id = :id
            AND world_id = :world_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->rowCount();
  }
}
