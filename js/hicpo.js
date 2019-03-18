(function($){

	// Add a 'drag' icon to the table rows
	$('table.posts #the-list, table.pages #the-list, table.tags #the-list, table.sites #the-list')
		.find('tr .check-column')
		.append('<div class="drag-handle"><svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><path d="M32 2l18 18H36v24h14L32 62 14 44h14V20H14L32 2z"/></svg></div>');

	// posts

	$('table.posts #the-list, table.pages #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'helper': fixHelper,
		'handle': '.drag-handle',
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
		'helper': fixHelper,
		'handle': '.drag-handle',
		'update' : function(e, ui) {
			$.post( ajaxurl, {
				action: 'update-menu-order-tags',
				order: $('#the-list').sortable('serialize'),
			});
		}
	});
	//$("#the-list").disableSelection();

	// sites

	// add number
	var site_table_tr = $('table.sites #the-list tr');
	site_table_tr.each( function() {
		var ret=null;
		var url = $(this).find('td.blogname a').attr('href');
		parameters = url.split('?');
		if( parameters.length > 1 ) {
			var params = parameters[1].split('&');
			var paramsArray = [];
			for( var i=0; i<params.length; i++) {
				var neet = params[i].split('=');
				paramsArray.push(neet[0]);
				paramsArray[neet[0]] = neet[1];
			}
			ret = paramsArray['id'];
		}
		$(this).attr('id','site-'+ret);
	} );

	$('table.sites #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'helper': fixHelper,
		'handle': '.drag-handle',
		'update' : function(e, ui) {
			$.post( ajaxurl, {
				action: 'update-menu-order-sites',
				order: $('#the-list').sortable('serialize'),
			});
		}
	});

	var fixHelper = function(e, ui) {
		ui.children().children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

})(jQuery)
