jQuery(document).ready(function() {
	jQuery("#the-list").sortable({
		'items': 'tr',
		'update' : function(e, u) {
			jQuery.post( ajaxurl, {
				action:'update-menu-order',
				order:jQuery("#the-list").sortable("serialize")
			});
		}
	});
	jQuery("#the-list").disableSelection();
});
