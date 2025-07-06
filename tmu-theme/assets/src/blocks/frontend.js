/**
 * TMU Blocks Frontend Functionality
 * 
 * This file provides frontend JavaScript functionality
 * for TMU blocks on the public-facing website.
 */

// Import frontend styles
import './frontend.scss';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize block interactions
    initializeMovieBlocks();
    initializeTvSeriesBlocks();
    initializeEpisodeBlocks();
    initializePeopleBlocks();
    
    console.log('TMU Blocks frontend initialized');
});

/**
 * Initialize movie block interactions
 */
function initializeMovieBlocks() {
    const movieBlocks = document.querySelectorAll('.tmu-movie-metadata');
    
    movieBlocks.forEach(block => {
        // Add click tracking for analytics
        const title = block.querySelector('.movie-title');
        if (title) {
            title.addEventListener('click', function() {
                // Track movie view
                trackMovieView(block.dataset.movieId);
            });
        }
        
        // Initialize poster loading
        const poster = block.querySelector('.movie-poster img');
        if (poster) {
            poster.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        }
    });
}

/**
 * Initialize TV series block interactions
 */
function initializeTvSeriesBlocks() {
    const tvBlocks = document.querySelectorAll('.tmu-tv-series-metadata');
    
    tvBlocks.forEach(block => {
        // Add click tracking
        const title = block.querySelector('.tv-series-title');
        if (title) {
            title.addEventListener('click', function() {
                trackTvSeriesView(block.dataset.seriesId);
            });
        }
    });
}

/**
 * Initialize episode block interactions
 */
function initializeEpisodeBlocks() {
    const episodeBlocks = document.querySelectorAll('.tmu-tv-episode-metadata');
    
    episodeBlocks.forEach(block => {
        // Add episode tracking
        const title = block.querySelector('.episode-title');
        if (title) {
            title.addEventListener('click', function() {
                trackEpisodeView(block.dataset.episodeId);
            });
        }
        
        // Initialize still image loading
        const still = block.querySelector('.episode-still img');
        if (still) {
            still.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        }
    });
}

/**
 * Initialize people block interactions
 */
function initializePeopleBlocks() {
    const peopleBlocks = document.querySelectorAll('.tmu-people-metadata');
    
    peopleBlocks.forEach(block => {
        // Add people tracking
        const name = block.querySelector('.people-name');
        if (name) {
            name.addEventListener('click', function() {
                trackPersonView(block.dataset.personId);
            });
        }
    });
}

/**
 * Track movie view for analytics
 */
function trackMovieView(movieId) {
    if (!movieId) return;
    
    // Send tracking data
    fetch('/wp-json/tmu/v1/track/movie/' + movieId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': tmuBlocks?.nonce || ''
        },
        body: JSON.stringify({
            action: 'view',
            timestamp: Date.now()
        })
    }).catch(error => {
        console.error('Error tracking movie view:', error);
    });
}

/**
 * Track TV series view for analytics
 */
function trackTvSeriesView(seriesId) {
    if (!seriesId) return;
    
    fetch('/wp-json/tmu/v1/track/tv/' + seriesId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': tmuBlocks?.nonce || ''
        },
        body: JSON.stringify({
            action: 'view',
            timestamp: Date.now()
        })
    }).catch(error => {
        console.error('Error tracking TV series view:', error);
    });
}

/**
 * Track episode view for analytics
 */
function trackEpisodeView(episodeId) {
    if (!episodeId) return;
    
    fetch('/wp-json/tmu/v1/track/episode/' + episodeId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': tmuBlocks?.nonce || ''
        },
        body: JSON.stringify({
            action: 'view',
            timestamp: Date.now()
        })
    }).catch(error => {
        console.error('Error tracking episode view:', error);
    });
}

/**
 * Track person view for analytics
 */
function trackPersonView(personId) {
    if (!personId) return;
    
    fetch('/wp-json/tmu/v1/track/person/' + personId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': tmuBlocks?.nonce || ''
        },
        body: JSON.stringify({
            action: 'view',
            timestamp: Date.now()
        })
    }).catch(error => {
        console.error('Error tracking person view:', error);
    });
}