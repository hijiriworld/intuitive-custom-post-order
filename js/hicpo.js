/* global jQuery, ajaxurl, hicpojs_ajax_vars */

( function ( $ ) {
	const fixHelper = function ( e, ui ) {
		ui.children()
			.children()
			.each( function () {
				$( this ).width( $( this ).width() );
			} );
		return ui;
	};

	// posts
	$( 'table.posts #the-list, table.pages #the-list' ).sortable( {
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		// eslint-disable-next-line no-unused-vars
		update( e, ui ) {
			$.post( ajaxurl, {
				action: 'update-menu-order',
				nonce: hicpojs_ajax_vars.nonce, // eslint-disable-line camelcase
				order: $( '#the-list' ).sortable( 'serialize' ),
			} );
		},
	} );

	// tags
	$( 'table.tags #the-list' ).sortable( {
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		// eslint-disable-next-line no-unused-vars
		update( e, ui ) {
			$.post( ajaxurl, {
				action: 'update-menu-order-tags',
				nonce: hicpojs_ajax_vars.nonce, // eslint-disable-line camelcase
				order: $( '#the-list' ).sortable( 'serialize' ),
			} );
		},
	} );

	// sites
	// add number
	const siteTableTr = $( 'table.sites #the-list tr' );
	siteTableTr.each( function () {
		let ret = null;
		const url = $( this ).find( 'td.blogname a' ).attr( 'href' );
		const parameters = url.split( '?' );
		if ( parameters.length > 1 ) {
			const params = parameters[ 1 ].split( '&' );
			const paramsArray = [];
			for ( let i = 0; i < params.length; i++ ) {
				const neet = params[ i ].split( '=' );
				paramsArray.push( neet[ 0 ] );
				paramsArray[ neet[ 0 ] ] = neet[ 1 ];
			}
			ret = paramsArray.id;
		}
		$( this ).attr( 'id', 'site-' + ret );
	} );

	$( 'table.sites #the-list' ).sortable( {
		items: 'tr',
		axis: 'y',
		helper: fixHelper,
		// eslint-disable-next-line no-unused-vars
		update( e, ui ) {
			$.post( ajaxurl, {
				action: 'update-menu-order-sites',
				nonce: hicpojs_ajax_vars.nonce, // eslint-disable-line camelcase
				order: $( '#the-list' ).sortable( 'serialize' ),
			} );
		},
	} );
} )( jQuery );
