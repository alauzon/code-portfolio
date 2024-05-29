<?php
global $jsIncludes;
// Declare js files to be added to the page by the header template.
$jsIncludes = [
  'js/webauthnauthenticate.js',
  'js/webauthnsubmit.js',
];
include 'layout/header.php';
global $csrfProtector;
?>
<main>
  <h2>Connexion</h2>
  <form id="iloginform" method="post" action="/">
    <label for="username">Nom d'utilisateur:</label>
    <input type="text" id="username" name="username" required>
    <label for="password">Mot de passe:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <?php $csrfProtector->csrfField() ?>
    <input type="submit" value="Connexion">
  </form>

  <h2>Je veux m'inscrire : </h2>
  <a href="/?page=inscription">Inscription</a>
</main>
<?php include 'layout/footer.php'; ?>
