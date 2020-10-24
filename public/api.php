<?php
require_once('../api/ApiRequest.php');
require_once('../api/data/CurrentlyPlaying.php');
require_once('../api/data/RecentlyPlayed.php');

function getCurrentlyPlaying($apiRequest) {
    // We want to access $apiRequest from in here
    global $apiRequest;

    // https://developer.spotify.com/documentation/web-api/reference/player/get-information-about-the-users-current-playback/
    $data = $apiRequest->request('https://api.spotify.com/v1/me/player/currently-playing');
    $data = json_decode($data);
    // If the json is null we don't want to try to create an object out of it
    return $data ? new CurrentlyPlaying($data) : null;
}

function getRecentlyPlayed($apiRequest, $historyLimit) {
    // We want to access $apiRequest from in here
    global $apiRequest;

    // https://developer.spotify.com/documentation/web-api/reference/player/get-recently-played/
    $data = $apiRequest->request('https://api.spotify.com/v1/me/player/recently-played', array('limit' => $historyLimit));
    $data = json_decode($data);
    // We receive an array, so we map it to RecentlyPlayed objects
    return array_map(function($item) {
        return new RecentlyPlayed($item);
    }, $data->items);
}



// with all requests, we first decode it to an object, and then we map it to a data object

    // Create a new ApiRequest object
    $apiRequest = new ApiRequest();

    // Get an array of the requests the user wants
    $requests = isset($_GET['request']) ? explode(' ', urldecode($_GET['request'])) : null;

    // Our returning object
    $ret = array();

    // If the user requests currentlyPlaying we provide that
    if ($requests == null || in_array('currentlyPlaying', $requests)) {
        $ret['currentlyPlaying'] = getCurrentlyPlaying($apiRequest);
    }

    // If the user requests recentlyPlayed we provide that
    if ($requests == null || in_array('recentlyPlayed', $requests)) {
        // You can provide GET attributes to have a bit of control over it
        $historyLimit = isset($_GET['historyLimit']) ? intval($_GET['historyLimit']) ?: 10 : 10;

        $ret['recentlyPlayed'] = getRecentlyPlayed($apiRequest, $historyLimit);
    }

    // Output as JSON
    header('Content-Type: application/json');
    echo json_encode($ret);
