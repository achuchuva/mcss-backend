<?php

class UserGateway
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

  // Get a user based on the api key
  public function getByAPIKey(string $key): array | false
  {
    $sql = "SELECT *
            FROM users
            WHERE api_key = :api_key";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":api_key", $key, PDO::PARAM_STR);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Get a user based on the email
  public function getByEmail(string $email): array | false
  {
    $sql = "SELECT *
            FROM users
            WHERE email = :email";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":email", $email, PDO::PARAM_STR);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Get a user based on the id
  public function getByID(int $id): array | false
  {
    $sql = "SELECT *
            FROM users
            WHERE id = :id";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
