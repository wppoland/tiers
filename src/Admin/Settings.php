<?php
/**
 * Admin settings page for Tiers.
 *
 * @package Tiers\Admin
 */

declare(strict_types=1);

namespace Plogins\Tiers\Admin;

defined( 'ABSPATH' ) || exit;

use Plogins\Tiers\Contract\HasHooks;
use Plogins\Tiers\Service\TiersService;

/**
 * Admin settings page for Tiers, registered under the WooCommerce menu.
 *
 * Settings are stored in `tiers_settings` (array):
 *  - tiers:       array of {min_qty, discount_percent, label}
 *  - show_table:  bool — show pricing table on product page
 *
 * @package Tiers\Admin
 */
final class Settings implements HasHooks {

	private const OPTION  = 'tiers_settings';
	private const PAGE    = 'tiers-settings';
	private const SECTION = 'tiers_general';

	/**
	 * Constructor.
	 *
	 * @param TiersService $tiers_service The core tiers service.
	 */
	public function __construct(
		private readonly TiersService $tiers_service,
	) {}

	/**
	 * Register WordPress hooks.
	 */
	public function registerHooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the WooCommerce submenu page.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Tiers Settings', 'plogins-tiers' ),
			__( 'Tiers', 'plogins-tiers' ),
			'manage_woocommerce',
			self::PAGE,
			array( $this, 'render_page' ),
		);
	}

	/**
	 * Enqueue the admin tier-builder JS on the settings page only.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE ) ) {
			return;
		}

		wp_enqueue_style(
			'tiers-admin',
			\Plogins\Tiers\Plugin::instance()->url( 'assets/css/admin.css' ),
			array(),
			\Plogins\Tiers\VERSION,
		);

		wp_enqueue_script(
			'tiers-admin',
			\Plogins\Tiers\Plugin::instance()->url( 'assets/js/admin-tiers.js' ),
			array(),
			\Plogins\Tiers\VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			),
		);

		wp_localize_script(
			'tiers-admin',
			'tiersAdmin',
			array(
				'i18n' => array(
					'remove'        => __( 'Remove', 'plogins-tiers' ),
					'minQtyLabel'   => __( 'Minimum quantity', 'plogins-tiers' ),
					'discountLabel' => __( 'Discount percent', 'plogins-tiers' ),
					'labelLabel'    => __( 'Label', 'plogins-tiers' ),
					'previewEmpty'  => __( 'Add a tier above to preview how it reads to shoppers.', 'plogins-tiers' ),
					/* translators: %d: minimum quantity (kept as a literal token for JS substitution). */
					'previewQty'    => __( 'Buy %d+', 'plogins-tiers' ),
				),
			),
		);
	}

	/**
	 * Render an accessible inline help affordance ("?") paired with a tooltip.
	 *
	 * The tooltip body is a real element wired via the Popover API / title
	 * fallback in JS, and linked with aria-describedby. Escapes all output.
	 *
	 * @param string $id   Unique tooltip id (also used as the popover target).
	 * @param string $text Help text shown in the tooltip.
	 */
	private function help_icon( string $id, string $text ): void {
		printf(
			'<button type="button" class="tiers-help" data-tip="%1$s" aria-label="%2$s">?</button>',
			esc_attr( $id ),
			esc_attr__( 'More information', 'plogins-tiers' ),
		);
		printf(
			'<span id="%1$s" class="tiers-tip" role="tooltip" popover="auto">%2$s</span>',
			esc_attr( $id ),
			esc_html( $text ),
		);
	}

	/**
	 * Register the settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			),
		);

		add_settings_section(
			self::SECTION,
			__( 'Global Volume Pricing Tiers', 'plogins-tiers' ),
			static function (): void {
				echo '<div class="tiers-settings__intro">';
				echo '<h2>' . esc_html__( 'Reward bigger orders, automatically', 'plogins-tiers' ) . '</h2>';
				echo '<p>' . esc_html__(
					'Set quantity thresholds and the discount each one unlocks. When a shopper adds enough of a product to their cart, the matching discount is applied automatically, no coupon codes needed. Tiers apply to every product; per-product overrides are available in Tiers PRO.',
					'plogins-tiers',
				) . '</p>';
				echo '</div>';
			},
			self::PAGE,
		);

		add_settings_field(
			'show_table',
			__( 'Show pricing table', 'plogins-tiers' ),
			array( $this, 'render_show_table' ),
			self::PAGE,
			self::SECTION,
		);

		add_settings_field(
			'placement',
			__( 'Table placement', 'plogins-tiers' ),
			array( $this, 'render_placement' ),
			self::PAGE,
			self::SECTION,
			array( 'label_for' => 'tiers_placement' ),
		);

		add_settings_field(
			'table_heading',
			__( 'Table heading', 'plogins-tiers' ),
			array( $this, 'render_table_heading' ),
			self::PAGE,
			self::SECTION,
			array( 'label_for' => 'tiers_table_heading' ),
		);

		add_settings_field(
			'show_savings',
			__( 'Savings column', 'plogins-tiers' ),
			array( $this, 'render_show_savings' ),
			self::PAGE,
			self::SECTION,
		);

		add_settings_field(
			'cart_savings_note',
			__( 'Cart savings note', 'plogins-tiers' ),
			array( $this, 'render_cart_savings_note' ),
			self::PAGE,
			self::SECTION,
		);

		add_settings_field(
			'tiers',
			__( 'Pricing tiers', 'plogins-tiers' ),
			array( $this, 'render_tiers_field' ),
			self::PAGE,
			self::SECTION,
		);
	}

	/**
	 * Available placement choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function placement_choices(): array {
		return array(
			'summary'      => __( 'Product summary (below price)', 'plogins-tiers' ),
			'before_cart'  => __( 'Before the add-to-cart form', 'plogins-tiers' ),
			'after_cart'   => __( 'After the add-to-cart form', 'plogins-tiers' ),
			'product_meta' => __( 'Product meta area', 'plogins-tiers' ),
			'shortcode'    => __( 'Only where I place it (shortcode / block)', 'plogins-tiers' ),
		);
	}

	/**
	 * Render the show_table checkbox field.
	 */
	public function render_show_table(): void {
		$options = (array) get_option( self::OPTION, array() );
		$checked = (bool) ( $options['show_table'] ?? true );
		?>
		<label for="tiers_show_table">
			<input
				type="checkbox"
				id="tiers_show_table"
				name="<?php echo esc_attr( self::OPTION ); ?>[show_table]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Display a volume pricing table on single product pages.', 'plogins-tiers' ); ?>
		</label>
		<?php
		$this->help_icon(
			'tiers-tip-show-table',
			__( 'Shows shoppers the quantity breaks and the price they unlock at each level, a proven nudge to buy more. The discount still applies in the cart even with this turned off.', 'plogins-tiers' ),
		);
	}

	/**
	 * Render the placement select field.
	 */
	public function render_placement(): void {
		$options = (array) get_option( self::OPTION, array() );
		$current = (string) ( $options['placement'] ?? 'summary' );
		?>
		<select id="tiers_placement" name="<?php echo esc_attr( self::OPTION ); ?>[placement]">
			<?php foreach ( $this->placement_choices() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->help_icon(
			'tiers-tip-placement',
			__( 'Controls where the table is auto-inserted on the product page. Pick "Only where I place it" to position it yourself with the [tiers_table] shortcode or the Volume pricing table block.', 'plogins-tiers' ),
		);
		?>
		<p class="description">
			<?php esc_html_e( 'Where the pricing table appears on the product page. Choose the last option to place it manually with the [tiers_table] shortcode or the "Volume pricing table" block.', 'plogins-tiers' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the optional table heading text field.
	 */
	public function render_table_heading(): void {
		$options = (array) get_option( self::OPTION, array() );
		$heading = (string) ( $options['table_heading'] ?? '' );
		?>
		<input
			type="text"
			id="tiers_table_heading"
			name="<?php echo esc_attr( self::OPTION ); ?>[table_heading]"
			value="<?php echo esc_attr( $heading ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g. Buy more, save more', 'plogins-tiers' ); ?>"
		/>
		<?php
		$this->help_icon(
			'tiers-tip-heading',
			__( 'A short title rendered directly above the table, e.g. "Buy more, save more". Leave it blank to show just the table.', 'plogins-tiers' ),
		);
		?>
		<p class="description">
			<?php esc_html_e( 'Optional heading shown above the pricing table. Leave blank to hide it.', 'plogins-tiers' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the show_savings checkbox field.
	 */
	public function render_show_savings(): void {
		$options = (array) get_option( self::OPTION, array() );
		$checked = (bool) ( $options['show_savings'] ?? false );
		?>
		<label for="tiers_show_savings">
			<input
				type="checkbox"
				id="tiers_show_savings"
				name="<?php echo esc_attr( self::OPTION ); ?>[show_savings]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Add a "You save" column to the pricing table.', 'plogins-tiers' ); ?>
		</label>
		<?php
		$this->help_icon(
			'tiers-tip-show-savings',
			__( 'Adds a column showing the cash saved per unit at each tier. Concrete savings figures convert better than a percentage alone.', 'plogins-tiers' ),
		);
	}

	/**
	 * Render the cart_savings_note checkbox field.
	 */
	public function render_cart_savings_note(): void {
		$options = (array) get_option( self::OPTION, array() );
		$checked = (bool) ( $options['cart_savings_note'] ?? false );
		?>
		<label for="tiers_cart_savings_note">
			<input
				type="checkbox"
				id="tiers_cart_savings_note"
				name="<?php echo esc_attr( self::OPTION ); ?>[cart_savings_note]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Show a per-line "You save" note under each discounted cart item.', 'plogins-tiers' ); ?>
		</label>
		<?php
		$this->help_icon(
			'tiers-tip-cart-note',
			__( 'Reassures shoppers in the cart by printing the exact amount saved under each qualifying line item. Reinforces the discount right before checkout.', 'plogins-tiers' ),
		);
	}

	/**
	 * Render the dynamic tier-builder table field.
	 */
	public function render_tiers_field(): void {
		$tiers     = $this->tiers_service->get_active_tiers();
		$has_tiers = ! empty( $tiers );
		?>
		<div id="tiers-builder">
			<p id="tiers-empty" class="tiers-empty"<?php echo $has_tiers ? ' hidden' : ''; ?>>
				<?php esc_html_e( 'No tiers yet. Add your first quantity break below, for example, 10% off when a shopper buys 5 or more.', 'plogins-tiers' ); ?>
			</p>
			<table class="widefat" id="tiers-table"<?php echo $has_tiers ? '' : ' hidden'; ?>>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Min. quantity', 'plogins-tiers' ); ?></th>
						<th><?php esc_html_e( 'Discount %', 'plogins-tiers' ); ?></th>
						<th><?php esc_html_e( 'Label (optional)', 'plogins-tiers' ); ?></th>
						<?php do_action( 'tiers_admin_settings_table_header' ); ?>
						<th></th>
					</tr>
				</thead>
				<tbody id="tiers-rows">
					<?php foreach ( $tiers as $i => $tier ) : ?>
						<tr>
							<td>
								<input
									type="number"
									name="<?php echo esc_attr( self::OPTION ); ?>[tiers][<?php echo esc_attr( (string) $i ); ?>][min_qty]"
									value="<?php echo esc_attr( (string) $tier['min_qty'] ); ?>"
									min="1"
									step="1"
									class="small-text"
									aria-label="<?php esc_attr_e( 'Minimum quantity', 'plogins-tiers' ); ?>"
									required
								/>
							</td>
							<td>
								<input
									type="number"
									name="<?php echo esc_attr( self::OPTION ); ?>[tiers][<?php echo esc_attr( (string) $i ); ?>][discount_percent]"
									value="<?php echo esc_attr( (string) $tier['discount_percent'] ); ?>"
									min="0.01"
									max="100"
									step="0.01"
									class="small-text"
									aria-label="<?php esc_attr_e( 'Discount percent', 'plogins-tiers' ); ?>"
									required
								/>
							</td>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr( self::OPTION ); ?>[tiers][<?php echo esc_attr( (string) $i ); ?>][label]"
									value="<?php echo esc_attr( $tier['label'] ); ?>"
									class="regular-text"
									aria-label="<?php esc_attr_e( 'Tier label (optional)', 'plogins-tiers' ); ?>"
								/>
							</td>
							<?php do_action( 'tiers_admin_settings_table_row', $tier, $i ); ?>
							<td>
								<button type="button" class="button tiers-remove-row">
									<?php esc_html_e( 'Remove', 'plogins-tiers' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="button" id="tiers-add-row" class="button">
					<?php esc_html_e( '+ Add tier', 'plogins-tiers' ); ?>
				</button>
			</p>
			<div class="tiers-preview" aria-live="polite">
				<p class="tiers-preview__title">
					<?php esc_html_e( 'Live preview', 'plogins-tiers' ); ?>
				</p>
				<div id="tiers-preview-list"></div>
			</div>
		</div>
		<p class="description">
			<?php
			esc_html_e( 'The highest matching tier wins. E.g., buying 12 units gets the "10+" discount, not the "5+" discount.', 'plogins-tiers' );
			?>
			<?php
			$this->help_icon(
				'tiers-tip-tiers',
				__( 'Minimum quantity is the smallest cart amount that unlocks the tier. Discount % is taken off the regular price. Label is optional copy a shopper sees (e.g. "Bulk deal"). Tiers are sorted automatically by quantity.', 'plogins-tiers' ),
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap tiers-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitise and normalise the incoming settings array before saving.
	 *
	 * @param mixed $raw Raw POST data.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$placement = isset( $raw['placement'] ) ? sanitize_key( (string) $raw['placement'] ) : 'summary';
		if ( ! array_key_exists( $placement, $this->placement_choices() ) ) {
			$placement = 'summary';
		}

		$sanitized = array(
			'show_table'        => ! empty( $raw['show_table'] ),
			'placement'         => $placement,
			'table_heading'     => sanitize_text_field( (string) ( $raw['table_heading'] ?? '' ) ),
			'show_savings'      => ! empty( $raw['show_savings'] ),
			'cart_savings_note' => ! empty( $raw['cart_savings_note'] ),
			'tiers'             => array(),
		);

		if ( is_array( $raw['tiers'] ?? null ) ) {
			foreach ( (array) $raw['tiers'] as $tier ) {
				if ( ! is_array( $tier ) ) {
					continue;
				}

				$min_qty = (int) ( $tier['min_qty'] ?? 0 );
				$percent = (float) ( $tier['discount_percent'] ?? 0 );
				$label   = sanitize_text_field( (string) ( $tier['label'] ?? '' ) );

				if ( $min_qty <= 0 || $percent <= 0 || $percent > 100 ) {
					continue;
				}

				$sanitized_tier = array(
					'min_qty'          => $min_qty,
					'discount_percent' => $percent,
					'label'            => $label,
				);

				/**
				 * Filter a single pricing tier row during settings sanitization.
				 *
				 * PRO uses this to sanitize and preserve the allowed_roles parameter.
				 *
				 * @param array{min_qty: int, discount_percent: float, label: string} $sanitized_tier Sanitized tier.
				 * @param array<string, mixed>                                         $tier           Raw tier data.
				 */
				$sanitized['tiers'][] = apply_filters( 'tiers_sanitize_tier', $sanitized_tier, $tier );
			}
		}

		return $sanitized;
	}
}
