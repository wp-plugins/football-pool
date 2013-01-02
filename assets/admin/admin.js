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


// tinymce extension
function tinymce_init_tabs( id ) {
	jQuery( 'li', '#' + id ).each( function() {
		jQuery( this ).bind( "click", function() {
			mcTabs.displayTab( 
								jQuery( this ).attr( 'id' ), 
								jQuery( this ).attr( 'id' ).replace( '_tab', '' ) + '_panel'
							);
			return false;
		})
	});
}

function tinymce_init() {
	tinyMCEPopup.resizeToInnerSize();
}

function tinymce_insert_shortcode() {
	var selected_val, 
		shortcode = '', 
		close_tag = false,
		content = false,
		the_text = '',
		panel = '',
		atts = '';
	
	// determine which panel was selected
	jQuery( 'div', '#panel_wrapper' ).each( function() { 
		if ( jQuery( this ).hasClass( 'current' ) ) panel = jQuery( this ).attr( 'id' );
	});
	
	var panel_id = '#' + panel;
	
	switch( panel ) {
		case 'options_panel':
		case 'links_panel':
			selected_val = jQuery( '.shortcode', panel_id ).val();
			if ( selected_val != '' ) {
				if ( selected_val == 'fp-link' ) {
					var slug = jQuery( '#slug', panel_id ).val();
					if ( slug != '' ) atts += ' slug="' + slug + '"';
				} else if ( selected_val == 'fp-register' ) {
					preserve_content = true;
					close_tag = true;
					var title = jQuery( '#link-title', panel_id ).val();
					if ( title != '' ) atts += ' title="' + title + '"';
					var new_window = jQuery( '#link-window', panel_id ).is( ':checked' );
					if ( new_window ) atts += ' new="1"';
				}
			}
			break;
		case 'pool_panel':
			break;
		default:
			tinyMCEPopup.close();
	}
	
	if ( selected_val != '' ) {
		if ( preserve_content && tinyMCE.activeEditor.selection.getContent() != '' ) {
			the_text = tinyMCE.activeEditor.selection.getContent( { format : 'text' } );
		}
		shortcode  = '[' + selected_val + atts + ']';
		shortcode += the_text;
		shortcode += ( close_tag ? '[/' + selected_val + ']' : '' );
	}
	
	if ( window.tinyMCE ) {
		window.tinyMCE.execInstanceCommand( 'content', 'mceInsertContent', false, shortcode );
		//Peforms a clean up of the current editor HTML.
		//tinyMCEPopup.editor.execCommand( 'mceCleanup' );
		//Repaints the editor. Sometimes the browser has graphic glitches.
		tinyMCEPopup.editor.execCommand( 'mceRepaint' );
		tinyMCEPopup.close();
	}
	return;
}
// end tinymce extension
