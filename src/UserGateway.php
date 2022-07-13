<?php

class UserGateway
{
  private PDO $conn;
  public function __construct(Database $database)
  {
    $this->conn = $database->getConnection();
  }

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
