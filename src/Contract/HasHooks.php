<?php
/**
 * HasHooks contract interface.
 *
 * Marks a service as having WordPress action/filter registrations.
 *
 * @package Tiers\Contract
 */

declare(strict_types=1);

namespace Plogins\Tiers\Contract;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for services that register WordPress hooks.
 */
interface HasHooks {

	/**
	 * Register all WordPress actions and filters for this service.
	 */
	public function registerHooks(): void;
}
