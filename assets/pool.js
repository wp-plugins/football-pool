jQuery( document ).ready( function() {
	// colorbox
	jQuery( ".fp-lightbox" ).colorbox( {
		transition		: 'elastic',
		speed			: 400
	} );
} );

function footballpool_change_joker( id ) {
	// set the joker input
	jQuery( "input[name*='_joker']", "#predictionform" ).val( id.substring( id.indexOf( "_" ) + 1 ) );
	// remove old joker
	jQuery( ".joker", "#matchinfo" ).removeClass( "joker" ).addClass( "nojoker" );
	// set new joker
	jQuery( "#" + id ).removeClass( "nojoker" ).addClass( "joker" );
}

function footballpool_update_chars( id, chars ) {
	var length = jQuery( "#" + id ).val().length;
	var remaining = chars - length;
	jQuery( "#" + id ).parent().find( "span span" ).replaceWith( "<span>" + remaining + "</span>" );
}

function footballpool_do_countdown( el, time_text, extra_text, year, month, day, hour, minute, second, format ) {
	var date_to = new Date(year, month-1, day, hour, minute, second).getTime();
	var date_now = new Date().getTime();
	var diff = Math.abs(Math.round((date_to - date_now) / 1000));
	var pre, post, txt = '';

	if ( date_to < date_now ) {
		pre = extra_text['pre_after'], post = extra_text['post_after'];
	} else {
		pre = extra_text['pre_before'], post = extra_text['post_before'];
	}
	
	var tmp;
	
	switch ( format ) {
		case 1: // only seconds
			txt += diff + ' ' + ( diff == 1 ? time_text['second'] : time_text['seconds'] );
			break;
		case 2: // days, hours, minutes, seconds
			switch ( true ) {
				case diff > 86400:
					tmp = Math.floor( diff / 86400 );
					txt += tmp + ' ' + ( tmp == 1 ? time_text['day'] : time_text['days'] ) + ', ';
					diff -= tmp * 86400;
				case diff > 3600:
					tmp = Math.floor( diff / 3600 );
					txt += tmp + ' ' + ( tmp == 1 ? time_text['hour'] : time_text['hours'] ) + ', ';
					diff -= tmp * 3600;
				case diff > 60:
					tmp = Math.floor( diff / 60 );
					txt += tmp + ' ' + ( tmp == 1 ? time_text['minute'] : time_text['minutes'] ) + ', ';
					diff -= tmp * 60;
				default:
					txt += diff + ' ' + ( diff == 1 ? time_text['second'] : time_text['seconds'] );
			}
			break;
		case 3: // hours, minutes, seconds
			switch ( true ) {
				case diff > 3600:
					tmp = Math.floor( diff / 3600 );
					txt += tmp + ' ' + ( tmp == 1 ? time_text['hour'] : time_text['hours'] ) + ', ';
					diff -= tmp * 3600;
				case diff > 60:
					tmp = Math.floor( diff / 60 );
					txt += tmp + ' ' + ( tmp == 1 ? time_text['minute'] : time_text['minutes'] ) + ', ';
					diff -= tmp * 60;
				default:
					txt += diff + ' ' + ( diff == 1 ? time_text['second'] : time_text['seconds'] );
			}
			break;
	}
	
	jQuery( el ).text( pre + txt + post );
}

function set_max_answers( id, max ) {
	var question = "#q" + id;
	
	// check onload
	check_max_answers( id, max );
	// and set the click action
	jQuery( question + " :checkbox" ).click( function() {
		check_max_answers( id, max )
	} );
}

function check_max_answers( id, max ) {
	var question = "#q" + id;
	if( jQuery( question + " :checkbox:checked" ).length >= max) {
		jQuery( question + " :checkbox:not(:checked)" ).attr( "disabled", "disabled" );
	} else {
		jQuery( question + " :checkbox" ).removeAttr( "disabled" );
	}
}

// based on http://www.frequency-decoder.com/2006/07/20/correctly-calculating-a-date-suffix
// suffixes must be an array of format ["th", "st", "nd", "rd", "th"];
function ordinal_suffix( d ) {
	suffixes = arguments[1] || ["th", "st", "nd", "rd", "th"];
	d = String( d );
	if ( d.substr( -( Math.min( d.length, 2 ) ) ) > 3 && d.substr( -(Math.min( d.length, 2 ) ) ) < 21 ) {
		suffix = suffixes[0];
	} else {
		suffix = suffixes[Math.min( Number( d ) % 10, 4 )];
	}
	return suffix;
}

function add_ordinal_suffix( d ) {
	return d + ordinal_suffix( d, arguments[1] );
}
