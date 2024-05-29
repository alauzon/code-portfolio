<?php
namespace Boite;
class RateLimiter
{
  private $db;
  private $maxAttempts;
  private $lockoutTime;

  public function __construct($db, $maxAttempts = 5, $lockoutTime = 900)
  {
    $this->db = $db;
    $this->maxAttempts = $maxAttempts;  // Nombre maximal de tentatives
    $this->lockoutTime = $lockoutTime;  // Temps de blocage en secondes
  }

  // Vérifier si l'utilisateur a dépassé le nombre maximal de tentatives
  public function isRateLimited($userID)
  {
    $query = "SELECT COUNT(*) as attempts, MAX(timestamp) as last_attempt_time FROM login_attempts WHERE user_id = :user_id AND timestamp > NOW() - INTERVAL 15 MINUTE";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":user_id", $userID);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['attempts'] >= $this->maxAttempts) {
      $lastAttemptTime = strtotime($row['last_attempt_time']);
      $currentTime = time();

      if (($currentTime - $lastAttemptTime) < $this->lockoutTime) {
        return true;  // L'utilisateur est bloqué
      }
    }
    return false;  // L'utilisateur n'est pas bloqué
  }

  // Enregistrer une tentative de connexion
  public function recordAttempt($userID)
  {
    $query = "INSERT INTO login_attempts (user_id, timestamp) VALUES (:user_id, NOW())";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":user_id", $userID);
    $stmt->execute();
  }

  // Réinitialiser le compteur de tentatives pour un utilisateur
  public function resetAttempts($userID)
  {
    $query = "DELETE FROM login_attempts WHERE user_id = :user_id";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":user_id", $userID);
    $stmt->execute();
  }

  // ... Autres méthodes utiles pour la gestion des limites de taux
}
