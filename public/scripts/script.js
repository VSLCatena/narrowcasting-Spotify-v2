window.addEventListener('load', function() {
    var spotifyApp = new Vue({
        el: '#spotifyApp',
        /** The initial state of our Vue application */
        data: {
            intervalRef: null, // The reference for our setInterval, this so we have the ability to clean up later
            user: null, // Our user object
            currentlyPlaying: null, // Our currentlyPlaying object
            recentlyPlayed: null, // Oyr recentlyPlayed object
            timeLastUpdated: 0, // The last time since we've updated the currentlyPlaying object
            timeProgressed: 0, // The timestamp since we've last updated the seeker, used to calculate the diff between last update
        },
        computed: {
            /** computes the percentage progression automatically for us so we can directly use it in our code */
            songProgress: function() {
                if (this.currentlyPlaying == null) return 0;

                return this.currentlyPlaying.currentProgress / this.currentlyPlaying.songDuration * 100;
            }
        },
        /** 
         * When the application is first ready to use, we want to do a full refresh to get all data,
         * and then start an interval that calls "tick" a couple of times per second.
         */
        mounted: function() {
            this.refreshRecentlyPlayed();
            this.intervalRef = setInterval(this.tick.bind(this), 200);
        },
        /**
         * Even though destroying this element will always be when you close this page, I do love
         * being thorough and clean up after myself. So we clear the "tick" interval.
         */
        beforeDestroy: function() {
            clearInterval(this.intervalRef);
        },
        watch: {
            // We watch the currentlyPlaying object for if the song changed.
            // And if so, we refresh to get the latest recentlyPlayed list
            currentlyPlaying: function(newCurrent, oldCurrent) {
                // If both objects are null nothing changed (although "watch" shouldn't trigger then)
                if (newCurrent == null && oldCurrent == null) return;

                // From the previous if we know that at least one is not null,
                // If one is and the other isn't, then something is changed and we do a refresh
                if (newCurrent == null || oldCurrent == null) {
                    // We call refreshRecentlyPlayed with a delay
                    // With some testing I found out that the recentlyPlayed is not updated
                    // in real time but has some delay. With this it should be fixed.
                    setTimeout(this.refreshRecentlyPlayed.bind(this), 5000);
                    return;
                }

                // At this point we know that both are not null


                // If the track name, or the first artist changed we update our everything
                if (
                    oldCurrent.track.name != newCurrent.track.name ||
                    oldCurrent.track.artists[0] != newCurrent.track.artists[0]
                ) {
                    // We call refreshRecentlyPlayed with a delay
                    // With some testing I found out that the recentlyPlayed is not updated
                    // in real time but has some delay. With this it should be fixed.
                    setTimeout(this.refreshRecentlyPlayed.bind(this), 5000);
                }

            },
        },
        methods: {
            /** Is called a couple of times a second */
            tick: function() {
                // The current time in millis
                var now = (new Date()).getTime();

                // If we are currently playing a song we update the progress
                if (this.currentlyPlaying != null && this.currentlyPlaying.isPlaying) {
                    
                    // Calculate the difference between the previous time we updated the progress and now
                    var diff = now - this.timeProgressed;
                    // Add the difference to the progress
                    var newProgress = this.currentlyPlaying.currentProgress + diff;
                    // And update the timeProgressed to be the current time
                    this.timeProgressed = now;

                    // If the new progress is bigger than the song duration we cap the progress to 
                    // the song duration and force an update.
                    if (newProgress > this.currentlyPlaying.songDuration) {
                        newProgress == this.currentlyPlaying.songDuration;
                        this.timeLastUpdated = 0;
                    }

                    // We replace the whole object with the same object, but with currentProcess being overwritten
                    this.currentlyPlaying = {
                        ...this.currentlyPlaying,
                        currentProgress: newProgress,
                    };
                }

                // If we haven't updated for over 5 seconds we refresh current playing song
                if (this.timeLastUpdated + 5000 < now) {
                    this.refreshCurrentlyPlaying();
                    // We update timeLastUpdated so we don't make like 5 calls because the API was slow to respond
                    this.timeLastUpdated = now;
                }
            },

            /** Refreshes only the currently playing object */
            refreshCurrentlyPlaying: function() {
                $.get('./api.php?request=currentlyPlaying').then(function (response) {
                    this.currentlyPlaying = response.currentlyPlaying;

                    /* We also need to update the timings since we're up to date :) */
                    this.timeLastUpdated = this.timeProgressed = (new Date()).getTime();
                }.bind(this));
            },
            /** Refreshes the recentlyPlayed object */
            refreshRecentlyPlayed: function() {
                $.get('./api.php?request=recentlyPlayed').then(function(response) {
                    this.recentlyPlayed = response.recentlyPlayed;
                }.bind(this));
            },
            /**
             * Converts the ISO date to a hh:mm format
             * 
             * @param {string} dateString the ISO 8601 date
             * 
             * @returns a string formatted like hh:mm
             */
            parseDate: function(dateString) {
                // Luckily for us the Date object accepts an ISO 8601 string
                var date = new Date(dateString);

                // We convert the hours to a string here, and append it with two 0's for easy formatting
                var hours = '00' + date.getHours();
                // Same for minutes
                var minutes = '00' + date.getMinutes();

                // substr(-2) will get us the last two characters. So for 003 it's 03
                return hours.substr(-2) + ':' + minutes.substr(-2);
            },
            /**
             * Converts the milliseconds to hh:mm:ss or mm:ss
             * 
             * @param {number} millis The milliseconds to convert
             * 
             * @returns a string formatted like hh:mm:ss or mm:ss
             */
            parseTime: function(millis) {
                // Convert millis to seconds
                var seconds = Math.floor(millis / 1000);
                // Get the amount of minutes from the seconds
                var minutes = Math.floor(seconds / 60);
                // Get the amount of hours from the minutes
                var hours = Math.floor(minutes / 60);
                
                // For if the amount is over an hour we want to do mod 60
                var minutes = '00' + (minutes % 60);
                // We do mod 60 on the seconds to get the seconds needed
                var seconds = '00' + (seconds % 60);

                // substr(-2) will get us the last two characters. So for 003 it's 03
                var time = minutes.substr(-2) + ':' + seconds.substr(-2);
                if (hours > 0) {
                    // If hours is 1 or more we add it to the time
                    time = hours + ':' + time;
                }

                return time;
            }
        },
    });
});