jQuery( document ).ready( function() {
	
	/**
	 *	Examples of setting the max possible answers for a multiple choice question (checkbox).
	 *
	 *	function set_max_answers( <question ID>, <max number of answers> )
	 */
	//set_max_answers( 1, 2 ); // question ID 1 has a max of 2
	//set_max_answers( 5, 4 ); // question ID 5 has a max of 4
	
	// set some default Highchart options
	Highcharts.setOptions( {
		// no link to highcharts.com
		credits: {
			enabled: false
		},
		// Google Chart colors
		colors: [ '#3366CC', '#DC3912', '#FF9900', '#109618', '#990099', '#0099C6', '#DD4477', 
				'#66AA00', '#B82E2E', '#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC',
				'#E67300', '#8B0707', '#651067', '#329262', '#5574A6', '#3B3EAC', '#B77322',
				'#16D620', '#B91383', '#F4359E', '#9C5935', '#A9C413', '#2A778D', '#668D1C',
				'#BEA413', '#0C5922', '#743411' ],
		// NL
		lang: {
			resetZoom: "weer uitzoomen",
			resetZoomTitle: "uitzoomen naar 1:1"
		}
	});
	
	// user selection on the statistics page
	line_chart_control();
	
	// fancybox
	jQuery( ".fancybox" ).fancybox( {
		openEffect	: 'elastic',
		closeEffect	: 'elastic',
		openSpeed	: 600, 
		closeSpeed	: 200 
	} );

});

function line_chart_control() {
	jQuery( "input:checkbox", "ol.userselector" ).bind( "click", function() {
		jQuery( this ).parent().toggleClass( "selected" );
	} );
}

function change_joker( id ) {
	// set the joker input
	jQuery( "input[name*='_joker']", "#predictionform" ).val( id.substring( id.indexOf( "_" ) + 1 ) );
	// remove old joker
	jQuery( ".joker", "#matchinfo" ).removeClass( "joker" ).addClass( "nojoker" );
	// set new joker
	jQuery( "#" + id ).removeClass( "nojoker" ).addClass( "joker" );
}

function update_chars( id, chars ) {
	var length = jQuery( "#" + id ).val().length;
	var remaining = chars - length;
	jQuery( "#" + id ).parent().find( "span span" ).replaceWith( "<span>" + remaining + "</span>" );
}

function do_countdown( el, time_text, extra_text, year, month, day, hour, minute, second, format ) {
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
	});
}

function check_max_answers( id, max ) {
	var question = "#q" + id;
	if( jQuery( question + " :checkbox:checked" ).length >= max) {
		jQuery( question + " :checkbox:not(:checked)" ).attr( "disabled", "disabled" );
	} else {
		jQuery( question + " :checkbox" ).removeAttr( "disabled" );
	}
}