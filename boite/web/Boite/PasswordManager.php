<?php
namespace Boite;

class PasswordManager
{

  // Hacher un mot de passe avant de le stocker dans la base de données
  public function hashPassword($password)
  {
    return password_hash($password, PASSWORD_BCRYPT);
  }

  // Vérifier un mot de passe soumis avec un hachage stocké dans la base de données
  public function verifyPassword($inputPassword, $storedHash)
  {
    return password_verify($inputPassword, $storedHash);
  }

  // Générer un sel aléatoire (si nécessaire pour une méthode de hachage personnalisée)
  // Note : Avec PASSWORD_BCRYPT, le sel est automatiquement généré et inclus dans le hachage
  public function generateSalt()
  {
    return bin2hex(random_bytes(32));
  }

  // Vous pouvez ajouter d'autres fonctions liées à la sécurité des mots de passe,
  // comme la vérification de la complexité du mot de passe, ici.

  // Exemple : Vérifier la complexité du mot de passe
  public function isPasswordStrong($password)
  {
    // Vérifier la longueur, la présence de chiffres, de lettres majuscules et minuscules, de caractères spéciaux, etc.
    // Retourner true si le mot de passe est jugé fort, sinon false
  }
}
