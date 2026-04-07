=== Soderlind JSON-LD ===
Contributors: suspended
Tags: json-ld, schema, structured data, seo, ai
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-optimized JSON-LD structured data for WordPress. Auto-detects content patterns and outputs schema.org markup via @graph for maximum AI search visibility.

== Description ==

Soderlind JSON-LD outputs a single `<script type="application/ld+json">` block containing a schema.org `@graph` with cross-referenced `@id` nodes. This structure is optimized for AI search engines and traditional search crawlers alike.

= Schema Types =

**Site-wide (every page):**

* Organization — name, logo, founding date, social profiles
* WebSite — site name, description, SearchAction
* BreadcrumbList — hierarchical navigation trail

**Page-context:**

* BlogPosting — single posts with author, image, categories, tags, word count
* Article — custom post types
* WebPage, AboutPage, ContactPage — pages detected by slug or template
* CollectionPage — archives and blog home with ItemList
* ProfilePage, Person — author archives

**Content-detected:**

* FAQPage — from `<details>/<summary>`, question headings, or FAQ blocks
* HowTo — from ordered lists, step headings, or HowTo blocks
* SoftwareApplication — from keyword analysis in post content
* VideoObject — from YouTube, Vimeo, and `<video>` embeds

= Key Features =

* Single `@graph` output with `@id` cross-referencing between nodes
* Automatic content pattern detection — no manual markup needed
* Transient caching with content-hash invalidation (7-day TTL, filterable)
* Multisite support — network-wide defaults with per-site overrides
* Developer filters for full customization

== Installation ==

1. Upload the `soderlind-json-ld` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to Settings → JSON-LD to configure your organization details.

On multisite, the plugin can be activated per-site or network-wide. Network defaults are configured under Network Admin → Settings → JSON-LD.

== Frequently Asked Questions ==

= Does this plugin require any configuration? =

No. It works out of the box using your site name and custom logo. For richer output, add organization details under Settings → JSON-LD.

= Does it work with multisite? =

Yes. Network admins can set defaults under Network Admin → Settings → JSON-LD. Individual sites can override any setting.

= How does content detection work? =

The plugin analyzes rendered post content for patterns: `<details>` elements and question headings become FAQ schema, ordered lists and step headings become HowTo schema, YouTube/Vimeo URLs become VideoObject schema, and posts with software-related keywords get SoftwareApplication schema.

= Can I customize the output? =

Yes. Use the `soderlind_jsonld_schemas` filter to modify the full schema array, or `soderlind_jsonld_schema_{type}` to target specific schema types. The cache TTL can be changed with `soderlind_jsonld_cache_ttl`.

= Does it conflict with Yoast SEO or Rank Math? =

The plugin detects Yoast and Rank Math FAQ/HowTo blocks as content sources. If those plugins already output their own JSON-LD, you may want to use the `soderlind_jsonld_schemas` filter to remove overlapping types.

== Screenshots ==

== Changelog ==

= 0.2.0 =
* Added WordPress Coding Standards (WPCS 3.3.0) compliance with phpcs.xml.dist.
* Added Norwegian Bokmål (nb_NO) translation.
* Added i18n support: Text Domain, Domain Path, load_plugin_textdomain(), and .pot file.
* Added GitHub Updater integration for automatic updates from GitHub releases.
* Added GitHub Actions workflows for release packaging and manual ZIP builds.
* Added GitHub issue templates (bug report and feature request).
* Added developer filters documentation (docs/filters.md).
* Added "Flush JSON-LD Cache" button on the settings page.
* Added media picker for logo selection on the settings page.
* Changed: plugin can now be activated per-site or network-wide (removed Network: true).
* Fixed: media picker was broken due to missing wp_enqueue_media() call.

= 0.1.0 =
* Initial release.
* 15 schema types: Organization, WebSite, BreadcrumbList, BlogPosting, Article, WebPage, AboutPage, ContactPage, CollectionPage, ProfilePage, Person, FAQPage, HowTo, SoftwareApplication, VideoObject.
* Automatic content detection for FAQ, HowTo, software, and video content.
* Transient caching with content-hash invalidation.
* Admin settings page with organization fields and media picker.
* Multisite support with network defaults and per-site overrides.
* Developer filters for schema customization.
