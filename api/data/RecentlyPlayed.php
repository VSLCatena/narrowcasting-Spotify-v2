<?php
require_once('Track.php');

// Creating a simplified object of the data defined here:
// https://developer.spotify.com/documentation/web-api/reference/player/get-recently-played/
class RecentlyPlayed {
    public $date;
    public $track;

    function __construct($data) {
        $this->date = $data->played_at;
        $this->track = new Track($data->track);
    }
}
