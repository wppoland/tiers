=== Tiers ===
Contributors: wppoland
Tags: woocommerce, pricing, bulk pricing, volume discount, tiered pricing
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Quantity and volume tiered pricing for WooCommerce, with a server-rendered price table on the product page.

== Description ==

Tiers adds quantity/volume based pricing to WooCommerce. Define a list of
tiers — each a minimum quantity and a discount percentage — and the discount is
applied to the cart line price once the quantity reaches that threshold. When a
quantity matches more than one tier, the highest-threshold tier wins, so larger
orders always get the deepest configured discount.

A price table is rendered server-side on the single product page so shoppers can
see the volume breakpoints before they add to cart. The table is plain HTML — no
jQuery and no client-side scripting — and is computed from the product's regular
price on every cart calculation, keeping totals consistent across WooCommerce's
repeated recalculations.

Configuration lives under a top-level **Tiers** admin menu: toggle the feature
on or off and manage the repeatable tier list. Invalid or empty rows are dropped
on save, discounts are clamped to 0–100%, and tiers are sorted by quantity.

= Features =

* Quantity/volume tiered pricing for simple products.
* Repeatable tier editor (minimum quantity + discount percent).
* Server-rendered price table on the single product page (no jQuery).
* Global on/off toggle.
* HPOS and cart/checkout blocks compatible.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/tiers`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to the **Tiers** menu, enable tiered pricing, and add one or more tiers.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes.

= How is the discount calculated? =

Each tier takes its discount percentage off the product's regular price. The
highest-threshold tier that the cart quantity satisfies is the one applied.

= Will multiple tiers stack? =

No. A single best-matching tier is applied per cart line.

== Screenshots ==

1. The volume price table on a single product page.
2. The Tiers settings screen with the repeatable tier editor.

== Changelog ==

= 0.1.0 =
* Initial release: quantity/volume tiered pricing with a server-rendered price table and a settings screen.
