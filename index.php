<?php

/**
 * Kirby Kozmoz LLMs Plugin
 *
 * @version   1.0.0
 * @author    Kozmozio
 * @copyright Kozmozio
 * @link      https://kozmoz.io
 * @license   MIT
 */

// Use Kirby class
use Kirby\Cms\App as Kirby;

// Only include autoload.php if it exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    include_once $autoloadPath;
}

// Load plugin classes first so they're available for hooks
require_once __DIR__ . '/src/Plugin.php';

// Load default configuration
$defaultConfig = require_once __DIR__ . '/config.php';

Kirby::plugin('kozmozio/llms', [
    // Use the default configuration from config.php
    'options' => $defaultConfig,
    'routes' => [
        [
            'pattern' => 'llms.txt',
            'action' => function () {
                // Get plugin instance
                $plugin = new Kozmozio\LLMs\Plugin();
                return $plugin->response();
            }
        ]
    ],
    // Add hooks to clear cache when content changes
    'hooks' => [
        // Clear cache when a page is created
        'page.create:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        // Clear cache when a page is updated
        'page.update:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        // Clear cache when a page is deleted
        'page.delete:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        // Clear cache when a page is changed (sorted, status, etc.)
        'page.changeStatus:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        'page.changeSlug:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        'page.changeTitle:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        'page.changeTemplate:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        },
        // Clear cache when site is updated
        'site.update:after' => function () {
            Kozmozio\LLMs\Plugin::clearCache();
        }
    ]
]); 