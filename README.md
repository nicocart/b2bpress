# B2BPress - WordPress B2B eCommerce Solution

[中文 README](README-ZH.md)

B2BPress is a WooCommerce-based WordPress plugin designed for B2B eCommerce. It streamlines WooCommerce for wholesale scenarios and ships with a powerful Product Table Generator to build and manage large B2B product catalogs.

![Screenshot](/res/image/1.png)

## Key Features

### WooCommerce "Lite Mode"
- Toggle off non-B2B features: cart, checkout, coupons, inventory, prices, marketing, email templates, and frontend CSS/JS
- Setup Wizard guides admins to switch into B2B mode and auto-detect conflicts with third-party plugins
- Declares compatibility with HPOS (High-Performance Order Storage) and prompts upgrade if legacy order tables are detected

### Product Table Generator
- Guided UI steps:
  1. Choose product categories
  2. Load all attributes in the category (including global attributes and custom taxonomies, e.g., brand)
  3. Multi-select and drag to sort columns
  4. Choose a table style
- Columns: product name (link to single product), thumbnail, SKU, custom attributes, hierarchical categories, stock status, and more
- Features: pagination, column sorting, frontend search, optional CSV export
- Preset styles: minimal, striped, card
- Fully translatable strings; RTL-friendly headers

### Display Options
- Shortcode:

```
[b2bpress_table id="123" style="striped" category="widgets" per_page="50"]
```

- Elementor Widget: live preview in editor; create/select tables directly

### Data Sync and Caching
- Listens to product change hooks; invalidates cache precisely when needed
- Cache layers: WordPress Object Cache (keys include category + attribute hash); Transients as fallback; automatically uses Redis/Memcached if enabled
- Admin tools: "Refresh Tables Now" button; WP-Cron scheduled rebuilds

### Permissions and Roles
- Only users with `manage_woocommerce` or custom capability `manage_b2bpress` can edit tables
- Optional "logged-in only" visibility and role-based column hiding

### Developer Extensibility
- Action hooks: `b2bpress_table_before_render`, `b2bpress_table_after_render`
- Filters: `b2bpress_column_value_$attribute_slug`, `b2bpress_table_styles`
- REST API: `/wp-json/b2bpress/v1/tables` (CRUD) for headless frontends and mobile apps

## Requirements

- WordPress 6.5+
- WooCommerce 8.7+
- PHP 8.0+

## Installation

1. Download the plugin ZIP
2. In WordPress Admin, go to Plugins > Add New
3. Click "Upload Plugin" and select the ZIP
4. Install and activate
5. Go to "B2BPress" > "Setup Wizard" and complete initial configuration

## Usage

### Create a Table
1. Go to "B2BPress" > "Tables"
2. Click "Add Table"
3. Enter a title and choose categories
4. Select columns and drag to reorder
5. Choose a style
6. Click "Create Table"

### Display a Table in a Page or Post

Use the shortcode:

```
[b2bpress_table id="123"]
```

Optional parameters:
- `id`: table ID (required)
- `style`: table style (`default`, `striped`, `card`)
- `per_page`: products per page

### Use in Elementor
1. In the editor, add the "B2B Product Table" widget
2. Select the table in the widget settings
3. Adjust style and options as needed

## Support

If you have questions or need help, please contact our support team.

## License

GPL v2 or later

## Changelog

### 1.0.1 - 2025-08-11
- Fix: Removed duplicate global activation/deactivation hooks to avoid running activation logic twice
- Security: Escaped table cell output and allowlisted HTML to reduce XSS risk
- Performance/Stability: Replaced full object cache flush with precise prefix/group-based invalidation
- UX: Added accessibility attributes to table headers and pagination; safer pre-rendered output

### 1.0.0 - 2025-08-11
- Initial release
- WooCommerce Lite Mode (toggle cart/checkout/coupons/stock/prices/marketing/emails/frontend CSS/JS)
- Product Table Generator (wizard UI, multi-column/sorting, pagination, search, optional CSV export)
- Elementor widget (live preview, create/select tables)
- Caching and sync (object cache + transients, WP-Cron scheduled rebuilds, admin one-click refresh)
- Permissions and visibility (capability-gated editing, logged-in visibility, role-based column hiding)
- Developer hooks and filters
- REST API `/wp-json/b2bpress/v1/tables` (CRUD)
- HPOS compatibility declaration

