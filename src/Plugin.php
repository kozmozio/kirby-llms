<?php

namespace Kozmozio\LLMs;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
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
        $this->options = $this->loadOptions();
        $this->cache = $this->kirby->cache('kozmozio.llms');
    }

    /**
     * Load options by merging config and (optional) panel overrides
     */
    protected function loadOptions(): array
    {
        // Base defaults
        $defaults = [
            'enabled' => true,
            'cache' => false,
            'cache.duration' => 60,
            'add_trailing_slash' => true,
            'sitemap_enabled' => true,
            'exclude' => [
                'templates' => ['error'],
                'pages' => []
            ]
        ];

        // Try direct dot notation first
        $config = $this->kirby->option('kozmozio.llms');

        // If not found, try nested structure
        if ($config === null) {
            $kozmozioOptions = $this->kirby->option('kozmozio');
            if (is_array($kozmozioOptions) && isset($kozmozioOptions['llms'])) {
                $config = $kozmozioOptions['llms'];
            }
        }

        if (!is_array($config)) {
            $config = [];
        }

        // Merge defaults and config
        $options = array_merge($defaults, $config);

        // Panel overrides if site content available
        if ($this->site && $this->site->content()) {
            $content = $this->site->content();

            if ($content->has('llms_enabled')) {
                $options['enabled'] = $content->llms_enabled()->toBool();
            }
            if ($content->has('llms_cache')) {
                $options['cache'] = $content->llms_cache()->toBool();
            }
            if ($content->has('llms_cacheDuration')) {
                $duration = $content->llms_cacheDuration()->toInt();
                if ($duration > 0) {
                    $options['cache.duration'] = $duration;
                }
            }
            if ($content->has('llms_add_trailing_slash')) {
                $options['add_trailing_slash'] = $content->llms_add_trailing_slash()->toBool();
            }
            if ($content->has('llms_excludeTemplates')) {
                $templates = $content->llms_excludeTemplates()->split();
                if (!empty($templates)) {
                    $options['exclude']['templates'] = $templates;
                }
            }
            if ($content->has('llms_excludePages')) {
                $pages = $content->llms_excludePages()->split();
                if (!empty($pages)) {
                    $options['exclude']['pages'] = $pages;
                }
            }
            if ($content->has('sitemap_enabled')) {
                $options['sitemap_enabled'] = $content->sitemap_enabled()->toBool();
            }
        }

        return $options;
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
        // Check plugin enabled
        if (isset($this->options['enabled']) && $this->options['enabled'] === false) {
            return new Response('LLMs.txt is disabled', 'text/plain', 404);
        }

        // Cache check
        if (!empty($this->options['cache']) && $cached = $this->cache->get('llms')) {
            $this->fromCache = true;
            return new Response($cached, 'text/plain');
        }

        // Generate content
        $content = $this->generateLlmsText();

        // Cache the content if caching is enabled
        if (!empty($this->options['cache'])) {
            $this->cache->set('llms', $content, ($this->options['cache.duration'] ?? 60) * 60);
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
     * Generate the LLMs text content
     *
     * @return string
     */
    protected function generateLlmsText(): string
    {
        $title = $this->site->title()->value();
        $output = "# {$title}\n\n";

        // Description: prefer metaDescription, fallback to description
        $description = '';
        if ($this->site->metaDescription()->isNotEmpty()) {
            $description = $this->site->metaDescription()->value();
        } elseif ($this->site->description()->isNotEmpty()) {
            $description = $this->site->description()->value();
        }

        if (!empty($description)) {
            $output .= "> " . $this->cleanText($description) . "\n\n";
        }

        $output .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "## Docs\n\n";

        $pages = $this->getFilteredPages();
        $addTrailing = $this->options['add_trailing_slash'] ?? true;

        $lines = [];
        foreach ($pages as $page) {
            $url = $page->url();
            if ($addTrailing) {
                $url = $this->ensureTrailingSlash($url);
            }
            $title = $page->title()->value();
            $desc = $page->description()->isNotEmpty() ? $this->cleanText($page->description()->value()) : '';
            $lines[] = "- [{$title}]({$url})" . ($desc ? " - {$desc}" : '');
        }

        if (!empty($lines)) {
            $output .= implode("\n", $lines) . "\n"; // exactly one blank line between items
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
    protected function isExcluded(Page $page)
    {
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

    /**
     * Get filtered pages based on options (shared by LLMs and sitemap)
     */
    protected function getFilteredPages(): Pages
    {
        $pages = $this->site->index()->listed();

        // Exclude templates (ensure faq variations are included)
        $excludeTemplates = $this->options['exclude']['templates'] ?? [];
        $faqVariations = ['faq', 'faqs', 'faqpage', 'faq-page'];
        foreach ($faqVariations as $var) {
            if (!in_array($var, $excludeTemplates, true)) {
                $excludeTemplates[] = $var;
            }
        }

        if (!empty($excludeTemplates)) {
            $pages = $pages->filter(function (Page $page) use ($excludeTemplates) {
                $templateName = $page->template()->name();
                return !in_array($templateName, $excludeTemplates, true);
            });
        }

        // Exclude specific pages by id or uri
        if (!empty($this->options['exclude']['pages'])) {
            foreach ($this->options['exclude']['pages'] as $excludePage) {
                $pages = $pages->filter(function (Page $page) use ($excludePage) {
                    return $page->id() !== $excludePage && $page->uri() !== $excludePage;
                });
            }
        }

        return $pages;
    }

    /**
     * Generate XML sitemap content
     */
    protected function generateSitemapXml(): string
    {
        $pages = $this->getFilteredPages();
        $addTrailing = $this->options['add_trailing_slash'] ?? true;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $siteUrl = $this->site->url();
        if ($addTrailing && substr($siteUrl, -1) !== '/') {
            $siteUrl .= '/';
        }
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($siteUrl) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";

        foreach ($pages as $page) {
            $url = $page->url();
            if ($addTrailing && substr($url, -1) !== '/') {
                $url .= '/';
            }

            $lastMod = $page->modified('Y-m-d\TH:i:s+00:00');
            $depth = count(explode('/', trim($page->uri(), '/')));
            $changefreq = $depth <= 1 ? 'weekly' : 'monthly';
            $priority = max(0.1, 1.0 - ($depth * 0.2));

            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $lastMod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
            $xml .= '    <priority>' . number_format($priority, 1) . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Sitemap route response
     */
    public function sitemapResponse(): Response
    {
        if (isset($this->options['sitemap_enabled']) && $this->options['sitemap_enabled'] === false) {
            return new Response('Sitemap is disabled', 'text/plain', 404);
        }

        if (!empty($this->options['cache']) && $cached = $this->cache->get('sitemap')) {
            return new Response($cached, 'application/xml');
        }

        $content = $this->generateSitemapXml();

        if (!empty($this->options['cache'])) {
            $this->cache->set('sitemap', $content, ($this->options['cache.duration'] ?? 60) * 60);
        }

        return new Response($content, 'application/xml');
    }
} 