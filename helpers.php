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
    }
    
    // Merge the settings, with panel settings taking precedence
    $settings = array_merge([
        'enabled' => true,
        'cache' => false,
        'cache.duration' => 60,
        'add_trailing_slash' => true,
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
        //    echo $page->template().'<br>';
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