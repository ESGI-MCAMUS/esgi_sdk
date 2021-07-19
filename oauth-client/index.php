<?php
session_start();
const CLIENT_ID = "client_60ef10742d903.05413444";
const CLIENT_FBID = "511719920161154";
const CLIENT_SECRET = "cd989e9a4b572963e23fe39dc14c22bbceda0e60";
const CLIENT_FBSECRET = "8d05e97f5befa64142446c05315fe663";

const CLIENT_TWITCHID = "jpcyy5xfenwlhi74bqww8c3bdcpell";
const CLIENT_TWITCHSECRET = "sqaoey36f56pd13bh1sdzeis37m4nc";

const STATE = "fdzefzefze";
function handleLogin() {
    echo "<h1>Login with OAUTH</h1>";
    // Server OAuth
    echo "<a href='http://localhost:8081/auth?response_type=code"
        . "&client_id=" . CLIENT_ID
        . "&scope=basic"
        . "&state=" . STATE . "'>Se connecter avec Oauth Server</a><br>";
    // Facebook OAuth
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . STATE
        . "&redirect_uri=https://localhost/fbauth-success'>Se connecter avec Facebook</a><br>";
    //TWITCH OAuth
    echo "<a href='https://id.twitch.tv/oauth2/authorize?client_id=" . CLIENT_TWITCHID . "&redirect_uri=https://localhost/twitch-auth-success&response_type=token'>Se connecter via Twitch</a><br>";
    // Google OAuth
    echo '<script src="https://apis.google.com/js/platform.js" async defer></script>'
        . '<meta name="google-signin-client_id" content="580135369036-ch72bhlqrv90v8jcnt4h6rdehblii0i3.apps.googleusercontent.com">'
        . '<div class="g-signin2" data-onsuccess="onSignIn"></div>'
        . '<script>function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            window.location.href = `https://localhost/google-auth-success?id=${profile.getId()}&email=${profile.getEmail()}&name=${profile.getName()}&image=${profile.getImageUrl()}`;
            }
        </script>';
}

function handleError() {
    ["state" => $state] = $_GET;
    echo "{$state} : Request cancelled";
}

function handleSuccess() {
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    // https://auth-server/token?grant_type=authorization_code&code=...&client_id=..&client_secret=...
    getUser([
        'grant_type' => "authorization_code",
        "code" => $code,
    ]);
}

function handleFbSuccess() {
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    // https://auth-server/token?grant_type=authorization_code&code=...&client_id=..&client_secret=...
    $url = "https://graph.facebook.com/oauth/access_token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_FBID . "&client_secret=" . CLIENT_FBSECRET . "&redirect_uri=https://localhost/fbauth-success";
    $result = file_get_contents($url);
    $resultDecoded = json_decode($result, true);
    ["access_token" => $token] = $resultDecoded;
    $userUrl = "https://graph.facebook.com/me?fields=id,name,email";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    echo file_get_contents($userUrl, false, $context);
}

function handleTwitchSuccess() {
    var_dump($_SERVER["REQUEST_URI"]);
}

function handleGoogleSuccess() {
    if (!isset($_GET["id"]) && !isset($_GET["email"]) && !isset($_GET["name"]) && !isset($_GET["image"])) {
        header('Location: /login');
    } else {
        $_SESSION["id"] = htmlspecialchars($_GET["id"]);
        $_SESSION["email"] = htmlspecialchars($_GET["email"]);
        $_SESSION["name"] = htmlspecialchars($_GET["name"]);
        $_SESSION["image"] = htmlspecialchars($_GET["image"]);
        $_SESSION["service"] = "google";
        var_dump($_SESSION);
        header('Location: /');
    }
}

function getUser($params) {
    $url = "http://oauth-server:8081/token?client_id=" . CLIENT_ID . "&client_secret=" . CLIENT_SECRET . "&" . http_build_query($params);
    $result = file_get_contents($url);
    $result = json_decode($result, true);
    $token = $result['access_token'];

    $apiUrl = "http://oauth-server:8081/me";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    echo file_get_contents($apiUrl, false, $context);
}

function accueil() {
    echo "<h1>OAuth ESGI - Intégration SDK</h1>";
    if (!isset($_SESSION["id"])) { // Non connecté
?>
<p>Vous n'êtes pas encore connecté ! Cliquez <a href="/login">ici</a> pour vous connecter</p>
<?php
    } else { // Connecté
        echo $_SESSION["image"] !== "" ? "<img src='" . $_SESSION["image"] . "' alt='user-img'/>" : "";
    ?>
<h2>Bonjour <?= $_SESSION["name"] ?> - <?= $_SESSION["id"] ?></h2>
<p>Vous êtes connecté via <?= $_SESSION["service"] ?> ! Cliquez <a
    href="/disconnect?service=<?= $_SESSION["service"] ?>">ici</a> pour vous déconnecter</p>
<p><i>Votre adresse email : <?= $_SESSION["email"] ?></i></p>
<?php
    }
    //var_dump($_SESSION);
}

function disconnect() {
    switch ($_GET["service"]) {
        case 'google':
            session_destroy();
        ?>
<script>
function signOut() {
  var auth2 = gapi.auth2.getAuthInstance();
  auth2.signOut().then(function() {
    console.log('User signed out.');
  });
}
</script>
<?php
            header('Location: /');
            break;

        default:
            # code...
            break;
    }
}
/**
 * AUTH CODE WORKFLOW
 * => Generate link (/login)
 * => Get Code (/auth-success)
 * => Exchange Code <> Token (/auth-success)
 * => Exchange Token <> User info (/auth-success)
 */
$route = strtok($_SERVER["REQUEST_URI"], "?");
switch ($route) {
    case '/':
        accueil();
        break;
    case '/login':
        handleLogin();
        break;
    case '/disconnect':
        disconnect();
        break;
    case '/auth-success':
        handleSuccess();
        break;
    case '/fbauth-success':
        handleFbSuccess();
        break;
    case '/google-auth-success':
        handleGoogleSuccess();
        break;
    case '/twitch-auth-success':
        handleTwitchSuccess();
        break;
    case '/auth-cancel':
        handleError();
        break;
    case '/password':
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            echo '<form method="POST">';
            echo '<input name="username">';
            echo '<input name="password">';
            echo '<input type="submit" value="Submit">';
            echo '</form>';
        } else {
            ["username" => $username, "password" => $password] = $_POST;
            getUser([
                'grant_type' => "password",
                "username" => $username,
                "password" => $password
            ]);
        }
        break;
    default:
        http_response_code(404);
        break;
}