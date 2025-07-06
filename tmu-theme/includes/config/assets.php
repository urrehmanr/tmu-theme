<?php
/**
 * TMU Asset Configuration
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset configuration for TMU theme
 */
return [
    'css' => [
        'main' => [
            'src' => 'main.css',
            'deps' => [],
            'version' => TMU_VERSION,
            'media' => 'all'
        ],
        'admin' => [
            'src' => 'admin.css',
            'deps' => [],
            'version' => TMU_VERSION,
            'media' => 'all'
        ]
    ],
    'js' => [
        'main' => [
            'src' => 'main.js',
            'deps' => ['jquery'],
            'version' => TMU_VERSION,
            'in_footer' => true
        ],
        'admin' => [
            'src' => 'admin.js',
            'deps' => ['jquery', 'wp-api'],
            'version' => TMU_VERSION,
            'in_footer' => true
        ]
    ],
    'inline_styles' => [
        'tmu-custom-props' => [
            'handle' => 'tmu-main-style',
            'css' => '
                :root {
                    --tmu-primary: #1e40af;
                    --tmu-secondary: #dc2626;
                    --tmu-accent: #059669;
                    --tmu-dark: #1f2937;
                    --tmu-light: #f9fafb;
                    --tmu-yellow: #f59e0b;
                    --tmu-purple: #7c3aed;
                }
            '
        ]
    ],
    'preload' => [
        'fonts' => [
            'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            'merriweather' => 'https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap'
        ]
    ]
];