jQuery(document).ready(function() {
	jQuery("#the-list").sortable({
		'items': 'tr',
		'axis': 'y',
		'helper': fixHelper,
		'update' : function(e, ui) {
			jQuery.post( ajaxurl, {
				action: 'update-menu-order',
				order: jQuery("#the-list").sortable("serialize"),
			});
		}
	});
	//jQuery("#the-list").disableSelection();
});

var fixHelper = function(e, ui) {
	ui.children().children().each(function() {
		jQuery(this).width(jQuery(this).width());
	});
	return ui;
};
