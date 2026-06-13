<?php

declare(strict_types=1);

namespace Tiers\Admin;

defined('ABSPATH') || exit;

use Tiers\Contract\HasHooks;

/**
 * Admin settings page registered as a top-level "Tiers" menu.
 *
 * Stores settings in the `tiers_settings` option (array): a feature toggle
 * plus a repeatable list of tiers, each a min quantity + discount percent.
 * All output is escaped; all input sanitised, clamped and sorted on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'tiers_settings';
    private const PAGE   = 'tiers-settings';

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Tiers Settings', 'tiers'),
            __('Tiers', 'tiers'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
            'dashicons-chart-bar',
            58,
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        $enabled  = (bool) ($settings['enabled'] ?? false);

        /** @var list<array{min_qty:int,discount_percent:float}> $tiers */
        $tiers = $this->normaliseRows($settings['tiers'] ?? []);

        // Always offer at least one (empty) editable row.
        if ($tiers === []) {
            $tiers[] = ['min_qty' => 0, 'discount_percent' => 0.0];
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable tiered pricing', 'tiers'); ?></th>
                            <td>
                                <label for="tiers_enabled">
                                    <input
                                        type="checkbox"
                                        id="tiers_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked($enabled, true); ?>
                                    />
                                    <?php esc_html_e('Apply quantity/volume discounts and show the price table on product pages.', 'tiers'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Pricing tiers', 'tiers'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Each tier discounts the line price once the cart quantity reaches the given minimum. The highest matching tier wins. Empty or invalid rows are dropped on save.', 'tiers'); ?>
                </p>

                <table class="widefat striped" id="tiers-rows" style="max-width:640px;">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Minimum quantity', 'tiers'); ?></th>
                            <th scope="col"><?php esc_html_e('Discount (%)', 'tiers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $i => $tier) : ?>
                            <tr>
                                <td>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        name="<?php echo esc_attr(self::OPTION); ?>[tiers][<?php echo esc_attr((string) $i); ?>][min_qty]"
                                        value="<?php echo esc_attr($tier['min_qty'] > 0 ? (string) $tier['min_qty'] : ''); ?>"
                                        class="small-text"
                                    />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="<?php echo esc_attr(self::OPTION); ?>[tiers][<?php echo esc_attr((string) $i); ?>][discount_percent]"
                                        value="<?php echo esc_attr($tier['discount_percent'] > 0 ? (string) $tier['discount_percent'] : ''); ?>"
                                        class="small-text"
                                    />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php
                        // Three extra blank rows so merchants can add tiers without JS.
                        $next = count($tiers);
                        for ($j = 0; $j < 3; $j++) :
                            $index = $next + $j;
                            ?>
                            <tr>
                                <td>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        name="<?php echo esc_attr(self::OPTION); ?>[tiers][<?php echo esc_attr((string) $index); ?>][min_qty]"
                                        value=""
                                        class="small-text"
                                    />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="<?php echo esc_attr(self::OPTION); ?>[tiers][<?php echo esc_attr((string) $index); ?>][discount_percent]"
                                        value=""
                                        class="small-text"
                                    />
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitises, validates and normalises the submitted settings before save:
     * coerces the toggle to bool, drops empty/invalid tier rows, clamps the
     * discount to 0–100, and sorts tiers by ascending minimum quantity.
     *
     * @param mixed $raw
     * @return array{enabled:bool,tiers:list<array{min_qty:int,discount_percent:float}>}
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return ['enabled' => false, 'tiers' => []];
        }

        $rawTiers = isset($raw['tiers']) && is_array($raw['tiers']) ? $raw['tiers'] : [];

        return [
            'enabled' => ! empty($raw['enabled']),
            'tiers'   => $this->normaliseRows($rawTiers),
        ];
    }

    /**
     * Coerce a loose list of rows into valid, clamped, sorted tier rows.
     *
     * @param mixed $rows
     * @return list<array{min_qty:int,discount_percent:float}>
     */
    private function normaliseRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $clean = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $minQty   = isset($row['min_qty']) ? (int) $row['min_qty'] : 0;
            $discount = isset($row['discount_percent']) ? (float) $row['discount_percent'] : 0.0;

            if ($minQty <= 0 || $discount <= 0) {
                continue;
            }

            // Clamp percent to a sane 0–100 range.
            $discount = max(0.0, min(100.0, $discount));

            $clean[] = [
                'min_qty'          => $minQty,
                'discount_percent' => $discount,
            ];
        }

        usort(
            $clean,
            static fn (array $a, array $b): int => $a['min_qty'] <=> $b['min_qty'],
        );

        return array_values($clean);
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require TIERS_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }
}
