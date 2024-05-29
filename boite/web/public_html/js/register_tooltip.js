$(document).ready(function() {
  $("#username").tooltip({
    content: "Instructions pour le nom d'usager:" +
      "<ul>" +
      "<li>Doit contenir au maximum 49 caractères.</li>" +
      "<li>Ne doit pas contenir les caractères spéciaux suivants: &amp;, ', \", &lt;, &gt;.</li>" +
      "</ul>",
    position: { my: "left+15 center", at: "right center" }
  });

  $("#password").tooltip({
    content: "Instructions pour le mot de passe:<ul>" +
      "<li>Doit contenir au moins 10 caractères.</li>" +
      "<li>Ne doit pas dépasser 254 caractères.</li>" +
      "<li>Inclure au moins une lettre minuscule (a-z).</li>" +
      "<li>Inclure au moins une lettre majuscule (A-Z).</li>" +
      "<li>Inclure au moins un chiffre (0-9).</li>" +
      "<li>Inclure au moins un caractère spécial (ex: !, @, #, $, etc).</li>" +
      "<li>Ne doit pas contenir les caractères suivants: &amp;, ', \", &lt;, &gt;.</li></ul>",
    position: { my: "left+15 center", at: "right center" }
  });
});
