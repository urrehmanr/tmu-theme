<?php
/**
 * TMDB Exception Handler
 * 
 * Custom exception class for TMDB API operations with enhanced error handling,
 * logging capabilities, and detailed error information for debugging.
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

/**
 * Exception class for TMDB API operations
 * 
 * Provides comprehensive error handling for TMDB API interactions
 */
class Exception extends \Exception {
    
    /**
     * Error codes for different TMDB API errors
     */
    const ERROR_INVALID_API_KEY = 'INVALID_API_KEY';
    const ERROR_REQUEST_COUNT_EXCEEDED = 'REQUEST_COUNT_EXCEEDED';
    const ERROR_INVALID_FORMAT = 'INVALID_FORMAT';
    const ERROR_RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    const ERROR_INTERNAL_ERROR = 'INTERNAL_ERROR';
    const ERROR_SERVICE_OFFLINE = 'SERVICE_OFFLINE';
    const ERROR_SUSPENDED_API_KEY = 'SUSPENDED_API_KEY';
    const ERROR_INVALID_SERVICE = 'INVALID_SERVICE';
    const ERROR_AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    const ERROR_DUPLICATE_ENTRY = 'DUPLICATE_ENTRY';
    const ERROR_INVALID_INPUT = 'INVALID_INPUT';
    const ERROR_INVALID_REQUEST_TOKEN = 'INVALID_REQUEST_TOKEN';
    const ERROR_RESOURCE_PRIVATE = 'RESOURCE_PRIVATE';
    const ERROR_NOTHING_TO_UPDATE = 'NOTHING_TO_UPDATE';
    const ERROR_REQUEST_DENIED = 'REQUEST_DENIED';
    const ERROR_REQUEST_LIMIT_EXCEEDED = 'REQUEST_LIMIT_EXCEEDED';
    
    /**
     * Additional error data
     * 
     * @var array
     */
    private $errorData;
    
    /**
     * Error code for categorization
     * 
     * @var string
     */
    private $errorCode;
    
    /**
     * HTTP status code
     * 
     * @var int
     */
    private $httpStatusCode;
    
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param string $errorCode Error code for categorization
     * @param int $httpStatusCode HTTP status code
     * @param array $errorData Additional error data
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        string $errorCode = '',
        int $httpStatusCode = 0,
        array $errorData = [],
        \Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->httpStatusCode = $httpStatusCode;
        $this->errorData = $errorData;
        
        parent::__construct($message, 0, $previous);
        
        $this->logError();
    }
    
    /**
     * Create exception from TMDB API response
     * 
     * @param array $response API response data
     * @param int $httpStatusCode HTTP status code
     * @return self
     */
    public static function fromApiResponse(array $response, int $httpStatusCode = 0): self {
        $message = $response['status_message'] ?? 'Unknown TMDB API error';
        $statusCode = $response['status_code'] ?? 0;
        
        $errorCode = self::mapStatusCodeToErrorCode($statusCode);
        
        return new self(
            $message,
            $errorCode,
            $httpStatusCode,
            $response
        );
    }
    
    /**
     * Create exception from HTTP error
     * 
     * @param \WP_Error $wpError WordPress HTTP error
     * @return self
     */
    public static function fromHttpError(\WP_Error $wpError): self {
        $message = 'HTTP Error: ' . $wpError->get_error_message();
        $errorCode = 'HTTP_ERROR';
        
        $errorData = [
            'error_code' => $wpError->get_error_code(),
            'error_data' => $wpError->get_error_data(),
        ];
        
        return new self(
            $message,
            $errorCode,
            0,
            $errorData
        );
    }
    
    /**
     * Get error code
     * 
     * @return string
     */
    public function getErrorCode(): string {
        return $this->errorCode;
    }
    
    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getHttpStatusCode(): int {
        return $this->httpStatusCode;
    }
    
    /**
     * Get additional error data
     * 
     * @return array
     */
    public function getErrorData(): array {
        return $this->errorData;
    }
    
    /**
     * Check if error is rate limit related
     * 
     * @return bool
     */
    public function isRateLimitError(): bool {
        return in_array($this->errorCode, [
            self::ERROR_REQUEST_COUNT_EXCEEDED,
            self::ERROR_REQUEST_LIMIT_EXCEEDED
        ]);
    }
    
    /**
     * Check if error is authentication related
     * 
     * @return bool
     */
    public function isAuthenticationError(): bool {
        return in_array($this->errorCode, [
            self::ERROR_INVALID_API_KEY,
            self::ERROR_SUSPENDED_API_KEY,
            self::ERROR_AUTHENTICATION_FAILED,
            self::ERROR_INVALID_REQUEST_TOKEN
        ]);
    }
    
    /**
     * Check if error is retryable
     * 
     * @return bool
     */
    public function isRetryable(): bool {
        return in_array($this->errorCode, [
            self::ERROR_INTERNAL_ERROR,
            self::ERROR_SERVICE_OFFLINE,
            self::ERROR_REQUEST_COUNT_EXCEEDED,
            self::ERROR_REQUEST_LIMIT_EXCEEDED
        ]);
    }
    
    /**
     * Get suggested retry delay in seconds
     * 
     * @return int
     */
    public function getRetryDelay(): int {
        if ($this->isRateLimitError()) {
            return 30; // Wait 30 seconds for rate limit
        }
        
        if ($this->errorCode === self::ERROR_SERVICE_OFFLINE) {
            return 300; // Wait 5 minutes for service issues
        }
        
        return 60; // Default 1 minute for other retryable errors
    }
    
    /**
     * Map TMDB status code to error code
     * 
     * @param int $statusCode TMDB status code
     * @return string
     */
    private static function mapStatusCodeToErrorCode(int $statusCode): string {
        $statusCodeMap = [
            1 => self::ERROR_INVALID_SERVICE,
            2 => self::ERROR_INVALID_SERVICE,
            3 => self::ERROR_AUTHENTICATION_FAILED,
            4 => self::ERROR_INVALID_FORMAT,
            5 => self::ERROR_INVALID_INPUT,
            6 => self::ERROR_INVALID_INPUT,
            7 => self::ERROR_INVALID_API_KEY,
            8 => self::ERROR_DUPLICATE_ENTRY,
            9 => self::ERROR_SERVICE_OFFLINE,
            10 => self::ERROR_SUSPENDED_API_KEY,
            11 => self::ERROR_INTERNAL_ERROR,
            12 => self::ERROR_INVALID_INPUT,
            13 => self::ERROR_INVALID_INPUT,
            14 => self::ERROR_AUTHENTICATION_FAILED,
            15 => self::ERROR_REQUEST_DENIED,
            16 => self::ERROR_INVALID_INPUT,
            17 => self::ERROR_INVALID_INPUT,
            18 => self::ERROR_REQUEST_DENIED,
            19 => self::ERROR_REQUEST_DENIED,
            20 => self::ERROR_INVALID_INPUT,
            21 => self::ERROR_INVALID_INPUT,
            22 => self::ERROR_INVALID_INPUT,
            23 => self::ERROR_INVALID_INPUT,
            24 => self::ERROR_INVALID_REQUEST_TOKEN,
            25 => self::ERROR_INVALID_REQUEST_TOKEN,
            26 => self::ERROR_INVALID_REQUEST_TOKEN,
            27 => self::ERROR_INVALID_INPUT,
            28 => self::ERROR_INVALID_INPUT,
            29 => self::ERROR_REQUEST_LIMIT_EXCEEDED,
            30 => self::ERROR_INVALID_INPUT,
            31 => self::ERROR_INVALID_INPUT,
            32 => self::ERROR_INVALID_INPUT,
            33 => self::ERROR_INVALID_INPUT,
            34 => self::ERROR_RESOURCE_NOT_FOUND,
            35 => self::ERROR_INVALID_INPUT,
            36 => self::ERROR_INVALID_INPUT,
            37 => self::ERROR_INVALID_INPUT,
            38 => self::ERROR_INVALID_INPUT,
            39 => self::ERROR_INVALID_INPUT,
            40 => self::ERROR_INVALID_INPUT,
            41 => self::ERROR_INVALID_INPUT,
            42 => self::ERROR_INVALID_INPUT,
            43 => self::ERROR_INVALID_INPUT,
            44 => self::ERROR_RESOURCE_PRIVATE,
            45 => self::ERROR_INVALID_INPUT,
            46 => self::ERROR_INVALID_INPUT,
            47 => self::ERROR_INVALID_INPUT,
        ];
        
        return $statusCodeMap[$statusCode] ?? self::ERROR_INTERNAL_ERROR;
    }
    
    /**
     * Log error to WordPress error log
     */
    private function logError(): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $logData = [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'http_status' => $this->httpStatusCode,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
        
        if (!empty($this->errorData)) {
            $logData['error_data'] = $this->errorData;
        }
        
        error_log('TMU TMDB Error: ' . wp_json_encode($logData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Convert exception to array for API responses
     * 
     * @return array
     */
    public function toArray(): array {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'http_status' => $this->httpStatusCode,
            'is_retryable' => $this->isRetryable(),
            'retry_delay' => $this->getRetryDelay(),
            'error_data' => $this->errorData,
        ];
    }
    
    /**
     * Get user-friendly error message
     * 
     * @return string
     */
    public function getUserFriendlyMessage(): string {
        switch ($this->errorCode) {
            case self::ERROR_INVALID_API_KEY:
                return __('TMDB API key is invalid. Please check your API key in settings.', 'tmu-theme');
                
            case self::ERROR_REQUEST_COUNT_EXCEEDED:
            case self::ERROR_REQUEST_LIMIT_EXCEEDED:
                return __('TMDB API rate limit exceeded. Please wait a moment before trying again.', 'tmu-theme');
                
            case self::ERROR_RESOURCE_NOT_FOUND:
                return __('The requested content was not found on TMDB.', 'tmu-theme');
                
            case self::ERROR_SERVICE_OFFLINE:
                return __('TMDB service is temporarily unavailable. Please try again later.', 'tmu-theme');
                
            case self::ERROR_SUSPENDED_API_KEY:
                return __('TMDB API key has been suspended. Please contact TMDB support.', 'tmu-theme');
                
            case self::ERROR_AUTHENTICATION_FAILED:
                return __('TMDB authentication failed. Please check your API key.', 'tmu-theme');
                
            case self::ERROR_INTERNAL_ERROR:
                return __('TMDB internal error occurred. Please try again later.', 'tmu-theme');
                
            default:
                return __('An error occurred while communicating with TMDB.', 'tmu-theme');
        }
    }
}