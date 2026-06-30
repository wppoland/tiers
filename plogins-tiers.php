<?php
/**
 * Plugin Name:       Plogins Tiers for WooCommerce
 * Plugin URI:        https://plogins.com/plogins-tiers/
 * Description:        Volume and quantity-based tiered pricing for WooCommerce
 * Version:           0.2.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WPPoland.com
 * Author URI:        https://wppoland.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plogins-tiers
 * Domain Path:       /languages
 * WC requires at least: 8.0
 *
 * @package Tiers
 */

declare(strict_types=1);

namespace Plogins\Tiers;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.2.1';
const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

define( 'TIERS_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIERS_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/autoload.php';

// HPOS + cart/checkout blocks compatibility.
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'Tiers requires WooCommerce to be active.', 'plogins-tiers' );
					echo '</p></div>';
				}
			);
			return;
		}

		add_action(
			'init',
			static function (): void {
				Plugin::instance()->boot();
			},
			0
		);
	},
	10
);
