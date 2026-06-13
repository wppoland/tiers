<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Tiers\Contract\HasHooks.
 *
 * Admin-only classes are included only when running in wp-admin context.
 *
 * @package Tiers
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Tiers\Admin\Settings;
use Tiers\Service\TiersService;

defined('ABSPATH') || exit;

$hooks = [
    TiersService::class,
];

if (is_admin()) {
    $hooks[] = Settings::class;
}

return $hooks;
