<?php
/**
 * Template loader utility.
 *
 * Resolves and includes templates with theme override support.
 * Lookup order: {theme}/tiers/{template}.php → {plugin}/templates/{template}.php
 *
 * @package Tiers\Util
 */

declare(strict_types=1);

namespace Plogins\Tiers\Util;

defined( 'ABSPATH' ) || exit;

use const Tiers\PLUGIN_DIR;

/**
 * Loads PHP templates with optional theme overrides.
 */
final class TemplateLoader {

	private const THEME_DIR = 'tiers';

	/**
	 * Include a template file, extracting $args as prefixed local variables.
	 *
	 * @param string               $template Template name (e.g. 'single-product/pricing-table').
	 * @param array<string, mixed> $args     Variables extracted into the template scope, prefixed with `tiers_`.
	 */
	public function include( string $template, array $args = array() ): void {
		$path = $this->locate( $template );

		if ( null === $path ) {
			return;
		}

		/**
		 * Filter template arguments before rendering.
		 *
		 * @param array<string, mixed> $args     Template arguments.
		 * @param string               $template Template name.
		 */
		$args = apply_filters( 'tiers/template/args', $args, $template );

		// Prefix every key with `tiers_` to keep the template scope clean.
		$tiers_scoped_args = array();
		foreach ( $args as $tiers_arg_key => $tiers_arg_value ) {
			if ( ! is_string( $tiers_arg_key ) || '' === $tiers_arg_key ) {
				continue;
			}
			$tiers_scoped_args[ str_starts_with( $tiers_arg_key, 'tiers_' ) ? $tiers_arg_key : 'tiers_' . $tiers_arg_key ] = $tiers_arg_value;
		}

		unset( $args, $tiers_arg_key, $tiers_arg_value );

		extract( $tiers_scoped_args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		include $path;
	}

	/**
	 * Locate the absolute path of a template file, or return null if not found.
	 *
	 * @param string $template Template name without leading slash.
	 * @return string|null
	 */
	public function locate( string $template ): ?string {
		$template = ltrim( $template, '/' );

		if ( ! str_ends_with( $template, '.php' ) ) {
			$template .= '.php';
		}

		// Check active theme first.
		$theme_path = locate_template( self::THEME_DIR . '/' . $template );

		if ( '' !== $theme_path ) {
			/**
			 * Filtered template path from theme.
			 *
			 * @var string
			 */
			return apply_filters( 'tiers/template/path', $theme_path, $template );
		}

		// Fall back to plugin bundled template.
		$plugin_path = PLUGIN_DIR . '/templates/' . $template;

		if ( file_exists( $plugin_path ) ) {
			/**
			 * Filtered template path from plugin.
			 *
			 * @var string
			 */
			return apply_filters( 'tiers/template/path', $plugin_path, $template );
		}

		return null;
	}
}
