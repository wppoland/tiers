<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Keep services thin; product logic lives in storefront-kit engines
 * instantiated here with this plugin's text-domain / option prefix / asset URLs.
 *
 * @package Tiers
 */

declare(strict_types=1);

use Tiers\Admin\Settings;
use Tiers\Container;
use Tiers\Migrator;
use Tiers\Service\TiersService;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    // Thin adapter over the storefront-kit DynamicPricingEngine.
    $c->singleton(TiersService::class, static fn (): TiersService => new TiersService());

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
    }
};
