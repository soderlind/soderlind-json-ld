# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] - 2026-04-07

### Added
- WordPress Coding Standards (WPCS 3.3.0) compliance with `phpcs.xml.dist`.
- Norwegian Bokmål (nb_NO) translation.
- Internationalization support: `Text Domain`, `Domain Path` headers, `load_plugin_textdomain()`, and `.pot` file.
- GitHub Updater integration for automatic updates from GitHub releases.
- GitHub Actions workflows for release packaging and manual ZIP builds.
- GitHub issue templates (bug report and feature request).
- Comprehensive developer filters documentation (`docs/filters.md`).
- "Flush JSON-LD Cache" button on the settings page.
- Media picker for logo selection on the settings page.

### Changed
- Plugin can now be activated per-site or network-wide (removed `Network: true`).

### Fixed
- Media picker was broken due to missing `wp_enqueue_media()` call.

## [0.1.0] - 2026-04-07

### Added
- Initial release.
- Organization, WebSite, and BreadcrumbList schemas on every page.
- BlogPosting schema for single posts with author, image, categories, tags, and word count.
- Article schema for custom post types.
- WebPage, AboutPage, and ContactPage schemas for pages.
- CollectionPage schema with ItemList for archives and blog home.
- ProfilePage and Person schemas for author archives.
- Content-detected FAQPage schema from `<details>` elements, question headings, and FAQ blocks.
- Content-detected HowTo schema from ordered lists, step headings, and HowTo blocks.
- Content-detected SoftwareApplication schema from keyword analysis.
- Content-detected VideoObject schema from YouTube, Vimeo, and `<video>` embeds.
- Transient caching with content-hash-based invalidation.
- Admin settings page (Settings → JSON-LD) with organization fields and media picker.
- Multisite support with network-wide defaults and per-site overrides.
- Network admin settings page (Network Admin → Settings → JSON-LD).
- Developer filters: `soderlind_jsonld_schemas`, `soderlind_jsonld_schema_{type}`, `soderlind_jsonld_cache_ttl`.
- PHPUnit test suite with Brain Monkey for WordPress mocking (82 tests).
