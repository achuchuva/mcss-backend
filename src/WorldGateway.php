<?php

class WorldGateway
{
  private PDO $conn;
  public function __construct(Database $database)
  {
    $this->conn = $database->getConnection();
  }

  public function getWorlds(int $user_id): array | false
  {
    $sql = "SELECT * 
            FROM worlds";

    $stmt = $this->conn->prepare($sql);

    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
