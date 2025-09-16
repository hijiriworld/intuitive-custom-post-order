/* global jQuery, ajaxurl, hicpojs_ajax_vars */
(function ($) {
	'use strict';

	const fixHelper = function (e, ui) {
		ui.children()
			.children()
			.each(function () {
				$(this).width($(this).width());
			});
		return ui;
	};

	function getMsg(key) {
		const m = (window.hicpojs_ajax_vars && window.hicpojs_ajax_vars.messages) || {};
		return m[key] || (key === 'saved' ? 'Saved.' : 'Failed to save.');
	}

	function notifySuccess(text) {
		if (window.wp && wp.a11y && wp.a11y.speak) {
			wp.a11y.speak(text, 'polite');
		}
	}

	function notifyError(text) {
		if (window.wp && wp.a11y && wp.a11y.speak) {
			wp.a11y.speak(text, 'assertive');
		} else {
			// eslint-disable-next-line no-alert
			window.alert(text);
		}
	}

	function postOrder(action) {
		const $list = $('#the-list');
		const payload = {
			action,
			nonce: hicpojs_ajax_vars.nonce,
			order: $list.sortable('serialize'),
		};
		$list.sortable('disable');
		$(document.body).addClass('hicpo-saving');
		return $.post((hicpojs_ajax_vars && hicpojs_ajax_vars.ajaxUrl) || ajaxurl, payload)
			.done(function (res) {
				if (res && res.success) {
					notifySuccess(getMsg('saved'));
				} else {
					notifyError((res && res.data && res.data.message) || getMsg('failed'));
				}
			})
			.fail(function () {
				notifyError(getMsg('failed'));
			})
			.always(function () {
				$(document.body).removeClass('hicpo-saving');
				$list.sortable('enable');
			});
	}

	// Posts & Pages
	$('table.posts #the-list, table.pages #the-list').sortable({
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		update() {
			postOrder('update-menu-order');
		},
	});

	// Terms (tags screen uses .tags)
	$('table.tags #the-list').sortable({
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		update() {
			postOrder('update-menu-order-tags');
		},
	});

	// Network: add id to each row from blog link (to match "site-<id>")
	const siteTableTr = $('table.sites #the-list tr');
	siteTableTr.each(function () {
		let ret = null;
		const url = $(this).find('td.blogname a').attr('href');
		if (!url) {
			return;
		}
		const parameters = url.split('?');
		if (parameters.length > 1) {
			const params = parameters[1].split('&');
			const paramsMap = {};
			for (let i = 0; i < params.length; i++) {
				const pair = params[i].split('=');
				if (pair.length === 2) {
					paramsMap[pair[0]] = pair[1];
				}
			}
			ret = paramsMap.id;
		}
		if (ret) {
			$(this).attr('id', 'site-' + ret);
		}
	});

	// Network: Sites
	$('table.sites #the-list').sortable({
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		update() {
			postOrder('update-menu-order-sites');
		},
	});
})(jQuery);
