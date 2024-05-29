<?php include 'layout/header.php'; ?>

<main>
  <h2>Profil de l'Utilisateur</h2>

  <!-- Display error or success message if need be. -->
  <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
  <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

  <div class="profile-info">
    <h3>Informations Personnelles</h3>
    <p><strong>Nom d'utilisateur&nbsp;: </strong> <?= $user->username ?></p>
    <p><strong>Email&nbsp;: </strong> <?= $user->email ?></p>
    <p><strong>Mode 2FA&nbsp;: </strong> <?= $user->webauthnkeys !== '[]' ? 'On' : 'Off' ?></p>
    <!-- Autres informations de l'utilisateur -->
  </div>

  <!-- Formulaire pour mettre à jour les informations du profil -->
  <div class="profile-update-form">
    <h3>Mettre à Jour le Profil</h3>
    <form action="update_profile.php" method="post">
      <div>
        <label for="email">Nouvel Email:</label>
        <input type="email" id="email" name="email" value="<?= $user->email ?>">
      </div>
      <div>
        <label for="password">Nouveau Mot de Passe:</label>
        <input type="password" id="password" name="password">
      </div>
      <!-- Ajoutez d'autres champs si nécessaire -->
      <div>
        <button type="submit">Mettre à Jour</button>
      </div>
    </form>
    <div>
      <a href="/?page=logout">Déconnexion</a>
    </div>
  </div>
</main>

<?php include 'layout/footer.php'; ?>
