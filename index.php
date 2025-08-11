<?php

/**
 * Kirby Kozmoz LLMs Plugin
 *
 * @version   1.0.0
 * @author    Inan Olcer Kozmoz
 * @copyright Inan Olcer Kozmoz
 * @link      https://kozmoz.io
 * @license   MIT
 */

use Kirby\Cms\App as Kirby;

// @include_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Plugin.php';

Kirby::plugin('kozmozio/llms', [
    'options' => require_once __DIR__ . '/config.php',
    
    // Register the routes
    'routes' => [
        [
            'pattern' => 'llms.txt',
            'action' => function () {
                $plugin = new \Kozmozio\LLMs\Plugin();
                return $plugin->response();
            }
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                $plugin = new \Kozmozio\LLMs\Plugin();
                return $plugin->sitemapResponse();
            }
        ]
    ],
    
    // Register the hooks to clear cache when content changes
    'hooks' => [
        'page.create:after' => function () {
            \Kozmozio\LLMs\Plugin::clearCache();
        },
        'page.update:after' => function () {
            \Kozmozio\LLMs\Plugin::clearCache();
        },
        'page.delete:after' => function () {
            \Kozmozio\LLMs\Plugin::clearCache();
        },
        'site.update:after' => function () {
            \Kozmozio\LLMs\Plugin::clearCache();
        }
    ],
    
    // Register the blueprints  
    'blueprints' => [
        'kozmoz/llms' => __DIR__ . '/blueprints/kozmoz/llms.yml',
        'kozmoz/llms-settings' => __DIR__ . '/blueprints/kozmoz/llms-settings.yml'
    ]
]); 