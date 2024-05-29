<?php
namespace Boite;

require_once 'User.php';
require_once 'SessionManager.php';

class Registrar
{
  private User $user;
  private \PDO $db;
  private Logger $logger;
  private CSRFProtector $csrfProtector;
  private SessionManager $sessionManager;
  const VALIDATION_OK = "Les données sont valides.";

  // Constructor to initialize the Registrar object
  public function __construct($db, $logger, $csrfProtector, $sessionManager)
  {
    $this->user = new User($db);
    $this->db = $db;
    $this->logger = $logger;
    $this->csrfProtector = $csrfProtector;
    $this->sessionManager = $sessionManager;
  }

  // Helper function to exit script with an error message
  private function exitWithError($s): never
  {
    http_response_code(400);
    echo "$s\n";
    exit;
  }

  // Check if the provided password is strong
  private function validateInputData(string $username, string $password): string {
    global $config;

    /* Check presence of & ' " < >characters in username and password */
    // Regular expression to match & ' " < >
    $pattern = '/[&\'"<>]/';
    // Check forbidden characters for username
    if (preg_match_all($pattern, $username, $matches)) {
      return "Le nom d'usager contient un ou plusieurs caractères interdits&nbsp;: " .  implode(', ', array_unique($matches[0])) . ".";
    }
    // Check forbidden characters for password
    if (preg_match_all($pattern, $password, $matches)) {
      return "Le mot de passe contient un ou plusieurs caractères interdits&nbsp;: " .  implode(', ', array_unique($matches[0])) . ".";
    }

    // Check if username is below the maximum of characters
    if (strlen($username) > $config['maxUsername']) {
      return "Le nom d'usager doit contenir au maximum " . $config['maxUsername'] . " caractères.";
    }

    // Check if the password length is minPasswordLength or more characters
    if (strlen($password) < $config['minPasswordLength']) {
      return "Le mot de passe doit contenir au moins " . $config['minPasswordLength'] . " caractères.";
    }

    // Check if the password length is less than or equal to maxPasswordLength
    if (strlen($password) > $config['maxPasswordLength']) {
      return "Le mot de passe doit contenir au maximum " . $config['maxPasswordLength'] . " caractères.";
    }

    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
      return "Le mot de passe doit inclure au moins une lettre minuscule.";
    }

    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
      return "Le mot de passe doit inclure au moins une lettre majuscule.";
    }

    // Check for at least one digit
    if (!preg_match('/\d/', $password)) {
      return "Le mot de passe doit contenir au moins un chiffre.";
    }

    // Check for at least one special character
    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
      return "Le mot de passe doit contenir au moins un caractère spécial.";
    }

    // If all conditions are met
    return self::VALIDATION_OK;
  }

  // Register a new user by AJAX call.
  public function register(): never
  {
    // Cleanup input data
    // Only email is trimmed as to make sure that the typed username and password will be in the DB so that the user
    // that is using a password manager like LastPass will have the same data in our DB and in the password manager.
    $username = $_POST['username'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $webauthn = $_POST['webauthn'];

    // Validate password strength
    $validateInputDataMessage = $this->validateInputData($username, $password);
    if ($validateInputDataMessage !== self::VALIDATION_OK) {
      $response = [
        'message' => $validateInputDataMessage,
      ];
      $this->logger->logDebug("Authenticator::login(), j = " . print_r($response, TRUE));
      header('Content-type: application/json');
      echo json_encode($response);
      exit;
    }

    // CSRF protection validation
    try {
      $this->csrfProtector->validateRequest();
    } catch (\Exception $e) {
      $response = [
        'message' => "Erreur inconnue.",
      ];
      $this->logger->logDebug("Authenticator::login(), j = " . print_r($response, TRUE));
      header('Content-type: application/json');
      echo json_encode($response);
      exit;
    }

    // Check if the user already exists
    $this->user = new User($this->db);
    if ($this->user->exists($username, $email)) {
      $this->logger->logInfo("Registrar::register(), User already exists.");
      $this->exitWithError('Un utilisateur avec ce nom ou cet email existe déjà.');
    } else {
      // Create a new user if validations are passed
      $this->user->username = $username;
      $this->user->email = $email;
      $this->user->password = $password;

      if ($this->user->create()) {
        $this->logger->logDebug("Registrar: user created : " . $this->user->id . '.');
        if ($webauthn === "true") {
          $this->logger->logDebug("Registrar: 2FA active.");
          // Handle WebAuthn registration if selected
          $webauthn = new \Davidearl\WebAuthn\WebAuthn($_SERVER['HTTP_HOST']);
          $challenge = $webauthn->prepareChallengeForRegistration($username, $this->user->webauthnId, TRUE);
          $this->sessionManager->setSessionData('webauthn_challenge_user_id', $this->user->id);
          $response = ['challenge' => $challenge];
        } else {
          // Mark user as fully registered if WebAuthn is not used
          $this->user->status = TRUE;
          $this->user->update();
          $response = [
            'message' => "Bravo, votre compte a bien été créé.",
            'redirect' => '/',
          ];
        }
        header('Content-type: application/json');
        echo json_encode($response);
        exit;
      } else {
        // Error creating the user.
        $this->exitWithError('Une erreur est survenue lors de la création de l\'utilisateur.');
      }
    }
  }

  // Register a new user - step 2 of the AJAX call
  public function registerStep2(): never {
    // Ensure that a user session is established
    $this->user = new User($this->db);
    if (empty($this->sessionManager->getSessionData('webauthn_challenge_user_id'))) {
      $this->exitWithError('userId not set');
    }

    // Retrieve and update user information
    $this->user->getUserById($this->sessionManager->getSessionData('webauthn_challenge_user_id'));
    $webauthn = new \Davidearl\WebAuthn\WebAuthn($_SERVER['HTTP_HOST']);
    $this->sessionManager->setSessionData('user_id', $this->user->id);

    // Register and update WebAuthn keys
    $this->user->webauthnkeys = $webauthn->register($_POST['register'], $this->user->webauthnkeys);
    $this->user->status = TRUE;
    $this->user->update();

    // Respond with a success message
    $response = [
      'message' => "Bravo, votre compte a bien été créé.",
      'redirect' => '/',
    ];

    header('Content-type: application/json');
    echo json_encode($response);
    exit;
  }
}
