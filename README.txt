=== B2BPress ===
Contributors: b2bpress
Tags: woocommerce, b2b, product table, catalog, wholesale
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

B2BPress is a WooCommerce-based B2B solution that streamlines non-B2B features and provides a powerful Product Table Generator.

Key features:
- WooCommerce Lite Mode: disable cart/checkout/coupons/stock/prices/marketing/frontend CSS/JS
- Product Table Generator: pagination, search, styles, attributes, SKU, stock, thumbnails
- REST API: /wp-json/b2bpress/v1/tables (CRUD)
- HPOS compatibility

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/b2bpress` directory, or install the ZIP via Plugins > Add New > Upload Plugin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to B2BPress > Settings to configure.

== Frequently Asked Questions ==

= Does it require WooCommerce? =
Yes. WooCommerce 8.7+ is required.

= How to show a table? =
Use the shortcode: `[b2bpress_table id="123"]`

== Changelog ==

= 1.2.0 =
* I18n: Remove runtime mapping; rely on .po/.mo only. Backend follows user locale; frontend follows site locale
* Settings: Prevent first-save auto-check; preserve old values when absent
* UX: Elementor widget registration compatibility updates
* Performance: Paginated category attribute scan; restrict cache cleanup to admin/cron/CLI
* Compliance: Readme/license headers; safer admin strings

= 1.1.1 =
* I18n: Backend pages have English fallbacks; default to .po/.mo; runtime fallback is opt-in via filter (removed in 1.2.0)
* Settings: Prevent first-save auto-check of all boolean options
* UX: Frontend consistently uses user language; Elementor registration compatibility
* Performance: Pagination for category attributes; limit cache cleanup context
* Compliance: readme/license headers; safer admin strings

= 1.1.0 =
* Add new table style set and caching improvements
* Improve escaping and license headers for WordPress.org review

== Upgrade Notice ==

= 1.1.0 =
Security and compatibility improvements. Please update.

== Privacy ==

This plugin stores a user preference `b2bpress_language` in user meta for language display. No personal data is sent to remote servers. On uninstall, options and transients with `b2bpress_` prefix are deleted. Site owners can remove the user meta via profile editing or user data erase tools.

== Internationalization ==

Text domain: `b2bpress` with `Domain Path: /languages`.
We ship `b2bpress-en_US.po` and a starter `b2bpress.pot`. Developers can generate updated POT via WP-CLI: `wp i18n make-pot . languages/b2bpress.pot`.

