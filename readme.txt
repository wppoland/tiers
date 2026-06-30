=== Plogins Tiers for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, volume pricing, quantity discount, bulk pricing, tiered pricing
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Volume pricing tiers for WooCommerce. Set quantity discount bands and show a server-rendered pricing table on product pages. No jQuery.

== Description ==

Tiers gives a WooCommerce store quantity-based pricing. You set the breakpoints, buy 5, save 5%; buy 10, save 10%, and the discount is taken off the line item the moment a shopper carts enough units. The same breakpoints are shown as a table on the product page so people can see the price they'd pay before they add to cart.

The discount is calculated in PHP on `woocommerce_before_calculate_totals`, so the pricing logic ships no front-end JavaScript. The product-page table is a plain HTML `<table>` printed server-side with `<th scope>` and a `<caption>`, so it reads correctly to screen readers and doesn't shift the layout as the page loads.

When a quantity matches more than one tier, the deepest qualifying tier applies, 12 units takes the "10+" price, not the "5+" price. Tiers never raises a price either, so a product that's already on sale keeps its lower price.

Tiers declares compatibility with WooCommerce HPOS and the Cart/Checkout Blocks. It stores everything in a single `wp_options` row and creates no custom tables, so deleting the plugin leaves the database as it was.

**What you get**

* Any number of global pricing tiers, each a minimum quantity and a discount percentage (with an optional label)
* Automatic discounting in the cart, with the highest matching tier winning
* A pricing table on single product pages, with a choice of where it appears: product summary, before or after the add-to-cart form, the product meta area, or nowhere automatic
* A `[tiers_table]` shortcode and a "Volume pricing table" block for dropping the table in by hand
* An optional heading above the table and an optional "You save" column
* An optional "You save" note under each discounted line in the cart
* An admin tier builder that adds and removes rows in place, with a live preview of how each tier reads
* A Polish translation, plus a bundled POT file for translating into other languages (text domain `plogins-tiers`)
* A `tiers_product_tiers` filter that lets Tiers PRO swap in per-product or role-based tiers

**Documentation:** https://plogins.com/tiers/docs/

== Installation ==

1. Install and activate WooCommerce (8.0 or later).
2. Upload the `plogins-tiers` folder to `/wp-content/plugins/`, or grab a copy from https://github.com/wppoland/plogins-tiers.
3. Activate the plugin through the **Plugins** screen.
4. Go to **WooCommerce → Tiers** and add at least one pricing tier (e.g. 5 units → 5% off).
5. The pricing table appears automatically on product pages, and discounts apply in the cart.

== Frequently Asked Questions ==

= Documentation and links =

* **Documentation** - https://plogins.com/tiers/docs/
* **Plugin page** - https://plogins.com/tiers/
* **Source code** - https://github.com/wppoland/plogins-tiers
* **Bug reports and feature requests** - https://github.com/wppoland/plogins-tiers/issues


= Does Tiers require WooCommerce? =
Yes. Tiers is a WooCommerce extension and requires WooCommerce 8.0 or later.

= Does the pricing table reload the page? =
No. The pricing table is server-rendered, it loads with the page, before any JavaScript runs. There is no AJAX or hydration step.

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

1. Volume pricing table on a product page, shows quantity ranges, discount percentages, and resulting prices.
2. Admin settings page, tier builder with add/remove rows and a show/hide table toggle.

== External Services ==

Tiers does not connect to any external services. Pricing tiers are stored in a single `tiers_settings` row in your WordPress options table, and the discount is calculated in PHP on your own server, no data ever leaves your site. The plugin sends no email and makes no remote requests; the product-page pricing table is rendered locally from those stored tiers.

== Development ==

Tiers is developed in the open. The PHP, JS, and CSS you install are the same files in the repository, nothing is minified or generated by a build step. Read the code, file a bug, or send a patch at https://github.com/wppoland/plogins-tiers.

== Changelog ==

= 0.2.1 =
* Renamed to Plogins Tiers for WooCommerce for a more distinctive plugin name.

= 0.2.0 =
* New: configurable table placement (product summary, before/after add-to-cart form, product meta, or manual only).
* New: `[tiers_table]` shortcode and a "Volume pricing table" block for placing the table anywhere.
* New: optional custom table heading.
* New: optional "You save" column in the pricing table.
* New: optional per-line "You save" note in the cart.
* New: translation support, with a Domain Path of `/languages`, a bundled `tiers.pot`, and a Polish translation.
* Fix: define the missing `Tiers\PLUGIN_DIR` constant so the plugin boots reliably.
* Housekeeping: removed an unused template; expanded coding-standards coverage to templates and blocks.

= 0.1.0 =
* Initial release: global volume pricing tiers, server-rendered pricing table on product pages, admin tier builder, `tiers/product_tiers` filter for PRO overrides. No jQuery, no layout shift, WCAG 2.2 AA.
