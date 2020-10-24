<?php
require_once('../env.php');
const DATA_FILE = __DIR__ . '/../data.json';

/**
 * The heart of our application, with this object we make all our API calls
 */
class ApiRequest {

    /** Our Spotify clientId */
    private $clientId;
    /** Our Spotify clientSecret */
    private $clientSecret;
    /** Our Spotify redirectUri as defined at Spotify itself */
    private $redirectUri;

    /** The accessToken of the user which will be used for all API calls */
    private $accessToken = null;
    /** The expirationDate in UnixTimeStamp of when this accessToken will expire */
    private $expirationDate = null;
    /** The refreshToken which will be used to grab a new accessToken when the previous one expired */
    private  $refreshToken = null;

    /**
     * If you don't provide any arguments it will automatically grab it from the env file.
     * 
     * Note: Currently doesn't work with multiple clientId and clientSecrets etc.
     * Neither doesn't it work with multiple accessTokens.
     * This is because all this information is stored in the exact same file and will overwrite
     * eachother.
     * 
     * @param clientId      Our spotify clientId
     * @param clientSecret  Our spotify clientSecret
     * @param redirectUri   The uri spotify redirects the user to after logging in 
     */
    function __construct($clientId = CLIENT_ID, $clientSecret = CLIENT_SECRET,  $redirectUri = REDIRECT_URI) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        
        $this->loadData();
    }

    /**
     * The heart of the class. Makes a request to the Spotify API. Returns the result.
     * 
     * @param uri       The Spotify API endpoint
     * @param param     an array of parameters. Will automatically be formatted.
     * @param method    The method to make the request with. Follows the REST api guidelines.
     * 
     * @return json     The raw string json data received from the API
     */
    function request($uri,$params = null, $method = 'GET') {
        // If one of the following three is null it means we haven't logged in yet, or something went wrong
        // So we have to login again
        if ($this->accessToken == null || $this->refreshToken == null || $this->expirationDate == null)
            throw new Exception("Please login with spotify!");

        // If our token has expired or expires within a minute we refresh it
        if ($this->expirationDate - 60 < time()) {
            $this->refreshToken();
        }
        
        // If we received any params we're going to format them and append it to the $uri
        if ($params != null && count($params) > 0) {
            $payload = [];
            foreach ($params as $key => $value) {
                // We fill the array with simple key=value strings
                $payload[] = urlencode($key) . '=' . urlencode($value);
            }
    
            // Then we combine it by joining them together with a &. Following URL formatting
            $payload = implode('&', $payload);

            // And then we append it to the $uri
            $uri = $uri . '?' . $payload;
        }


        // Our bearer token
        $headers = array('Authorization: Bearer ' . $this->accessToken);

        // Initialize CURL with our uri
        $ch = curl_init($uri);
        // The REST method like get or post
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
        // Our headers as defined above
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // If false it would directly output it to the browser, we don't want that. 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Grab the data
        $data = curl_exec($ch);
        // And close the CURL instance to free up server resources
        curl_close($ch);
        
        return $data;
    }


    /** 
     * Our initial request for the accessToken
     * You'll have to provide it with the token that spotify included when spotify redirects the user back.
     * 
     * @param token The 'code' provided by Spotify 
     */
    function getAccessToken($token) {
        $this->getToken("grant_type=authorization_code&code=$token&redirect_uri=" . urlencode($this->redirectUri));
    }


    /** Refreshes the token */
    private function refreshToken() {
        $this->getToken("grant_type=refresh_token&refresh_token=" . $this->refreshToken);
    }

    /** 
     * A function to retrieve the token. We just need to pass the payload needed to grab it as it's the only difference
     * between getting the authorization_code and the refresh_token.
     * 
     * @param payload The payload of the request. Look at #getAccessToken and #refreshToken how it is structured
     */
    private function getToken($payload) {
        // Gets the acces token following the OAuth workflow
        // https://developer.spotify.com/documentation/general/guides/authorization-guide/#authorization-code-flow

        // The header needs to be set to our clientId and clientSecret
        $headers = array('Authorization: Basic ' . base64_encode($this->clientId.':'.$this->clientSecret));

        $ch = curl_init('https://accounts.spotify.com/api/token');
        // It's a POST request
        curl_setopt($ch, CURLOPT_POST, 1);
        // The provided payload
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        // The headers as defined above
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // If false it would directly output it to the browser, we don't want that.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Grab the data
        $data = curl_exec($ch);
        // Close the CURL request to free up server resources
        curl_close($ch);

        // If the data is not false we decode it and save it
        if ($data !== false) {
            $data = json_decode($data);

            // If there is an error we throw an exception which we capture to display the error
            if (isset($data->error)) {
                throw new Exception($data->error_description);
            }

            // Here we set the three fields.
            $this->accessToken = $data->access_token;
            // The expiration date is the current time + the expire time we got from spotify
            $this->expirationDate = time() + intval($data->expires_in);
            // The refresh_token is only provided on the authorization_code request, not in the refresh_token request
            // so we want to make sure we only set it if it is present.
            if (isset($data->refresh_token)) $this->refreshToken = $data->refresh_token;

            // Lastly we save it to disk
            $this->saveData();
        }
    }


    /** Stores the accessToken, expirationDate and refreshToken to disk */
    private function saveData() {
        // Create a new object with relevant information
        $data = new stdClass();
        $data->accessToken = $this->accessToken;
        $data->expirationDate = $this->expirationDate;
        $data->refreshToken = $this->refreshToken;

        // And save it to disk (will automatically create the file for us)
        file_put_contents(DATA_FILE, json_encode($data));
    }
    
    /** Load data from disk, such as accessToken and refreshToken */
    private function loadData() {
        // If the file does not yet exists we cancel immediately
        if (!file_exists(DATA_FILE)) return;

        // Grab all the contents from the file
        $data = file_get_contents(DATA_FILE);
        // And convert it to a json object
        $data = json_decode($data);

        // If something went wrong with decoding we cancel
        if ($data == false) return;

        // Store our information in our object
        $this->accessToken = $data->accessToken;
        $this->expirationDate = $data->expirationDate;
        $this->refreshToken = $data->refreshToken;
    }
}
