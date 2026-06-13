<?php
/**
 * Default settings, merged under the option key `tiers_settings`.
 *
 * The feature ships disabled with no tiers; the merchant opts in and
 * configures tiers from the Tiers admin screen.
 *
 * @package Tiers
 *
 * @return array{enabled:bool,tiers:list<array{min_qty:int,discount_percent:float}>}
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    'enabled' => false,
    'tiers'   => [],
];
