<?php
namespace Boite;

class User
{
  private $db;
  private $table_name = "users";

  // Propriétés représentant les colonnes de la table
  public int $id;
  public string $webauthnId;
  public string $username;
  public string $password;
  public string $email;
  public string $webauthnkeys;
  public bool $status;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Créer un nouvel utilisateur
  public function create()
  {
    $query = "INSERT INTO " . $this->table_name . " SET webauthnId=:webauthnId, webauthnkeys=:webauthnkeys, username=:username, password=:password, email=:email";

    $stmt = $this->db->prepare($query);

    // Nettoyage des données
    $this->username = htmlspecialchars(strip_tags($this->username));
    $this->password = htmlspecialchars(strip_tags($this->password));
    $this->email = htmlspecialchars(strip_tags($this->email));

    // Hachage du mot de passe
    $this->password = password_hash($this->password, PASSWORD_BCRYPT);

    // Some default and calculated values.
    $this->webauthnId = md5(time() . '-'. rand(1,1000000000));
    $this->webauthnkeys = "[]";

    // Liaison des valeurs
    $stmt->bindParam(":webauthnId", $this->webauthnId);
    $stmt->bindParam(":webauthnkeys", $this->webauthnkeys);
    $stmt->bindParam(":username", $this->username);
    $stmt->bindParam(":password", $this->password);
    $stmt->bindParam(":email", $this->email);

    // Exécution de la requête
    if ($stmt->execute()) {
      $this->id = $this->db->lastInsertId();
      return true;
    }
    return false;
  }

  // Vérifier les informations d'identification pour la connexion
  public function login(): bool
  {
    $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";

    $stmt = $this->db->prepare($query);
    $this->username = htmlspecialchars(strip_tags($this->username));
    $stmt->bindParam(":username", $this->username);

    $stmt->execute();
    $num = $stmt->rowCount();

    if ($num > 0) {
      $row = $stmt->fetch(\PDO::FETCH_ASSOC);
      $this->id = $row['id'];
      $this->webauthnId = $row['webauthnId'];
      $this->webauthnkeys = $row['webauthnkeys'];

      // Utilisez une variable locale pour le mot de passe saisi.
      $inputPassword = $this->password;

      // Vérification du mot de passe
      if (password_verify($inputPassword, $row['password'])) {
        return true;
      }
    }
    return false;
  }

  // Mettre à jour les informations de l'utilisateur
  public function update(): bool
  {
    $query = "UPDATE " . $this->table_name . " SET webauthnId=:webauthnId, email=:email, webauthnkeys=:webauthnkeys, status=:status WHERE id = :id";

    $stmt = $this->db->prepare($query);

    $stmt->bindParam(':webauthnId', $this->webauthnId);
    $stmt->bindParam(':email', $this->email);
    $stmt->bindParam(':status', $this->status);
    $stmt->bindParam(':webauthnkeys', $this->webauthnkeys);
    $stmt->bindParam(':id', $this->id);

    if ($stmt->execute()) {
      return true;
    }
    return false;
  }

  // Méthode pour récupérer les informations d'un utilisateur
  public function getUserById($id)
  {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    $this->id = $result['id'];
    $this->webauthnId = $result['webauthnId'];
    $this->username = $result['username'];
    $this->password = $result['password'];
    $this->email = $result['email'];
    $this->webauthnkeys = $result['webauthnkeys'] ? $result['webauthnkeys'] : [];
    $this->status = $result['status'];
  }

  public function exists($username, $email)
  {
    $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username AND email = :email AND status = 1 LIMIT 0,1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);

    $stmt->execute();
    $num = $stmt->rowCount();

    if ($num > 0) {
      return true;
    }
    return false;
  }

  // ... Autres méthodes utiles (récupération des données, suppression, etc.)
}

?>
