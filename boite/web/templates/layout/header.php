<!DOCTYPE html>
<html>
<head>
  <title>Boîte</title>
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link rel="stylesheet" type="text/css" href="css/webauthn.css">
  <script src='https://code.jquery.com/jquery-3.3.1.min.js'></script>
  <script src='js/messages.js'></script>
  <?php
  global$jsIncludes;
  // optionally include js files.
  if (!is_null($jsIncludes)) {
    foreach ($jsIncludes as $jsInclude) {
      echo "<script src='$jsInclude'></script>";
    }
  }
  ?>
</head>
<body>
<div id="messages">
</div>
<div id="errors">
</div>
<header>
  <nav>
    <!-- Liens de navigation -->
  </nav>
</header>
