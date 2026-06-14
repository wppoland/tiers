/**
 * Tiers - Admin tier builder.
 *
 * Adds/removes tier rows, wires accessible info tooltips (native Popover API
 * with an aria-describedby + title fallback), and renders a live shopper-facing
 * preview. Vanilla JS, no dependencies, enqueued with defer in the footer.
 */
(function () {
	'use strict';

	var OPTION = 'tiers_settings';

	// Localised strings + config, injected via wp_localize_script.
	var L10N = (window.tiersAdmin && window.tiersAdmin.i18n) || {};
	function t(key, fallback) {
		return typeof L10N[key] === 'string' ? L10N[key] : fallback;
	}

	function getRows() {
		return Array.prototype.slice.call(
			document.querySelectorAll('#tiers-rows tr')
		);
	}

	function createRow(index) {
		var tr = document.createElement('tr');
		tr.innerHTML =
			'<td><input type="number" name="' +
			OPTION +
			'[tiers][' +
			index +
			'][min_qty]" value="" min="1" step="1" class="small-text" required aria-label="' +
			t('minQtyLabel', 'Minimum quantity') +
			'" /></td>' +
			'<td><input type="number" name="' +
			OPTION +
			'[tiers][' +
			index +
			'][discount_percent]" value="" min="0.01" max="100" step="0.01" class="small-text" required aria-label="' +
			t('discountLabel', 'Discount percent') +
			'" /></td>' +
			'<td><input type="text" name="' +
			OPTION +
			'[tiers][' +
			index +
			'][label]" value="" class="regular-text" aria-label="' +
			t('labelLabel', 'Label') +
			'" /></td>' +
			'<td><button type="button" class="button tiers-remove-row">' +
			t('remove', 'Remove') +
			'</button></td>';
		return tr;
	}

	function reindexRows() {
		getRows().forEach(function (tr, idx) {
			tr.querySelectorAll('input').forEach(function (input) {
				input.name = input.name.replace(
					/\[tiers\]\[\d+\]/,
					'[tiers][' + idx + ']'
				);
			});
		});
	}

	function toggleEmptyState() {
		var empty = document.getElementById('tiers-empty');
		var table = document.getElementById('tiers-table');
		if (!empty || !table) {
			return;
		}
		var hasRows = getRows().length > 0;
		empty.hidden = hasRows;
		table.hidden = !hasRows;
	}

	/**
	 * Debounce helper.
	 */
	function debounce(fn, wait) {
		var timer;
		return function () {
			var args = arguments;
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				fn.apply(null, args);
			}, wait);
		};
	}

	/**
	 * Build the live, shopper-facing preview from the current rows.
	 */
	function renderPreview() {
		var host = document.getElementById('tiers-preview-list');
		if (!host) {
			return;
		}

		var data = getRows()
			.map(function (tr) {
				var qty = parseInt(
					(tr.querySelector('input[name*="[min_qty]"]') || {}).value,
					10
				);
				var pct = parseFloat(
					(tr.querySelector('input[name*="[discount_percent]"]') || {})
						.value
				);
				return { qty: qty, pct: pct };
			})
			.filter(function (d) {
				return d.qty > 0 && d.pct > 0;
			})
			.sort(function (a, b) {
				return a.qty - b.qty;
			});

		host.innerHTML = '';

		if (!data.length) {
			var p = document.createElement('p');
			p.className = 'tiers-preview__empty';
			p.textContent = t(
				'previewEmpty',
				'Add a tier above to preview how it reads to shoppers.'
			);
			host.appendChild(p);
			return;
		}

		var ul = document.createElement('ul');
		ul.className = 'tiers-preview__list';
		var qtyTpl = t('previewQty', 'Buy %d+');
		data.forEach(function (d) {
			var li = document.createElement('li');
			li.className = 'tiers-preview__item';

			var qty = document.createElement('span');
			qty.className = 'tiers-preview__qty';
			qty.textContent = qtyTpl.replace('%d', String(d.qty));

			var disc = document.createElement('span');
			disc.className = 'tiers-preview__discount';
			disc.textContent =
				'-' + String(parseFloat(d.pct.toFixed(2))) + '%';

			li.appendChild(qty);
			li.appendChild(disc);
			ul.appendChild(li);
		});
		host.appendChild(ul);
	}

	var renderPreviewDebounced = debounce(renderPreview, 200);

	/**
	 * Wire info "?" buttons to their tooltip bodies. Uses the native Popover
	 * API when available; otherwise falls back to a title attribute so the help
	 * text is still reachable. aria-describedby links button to tip either way.
	 */
	function wireTooltips() {
		var supportsPopover =
			typeof HTMLElement !== 'undefined' &&
			'popover' in HTMLElement.prototype;

		document.querySelectorAll('.tiers-help').forEach(function (btn) {
			var tipId = btn.getAttribute('data-tip');
			if (!tipId) {
				return;
			}
			var tip = document.getElementById(tipId);
			if (!tip) {
				return;
			}

			btn.setAttribute('aria-describedby', tipId);

			if (!supportsPopover) {
				// Fallback: surface the help text via the native title tooltip.
				btn.setAttribute('title', tip.textContent.trim());
				return;
			}

			function show() {
				try {
					tip.showPopover();
				} catch (e) {
					/* already open */
				}
				// Position the tip near its trigger.
				var rect = btn.getBoundingClientRect();
				tip.style.position = 'fixed';
				tip.style.insetBlockStart =
					rect.bottom + window.scrollY + 6 + 'px';
				tip.style.insetInlineStart =
					Math.max(8, rect.left) + 'px';
			}
			function hide() {
				try {
					tip.hidePopover();
				} catch (e) {
					/* already closed */
				}
			}

			btn.addEventListener('mouseenter', show);
			btn.addEventListener('mouseleave', hide);
			btn.addEventListener('focus', show);
			btn.addEventListener('blur', hide);
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (tip.matches(':popover-open')) {
					hide();
				} else {
					show();
				}
			});
		});
	}

	function init() {
		var addBtn = document.getElementById('tiers-add-row');
		var tbody = document.getElementById('tiers-rows');
		var builder = document.getElementById('tiers-builder');

		wireTooltips();
		toggleEmptyState();
		renderPreview();

		if (!addBtn || !tbody) {
			return;
		}

		addBtn.addEventListener('click', function () {
			var index = getRows().length;
			var row = createRow(index);
			tbody.appendChild(row);
			toggleEmptyState();
			renderPreview();
			var firstInput = row.querySelector('input');
			if (firstInput) {
				firstInput.focus();
			}
		});

		// Event delegation for removals.
		tbody.addEventListener('click', function (e) {
			var target = e.target;
			if (!target || !target.classList.contains('tiers-remove-row')) {
				return;
			}
			var row = target.closest('tr');
			if (!row) {
				return;
			}
			row.remove();
			reindexRows();
			toggleEmptyState();
			renderPreview();
			if (addBtn) {
				addBtn.focus();
			}
		});

		// Live preview as values change.
		if (builder) {
			builder.addEventListener('input', renderPreviewDebounced);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
