# Changelog

All notable changes to the kirby-llms plugin will be documented in this file.

## [1.0.3] 

### Added
- New `llms_description` field in panel settings for custom site description
- Enhanced panel interface with improved field organization
- Support for dynamic page selection with `multiselect` fields
- Query-based page inclusion/exclusion using `site.index.listed`
- HTML links in info field that open in new windows

### Changed
- Updated description priority: `llms_description` > `description` > `metaDescription`
- Improved page filtering logic to include all pages including subpages
- Enhanced help text for better user guidance
- Reorganized panel layout with better field grouping

### Fixed
- Fixed page exclusion logic to properly handle nested pages
- Improved template exclusion to work with page paths
- Enhanced include page logic to override exclusions correctly

## [1.0.0] - Initial Release

### Added
- LLMs.txt generation for Large Language Models
- XML sitemap generation
- Panel-based configuration interface
- Caching support for improved performance
- Page and template exclusion rules
- Trailing slash configuration
- Cache management and clearing functionality
