(function($){

	var fixStart = function(e, ui) {
		// this will make the transition smoother by making the placeholder the same height
		ui.placeholder.height(ui.item.height());
	};

	var fixHelper = function(e, ui) {
		// this doesn't work properly at the bottom where it was..so I moved it to the top
		ui.children().children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

	
	// posts

	$('table.posts #the-list, table.pages #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'start': fixStart,
		'helper': fixHelper,
		'update' : function(e, ui) {
			$.post( ajaxurl, {
				action: 'update-menu-order',
				order: $('#the-list').sortable('serialize'),
			});
		}
	});
	//$("#the-list").disableSelection();
	
	// tags
	
	$('table.tags #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'start': fixStart,
		'helper': fixHelper,
		'update' : function(e, ui) {
			$.post( ajaxurl, {
				action: 'update-menu-order-tags',
				order: $('#the-list').sortable('serialize'),
			});
		}
	});
	//$("#the-list").disableSelection();
	
	$(document).ready(function(){

		var hicpoFixHeaders = function(){
		// this will add static widths to the header cells, 
		// to avoid shifting width on drag when using custom columns
			$('.wp-list-table thead th, .wp-list-table thead td')
			.each(function(){
				$(this).css('width', $(this).width());
			});

			console.log('headers');
		}; 
		// load on startup
		hicpoFixHeaders();


		var hicpoResetHeaders = function(){
			// this will reset headers and body cells
			$('.wp-list-table thead th, .wp-list-table thead td')
			.each(function(){
				$(this).css('width', '');
			});
			$('.wp-list-table tbody th, .wp-list-table tbody td')
			.each(function(){
				$(this).css('width', '');
			});
			console.log('headers');
		}; 

		// reset on window resize 
		$( window ).resize(function() {
			console.log('resize');
			hicpoResetHeaders();
		  hicpoFixHeaders();
		});

	});

})(jQuery)
