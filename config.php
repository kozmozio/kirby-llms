<?php

/**
 * Default configuration for Kirby Kozmoz LLMs Plugin
 */
return [
    'cache' => false,
    'cache.duration' => 60, // minutes
    'exclude' => [
        'templates' => ['error'],
        'pages' => []
    ]
];