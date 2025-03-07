# Kirby Kozmoz LLMs Plugin

A Kirby CMS plugin that generates an `llms.txt` file in the root of your website or responds to the `llms.txt` route with necessary information for Large Language Models (LLMs).

## Overview

This plugin creates an `llms.txt` file that provides structured information about your website to help Large Language Models better understand and interact with your content. Similar to how `robots.txt` works for search engines, `llms.txt` provides guidance for LLMs.

## Features

- Generates an `llms.txt` file in the root of your website in Markdown format
- Provides a dedicated route to access the LLMs information
- Creates proper Markdown links for pages with trailing slashes
- Strips HTML tags from descriptions and metadata for clean output
- Configurable exclusion of pages and templates
- Automatic cache clearing when content changes
- Supports configuration via your site's main config.php file

## Installation

### Manual Installation

1. Download or clone this repository
2. Place the folder `kirby-llms` in your `site/plugins` directory
3. Rename the folder to `kirby-llms` if needed

### Composer Installation

```bash
composer require kozmozio/kirby-llms
```

## Configuration

You can configure the plugin by adding options to your `config.php` file:

```php
return [
  'kozmozio.llms' => [
    'cache' => true,
    'cache.duration' => 60, // minutes
    'exclude' => [
      'templates' => ['error'],
      'pages' => ['private-page', 'another-page']
    ]
  ]
];
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `cache` | boolean | `false` | Enable or disable caching |
| `cache.duration` | integer | `60` | Cache duration in minutes |
| `exclude.templates` | array | `['error']` | Templates to exclude from the output |
| `exclude.pages` | array | `[]` | Pages to exclude from the output |

### Excluding Pages

You can exclude specific pages from the llms.txt output by adding their slugs to the `exclude.pages` array in your configuration. The plugin performs matching on:

- Exact page ID or URI match
- Pages with the same name in different locations (e.g., if you exclude 'inan-olcer', both 'inan-olcer' and 'team/inan-olcer' will be excluded)
- Child pages of excluded parents

For example:
```php
'exclude' => [
  'pages' => ['about', 'blog/private-post', 'team-member']
]
```

## Usage

Once installed, the plugin automatically sets up a route at `yourdomain.com/llms.txt` that generates the LLMs information.

The generated content will be in Markdown format with proper Markdown links for pages and their descriptions. All HTML tags are automatically stripped from descriptions and metadata to ensure clean, plain text output.

### Automatic Cache Clearing

The plugin automatically clears its cache when:
- Pages are created, updated, or deleted
- Page properties change (status, slug, title, template)
- Site information is updated

This ensures that the `llms.txt` content is always up-to-date with your website's content.

### Example Output

```markdown
# Your Website Title

> Your Website Title is your website description

Generated on: 2023-06-15 12:34:56

## Docs

- [Home](https://example.com/) - Welcome to our website
- [About Us](https://example.com/about/) - Learn more about our company and our mission
- [Products](https://example.com/products/) - Explore our range of products
- [Blog](https://example.com/blog/) - Read our latest articles
- [Contact](https://example.com/contact/) - Get in touch with us
```

## Static Site Generation

If you're using a static site generator with Kirby, make sure to include the `llms.txt` route in your static routes:

```php
array_push($staticRoutes, [
  'path' => 'llms.txt', 
  'route' => 'llms.txt'
]);
```

## Development Status

### Phase 1: Basic Structure and Setup ✓

1. Create plugin folder structure ✓
2. Set up plugin initialization ✓
3. Register the route for `llms.txt` ✓
4. Implement basic configuration options ✓

### Phase 2: Core Functionality ✓

1. Develop the content generator for `llms.txt` ✓
2. Implement page collection and filtering ✓
3. Create the response formatter ✓
4. Add caching mechanism ✓

### Phase 3: Advanced Features ✓

1. Add customization options for content exclusion ✓
2. Implement metadata extraction ✓
3. Add automatic cache clearing when content changes ✓
4. Strip HTML tags from descriptions and metadata ✓
5. Ensure URLs have trailing slashes ✓

### Phase 4: Testing and Documentation ✓

1. Test with different Kirby setups ✓
2. Create comprehensive documentation ✓
3. Add examples and use cases ✓
4. Prepare for release ✓

## License

MIT

## Author

[Inan Olcer Kozmoz](https://kozmoz.io)
