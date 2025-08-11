<?php

/**
 * Helper functions for the Kirby Kozmoz LLMs Plugin
 */

/**
 * Get the LLMs settings by merging config and panel settings
 * 
 * @return array
 */
function llms() {
    static $settings = null;
    
    if ($settings !== null) {
        return $settings;
    }
    
    // Get the default settings from the config
    $config = kirby()->option('kozmozio.llms', []);
    
    // Get the panel settings if they exist
    $panel = [];
    
    // Only try to get panel settings if the site object exists
    if (site() && site()->content()) {
        if (site()->content()->has('llms_enabled')) {
            $panel['enabled'] = site()->content()->llms_enabled()->toBool();
        }
        
        if (site()->content()->has('llms_cache')) {
            $panel['cache'] = site()->content()->llms_cache()->toBool();
        }
        
        if (site()->content()->has('llms_cacheDuration')) {
            $panel['cacheDuration'] = site()->content()->llms_cacheDuration()->toInt();
        }
        
        if (site()->content()->has('llms_add_trailing_slash')) {
            $panel['add_trailing_slash'] = site()->content()->llms_add_trailing_slash()->toBool();
        }
        
        if (site()->content()->has('llms_excludeTemplates')) {
            $panel['excludeTemplates'] = site()->content()->llms_excludeTemplates()->split();
        }
        
        if (site()->content()->has('llms_excludePages')) {
            $panel['excludePages'] = site()->content()->llms_excludePages()->split();
        }
        
        if (site()->content()->has('sitemap_enabled')) {
            $panel['sitemap_enabled'] = site()->content()->sitemap_enabled()->toBool();
        }
    }
    
    // Merge the settings, with panel settings taking precedence
    $settings = array_merge([
        'enabled' => true,
        'cache' => false,
        'cache.duration' => 60,
        'add_trailing_slash' => true,
        'sitemap_enabled' => true,
        'exclude' => [
            'templates' => ['error'],
            'pages' => []
        ]
    ], $config);
    
    // Override with panel settings if they exist
    if (isset($panel['enabled'])) {
        $settings['enabled'] = $panel['enabled'];
    }
    
    if (isset($panel['cache'])) {
        $settings['cache'] = $panel['cache'];
    }
    
    if (isset($panel['cacheDuration']) && !empty($panel['cacheDuration'])) {
        $settings['cache.duration'] = $panel['cacheDuration'];
    }
    
    if (isset($panel['add_trailing_slash'])) {
        $settings['add_trailing_slash'] = $panel['add_trailing_slash'];
    }
    
    if (isset($panel['excludeTemplates']) && !empty($panel['excludeTemplates'])) {
        $settings['exclude']['templates'] = $panel['excludeTemplates'];
    }
    
    if (isset($panel['excludePages']) && !empty($panel['excludePages'])) {
        $settings['exclude']['pages'] = $panel['excludePages'];
    }
    
    if (isset($panel['sitemap_enabled'])) {
        $settings['sitemap_enabled'] = $panel['sitemap_enabled'];
    }
    
    return $settings;
}

/**
 * Clear the LLMs cache
 */
function llms_clear_cache() {
    try {
        kirby()->cache('kozmozio.llms')->flush();
    } catch (Exception $e) {
        // Cache might not exist yet
    }
}

/**
 * Generate the LLMs.txt content
 * 
 * @param array $settings
 * @return string
 */
function generateLlmsContent($settings) {
    // Generate the content
    $site = site();
    $content = "# " . $site->title()->value() . "\n\n";
    
    // Check for site description - try metaDescription first, then fall back to description
    $description = '';
    if ($site->metaDescription()->isNotEmpty()) {
        $description = $site->metaDescription()->value();
    } elseif ($site->description()->isNotEmpty()) {
        $description = $site->description()->value();
    }
    
    if (!empty($description)) {
        $content .= "> " . strip_tags($description) . "\n\n";
    }
    
    $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "## Docs\n\n";
    
    // Get filtered pages using the same logic as sitemap
    $pages = getFilteredPages($settings);
    
    // Get the add_trailing_slash setting (default to true if not set)
    $addTrailingSlash = $settings['add_trailing_slash'] ?? true;
    
    // Generate the page list
    foreach ($pages as $page) {
        $url = $page->url();
        
        // Ensure URL has trailing slash if the setting is enabled
        if ($addTrailingSlash && substr($url, -1) !== '/') {
            $url .= '/';
        }
        
        $title = $page->title()->value();
        $description = $page->description()->isNotEmpty() 
            ? strip_tags($page->description()->value()) 
            : '';
        
        $content .= "- [$title]($url)" . ($description ? " - $description" : "") . "\n";
    }
    
    return $content;
}

/**
 * Get filtered pages based on settings (shared logic between LLMS and sitemap)
 * 
 * @param array $settings
 * @return \Kirby\Cms\Pages
 */
function getFilteredPages($settings) {
    $site = site();
    
    // Get all pages, excluding those in the settings
    $pages = $site->index()->listed();
    
    // Filter out excluded templates
    if (!empty($settings['exclude']['templates'])) {
        $excludeTemplates = $settings['exclude']['templates'];
        
        // Make sure 'faq' and variations are in the excluded templates
        $faqVariations = ['faq', 'faqs', 'faqpage', 'faq-page'];
        foreach ($faqVariations as $faqVar) {
            if (!in_array($faqVar, $excludeTemplates)) {
                $excludeTemplates[] = $faqVar;
            }
        }
        
        // Filter pages by template and also by URI containing 'faq'
        $filteredPages = $pages->filter(function($page) use ($excludeTemplates) {
            $templateName = $page->template();
            // Exclude if template is in the exclude list
            if (in_array($templateName, $excludeTemplates)) {
                return false;
            }
            
            return true;
        });
        
        $pages = $filteredPages;
    }
    
    // Filter out excluded pages
    if (!empty($settings['exclude']['pages'])) {
        foreach ($settings['exclude']['pages'] as $excludePage) {
            $pages = $pages->filter(function ($page) use ($excludePage) {
                return $page->id() !== $excludePage && 
                       $page->uri() !== $excludePage;
            });
        }
    }
    
    return $pages;
}

/**
 * Generate the XML sitemap content using the same page list as LLMs.txt
 * 
 * @param array $settings
 * @return string
 */
function generateSitemapContent($settings) {
    // Get filtered pages using the same logic as LLMS
    $pages = getFilteredPages($settings);
    
    // Get the add_trailing_slash setting (default to true if not set)
    $addTrailingSlash = $settings['add_trailing_slash'] ?? true;
    
    // Start building the XML sitemap
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Add the homepage
    $siteUrl = site()->url();
    if ($addTrailingSlash && substr($siteUrl, -1) !== '/') {
        $siteUrl .= '/';
    }
    
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars($siteUrl) . '</loc>' . "\n";
    $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>' . "\n";
    $xml .= '    <changefreq>daily</changefreq>' . "\n";
    $xml .= '    <priority>1.0</priority>' . "\n";
    $xml .= '  </url>' . "\n";
    
    // Add each page to the sitemap
    foreach ($pages as $page) {
        $url = $page->url();
        
        // Ensure URL has trailing slash if the setting is enabled
        if ($addTrailingSlash && substr($url, -1) !== '/') {
            $url .= '/';
        }
        
        // Get the last modified date
        $lastMod = $page->modified('Y-m-d\TH:i:s+00:00');
        
        // Determine change frequency and priority based on page depth
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