// minified with http://closure-compiler.appspot.com/home

jQuery( document ).ready( function() {
	// colorbox
	jQuery( ".fp-lightbox" ).colorbox( {
		transition		: 'elastic',
		speed			: 400
	} );
} );

var FootballPool = (function ( $ ) {
	
	var i18n = FootballPool_i18n;
	
	function change_joker( id ) {
		var the_form = $( '#' + id ).closest( 'form' );
		// set the joker input
		var joker = id.substring( id.indexOf( '_' ) + 1 );
		joker = joker.substring( 0, joker.indexOf( '_' ) );
		$( "input[name*='_joker']", the_form ).val( joker );
		// remove old joker
		$( ".fp-joker" ).removeClass( "fp-joker" ).addClass( "fp-nojoker" );
		// set new joker
		$( "#" + id ).removeClass( "fp-nojoker" ).addClass( "fp-joker" );
	}
	
	function update_chars( id, chars ) {
		var length = $( "#" + id ).val().length;
		var remaining = chars - length;
		$( "#" + id ).parent().find( "span span" ).replaceWith( "<span>" + remaining + "</span>" );
	}
	
	function do_countdown( el, extra_text, year, month, day, hour, minute, second, format ) {
		var date_to = new Date( year, month-1, day, hour, minute, second ).getTime();
		var date_now = new Date().getTime();
		var diff = Math.abs( Math.round( ( date_to - date_now ) / 1000 ) );
		var pre, post, txt = '';
		var tmp;
		
		if ( extra_text == null ) {
			extra_text = {
							'pre_before' : i18n.count_pre_before, 
							'post_before' : i18n.count_post_before, 
							'pre_after' : i18n.count_pre_after, 
							'post_after' : i18n.count_post_after
						}
		}
		
		if ( date_to < date_now ) {
			pre = extra_text['pre_after'], post = extra_text['post_after'];
		} else {
			pre = extra_text['pre_before'], post = extra_text['post_before'];
		}
		
		switch ( format ) {
			case 1: // only seconds
				txt += diff + ' ' + ( diff == 1 ? i18n.count_second : i18n.count_seconds );
				break;
			case 2: // days, hours, minutes, seconds
				switch ( true ) {
					case diff > 86400:
						tmp = Math.floor( diff / 86400 );
						txt += tmp + ' ' + ( tmp == 1 ? i18n.count_day : i18n.count_days ) + ', ';
						diff -= tmp * 86400;
					case diff > 3600:
						tmp = Math.floor( diff / 3600 );
						txt += tmp + ' ' + ( tmp == 1 ? i18n.count_hour : i18n.count_hours ) + ', ';
						diff -= tmp * 3600;
					case diff > 60:
						tmp = Math.floor( diff / 60 );
						txt += tmp + ' ' + ( tmp == 1 ? i18n.count_minute : i18n.count_minutes ) + ', ';
						diff -= tmp * 60;
					default:
						txt += diff + ' ' + ( diff == 1 ? i18n.count_second : i18n.count_seconds );
				}
				break;
			case 3: // hours, minutes, seconds
				switch ( true ) {
					case diff > 3600:
						tmp = Math.floor( diff / 3600 );
						txt += tmp + ' ' + ( tmp == 1 ? i18n.count_hour : i18n.count_hours ) + ', ';
						diff -= tmp * 3600;
					case diff > 60:
						tmp = Math.floor( diff / 60 );
						txt += tmp + ' ' + ( tmp == 1 ? i18n.count_minute : i18n.count_minutes ) + ', ';
						diff -= tmp * 60;
					default:
						txt += diff + ' ' + ( diff == 1 ? i18n.count_second : i18n.count_seconds );
				}
				break;
		}
		
		$( el ).text( pre + txt + post );
	}
	
	// based on http://www.frequency-decoder.com/2006/07/20/correctly-calculating-a-date-suffix
	// suffixes must be an array of format ["th", "st", "nd", "rd", "th"];
	function ordinal_suffix( d ) {
		var suffix = '';
		var suffixes = arguments[1] || ["th", "st", "nd", "rd", "th"];
		d = String( d );
		if ( d.substr( -( Math.min( d.length, 2 ) ) ) > 3 && d.substr( -( Math.min( d.length, 2 ) ) ) < 21 ) {
			suffix = suffixes[0];
		} else {
			suffix = suffixes[Math.min( Number( d ) % 10, 4 )];
		}
		return suffix;
	}

	function add_ordinal_suffix( d ) {
		return d + ordinal_suffix( d, arguments[1] );
	}

	function charts_user_toggle() {
		$( "input:checkbox", ".user-selector ol" ).bind( "click", function() {
			$( this ).parent().parent().toggleClass( "selected" );
		} );
	}
	
	function set_max_answers( id, max ) {
		var question = "#q" + id;
		
		// check onload
		check_max_answers( id, max );
		
		// and set the click action
		$( question + " :checkbox" ).click( function() {
			check_max_answers( id, max )
		} );
	}

	function check_max_answers( id, max ) {
		var question = "#q" + id;
		if( $( question + " :checkbox:checked" ).length >= max ) {
			$( question + " :checkbox:not(:checked)" ).attr( "disabled", "disabled" );
		} else {
			$( question + " :checkbox" ).removeAttr( "disabled" );
		}
	}
	
	return {
		// public methods
		add_ordinal_suffix: add_ordinal_suffix,
		change_joker: change_joker,
		update_chars: update_chars,
		countdown: do_countdown,
		charts_user_toggle: charts_user_toggle,
		set_max_answers: set_max_answers
	};

} )( jQuery );

