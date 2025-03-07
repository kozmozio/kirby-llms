<?php

namespace Kozmozio\LLMs;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;
use Kirby\Http\Response;

/**
 * Main Plugin Class for Kirby Kozmoz LLMs
 * 
 * This plugin generates a structured text file with information about the website
 * for Large Language Models (LLMs) to better understand the site's content.
 * 
 * @author Kozmozio <info@kozmoz.io>
 * @version 1.0.0
 * @license MIT
 */
class Plugin
{
    /**
     * @var \Kirby\Cms\App
     */
    protected $kirby;

    /**
     * @var \Kirby\Cms\Site
     */
    protected $site;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Kirby\Cache\Cache
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $fromCache = false;

    /**
     * Constructor
     * 
     * Initializes the plugin and loads configuration
     */
    public function __construct()
    {
        $this->kirby = Kirby::instance();
        $this->site = $this->kirby->site();
        
        // First try to load with direct dot notation (kozmozio.llms)
        $this->options = $this->kirby->option('kozmozio.llms');
        
        // If that fails, try the nested approach (kozmozio -> llms)
        if ($this->options === null) {
            $kozmozioOptions = $this->kirby->option('kozmozio');
            if (is_array($kozmozioOptions) && isset($kozmozioOptions['llms'])) {
                $this->options = $kozmozioOptions['llms'];
            }
        }
        
        // If no config is found, set some defaults
        if ($this->options === null) {
            $this->options = [
                'cache' => false,
                'cache.duration' => 60,
                'exclude' => [
                    'templates' => ['error'],
                    'pages' => []
                ]
            ];
        }
        
        $this->cache = $this->kirby->cache('kozmozio.llms');
    }

    /**
     * Clear the cache
     * 
     * Static method to clear the plugin's cache, used by hooks
     * 
     * @return void
     */
    public static function clearCache()
    {
        $kirby = Kirby::instance();
        $cache = $kirby->cache('kozmozio.llms');
        $cache->flush();
    }

    /**
     * Generate the response
     * 
     * Creates and returns the llms.txt content, using cache if available
     *
     * @return \Kirby\Http\Response
     */
    public function response()
    {
        // Check if cache is enabled and a cached version exists
        if ($this->options['cache'] && $cached = $this->cache->get('llms')) {
            $this->fromCache = true;
            return new Response($cached, 'text/plain');
        }

        // Generate content
        $content = $this->generateText();

        // Cache the content if caching is enabled
        if ($this->options['cache']) {
            $this->cache->set('llms', $content, $this->options['cache.duration'] * 60);
        }

        return new Response($content, 'text/plain');
    }

    /**
     * Clean text by stripping HTML tags and normalizing whitespace
     * 
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    protected function cleanText($text) {
        // Strip HTML tags
        $text = strip_tags($text);
        // Convert HTML entities to their corresponding characters
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Ensure URL ends with a trailing slash
     * 
     * @param string $url The URL to process
     * @return string The URL with a trailing slash
     */
    protected function ensureTrailingSlash($url) {
        if (substr($url, -1) !== '/') {
            return $url . '/';
        }
        return $url;
    }

    /**
     * Generate text format
     * 
     * Creates the Markdown-formatted content for llms.txt
     * 
     * @return string The generated content
     */
    protected function generateText() {
        $title = $this->site->title()->value();
        $output = "#{$title}\n\n";
        
        if ($this->site->metaDescription()->isNotEmpty()) {
            $description = $this->cleanText($this->site->metaDescription()->value());
            $output .= ">  {$title} is {$description}\n\n";
        }
        
        $output .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "## Docs\n\n";
        
        // Add all pages
        $pages = $this->site->index();
        foreach ($pages as $page) {
            // Skip excluded pages
            if ($this->isExcluded($page)) {
                continue;
            }
            
            // Add page title and URL in markdown format with trailing slash
            $pageUrl = $this->ensureTrailingSlash($page->url());
            $output .= "- [{$page->title()->value()}]({$pageUrl})";
            
            // Add description if available, with HTML tags stripped
            if ($page->description()->isNotEmpty()) {
                $cleanDescription = $this->cleanText($page->description()->value());
                $output .= " - {$cleanDescription}";
            }
            
            $output .= "\n\n";
        }
        
        return $output;
    }

    /**
     * Check if a page should be excluded
     * 
     * Determines if a page should be excluded from the output based on
     * template and page exclusion rules
     * 
     * @param \Kirby\Cms\Page $page The page to check
     * @return bool True if the page should be excluded, false otherwise
     */
    protected function isExcluded(Page $page) {
        // Get the page ID and URI for comparison
        $pageId = $page->id();
        $pageUri = $page->uri();
        
        // Check excluded templates
        if (isset($this->options['exclude']['templates']) && is_array($this->options['exclude']['templates'])) {
            $template = $page->template()->name();
            if (in_array($template, $this->options['exclude']['templates'])) {
                return true;
            }
        }
        
        // Check excluded pages
        if (isset($this->options['exclude']['pages']) && is_array($this->options['exclude']['pages'])) {
            foreach ($this->options['exclude']['pages'] as $excludedPage) {
                // Normalize the excluded page path
                $excludedPage = trim($excludedPage);
                
                // Direct match with ID or URI
                if ($pageId === $excludedPage || $pageUri === $excludedPage) {
                    return true;
                }
                
                // Check if page is in a subfolder but has the same name
                // For example, if 'inan-olcer' is excluded, 'team/inan-olcer' should also be excluded
                $pageParts = explode('/', $pageUri);
                $pageName = end($pageParts);
                if ($pageName === $excludedPage) {
                    return true;
                }
                
                // Check if page is a child of an excluded parent
                if (!empty($excludedPage) && Str::startsWith($pageUri, $excludedPage . '/')) {
                    return true;
                }
            }
        }
        
        return false;
    }
} 