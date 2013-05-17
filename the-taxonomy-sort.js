jQuery( document ).ready( function( $ )
{
	// if it is taxonomy page
	if( adminpage == 'edit-tags-php' )
	{
		// make table rows sortable
		$( '.wp-list-table.tags tbody' ).sortable({
			items: 'tr:not(.inline-edit-row)',
			cursor: 'move',
			axis: 'y',
			containment: 'table.widefat',
			scrollSensitivity: 40,
			stop: function( event, ui ) {
				var rows 	= new Array();

				$( '.wp-list-table.tags tbody tr:not(.inline-edit-row)' ).each( function( i, e ) {
					var rowID	= parseInt($( e ).attr( 'id' ).substr( 4 ));
					rows[i]		= rowID;
				} );

				// post rows for sorting
				$.post( ajaxurl, { 'rows' : rows, 'action' : 'get_inline_boxes' } );
			}
		});
	}
} );