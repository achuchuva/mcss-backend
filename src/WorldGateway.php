<?php

class WorldGateway
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

  // Get all worlds for given user
  public function getAllForUser(int $user_id): array | false
  {
    $sql = "SELECT *
            FROM worlds
            WHERE user_id = :user_id
            OR id in (SELECT world_id FROM world_access WHERE user_id = :user_id2) -- Get worlds that the user has also joined
            ORDER BY name";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id2", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    $data = [];

    // Booleans are stored as ints in the database, convert them back
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $row['shared'] = (bool) $row['shared'];

      $data[] = $row;
    }

    return $data;
  }

  // Get a single world based on id and the user_id
  public function getForUser(string $id, int $user_id): array | false
  {
    $sql = "SELECT * 
            FROM worlds
            WHERE id = :id
            AND (user_id = :user_id
            OR id in (SELECT world_id FROM world_access WHERE user_id = :user_id2))";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id2", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Booleans are stored as ints in the database, convert them back
    if ($data !== false) {
      $data['shared'] = (bool) $data['shared'];
    }

    return $data;
  }

  // Get or find a world using it's share_id
  public function findByShareID($share_id): array | false
  {
    $sql = "SELECT * 
            FROM worlds
            WHERE share_id = :share_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":share_id", $share_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Join shared world using a share_id
  public function joinSharedWorld(string $share_id, int $user_id): int
  {
    // Table world_access specifies world_id's that can be accessed by certain user_id's
    $sql = "INSERT INTO world_access (world_id, user_id)
    VALUES (:world_id, :user_id)";

    $world_id = $this->findByShareID($share_id)["id"];

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    return $this->conn->lastInsertId();
  }

  // Get or find any matching rows in the world_access table via world_id and user_id
  public function findWorldAccess(int $world_id, int $user_id): array | false
  {
    $sql = "SELECT *
            FROM world_access
            WHERE user_id = :user_id
            AND world_id = :world_id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":world_id", $world_id, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Create a new world for a given user with passed in data
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

    // Generate a random, unique, 5 integer long share id
    do {
      $share_id = rand(10000, 99999);
    } while ($this->findByShareID($share_id));

    $stmt->bindValue(":share_id", $share_id, PDO::PARAM_INT);

    $stmt->execute();

    return $this->conn->lastInsertId();
  }

  // Update world for a given user
  public function updateForUser(string $id, array $data, string $user_id): int
  {
    $fields = [];

    // Check which fields are present and need to be updated
    if (!empty($data["name"])) {
      $fields["name"] = [
        $data["name"],
        PDO::PARAM_STR
      ];
    }

    if (!empty($data["image_base64"])) {
      $fields["image_url"] = [
        $this->decodeImage($id, $data),
        PDO::PARAM_STR
      ];
    }

    if (array_key_exists("shared", $data)) {
      $fields["shared"] = [
        $data["shared"],
        PDO::PARAM_BOOL
      ];
    }

    // Check if fields are empty, if they are return that zero rows were affected
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

      // Return the amount of rows that were affected
      return $stmt->rowCount();
    }
  }

  // Decode the image and save to the database
  private function decodeImage(string $id, array $data): string
  {
    $image = base64_decode($data["image_base64"]);
    $image_url = $id . ".png";
    file_put_contents("../world_images/" . $image_url, $image);
    return $image_url;
  }

  // Delete a world based on id and user_id
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
