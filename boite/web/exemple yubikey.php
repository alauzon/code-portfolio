<script type="application/javascript">
<?php
echo file_get_contents(dirname(__DIR__).'/src/webauthnregister.js');
echo file_get_contents(dirname(__DIR__).'/src/webauthnauthenticate.js');
?>

</script>
<?php
$webauthn = new \Davidearl\WebAuthn\WebAuthn($_SERVER['HTTP_HOST']);

/* initiate the registration */
$username = $_POST['registerusername'];
$crossplatform = ! empty($_POST['crossplatform']) && $_POST['crossplatform'] == 'Yes';
$userid = md5(time() . '-'. rand(1,1000000000));

if (file_exists(userpath($username))) {
oops("user '{$username}' already exists");
}

/* Create a new user in the database. In principle, you can store more
 than one key in the user's webauthnkeys,
 but you'd probably do that from a user profile page rather than initial
 registration. The procedure is the same, just don't cancel existing
 keys like this.*/
$user = (object)['name'=> $username,
               'id'=> $userid,
               'webauthnkeys' => $webauthn->cancel()];
saveuser($user);
$_SESSION['username'] = $username;
$j = ['challenge' => $webauthn->prepareChallengeForRegistration($username, $userid, $crossplatform)];

header('Content-type: application/json');
echo json_encode($j);
exit;

// Au retour de l'authentification en JS
if (empty($_SESSION['username'])) { oops('username not set'); }
$user = getuser($_SESSION['username']);

/* The heart of the matter */
$user->webauthnkeys = $webauthn->register($_POST['register'], $user->webauthnkeys);

/* Save the result to enable a challenge to be raised agains this
 newly created key in order to log in */
saveuser($user);
$j = 'ok';
header('Content-type: application/json');
echo json_encode($j);
exit;

// ================================================================================

$j == "{
  "publicKey": {
    "challenge": [
      226,
      195,
      182,
      231,
      31,
      13,
      178,
      120,
      52,
      154,
      187,
      117,
      99,
      6,
      202,
      116
    ],
    "user": {
      "displayName": "alauzon@alainlauzon.com",
      "name": "alauzon@alainlauzon.com",
      "id": [
        53,
        55,
        54,
        101,
        97,
        50,
        53,
        99,
        97,
        48,
        57,
        97,
        102,
        56,
        48,
        50,
        99,
        48,
        54,
        57,
        55,
        97,
        98,
        56,
        56,
        56,
        50,
        100,
        97,
        50,
        56,
        57
      ]
    },
    "rp": {
      "id": "boite.lndo.site",
      "name": "boite.lndo.site"
    },
    "pubKeyCredParams": [
      {
        "alg": -7,
        "type": "public-key"
      },
      {
        "alg": -257,
        "type": "public-key"
      }
    ],
    "authenticatorSelection": {
      "authenticatorAttachment": "cross-platform",
      "requireResidentKey": false,
      "userVerification": "discouraged"
    },
    "attestation": null,
    "timeout": 60000,
    "excludeCredentials": [],
    "extensions": {
      "exts": true
    }
  },
  "b64challenge": "4sO25x8Nsng0mrt1YwbKdA"
}"
