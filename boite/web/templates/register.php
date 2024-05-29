<?php
global $jsIncludes;
global $config;
// Declare js files to be added to the page by the header template.
$jsIncludes = [
  'js/webauthnregister.js',
  'js/webauthnsubmit.js',
  'js/register_tooltip.js'
];
include 'layout/header.php';
global $csrfProtector;
?>

<main>
  <h2>Inscription</h2>

  <!-- Display error or success message if need be. -->
  <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
  <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

  <div class='cerror'></div>
  <div class='cdone'></div>

  <div class='ccontent'>

  <!- Form will submit in AJAX mode. ->
  <div class='cbox' id='iregister'>
    <form id='iregisterform' action='/' method='POST'>
      <div>
        <label for="username">Nom d'utilisateur:</label>
        <input type="text" id="username" name="username" required
title="Instructions pour le nom d'usager :
  Doit contenir au maximum <?php echo $config['maxUsername']?> caractères.
  Ne doit pas contenir les caractères spéciaux suivants: &, ', &quot;, &lt;, &gt;."
        >

      </div>

      <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div>
        <label for="password">Mot de passe:</label>
        <input type="password" id="password" name="password" required
title="Instructions pour le mot de passe :
  Doit contenir au moins <?php echo $config['minPasswordLength']?> caractères.
  Doit contenir au maximum <?php echo $config['maxPasswordLength']?> caractères.
  Inclure au moins une lettre minuscule (a-z).
  Inclure au moins une lettre majuscule (A-Z).
  Inclure au moins un chiffre (0-9).
  Inclure au moins un caractère spécial (ex: !, @, #, $, etc).
  Ne doit pas contenir les caractères spéciaux suivants: &, ', &quot;, &lt;, &gt;."
        >
      </div>

      <div>
        <label for="webauthn">Je veux utiliser ma clé Webauthn (Yubikey ou autre)&nbsp;:</label>
        <input type="checkbox" id="webauthn" name="webauthn">
      </div>
      <?php $csrfProtector->csrfField() ?>

      <div>
        <button type="submit">S'inscrire</button>
      </div>
    </form>
    <div class='cdokey' id='iregisterdokey'>
      Do your thing: press button on key, swipe fingerprint or whatever
    </div>
  </div>
</main>

<?php include 'layout/footer.php'; ?>
