<?php

use Boite\Authenticator;
use Boite\Registrar;
use Boite\User;

global $db;
global $logger;
global $templateRenderer;
global $sessionManager;
global $csrfProtector;

// Include required files
require_once '../bootstrap.php';

// Gestion des requêtes et navigation
$page = $_GET['page'] ?? 'login';

switch ($page) {
  case 'login':
    // Logic for the login page.
    if (!$sessionManager->isSessionKeySet('user_id')) {
      echo $templateRenderer->render('login.php', []);
    }
    else {
      $user = new User($db);
      $user->getUserById($sessionManager->getSessionData('user_id'));
      echo $templateRenderer->render('profile.php', ["user" => $user]);
    }
    break;
  case 'authentifier':
    // Logic for the authentication.
    if (!$sessionManager->validateSession()) {
      $authenticator = new Authenticator(
        $db,
        $logger,
        $sessionManager,
        $csrfProtector);
      $authenticator->login();
    }
    else {
      $user = new User($db);
      $user = $user->getUserById($sessionManager->getSessionData('user_id'));
      echo $templateRenderer->render('profile.php', ["user" => $user]);
    }
    break;
  case 'authentifierEtape2':
    // Logic for the authentication step 2.
    if (!$sessionManager->validateSession()) {
      $authenticator = new Authenticator(
        $db,
        $logger,
        $sessionManager,
        $csrfProtector);
      $authenticator->loginStep2();
    }
    else {
      $user = new User($db);
      $user = $user->getUserById($sessionManager->getSessionData('user_id'));
      echo $templateRenderer->render('profile.php', ["user" => $user]);
    }
    break;
  case 'logout':
    // Logout logic.
    if ($sessionManager->validateSession()) {
      $sessionManager->destroySession();
    }
    echo $templateRenderer->render('login.php', []);
    break;
  case 'profile':
    // Profile logic
    $user = new User($db);
    $user = $user->getUserById($sessionManager->getSessionData('user_id'));
    echo $templateRenderer->render('profile.php', ['user' => $user]);
    break;
  case 'inscription':
    // @todo check si déjà loggé alors faire afficher un message?
    echo $templateRenderer->render('register.php', []);
    break;
  case 'soumissionInscriptionEtape1':
    $registrar = new Registrar(
      $db,
      $logger,
      $csrfProtector,
      $sessionManager);
    $registrar->register();
    break;
  case 'soumissionInscriptionEtape2':
    $registrar = new Registrar(
      $db,
      $logger,
      $csrfProtector,
      $sessionManager);
    $registrar->registerStep2();
    break;

}

