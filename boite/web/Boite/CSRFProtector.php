<?php
namespace Boite;

class CSRFProtector
{
  // Générer un token CSRF et le stocker dans la session
  /**
   * @throws \Exception
   */
  public function generateToken(): string
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
  }

  // Vérifier le token CSRF envoyé avec la requête par rapport à celui stocké dans la session
  public function verifyToken($token): bool
  {
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
      return true;
    }
    return false;
  }

  // Fonction pour inclure un champ de token CSRF dans un formulaire

  /**
   * @throws \Exception
   */
  public function csrfField(): void
  {
    $token = $this->generateToken();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
  }

  // Fonction pour valider le token CSRF lors de la soumission du formulaire

  /**
   * @throws \Exception
   */
  public function validateRequest(): void
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $token = $_POST['csrf_token'] ?? '';
      if (!$this->verifyToken($token)) {
        // Gérer l'échec de la vérification du token ici
        throw new \Exception("Invalid CSRF token.");
      }
    }
  }
}
