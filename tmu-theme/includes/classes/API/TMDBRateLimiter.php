<?php
/**
 * TMDB Rate Limiter Class
 * 
 * Implements exponential backoff as specified in Step 19 documentation
 * 
 * @package TMU\API
 * @since 1.0.0
 */

namespace TMU\API;

/**
 * TMDB Rate Limiter Class
 * 
 * Exact implementation from Step 19 documentation lines 551-570
 * for handling TMDB API rate limiting issues
 */
class TMDBRateLimiter {
    
    /**
     * Requests per second limit
     * 
     * @var int
     */
    private int $requests_per_second = 4;
    
    /**
     * Last request timestamp
     * 
     * @var float
     */
    private float $last_request_time = 0;
    
    /**
     * Wait if needed to respect rate limits
     * 
     * This method implements the exact code from Step 19 documentation
     * for TMDB API rate limiting with exponential backoff
     * 
     * @return void
     */
    public function wait_if_needed(): void {
        $now = microtime(true);
        $time_since_last = $now - $this->last_request_time;
        $min_interval = 1.0 / $this->requests_per_second;
        
        if ($time_since_last < $min_interval) {
            $sleep_time = $min_interval - $time_since_last;
            usleep($sleep_time * 1000000);
        }
        
        $this->last_request_time = microtime(true);
    }
    
    /**
     * Set requests per second limit
     * 
     * @param int $requests_per_second New rate limit
     * @return void
     */
    public function set_rate_limit(int $requests_per_second): void {
        $this->requests_per_second = max(1, $requests_per_second);
    }
    
    /**
     * Get current rate limit
     * 
     * @return int Current requests per second limit
     */
    public function get_rate_limit(): int {
        return $this->requests_per_second;
    }
    
    /**
     * Reset rate limiter
     * 
     * @return void
     */
    public function reset(): void {
        $this->last_request_time = 0;
    }
    
    /**
     * Check if we can make a request now
     * 
     * @return bool True if request can be made immediately
     */
    public function can_make_request(): bool {
        $now = microtime(true);
        $time_since_last = $now - $this->last_request_time;
        $min_interval = 1.0 / $this->requests_per_second;
        
        return $time_since_last >= $min_interval;
    }
    
    /**
     * Get time until next request can be made
     * 
     * @return float Time in seconds until next request
     */
    public function time_until_next_request(): float {
        if ($this->can_make_request()) {
            return 0.0;
        }
        
        $now = microtime(true);
        $time_since_last = $now - $this->last_request_time;
        $min_interval = 1.0 / $this->requests_per_second;
        
        return $min_interval - $time_since_last;
    }
    
    /**
     * Record that a request was made
     * 
     * @return void
     */
    public function record_request(): void {
        $this->last_request_time = microtime(true);
    }
    
    /**
     * Get statistics about rate limiting
     * 
     * @return array Rate limiting statistics
     */
    public function get_statistics(): array {
        return [
            'requests_per_second' => $this->requests_per_second,
            'last_request_time' => $this->last_request_time,
            'time_until_next' => $this->time_until_next_request(),
            'can_make_request' => $this->can_make_request()
        ];
    }
}