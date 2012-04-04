<?php 
// shortcodes
add_shortcode( 'link', array( 'Football_Pool_Shortcodes', 'shortcode_link' ) );
add_shortcode( 'webmaster', array( 'Football_Pool_Shortcodes', 'shortcode_webmaster' ) );
add_shortcode( 'bank', array( 'Football_Pool_Shortcodes', 'shortcode_bank' ) );
add_shortcode( 'money', array( 'Football_Pool_Shortcodes', 'shortcode_money' ) );
add_shortcode( 'start', array( 'Football_Pool_Shortcodes', 'shortcode_start' ) );
add_shortcode( 'totopoints', array( 'Football_Pool_Shortcodes', 'shortcode_totopoints' ) );
add_shortcode( 'fullpoints', array( 'Football_Pool_Shortcodes', 'shortcode_fullpoints' ) );
add_shortcode( 'countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );

class Football_Pool_Shortcodes {
	//[countdown]
	public function shortcode_countdown( $atts ) {
		$matches = new Matches();
		$firstMatch = $matches->get_first_match_info();
		$date = $firstMatch['matchTimestamp'];
		$year  = date( 'Y', $date );
		$month = date( 'm', $date );
		$day   = date( 'd', $date );
		$hour  = date( 'H', $date );
		$min   = date( 'i', $date );
		$sec   = 0;
		
		$imgpath = FOOTBALLPOOL_PLUGIN_URL;
		
		return "<div style='text-align:center; width: 80%;'>
					<h2 id='countdown'>&nbsp;</h2>
				</div>
				<script type='text/javascript'>
				do_countdown( '#countdown', footballpool_countdown_text, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 2 );
				window.setInterval( function() { do_countdown( '#countdown', footballpool_countdown_text, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 2 ); }, 1000 );
				</script>";
	}
	
	//[link slug=""]
	public function shortcode_link( $atts ) {
		$link = '';
		if ( isset( $atts['slug'] ) ) {
			$id = get_option( 'footballpool_page_id_' . $atts['slug'] );
			if ( $id ) {
				$link = get_page_link( $id );
			}
		}
		return $link;
	}
	
	//[webmaster]
	public function shortcode_webmaster( $atts ) {
		return get_option( 'footballpool_webmaster' );
	}

	//[bank]
	public function shortcode_bank( $atts ) {
		return get_option( 'footballpool_bank' );
	}

	//[money]
	public function shortcode_money( $atts ) {
		return get_option( 'footballpool_money' );
	}

	//[start]
	public function shortcode_start( $atts ) {
		return get_option( 'footballpool_start' );
	}

	//[totopoints]
	public function shortcode_totopoints( $atts ) {
		return Utils::get_wp_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
	}

	//[fullpoints]
	public function shortcode_fullpoints( $atts ) {
		return Utils::get_wp_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
	}
}
?>
