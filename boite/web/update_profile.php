<?php

use src\User;

require_once '../bootstrap.php';  // Assurez-vous d'inclure toutes les classes et configurations nécessaires

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
  // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
  header("Location: login.php");
  exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Récupérer et nettoyer les données soumises
  $email = cleanInput($_POST['email']);
  $newPassword = cleanInput($_POST['password']);
  // ... autres champs si nécessaire

  // Valider les données (ex : vérifier si l'email est valide, etc.)
  // TODO: Ajouter des validations ici

  // Mise à jour de l'utilisateur dans la base de données
  $user = new User($db);
  $user->id = $userId;

  try {
    if (!empty($newPassword)) {
      $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
    }
    if (!empty($email)) {
      $user->email = $email;
    }

    if ($user->update()) {
      $success = 'Profil mis à jour avec succès.';
    } else {
      throw new Exception('Une erreur est survenue lors de la mise à jour.');
    }
  } catch (Exception $e) {
    $error = $e->getMessage();
  }
}

// Inclure le template pour le profil
include 'templates/profile.php';
?>
