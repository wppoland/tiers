<?php
/**
 * DI container.
 *
 * Lightweight dependency-injection container. Supports singleton and factory
 * bindings with no external dependencies.
 *
 * @package Tiers
 */

declare(strict_types=1);

namespace Plogins\Tiers;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Minimal service container.
 */
final class Container {

	/**
	 * Registered factory callables.
	 *
	 * @var array<class-string, callable>
	 */
	private array $factories = array();

	/**
	 * Cached singleton instances.
	 *
	 * @var array<class-string, object>
	 */
	private array $singletons = array();

	/**
	 * Flags for singleton bindings.
	 *
	 * @var array<class-string, true>
	 */
	private array $shared = array();

	/**
	 * Register a service as a singleton (created once, shared on subsequent calls).
	 *
	 * @template T of object
	 * @param class-string<T> $id      Binding key.
	 * @param callable(): T   $factory Factory that creates the instance.
	 */
	public function singleton( string $id, callable $factory ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$this->factories[ $id ] = $factory;
		$this->shared[ $id ]    = true;
	}

	/**
	 * Register a service as a factory (new instance on every call).
	 *
	 * @template T of object
	 * @param class-string<T> $id      Binding key.
	 * @param callable(): T   $factory Factory that creates the instance.
	 */
	public function bind( string $id, callable $factory ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$this->factories[ $id ] = $factory;
		unset( $this->shared[ $id ] );
	}

	/**
	 * Resolve a registered service from the container.
	 *
	 * @template T of object
	 * @param class-string<T> $id Binding key.
	 * @return T
	 * @throws InvalidArgumentException When the service has not been registered.
	 */
	public function get( string $id ): object { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		if ( isset( $this->singletons[ $id ] ) ) {
			/**
			 * Cached singleton instance.
			 *
			 * @var T
			 */
			return $this->singletons[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Service "%s" is not registered in the container.', esc_html( $id ) ),
			);
		}

		$instance = ( $this->factories[ $id ] )();

		if ( isset( $this->shared[ $id ] ) ) {
			$this->singletons[ $id ] = $instance;
		}

		/**
		 * Freshly created instance.
		 *
		 * @var T
		 */
		return $instance;
	}

	/**
	 * Returns true if the given class is registered in the container.
	 *
	 * @param class-string $id Binding key.
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->singletons[ $id ] );
	}
}
