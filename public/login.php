<?php
require_once('../env.php');
require_once('../api/ApiRequest.php');

$apiRequest = new ApiRequest();

// When we get the code, we pass it to our ApiRequest object so it can handle the rest
if (isset($_GET['code'])) {
    try {
        $apiRequest->getAccessToken($_GET['code']);
    } catch (Exception $e) {
        echo $e->getMessage() . '<br />';
        return;
    }

    echo 'Succesfully linked!';
    return;
}

// Define all the scopes we're going to need
$scopes = 'user-read-recently-played user-read-currently-playing';

// Encode the parameters we need to pass on
$escapedScopes = urlencode($scopes);
$escapedRedirectUri = urlencode($redirectUri);

// The URL to start the OAuth procedure
$authorizeUrl = "https://accounts.spotify.com/authorize?response_type=code&client_id=$clientId&scope=$escapedScopes&redirect_uri=$escapedRedirectUri";
?>
<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <a href="<?=$authorizeUrl?>">Login with Spotify</a>
</body>
</html>