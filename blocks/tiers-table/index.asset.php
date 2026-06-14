<?php
/**
 * Block editor script dependencies and version.
 *
 * Hand-authored (no build step): lists the WordPress script handles the editor
 * script relies on so they are enqueued in the correct order.
 *
 * @package Tiers
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
	),
	'version'      => '0.2.0',
);
