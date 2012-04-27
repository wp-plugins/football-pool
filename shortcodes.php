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
add_shortcode( 'fp-ranking', array( 'Football_Pool_Shortcodes', 'shortcode_ranking' ) );
add_shortcode( 'fp-group', array( 'Football_Pool_Shortcodes', 'shortcode_group' ) );

class Football_Pool_Shortcodes {
	//[fp-group] 
	//		id	: show the standing for the group with this id, defaults to a non-existing group and thus
	//			  will not show anything when none is given.
	public function shortcode_group( $atts ) {
		extract( shortcode_atts( array(
					'id' => -1,
				), $atts ) );
		
		$output = '';
		
		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();
		
		if ( is_numeric( $id ) && array_key_exists( $id, $group_names ) ) {
			$output = $groups->print_group_standing( $id );
		}
		
		return $output;
	}
	
	//[fp-ranking] 
	//		league	: only show users in this league, defaults to all
	//		num 	: number of users to show, defaults to 5
	//		date	: show ranking up until this date, 
	//				  possible values 'now', 'postdate', a datetime value formatted like this 'Y-m-d H:i',
	//				  defaults to 'now'
	public function shortcode_ranking( $atts ) {
		extract( shortcode_atts( array(
					'league' => FOOTBALLPOOL_LEAGUE_ALL,
					'num' => 5,
					'date' => 'now',
				), $atts ) );
		
		global $current_user;
		get_currentuserinfo();
		$pool = new Football_Pool_Pool;
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		if ( $date == 'postdate' ) {
			$score_date = get_the_date( 'Y-m-d H:i' );
		} elseif ( ( $score_date = DateTime::createFromFormat( 'Y-m-d H:i', $date ) ) !== false ) {
			$score_date = $score_date->format( 'Y-m-d H:i' );
		} else {
			$score_date = '';
		}
		
		$rows = $pool->get_pool_ranking_limited( $league, $num, $score_date );
		
		$output = '';
		if ( count( $rows ) > 0 ) {
			$i = 1;
			$output .= '<table class="poolranking">';
			//$output .= '<caption>' . __( 'de stand op', FOOTBALLPOOL_TEXT_DOMAIN ) . " {$score_date}</caption>";
			foreach ( $rows as $row ) {
				$class = ( $i % 2 == 0 ? 'even' : 'odd' );
				if ( $row['userId'] == $current_user->ID ) $class .= ' currentuser';
				
				$output .= '<tr class="' . $class . '"><td>' . $i++ . '.</td>'
						. '<td><a href="' . $userpage . '?user=' . $row['userId'] . '">' . $row['userName'] 
						. '</a></td>' . '<td class="score">' . $row['points'] . '</td></tr>';
			}
			$output .= '</table>';
		} else {
			$output = '<p>' . __( 'Geen wedstrijdgegevens beschikbaar.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
		}
		
		return $output;
	}
	
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
		return Football_Pool_Utils::get_wp_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
	}

	//[fullpoints]
	public function shortcode_fullpoints( $atts ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
	}
}
?>
