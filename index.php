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

@include_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

Kirby::plugin('kozmozio/llms', [
    'options' => require_once __DIR__ . '/config.php',
    
    // Register the routes
    'routes' => [
        [
            'pattern' => 'llms.txt',
            'action' => function () {
                // Get plugin settings
                $settings = llms();
                
                // Check if the plugin is enabled
                if (!$settings['enabled']) {
                    return new Kirby\Http\Response('LLMs.txt is disabled', 'text/plain', 404);
                }
                
                // Check if caching is enabled
                if ($settings['cache']) {
                    $cache = kirby()->cache('kozmozio.llms');
                    $cacheId = 'llms_content';
                    
                    // Try to get from cache first
                    $content = $cache->get($cacheId);
                    
                    if ($content) {
                        return new Kirby\Http\Response($content, 'text/plain');
                    }
                }
                
                // Generate the content
                $content = generateLlmsContent($settings);
                
                // Cache the content if caching is enabled
                if ($settings['cache']) {
                    $cache->set($cacheId, $content, $settings['cache.duration'] * 60);
                }
                
                // Return the content as a plain text response
                return new Kirby\Http\Response($content, 'text/plain');
            }
        ]
    ],
    
    // Register the hooks to clear cache when content changes
    'hooks' => [
        'page.create:after' => function () {
            llms_clear_cache();
        },
        'page.update:after' => function () {
            llms_clear_cache();
        },
        'page.delete:after' => function () {
            llms_clear_cache();
        },
        'site.update:after' => function () {
            llms_clear_cache();
        }
    ],
    
    // Register the blueprints  
    'blueprints' => [
        'kozmoz/llms' => __DIR__ . '/blueprints/kozmoz/llms.yml',
        'kozmoz/llms-settings' => __DIR__ . '/blueprints/kozmoz/llms-settings.yml'
    ]
]); 