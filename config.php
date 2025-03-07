<?php

/**
 * Default configuration for Kirby Kozmoz LLMs Plugin
 */
return [
    'enabled' => true, // Enable panel settings
    'cache' => false,
    'cache.duration' => 60, // minutes
    'add_trailing_slash' => true, // Whether to add trailing slashes to URLs
    'exclude' => [
        'templates' => ['error', 'faq', 'faqs', 'faqpage', 'faq-page'],
        'pages' => ['faqs']
    ],
    // Panel settings
    'panel' => [
        'enabled' => true, // Enable panel settings
        'icon' => 'code', // Icon to use in the panel
        'label' => 'LLMs Settings' // Label to display in the panel
    ]
];