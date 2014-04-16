// minified with http://closure-compiler.appspot.com/home

jQuery( document ).ready( function() {
	jQuery( 'body.football-pool input.current-page' ).keydown( function( event ) {
		if ( event.which == 13 ) jQuery( 'input[name="action"]' ).val( '' );
	} );
	
	jQuery( 'div.matchtype input:checkbox' ).click( function() {
		var matchtype_id = jQuery( this ).attr( 'id' ).replace( 'matchtype-', '' );
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( 'div.matchtype-' + matchtype_id + ' input:checkbox' ).each( function() {
				jQuery( this ).attr( 'checked', 'checked' );
			} );
		} else {
			jQuery( 'div.matchtype-' + matchtype_id + ' input:checkbox' ).each( function() {
				jQuery( this ).removeAttr( 'checked' );
			} );
		}
	} );
	
	try {
		jQuery( 'a.ranking-log-summary' ).colorbox( { 
			html: function() {
					return '<div class="ranking-log">' 
							+ jQuery( 'div.ranking-log-summary', jQuery( this ).parent() ).html() 
							+ jQuery( 'div.ranking-log-rest', jQuery( this ).parent() ).html() 
							+ '</div>';
				},
			innerWidth: "600px",
			innerHeight: "400px"
		} );
	} catch( err ) { }
} );

var FootballPoolAdmin = (function ( $ ) {
	
	var value_store = [];
	
	// tinymce extension
	function tinymce_init_tabs( id ) {
		$( 'li', '#' + id ).each( function() {
			$( this ).bind( "click", function() {
				mcTabs.displayTab( 
									$( this ).attr( 'id' ), 
									$( this ).attr( 'id' ).replace( '_tab', '' ) + '_panel'
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
		$( 'div', '#panel_wrapper' ).each( function() { 
			if ( $( this ).hasClass( 'current' ) ) panel = $( this ).attr( 'id' );
		});
		
		var panel_id = '#' + panel;
		
		selected_val = $( '.shortcode', panel_id ).val();
		switch( selected_val ) {
			case 'fp-link':
				var slug = $( '#slug', panel_id ).val();
				if ( slug != '' ) atts += ' slug="' + slug + '"';
				break;
			case 'fp-register':
				preserve_content = true;
				close_tag = true;
				var title = $( '#link-title', panel_id ).val();
				if ( title != '' ) atts += ' title="' + title + '"';
				var new_window = $( '#link-window', panel_id ).is( ':checked' );
				if ( new_window ) atts += ' new="1"';
				break;
			case 'fp-countdown':
				var count_to = $( 'input[name=count_to]:checked', panel_id ).val();
				if ( count_to == 'date' ) {
					var the_date = $( '#count-date', panel_id ).val();
					if ( the_date != '' ) atts += ' date="' + the_date + '"';
				} else if ( count_to == 'match' ) {
					var match = $( '#count-match', panel_id ).val();
					if ( match > 0 ) atts += ' match="' + match + '"';
				}
				var inline = $( '#count-inline', panel_id ).is( ':checked' );
				if ( inline ) atts += ' display="inline"';
				var no_texts = $( '#count-no-texts', panel_id ).is( ':checked' );
				var texts = '';
				if ( no_texts ) {
					texts = 'none';
				} else {
					texts = [ $( '#text-1', panel_id ).val(), $( '#text-2', panel_id ).val(), $( '#text-3', panel_id ).val(), $( '#text-4', panel_id ).val() ].join( ';' );
				}
				if ( texts != '' && texts != ';;;' ) atts += ' texts="' + texts + '"';
				var time_format = $( '#count-format', panel_id ).val();
				if ( time_format > 0 ) atts += ' format="' + time_format + '"';
				break;
			case 'fp-group':
				var group = $( '#group-id', panel_id ).val();
				if ( group > 0 ) atts += ' id=' + group;
				break;
			case 'fp-ranking':
				var ranking = $( '#ranking-id', panel_id ).val();
				if ( ranking > 0 ) atts += ' ranking="' + ranking + '"';
				var league = $( '#ranking-league', panel_id ).val();
				if ( league > 0 ) atts += ' league="' + league + '"';
				var num = $( '#ranking-num', panel_id ).val();
				if ( num > 0 ) atts += ' num="' + num + '"';
				var date = $( 'input:radio[name=ranking-date]:checked', panel_id ).val();
				if ( date == 'custom' ) date = $( '#ranking-date-custom-value', panel_id ).val();
				if ( date != '' ) atts += ' date="' + date + '"';
				break;
			case 'fp-predictions':
				var match = $( '#predictions-match', panel_id ).val();
				if ( match > 0 ) atts += ' match="' + match + '"';
				var question = $( '#predictions-question', panel_id ).val();
				if ( question > 0 ) atts += ' question="' + question + '"';
				var text = $( '#predictions-text', panel_id ).val();
				if ( text != '' ) atts += ' text="' + text + '"';
				break;
			case 'fp-user-score':
				var ranking = $( '#user-score-ranking-id', panel_id ).val();
				if ( ranking > 0 ) atts += ' ranking="' + ranking + '"';
				var user = $( '#user-score-user-id', panel_id ).val();
				if ( user != '' ) atts += ' user="' + user + '"';
				var text = $( '#user-score-text', panel_id ).val();
				if ( text != '' ) atts += ' text="' + text + '"';
				var date = $( 'input:radio[name=user-score-date]:checked', panel_id ).val();
				if ( date == 'custom' ) date = $( '#user-score-date-custom-value', panel_id ).val();
				if ( date != '' ) atts += ' date="' + date + '"';
				break;
			case 'fp-user-ranking':
				var ranking = $( '#user-ranking-ranking-id', panel_id ).val();
				if ( ranking > 0 ) atts += ' ranking="' + ranking + '"';
				var user = $( '#user-ranking-user-id', panel_id ).val();
				if ( user != '' ) atts += ' user="' + user + '"';
				var text = $( '#user-ranking-text', panel_id ).val();
				if ( text != '' ) atts += ' text="' + text + '"';
				var date = $( 'input:radio[name=user-ranking-date]:checked', panel_id ).val();
				if ( date == 'custom' ) date = $( '#user-ranking-date-custom-value', panel_id ).val();
				if ( date != '' ) atts += ' date="' + date + '"';
				break;
			case 'fp-predictionform':
				var matches = $( '#match-id', panel_id ).val() || [];
				if ( matches.length > 0 ) atts += ' match="' + matches.join( ',' ) + '"';
				var matchtypes = $( '#matchtype-id', panel_id ).val() || [];
				if ( matchtypes.length > 0 ) atts += ' matchtype="' + matchtypes.join( ',' ) + '"';
				var questions = $( '#question-id', panel_id ).val() || [];
				if ( questions.length > 0 ) atts += ' question="' + questions.join( ',' ) + '"';
				break;
			case 'fp-matches':
				var group_id = $( '#matches-group-id', panel_id ).val();
				if ( group_id != '' ) atts += ' group="' + group_id + '"';
				var matches = $( '#matches-match-id', panel_id ).val() || [];
				if ( matches.length > 0 ) atts += ' match="' + matches.join( ',' ) + '"';
				var matchtypes = $( '#matches-matchtype-id', panel_id ).val() || [];
				if ( matchtypes.length > 0 ) atts += ' matchtype="' + matchtypes.join( ',' ) + '"';
				break;
			case 'fp-league-info':
				var league_id = $( '#league-info-league-id', panel_id ).val();
				if ( league_id > 0 ) atts += ' league="' + league_id + '"';
				var info = $( 'input:radio[name=league-info-info]:checked', panel_id ).val();
				if ( info != '' ) atts += ' info="' + info + '"';
				var ranking_id = $( '#league-info-ranking-id', panel_id ).val();
				if ( ranking_id > 0 ) atts += ' ranking="' + ranking_id + '"';
				var format = $( '#league-info-format', panel_id ).val();
				if ( format != '' ) atts += ' format="' + format + '"';
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
			window.tinyMCE.execCommand( 'mceInsertContent', 0, shortcode );
			//Peforms a clean up of the current editor HTML.
			//tinyMCEPopup.editor.execCommand( 'mceCleanup' );
			//Repaints the editor. Sometimes the browser has graphic glitches.
			tinyMCEPopup.editor.execCommand( 'mceRepaint' );
			tinyMCEPopup.close();
		}
		return;
	}
	// end tinymce extension
	
	// score calculation handling
	function calculate_score_history() {
		var post_data;
		var data = arguments[0] || 0;
		var ranking = arguments[1] || 0;
		var ajax_action = 'footballpool_calculate_scorehistory';
		
		if ( data === 0 ) {
			post_data = {
				action: ajax_action,
				fp_recalc_nonce: FootballPoolAjax.fp_recalc_nonce,
				step: 0,
				single_ranking: ranking
			}
			
			try {
				var jqhxr = $.post( ajaxurl, post_data, function( response ) {
								$.colorbox( { 
													html: response.colorbox_html,
													overlayClose: false, 
													escKey: false, 
													arrowKey: false,
													close: FootballPoolAjax.colorbox_close,
													innerWidth: "500px",
													innerHeight: "285px"
												} );
								$( "#cboxClose" ).hide();
							}, 'json' )
				.fail( function( jqXHR, textStatus, errorThrown ) { alert( "Error message:\n" + errorThrown ); } );
			} catch( e ) {
				alert( FootballPoolAjax.error_label + ":\n" + e );
			}
		} else {
			if ( data === 1 ) {
				// get data from the form in step 0
				data = $.parseJSON( $( '#step-0-data' ).val() );
				data.calculation_type = $( 'input[name=calculation_type]:checked', '.calculation-type-select' ).val();
				// hide the form and show the progress bar
				$( '#step-0-form' ).hide();
				$( '#progress' ).show();
				$( '#progressbar' ).progressbar( { max: 0 } );
			}
			
			post_data = {
				action: ajax_action,
				fp_recalc_nonce: data.fp_recalc_nonce,
				step: data.step,
				sub_step: data.sub_step,
				single_ranking: data.single_ranking,
				ranking: data.ranking,
				progress: data.progress,
				total_steps: data.total_steps,
				user_set: data.user_set,
				total_user_sets: data.total_user_sets,
				total_users: data.total_users,
				calculation_type: data.calculation_type
			}
			
			$( '#ajax-loader' ).show();
			try {
				var jqhxr = $.post( ajaxurl, post_data, function( response ) {
								// update progress bar and status message
								var bar = $( '#progressbar' );
								if ( bar.progressbar( 'option', 'max' ) == 0 ) {
									bar.progressbar( 'option', 'max', response.total_steps );
								}
								bar.progressbar( 'option', 'value', response.progress );
								$( '#calculation-message' ).html( response.message );
								
								// continue or stop?
								if ( response.error === false ) {
									if ( response.step == 9 ) {
										$( '#ajax-loader' ).hide();
										$( '#cboxClose' ).show();
										$( '#button-calculate-single-ranking-' + response.single_ranking ).hide();
										$( '#log-ranking-' + response.single_ranking ).hide();
									} else {
										calculate_score_history( response );
									}
								} else {
									score_calculation_error( response.error );
								}
							}, 'json' )
				.fail( function( jqXHR, textStatus, errorThrown ) { score_calculation_error(); } );
			} catch( e ) {
				alert( FootballPoolAjax.error_label + ":\n" + e );
			}
		}
	}

	function score_calculation_error() {
		var msg = arguments[0] || FootballPoolAjax.error_message;
		$( '#ajax-loader' ).hide();
		$( "#cboxClose" ).show();
		$( '#step-0-form' ).hide();
		$( '#progress' ).show();
		$( '#calculation-message' ).html( '<span class="error">' + msg + '</span>' );
	}
	// end score calculation handler
	
	function bulk_action_warning( id ) {
		var bulk_select = $( '#' + id );
		var msg;
		if ( bulk_select && bulk_select.prop( 'selectedIndex' ) != 0 ) {
			msg = $( '#' + id + ' option').filter( ':selected' ).attr( 'bulk-msg' );
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
		$( '#' + id + '_points' ).toggle();
	}
	
	function set_input_param( param, id, value ) {
		var param_value;
		if ( $.isArray( id ) && id.length >= 1 ) {
			$.each( id, function( i, v ) { 
				if ( v != '' ) {
					if ( ! $.isArray( value_store[v] ) ) value_store[v] = [];
					value_store[v][param] = $( v ).attr( param );
					param_value = $.isArray( value ) ? value[i] : value;
					$( v ).attr( param, param_value );
				}
			} );
		} else {
			if ( id != '' ) {
				value_store[id] = [ param, $( id ).attr( param ) ];
				param_value = $.isArray( value ) ? value[0] : value;
				$( id ).attr( param, param_value );
			}
		}
	}

	function restore_input_param( param, id ) {
		var param_value = '';
		if ( $.isArray( id ) && id.length >= 1 ) {
			$.each( id, function( i, v ) {
				param_value = ( typeof value_store[v][param] != undefined ) ? value_store[v][param] : '';
				$( v ).attr( param, param_value );
			} );
		} else {
			if ( id != '' ) {
				param_value = ( typeof value_store[id][param] != undefined ) ? value_store[id][param] : '';
				$( id ).attr( param, param_value );
			}
		}
	}
	
	function disable_inputs( id ) {
		var check_id = arguments[1] || '';
		var readonly = false;
		if ( check_id != '' ) {
			readonly = $( '#' + check_id ).is( ':checked' );
		}
		
		if ( $.isArray( id ) && id.length >= 1 ) {
			$.each( id, function( i, v ) { 
				if ( v != '' ) {
					if ( check_id != '' ) {
						if ( readonly ) {
							$( v ).attr( 'disabled', 'disabled' );
						} else {
							$( v ).removeAttr( 'disabled' );
						}
					} else {
						$( v ).attr( 'disabled', 'disabled' ); 
					}
				}
			} );
		} else if ( id != '' ) {
			if ( check_id != '' ) {
				if ( readonly ) {
					$( id ).attr( 'disabled', 'disabled' );
				} else {
					$( id ).removeAttr( 'disabled' );
				}
			} else {
				$( id ).attr( 'disabled', 'disabled' ); 
			}
		}
	}

	function toggle_linked_radio_options( active_id, disabled_id ) {
		if ( $.isArray( active_id ) && active_id.length >= 1 ) {
			$.each( active_id, function( i, v ) { 
				if ( v != '' ) $( v ).toggle( true ); 
			} );
		} else if ( active_id != '' ) {
			$( active_id ).toggle( true );
		}
		
		if ( $.isArray( disabled_id ) && disabled_id.length >= 1 ) {
			$.each( disabled_id, function( i, v ) { 
				if ( v != '' ) $( v ).toggle( false ); 
			} );
		} else if ( disabled_id != '' ) {
			$( disabled_id ).toggle( false );
		}
	}
	
	return {
		// public methods
		tinymce_init: tinymce_init,
		tinymce_init_tabs: tinymce_init_tabs,
		tinymce_insert_shortcode: tinymce_insert_shortcode,
		calculate: calculate_score_history,
		bulk_action_warning: bulk_action_warning,
		toggle_points: toggle_points,
		set_input_param: set_input_param,
		restore_input_param: restore_input_param,
		disable_inputs: disable_inputs,
		toggle_linked_options: toggle_linked_radio_options
	};

} )( jQuery );

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
