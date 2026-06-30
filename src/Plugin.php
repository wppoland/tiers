<?php
/**
 * Main plugin orchestrator.
 *
 * Wires the DI container and boots every HasHooks service listed in
 * config/hooks.php, then fires the `tiers/booted` action so other code can
 * extend the plugin without modifying core files.
 *
 * @package Tiers
 */

declare(strict_types=1);

namespace Plogins\Tiers;

defined( 'ABSPATH' ) || exit;

use Plogins\Tiers\Contract\HasHooks;

/**
 * Plugin singleton.
 */
final class Plugin {

	/**
	 * Shared singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Whether the plugin has been booted already.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Private constructor — use Plugin::instance() instead.
	 */
	private function __construct() {
		$this->container = new Container();
	}

	/**
	 * Returns the shared plugin instance, creating it on first call.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Returns the DI container.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Absolute path to the plugin directory (with optional relative segment appended).
	 *
	 * @param string $relative Optional relative path to append.
	 */
	public function path( string $relative = '' ): string {
		return PLUGIN_DIR . ( '' !== $relative ? '/' . ltrim( $relative, '/' ) : '' );
	}

	/**
	 * URL to the plugin directory (with optional relative segment appended).
	 *
	 * @param string $relative Optional relative path to append.
	 */
	public function url( string $relative = '' ): string {
		return plugins_url( $relative, PLUGIN_FILE );
	}

	/**
	 * Boot the plugin: register services, then register hooks.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		// Register service factories.
		( require PLUGIN_DIR . '/config/services.php' )( $this->container );

		// Boot hook subscribers in declared order.
		/**
		 * List of HasHooks service class names to boot.
		 *
		 * @var array<class-string<HasHooks>> $tiers_hooks
		 */
		$tiers_hooks = require PLUGIN_DIR . '/config/hooks.php';
		foreach ( $tiers_hooks as $hook_class ) {
			$service = $this->container->get( $hook_class );
			if ( $service instanceof HasHooks ) {
				$service->registerHooks();
			}
		}

		/**
		 * Fires after Tiers has fully booted.
		 *
		 * Other code can hook onto this action to extend the plugin without
		 * modifying core files.
		 *
		 * @since 0.1.0
		 * @param Plugin $plugin The booted plugin instance.
		 */
		do_action( 'tiers/booted', $this );
	}
}
