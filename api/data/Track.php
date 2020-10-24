<?php

// Creating a simplified object of the data defined here:
// https://developer.spotify.com/documentation/web-api/reference/tracks/get-track/
class Track {
    public $name;
    public $artists;
    public $popularity;
    public $image;

    function __construct($data) {
        $this->name = $data->name;
        $this->popularity = $data->popularity;

        // Map the artists to just their names
        $this->artists = array_map(
            function($item) { return $item->name; }, 
            $data->artists
        );
    
        // Find the biggest image
        $biggestImage = null;
        $biggestSize = 0;
        foreach ($data->album->images as $image) {
            if ($image->width > $biggestSize) {
                $biggestImage = $image->url;
                $biggestSize = $image->width;
            }
        }
        $this->image = $biggestImage;
    }
}
