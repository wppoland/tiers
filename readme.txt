=== Tiers ===
Contributors: motylanogha
Tags: woocommerce, volume pricing, quantity discount, bulk pricing, tiered pricing
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessible quantity / volume pricing tiers for WooCommerce. Set discount bands and show a clean, server-rendered pricing table. No jQuery.

== Description ==

Tiers adds volume pricing to WooCommerce products: define quantity thresholds and the discount to apply when a customer adds that many units to the cart. A server-rendered pricing table shows the available discounts directly on the product page.

**Why Tiers?**

* **No jQuery.** Discounts are computed in PHP on `woocommerce_before_calculate_totals`. Zero front-end JavaScript required for pricing logic.
* **Server-rendered pricing table.** The pricing table is a plain HTML `<table>` rendered in PHP. No hydration, no layout shift, no waiting.
* **WCAG 2.2 AA.** The pricing table uses proper `<th scope>` and `<caption>` elements. Screen readers announce quantities and prices correctly.
* **Highest-tier wins logic.** A customer buying 12 units gets the "10+ units" discount, not the "5+ units" discount. Clean, predictable, no surprises.
* **No conflicts with WooCommerce coupons.** The discount is applied as a modified line-item price, which WooCommerce totals recalculate correctly.
* **Compatible with HPOS and Cart/Checkout Blocks.** No reliance on legacy WooCommerce internals.
* **Clean uninstall.** No custom tables. Remove the plugin and your database is exactly as it was.

**Features**

* Define unlimited global pricing tiers (min quantity → discount percentage)
* Server-rendered volume pricing table on single product pages
* Tiers sorted and applied automatically — highest matching tier wins
* Choose where the table appears: product summary, before/after the add-to-cart form, the product meta area, or place it manually
* `[tiers_table]` shortcode and a "Volume pricing table" block for manual placement anywhere
* Optional custom table heading and an optional "You save" column
* Optional per-line "You save" note under each discounted cart item
* Optional table visibility toggle
* Admin settings with a live tier builder (add / remove rows)
* Fully translatable (Text Domain `tiers`, translations in `/languages`)
* `tiers/product_tiers` filter for per-product or role-based overrides (PRO)

**Documentation:** https://plogins.com/tiers/docs/

== Installation ==

1. Install and activate WooCommerce (8.0 or later).
2. Upload the `tiers` folder to `/wp-content/plugins/` or install directly from the WordPress plugin directory.
3. Activate the plugin through the **Plugins** screen.
4. Go to **WooCommerce → Tiers** and add at least one pricing tier (e.g. 5 units → 5% off).
5. The pricing table appears automatically on product pages, and discounts apply in the cart.

== Frequently Asked Questions ==

= Does Tiers require WooCommerce? =
Yes. Tiers is a WooCommerce extension and requires WooCommerce 8.0 or later.

= Does the pricing table reload the page? =
No. The pricing table is server-rendered — it loads with the page, before any JavaScript runs. There is no AJAX or hydration step.

= How does the "highest tier wins" logic work? =
If a customer has 12 units of a product in their cart, and you have tiers for "5+" (5% off) and "10+" (10% off), they receive 10% off. The tiers are evaluated from lowest to highest min_qty and the last match wins.

= Can I apply different tiers to different products? =
In the free version, tiers are global (applied to all products). Tiers PRO adds per-product tier overrides via the product edit screen.

= Are discounts compatible with WooCommerce coupons? =
Yes. Tiers modifies the cart line-item price before WooCommerce calculates totals, so standard WooCommerce coupons work normally on top of the tiered price.

= What happens when I deactivate the plugin? =
Discounts stop being applied and the pricing table no longer appears. Your settings are retained in the database.

= What happens when I delete the plugin? =
The uninstall routine removes the `tiers_settings` option. No custom tables are created, so your database is left clean.

= Does it work with taxes? =
Yes. Tiers modifies prices before WooCommerce's tax calculation, so WooCommerce's own tax logic applies to the discounted price.

== Screenshots ==

1. Volume pricing table on a product page — shows quantity ranges, discount percentages, and resulting prices.
2. Admin settings page — tier builder with add/remove rows and a show/hide table toggle.

== Development ==

The full source (PHP, JS, CSS) is publicly available at:

https://github.com/wppoland/tiers

There is no build step. All shipped assets are the source. There is no obfuscation.

== Changelog ==

= 0.2.0 =
* New: configurable table placement (product summary, before/after add-to-cart form, product meta, or manual only).
* New: `[tiers_table]` shortcode and a "Volume pricing table" block for placing the table anywhere.
* New: optional custom table heading.
* New: optional "You save" column in the pricing table.
* New: optional per-line "You save" note in the cart.
* New: full internationalisation — Domain Path `/languages`, bundled `tiers.pot`, and `load_plugin_textdomain()`.
* Fix: define the missing `Tiers\PLUGIN_DIR` constant so the plugin boots reliably.
* Housekeeping: removed an unused template; expanded coding-standards coverage to templates and blocks.

= 0.1.0 =
* Initial release: global volume pricing tiers, server-rendered pricing table on product pages, admin tier builder, `tiers/product_tiers` filter for PRO overrides. No jQuery, no layout shift, WCAG 2.2 AA.
