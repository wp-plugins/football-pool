jQuery( document ).ready( function() {
	// set some default Highcharts options
	if ( typeof Highcharts != 'undefined' ) {
		Highcharts.setOptions( {
			// no link to highcharts.com
			credits: {
				enabled: false
			}
			// Google Chart colors
			, colors: [ '#3366CC', '#DC3912', '#FF9900', '#109618', '#990099', '#0099C6', '#DD4477', 
					'#66AA00', '#B82E2E', '#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC',
					'#E67300', '#8B0707', '#651067', '#329262', '#5574A6', '#3B3EAC', '#B77322',
					'#16D620', '#B91383', '#F4359E', '#9C5935', '#A9C413', '#2A778D', '#668D1C',
					'#BEA413', '#0C5922', '#743411' ]
			// // NL
			// , lang: {
				// resetZoom: "weer uitzoomen",
				// resetZoomTitle: "uitzoomen naar 1:1"
			// }
		} );
	}
	
	// user selection on the statistics page
	FootballPool.charts_user_toggle();
} );
