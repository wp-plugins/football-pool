function close_calculation_iframe() {
	jQuery( '#fp-calculation-iframe' ).hide( 'slow', function() { jQuery(this).remove() } );
}

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

var value_store = [];
function set_input_param( param, id, value ) {
	var param_value;
	if ( jQuery.isArray( id ) && id.length >= 1 ) {
		jQuery.each( id, function( i, v ) { 
			if ( v != '' ) {
				if ( ! jQuery.isArray( value_store[v] ) ) value_store[v] = [];
				value_store[v][param] = jQuery( v ).attr( param );
				param_value = jQuery.isArray( value ) ? value[i] : value;
				jQuery( v ).attr( param, param_value );
			}
		} );
	} else {
		if ( id != '' ) {
			value_store[id] = [ param, jQuery( id ).attr( param ) ];
			param_value = jQuery.isArray( value ) ? value[0] : value;
			jQuery( id ).attr( param, param_value );
		}
	}
}

function restore_input_param( param, id ) {
	var param_value = '';
	if ( jQuery.isArray( id ) && id.length >= 1 ) {
		jQuery.each( id, function( i, v ) {
			param_value = ( typeof value_store[v][param] != undefined ) ? value_store[v][param] : '';
			jQuery( v ).attr( param, param_value );
		} );
	} else {
		param_value = ( typeof value_store[id][param] != undefined ) ? value_store[id][param] : '';
		jQuery( id ).attr( param, param_value );
	}
}

function disable_inputs( id ) {
	var check_id = arguments[1] || '';
	var readonly = false;
	if ( check_id != '' ) {
		readonly = jQuery( '#' + check_id ).is(':checked');
	}
	
	if ( jQuery.isArray( id ) && id.length >= 1 ) {
		jQuery.each( id, function( i, v ) { 
			if ( v != '' ) {
				if ( check_id != '' ) {
					if ( readonly ) {
						jQuery( v ).attr( 'disabled', 'disabled' );
					} else {
						jQuery( v ).removeAttr( 'disabled' );
					}
				} else {
					jQuery( v ).attr( 'disabled', 'disabled' ); 
				}
			}
		} );
	} else if ( id != '' ) {
		if ( check_id != '' ) {
			if ( readonly ) {
				jQuery( id ).attr( 'disabled', 'disabled' );
			} else {
				jQuery( id ).removeAttr( 'disabled' );
			}
		} else {
			jQuery( id ).attr( 'disabled', 'disabled' ); 
		}
	}
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
		preserve_content = false;
		the_text = '',
		panel = '',
		atts = '';
	
	// determine which panel was selected
	jQuery( 'div', '#panel_wrapper' ).each( function() { 
		if ( jQuery( this ).hasClass( 'current' ) ) panel = jQuery( this ).attr( 'id' );
	});
	
	var panel_id = '#' + panel;
	
	selected_val = jQuery( '.shortcode', panel_id ).val();
	switch( selected_val ) {
		case 'fp-link':
			var slug = jQuery( '#slug', panel_id ).val();
			if ( slug != '' ) atts += ' slug="' + slug + '"';
			break;
		case 'fp-register':
			preserve_content = true;
			close_tag = true;
			var title = jQuery( '#link-title', panel_id ).val();
			if ( title != '' ) atts += ' title="' + title + '"';
			var new_window = jQuery( '#link-window', panel_id ).is( ':checked' );
			if ( new_window ) atts += ' new="1"';
			break;
		case 'fp-countdown':
			var count_to = jQuery( 'input[name=count_to]:checked', panel_id ).val();
			if ( count_to == 'date' ) {
				var the_date = jQuery( '#count-date', panel_id ).val();
				if ( the_date != '' ) atts += ' date="' + the_date + '"';
			} else if ( count_to == 'match' ) {
				var match = jQuery( '#count-match', panel_id ).val();
				if ( match > 0 ) atts += ' match="' + match + '"';
			}
			var inline = jQuery( '#count-inline', panel_id ).is( ':checked' );
			if ( inline ) atts += ' display="inline"';
			var no_texts = jQuery( '#count-no-texts', panel_id ).is( ':checked' );
			var texts = '';
			if ( no_texts ) {
				texts = 'none';
			} else {
				texts = [ jQuery( '#text-1', panel_id ).val(), jQuery( '#text-2', panel_id ).val(), jQuery( '#text-3', panel_id ).val(), jQuery( '#text-4', panel_id ).val() ].join( ';' );
			}
			if ( texts != '' && texts != ';;;' ) atts += ' texts="' + texts + '"';
			break;
		case 'fp-group':
			var group = jQuery( '#group-id', panel_id ).val();
			if ( group > 0 ) atts += ' id=' + group;
			break;
		case 'fp-ranking':
			var league = jQuery( '#ranking-league', panel_id ).val();
			if ( league > 0 ) atts += ' league="' + league + '"';
			var num = jQuery( '#ranking-num', panel_id ).val();
			if ( num > 0 ) atts += ' num="' + num + '"';
			var date = jQuery( 'input:radio[name=ranking-date]:checked', panel_id ).val();
			if ( date == 'custom' ) date = jQuery( '#ranking-date-custom-value', panel_id ).val();
			if ( date != '' ) atts += ' date="' + date + '"';
			break;
		default:
			if ( selected_val == '' ) tinyMCEPopup.close();
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
