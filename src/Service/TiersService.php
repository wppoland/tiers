<?php
/**
 * Volume pricing tiers service.
 *
 * @package Tiers\Service
 */

declare(strict_types=1);

namespace Plogins\Tiers\Service;

defined( 'ABSPATH' ) || exit;

use Plogins\Tiers\Contract\HasHooks;
use Plogins\Tiers\Util\TemplateLoader;

/**
 * Volume / quantity-based pricing tiers for WooCommerce.
 *
 * Reads tiers from the `tiers_settings` option, applies line-item discounts
 * in the cart, and renders a pricing table on the product page.
 *
 * The highest matching tier wins (not cumulative).
 *
 * @package Tiers\Service
 */
final class TiersService implements HasHooks {

	private const OPTION = 'tiers_settings';

	/**
	 * Constructor.
	 *
	 * @param TemplateLoader $template_loader Template loader utility.
	 */
	public function __construct(
		private readonly TemplateLoader $template_loader,
	) {}

	/**
	 * Supported placement hooks for the product-page pricing table.
	 *
	 * Maps a stored placement key to a WooCommerce single-product action hook
	 * and its priority. `shortcode` is intentionally absent: it disables the
	 * automatic placement so the table only renders via the block/shortcode.
	 *
	 * @return array<string, array{hook: string, priority: int}>
	 */
	public static function placements(): array {
		return array(
			'summary'      => array(
				'hook'     => 'woocommerce_single_product_summary',
				'priority' => 26,
			),
			'before_cart'  => array(
				'hook'     => 'woocommerce_before_add_to_cart_form',
				'priority' => 10,
			),
			'after_cart'   => array(
				'hook'     => 'woocommerce_after_add_to_cart_form',
				'priority' => 10,
			),
			'product_meta' => array(
				'hook'     => 'woocommerce_product_meta_start',
				'priority' => 5,
			),
		);
	}

	/**
	 * Register WordPress hooks.
	 */
	public function registerHooks(): void {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_cart_discounts' ), 25 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$settings  = $this->global_settings();
		$placement = (string) ( $settings['placement'] ?? 'summary' );

		if ( ( $settings['show_table'] ?? true ) && 'shortcode' !== $placement ) {
			$placements = self::placements();
			$target     = $placements[ $placement ] ?? $placements['summary'];
			add_action( $target['hook'], array( $this, 'render_pricing_table' ), $target['priority'] );
		}

		// Per-line savings note in the cart (display only).
		if ( $settings['cart_savings_note'] ?? false ) {
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'render_cart_savings_note' ), 10, 3 );
		}

		// Shortcode + block render callback (always available).
		add_shortcode( 'tiers_table', array( $this, 'render_shortcode' ) );

		// Boot runs on `init`; register the block on the same hook. If we are
		// already inside init, hook a slightly later priority so it still fires.
		if ( did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'register_block' ), 20 );
		} else {
			add_action( 'init', array( $this, 'register_block' ) );
		}
	}

	/**
	 * Register the server-rendered Tiers table block.
	 */
	public function register_block(): void {
		$metadata = \Plogins\Tiers\PLUGIN_DIR . '/blocks/tiers-table';

		if ( function_exists( 'register_block_type' ) && file_exists( $metadata . '/block.json' ) ) {
			register_block_type(
				$metadata,
				array( 'render_callback' => array( $this, 'render_block' ) )
			);
		}
	}

	/**
	 * Register and conditionally enqueue front-end assets (CSS only, no jQuery).
	 *
	 * The style is registered unconditionally so the shortcode/block can enqueue
	 * it on demand, and auto-enqueued on single product pages when a table will
	 * be shown.
	 */
	public function enqueue_assets(): void {
		$this->register_style();

		if ( ! is_product() ) {
			return;
		}

		$tiers = $this->get_active_tiers();

		if ( empty( $tiers ) || ! ( $this->global_settings()['show_table'] ?? true ) ) {
			return;
		}

		wp_enqueue_style( 'tiers-pricing' );
	}

	/**
	 * Register the front-end style if it has not been registered yet.
	 *
	 * Registration normally happens on `wp_enqueue_scripts`, but shortcode and
	 * block render paths can run before (or without) that hook — e.g. a block
	 * server-render during a REST editor preview. Registering on demand keeps
	 * enqueue_style() from silently no-opping in those contexts.
	 */
	private function register_style(): void {
		if ( wp_style_is( 'tiers-pricing', 'registered' ) ) {
			return;
		}

		wp_register_style(
			'tiers-pricing',
			\Plogins\Tiers\Plugin::instance()->url( 'assets/css/tiers.css' ),
			array(),
			\Plogins\Tiers\VERSION,
		);
	}

	/**
	 * Apply the highest matching tier discount to each cart line item.
	 *
	 * @param \WC_Cart $cart The WooCommerce cart instance.
	 */
	public function apply_cart_discounts( \WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$qty = (int) ( $item['quantity'] ?? 0 );

			if ( $qty <= 0 ) {
				continue;
			}

			$tiers = $this->get_active_tiers_for_product( $product );

			if ( empty( $tiers ) ) {
				continue;
			}

			$matching_tier = $this->find_matching_tier( $tiers, $qty, $product );

			if ( null === $matching_tier ) {
				continue;
			}

			$percent = (float) ( $matching_tier['discount_percent'] ?? 0 );

			if ( $percent <= 0 ) {
				continue;
			}

			$regular = (float) $product->get_regular_price();

			if ( $regular <= 0 ) {
				$regular = (float) $product->get_price();
			}

			if ( $regular <= 0 ) {
				continue;
			}

			$discounted = round( $regular * ( 1.0 - $percent / 100.0 ), wc_get_price_decimals() );

			// Never raise the price: if the product is already cheaper (e.g. on
			// sale, or a deeper tier already ran in an earlier pass of this
			// multi-fire hook), leave the lower price in place. This also keeps
			// the operation idempotent and avoids double-discounting.
			$current = (float) $product->get_price();

			if ( $current > 0 && $discounted >= $current ) {
				continue;
			}

			$product->set_price( (string) $discounted );
		}
	}

	/**
	 * Render the pricing table on single product pages (auto placement).
	 */
	public function render_pricing_table(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$settings = $this->global_settings();

		if ( ! ( $settings['show_table'] ?? true ) ) {
			return;
		}

		echo $this->get_table_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped inside the template.
	}

	/**
	 * Render the `[tiers_table]` shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string Rendered table HTML, or empty string when nothing to show.
	 */
	public function render_shortcode( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array( 'product_id' => 0 ),
			is_array( $atts ) ? $atts : array(),
			'tiers_table',
		);

		$product = $this->resolve_product( (int) $atts['product_id'] );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$this->register_style();
		wp_enqueue_style( 'tiers-pricing' );

		return $this->get_table_html( $product );
	}

	/**
	 * Render the Tiers table block (server-side).
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Rendered table HTML.
	 */
	public function render_block( array $attributes = array() ): string {
		$product_id = isset( $attributes['productId'] ) ? (int) $attributes['productId'] : 0;
		$product    = $this->resolve_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$this->register_style();
		wp_enqueue_style( 'tiers-pricing' );

		return $this->get_table_html( $product );
	}

	/**
	 * Append a per-line "You save" note to the cart item subtotal.
	 *
	 * Display only — no pricing logic. The discount itself is applied in
	 * apply_cart_discounts().
	 *
	 * @param string               $subtotal  Formatted subtotal HTML.
	 * @param array<string, mixed> $cart_item Cart item data.
	 * @param string               $cart_item_key Cart item key (unused).
	 * @return string
	 */
	public function render_cart_savings_note( string $subtotal, array $cart_item, string $cart_item_key ): string {
		unset( $cart_item_key );

		$product = $cart_item['data'] ?? null;
		$qty     = (int) ( $cart_item['quantity'] ?? 0 );

		if ( ! $product instanceof \WC_Product || $qty <= 0 ) {
			return $subtotal;
		}

		$tiers = $this->get_active_tiers_for_product( $product );
		$match = empty( $tiers ) ? null : $this->find_matching_tier( $tiers, $qty, $product );

		if ( null === $match ) {
			return $subtotal;
		}

		$regular = (float) $product->get_regular_price();

		if ( $regular <= 0 ) {
			return $subtotal;
		}

		$current = (float) $product->get_price();
		$saved   = round( ( $regular - $current ) * $qty, wc_get_price_decimals() );

		if ( $saved <= 0 ) {
			return $subtotal;
		}

		return $subtotal . '<br /><small class="tiers-cart-savings">' . sprintf(
			/* translators: %s: formatted saved amount */
			esc_html__( 'You save %s', 'plogins-tiers' ),
			wp_kses_post( wc_price( $saved ) )
		) . '</small>';
	}

	/**
	 * Build the pricing-table HTML for a product (shared by all render paths).
	 *
	 * @param \WC_Product $product The product to render the table for.
	 * @return string Captured template output, or empty string when no tiers.
	 */
	private function get_table_html( \WC_Product $product ): string {
		$tiers = $this->get_active_tiers_for_product( $product );

		if ( empty( $tiers ) ) {
			return '';
		}

		$settings = $this->global_settings();

		ob_start();
		$this->template_loader->include(
			'single-product/pricing-table',
			array(
				'product'      => $product,
				'tiers'        => $tiers,
				'heading'      => (string) ( $settings['table_heading'] ?? '' ),
				'show_savings' => (bool) ( $settings['show_savings'] ?? false ),
			),
		);

		return (string) ob_get_clean();
	}

	/**
	 * Resolve a product from an explicit id, falling back to the global product.
	 *
	 * @param int $product_id Explicit product id, or 0 to use the current product.
	 * @return \WC_Product|null
	 */
	private function resolve_product( int $product_id ): ?\WC_Product {
		if ( $product_id > 0 ) {
			$product = wc_get_product( $product_id );
			return $product instanceof \WC_Product ? $product : null;
		}

		global $product;

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Returns all configured pricing tiers, sorted by min_qty ASC.
	 *
	 * @return list<array{min_qty: int, discount_percent: float, label: string}>
	 */
	public function get_active_tiers(): array {
		$settings = $this->global_settings();
		$raw      = $settings['tiers'] ?? array();

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return array();
		}

		$tiers = array();

		foreach ( $raw as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}

			$min_qty = (int) ( $tier['min_qty'] ?? 0 );
			$percent = (float) ( $tier['discount_percent'] ?? 0 );

			if ( $min_qty <= 0 || $percent <= 0 || $percent > 100 ) {
				continue;
			}

			$sanitized_tier = array(
				'min_qty'          => $min_qty,
				'discount_percent' => $percent,
				'label'            => sanitize_text_field( (string) ( $tier['label'] ?? '' ) ),
			);

			/**
			 * Filter a single active pricing tier row.
			 *
			 * PRO uses this to load extra parameters (e.g. allowed_roles).
			 *
			 * @param array{min_qty: int, discount_percent: float, label: string} $sanitized_tier Sanitized tier.
			 * @param array<string, mixed>                                         $tier           Raw tier data.
			 */
			$tiers[] = apply_filters( 'tiers_active_tier', $sanitized_tier, $tier );
		}

		usort( $tiers, static fn( array $a, array $b ): int => $a['min_qty'] <=> $b['min_qty'] );

		return $tiers;
	}

	/**
	 * Returns tiers for a specific product (allows PRO overrides via filter).
	 *
	 * @param \WC_Product $product The product being viewed or carted.
	 * @return list<array{min_qty: int, discount_percent: float, label: string}>
	 */
	public function get_active_tiers_for_product( \WC_Product $product ): array {
		/**
		 * Filter the pricing tiers for a specific product.
		 *
		 * PRO uses this to inject per-product tier overrides.
		 *
		 * @param list<array{min_qty: int, discount_percent: float, label: string}> $tiers   Global tiers.
		 * @param \WC_Product                                                        $product The product.
		 */
		return (array) apply_filters( 'tiers_product_tiers', $this->get_active_tiers(), $product );
	}

	/**
	 * Finds the highest-matching tier for the given quantity.
	 *
	 * @param list<array{min_qty: int, discount_percent: float, label: string}> $tiers   Tiers sorted ASC by min_qty.
	 * @param int                                                               $qty     Cart item quantity.
	 * @param \WC_Product                                                       $product The product (used for per-product overrides).
	 * @return array{min_qty: int, discount_percent: float, label: string}|null
	 */
	private function find_matching_tier( array $tiers, int $qty, \WC_Product $product ): ?array {
		$match = null;

		foreach ( $tiers as $tier ) {
			if ( $qty >= $tier['min_qty'] ) {
				$match = $tier;
			}
		}

		return $match;
	}

	/**
	 * Returns the raw settings array from the database.
	 *
	 * @return array<string, mixed>
	 */
	private function global_settings(): array {
		$settings = get_option( self::OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}
}
