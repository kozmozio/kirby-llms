<?php

/**
 * Default configuration for Kirby Kozmoz LLMs Plugin
 */
return [
    'enabled' => true, // Enable panel settings
    'cache' => false,
    'cache.duration' => 60, // minutes
    'add_trailing_slash' => true, // Whether to add trailing slashes to URLs
    'group_by' => 'parent', // Group pages by: 'parent', 'template', or 'none'
    'sitemap_enabled' => true, // Enable XML sitemap generation
    'exclude' => [
        'templates' => ['error'],
        'pages' => []
    ],

];