/**
 * Tiers – "Volume pricing table" block (editor).
 *
 * A dynamic, server-rendered block. The editor shows a lightweight placeholder
 * with an optional product id; the front-end output is rendered in PHP. No build
 * step, no JSX — uses wp.element.createElement directly.
 */
(function (blocks, element, blockEditor, components, i18n) {
	'use strict';

	if (!blocks || !element) {
		return;
	}

	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;

	blocks.registerBlockType('tiers/tiers-table', {
		edit: function (props) {
			var productId = props.attributes.productId || 0;

			var control = el(components.TextControl, {
				type: 'number',
				label: __('Product ID (0 = current product)', 'plogins-tiers'),
				value: productId,
				min: 0,
				onChange: function (value) {
					props.setAttributes({ productId: parseInt(value, 10) || 0 });
				},
			});

			var placeholder = el(
				components.Placeholder,
				{
					icon: 'editor-table',
					label: __('Volume pricing table', 'plogins-tiers'),
					instructions: __(
						'The tier table renders on the front end for the selected product.',
						'plogins-tiers'
					),
				},
				control
			);

			return el('div', useBlockProps(), placeholder);
		},
		save: function () {
			// Dynamic block: rendered server-side in PHP.
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
