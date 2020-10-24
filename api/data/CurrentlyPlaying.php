<?php
require_once('Track.php');

// Creating a simplified object of the data defined here:
// https://developer.spotify.com/documentation/web-api/reference/player/get-information-about-the-users-current-playback/
class CurrentlyPlaying {
    public $songDuration;
    public $currentProgress;
    public $track;
    public $isPlaying;

    function __construct($data) {
        $this->songDuration = $data->item->duration_ms;
        $this->currentProgress = $data->progress_ms;
        $this->track = new Track($data->item);
        $this->isPlaying = $data->is_playing;
    }
}
