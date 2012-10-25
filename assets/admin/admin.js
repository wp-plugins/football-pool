function bulk_action_warning( id ) {
	var bulk_select = jQuery( '#' + id );
	var msg;
	if ( bulk_select && bulk_select.prop( 'selectedIndex' ) != 0 ) {
		msg = jQuery( '#' + id + ' option').filter( ':selected' ).attr( 'bulk-msg' );
		// console.log(msg);
		if ( msg != '' && msg != undefined ) {
			return( confirm( msg ) );
		} else {
			return true;
		}
	} else {
		return false;
	}
}

function toggle_points( id ) {
	jQuery( '#' + id + '_points' ).toggle();
}

function toggle_linked_radio_options( active_id, disabled_id ) {
	if ( jQuery.isArray( active_id ) && active_id.length >= 1 ) {
		jQuery.each( active_id, function( i, v ) { 
			if ( v != '' ) jQuery( v ).toggle( true ); 
		} );
	} else if ( active_id != '' ) {
		jQuery( active_id ).toggle( true );
	}
	
	if ( jQuery.isArray( disabled_id ) && disabled_id.length >= 1 ) {
		jQuery.each( disabled_id, function( i, v ) { 
			if ( v != '' ) jQuery( v ).toggle( false ); 
		} );
	} else if ( disabled_id != '' ) {
		jQuery( disabled_id ).toggle( false );
	}
}

// jQuery Input Hints plugin
// Copyright (c) 2009 Rob Volk
// http://www.robvolk.com

jQuery( document ).ready( function() {
   jQuery( 'input[title].with-hint' ).inputHints();
});

jQuery.fn.inputHints=function() {
	// hides the input display text stored in the title on focus
	// and sets it on blur if the user hasn't changed it.

	// show the display text
	// changed (AntoineH): only for empty inputs
	jQuery(this).each(function(i) {
		if (jQuery(this).val() == '') {
			jQuery(this).val(jQuery(this).attr('title'))
				.addClass('hint');
		}
	});

	// hook up the blur & focus
	return jQuery(this).focus(function() {
		if (jQuery(this).val() == jQuery(this).attr('title'))
			jQuery(this).val('')
				.removeClass('hint');
	}).blur(function() {
		if (jQuery(this).val() == '')
			jQuery(this).val(jQuery(this).attr('title'))
				.addClass('hint');
	});
}; // jQuery Input Hints plugin
