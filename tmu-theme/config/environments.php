<?php
/**
 * Environment Configuration
 * 
 * Environment-specific configuration settings.
 * 
 * @package TMU\Config
 * @since 1.0.0
 */

return [
    'development' => [
        'debug' => true,
        'cache_enabled' => false,
        'tmdb_api_key' => getenv('TMDB_API_KEY_DEV'),
        'database_host' => 'localhost',
        'database_name' => 'tmu_dev',
        'cdn_url' => '',
        'redis_enabled' => false,
    ],
    'staging' => [
        'debug' => false,
        'cache_enabled' => true,
        'tmdb_api_key' => getenv('TMDB_API_KEY_STAGING'),
        'database_host' => getenv('DB_HOST_STAGING'),
        'database_name' => getenv('DB_NAME_STAGING'),
        'cdn_url' => getenv('CDN_URL_STAGING'),
        'redis_enabled' => true,
    ],
    'production' => [
        'debug' => false,
        'cache_enabled' => true,
        'tmdb_api_key' => getenv('TMDB_API_KEY_PROD'),
        'database_host' => getenv('DB_HOST_PROD'),
        'database_name' => getenv('DB_NAME_PROD'),
        'cdn_url' => getenv('CDN_URL_PROD'),
        'redis_enabled' => true,
    ]
];