<?php
/**
 * Tiers hooks configuration.
 *
 * Ordered list of HasHooks services to boot during plugin initialisation.
 * Admin-only services are appended only when running in wp-admin.
 *
 * @package Tiers
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Plogins\Tiers\Admin\Settings;
use Plogins\Tiers\Service\TiersService;

$tiers_hooks = array(
	TiersService::class,
);

if ( is_admin() ) {
	$tiers_hooks[] = Settings::class;
}

return $tiers_hooks;
