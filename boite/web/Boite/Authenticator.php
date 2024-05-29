<?php
namespace Boite;

use JetBrains\PhpStorm\NoReturn;

class Authenticator
{
  private User $user;
  private SessionManager $sessionManager;
  private Logger $logger;
  private CSRFProtector $csrfProtector;

  public function __construct($db, $logger, $sessionManager, $csrfProtector) {
    $this->user = new User($db);
    $this->sessionManager = $sessionManager;
    $this->logger = $logger;
    $this->csrfProtector = $csrfProtector;
  }

  private function exitWithError($s): never
  {
    http_response_code(400);
    echo "$s\n";
    exit;
  }

  // Méthode pour la connexion de l'utilisateur
  public function login(): never {
    try {
      $this->csrfProtector->validateRequest();
    } catch (\Exception $e) {
      // Simulate wrong username/password if the token does not validate.
      $result = [
        'message' => "Erreur du nom d'usager et/ou du mot de passe. Essayez à nouveau avec les bons identifiants de connexion.",
        'redirect' => '/',
      ];
      $this->logger->logDebug("Authenticator::login(), j = " . print_r($result, TRUE));
      header('Content-type: application/json');
      echo json_encode($result);
      exit;
    }
    $this->user->username = htmlspecialchars(strip_tags($_POST['username']));
    $this->user->password = htmlspecialchars(strip_tags($_POST['password']));

    if ($this->user->login()) {
      if ($this->user->webauthnkeys !== '[]') {
        // Ask the user to validate with his key.
        $webauthn = new \Davidearl\WebAuthn\WebAuthn($_SERVER['HTTP_HOST']);
        $result = ['challenge' => $webauthn->prepareForLogin($this->user->webauthnkeys)];
        $this->sessionManager->setSessionData('webauthn_challenge_user_id', $this->user->id);
      }
      else {
        // User without the 2FA key, so logged in.
        $this->sessionManager->setSessionData('user_id', $this->user->id);
        $this->sessionManager->setSessionData('username', $this->user->username);
        $result = [
          'message' => "Bienvenue dans la boîte!",
          'redirect' => '/',
        ];
      }
    }
    else {
      $result = [
        'message' => "Erreur du nom d'usager et/ou du mot de passe. Essayez à nouveau avec les bons identifiants de connexion.",
        'redirect' => '/',
      ];
    }
    $this->logger->logDebug("Authenticator::login(), j = " . print_r($result, TRUE));
    header('Content-type: application/json');
    echo json_encode($result);
    exit;
  }

  public function loginStep2(): never {
    if (!$this->sessionManager->getSessionData('webauthn_challenge_user_id')) {
      $this->exitWithError('webauthn_challenge_user_id not set');
    }
    $this->user->getUserById($this->sessionManager->getSessionData('webauthn_challenge_user_id'));
    $this->sessionManager->setSessionData('user_id', $this->user->id);
    $this->sessionManager->setSessionData('username', $this->user->username);
    $result = [
      'message' => "Bienvenue dans la boîte!",
      'redirect' => '/',
    ];
    $this->logger->logDebug("Authenticator::login(), j = " . print_r($result, TRUE));
    header('Content-type: application/json');
    echo json_encode($result);
    exit;
  }

  // Méthode pour la déconnexion de l'utilisateur
  public function logout()
  {
    $this->sessionManager->destroySession();
  }

  // Méthode pour vérifier si l'utilisateur est connecté
  public function isAuthenticated()
  {
    return $this->sessionManager->isSessionKeySet('user_id');
  }

  // Méthode pour gérer l'authentification à deux facteurs (2FA) avec YubiKey
  public function verifyTwoFactorAuthentication($yubikeyCode)
  {
    // Ici, intégrez la logique de validation du code YubiKey
    // Vous devez faire appel à un service externe ou à une API pour valider le code YubiKey

    // Retourner true si le code est valide, sinon false
  }

  // ... Autres méthodes utiles pour la gestion de l'authentification
}
