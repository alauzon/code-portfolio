<?php
namespace Boite;
class SessionManager
{
  public function __construct()
  {
    // Sécuriser les cookies de session
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
      'lifetime' => $cookieParams["lifetime"],
      'path' => $cookieParams["path"],
      'domain' => $cookieParams["domain"],
      'secure' => true,
      'httponly' => true,
      'samesite' => "None"
    ]);
    session_start();
    session_regenerate_id(true);
  }

  // Démarrer une nouvelle session ou reprendre une existante
  public function startSession()
  {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
    session_regenerate_id(true); // Prévenir la fixation de session
  }

  // Stocker une information dans la session
  public function setSessionData($key, $value)
  {
    $_SESSION[$key] = $value;
  }

  // Obtenir une information de la session
  public function getSessionData($key)
  {
    return $_SESSION[$key] ?? null;
  }

  // Vérifier si la session a une certaine clé
  public function isSessionKeySet($key)
  {
    return isset($_SESSION[$key]);
  }

  // Détruire la session
  public function destroySession()
  {
    $_SESSION = array();
    session_destroy();
  }

  // Fonction pour valider une session existante
  // Cette méthode peut être utilisée pour vérifier si une session est toujours valide
  // lors d'une demande de l'utilisateur
  public function validateSession()
  {
    global $logger;
    if (!isset($_SESSION['user_id'])) {
      $logger->logDebug('Session is not valid.');
      return false;
    }
    $logger->logDebug('Session is valid,');
    return true;
  }

  // ... Autres méthodes utiles pour la gestion des sessions
}
