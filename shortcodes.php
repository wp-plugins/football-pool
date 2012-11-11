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
add_shortcode( 'fp-register', array( 'Football_Pool_Shortcodes', 'shortcode_register_link' ) );

class Football_Pool_Shortcodes {
	//[fp-register]
	//		title	: title parameter for the <a href>
	public function shortcode_register_link( $atts, $content = '' ) {
		extract( shortcode_atts( array(
					'title' => '',
					'new' => '0',
				), $atts ) );
		
		$title = ( $title != '' ) ? sprintf( ' title="%s"', $title ) : '';
		$site_url = get_site_url();
		$redirect = get_permalink();
		$redirect = ( $redirect != false ) ? sprintf( '&amp;redirect_to=%s', $redirect ) : '';
		$content = ( $content > '' ) ? $content : __( 'register', FOOTBALLPOOL_TEXT_DOMAIN );
		$target = ( $new == '1' ) ? ' target="_blank"' : '';
		
		return sprintf( '<a href="%s/wp-login.php?action=register%s"%s%s>%s</a>'
						, $site_url
						, $redirect
						, $title
						, $target
						, $content
				);
	}
	
	//[fp-group]
	//		id	: show the standing for the group with this id, defaults to a non-existing group and thus
	//			  will not show anything when none is given.
	public function shortcode_group( $atts ) {
		extract( shortcode_atts( array(
					'id' => 1,
				), $atts ) );
		
		$output = '';
		
		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();
		
		if ( is_numeric( $id ) && array_key_exists( $id, $group_names ) ) {
			$output = $groups->print_group_standing( $id, 'wide', 'shortcode' );
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
		$default_num = 5;
		
		extract( shortcode_atts( array(
					'league' => FOOTBALLPOOL_LEAGUE_ALL,
					'num' => $default_num,
					'date' => 'now',
				), $atts ) );
		
		global $current_user;
		get_currentuserinfo();
		$pool = new Football_Pool_Pool;
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		if ( !is_numeric( $num ) || $num <= 0 ) {
			$num = $default_num;
		}
		
		if ( $date == 'postdate' ) {
			$score_date = get_the_date( 'Y-m-d H:i' );
		//} elseif ( ( $score_date = DateTime::createFromFormat( 'Y-m-d H:i', $date ) ) !== false ) {
		} elseif ( ( $score_date = date_create( $date ) ) !== false ) {
			$score_date = $score_date->format( 'Y-m-d H:i' );
		} else {
			$score_date = '';
		}
		
		$rows = $pool->get_pool_ranking_limited( $league, $num, $score_date );
		
		$output = '';
		if ( count( $rows ) > 0 ) {
			$i = 1;
			$output .= '<table class="poolranking">';
			//$output .= '<caption>' . __( 'ranking on date', FOOTBALLPOOL_TEXT_DOMAIN ) . " {$score_date}</caption>";
			foreach ( $rows as $row ) {
				$class = ( $i % 2 == 0 ? 'even' : 'odd' );
				if ( $row['userId'] == $current_user->ID ) $class .= ' currentuser';
				
				$url = esc_url( add_query_arg( array( 'user' => $row['userId'] ), $userpage ) );
				$output .= '<tr class="' . $class . '"><td>' . $i++ . '.</td>'
						. '<td><a href="' . $url . '">' . $row['userName'] 
						. '</a></td>' . '<td class="score">' . $row['points'] . '</td></tr>';
			}
			$output .= '</table>';
		} else {
			$output = '<p>' . __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
		}
		
		return $output;
	}
	
	//[countdown]
	public function shortcode_countdown( $atts ) {
		extract( shortcode_atts( array(
					'date' => '',
					'match' => '',
					'texts' => '',
					'display' => 'block',
				), $atts ) );
		
		$matches = new Football_Pool_Matches();
		
		$cache_key = 'fp_countdown_id';
		$id = wp_cache_get( $cache_key );
		if ( $id === false ) {
			$id = 1;
		}
		wp_cache_set( $cache_key, $id + 1 );
		
		$countdown_date = 0;
		if ( (int) $match > 0 ) {
			$match_info = $matches->get_match_info( (int) $match );
			if ( array_key_exists( 'playDate', $match_info ) )
				$countdown_date = new DateTime( Football_Pool_Utils::date_from_gmt( $match_info['playDate'] ) );
		}
		
		if ( ! is_object( $countdown_date ) ) {
			// $countdown_date = DateTime::createFromFormat( 'Y-m-d H:i', $date );
			$countdown_date = date_create( $date );
			if ( $date == '' || $countdown_date === false ) {
				$firstMatch = $matches->get_first_match_info();
				$countdown_date = new DateTime( Football_Pool_Utils::date_from_gmt( $firstMatch['playDate'] ) );
			}
		}
		
		if ( $texts == 'none' ) $texts = ';;;';
		$texts = explode( ';', $texts );
		
		if ( is_array( $texts ) && count( $texts ) == 4 ) {
			$extra_text = "{'pre_before':'{$texts[0]}', 'post_before':'{$texts[1]}', 'pre_after':'{$texts[2]}', 'post_after':'{$texts[3]}'}";
		} else {
			$extra_text = 'footballpool_countdown_extra_text';
		}
		
		$year  = $countdown_date->format( 'Y' );
		$month = $countdown_date->format( 'm' );
		$day   = $countdown_date->format( 'd' );
		$hour  = $countdown_date->format( 'H' );
		$min   = $countdown_date->format( 'i' );
		$sec   = 0;
		
		$output = '';
		if ( $display == 'inline' ) {
			$output .= "<span id='countdown-{$id}'>&nbsp;</span>";
		} else {
			$output .= "<div style='text-align:center; width: 80%;'><h2 id='countdown-{$id}'>&nbsp;</h2></div>";
		}
		
		$output .= "<script type='text/javascript'>
					footballpool_do_countdown( '#countdown-{$id}', footballpool_countdown_time_text, {$extra_text}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 2 );
					window.setInterval( function() { footballpool_do_countdown( '#countdown-{$id}', footballpool_countdown_time_text, {$extra_text}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 2 ); }, 1000 );
					</script>";
		
		return $output;
	}
	
	//[link slug=""]
	public function shortcode_link( $atts ) {
		$link = '';
		if ( isset( $atts['slug'] ) ) {
			$id = Football_Pool_Utils::get_wp_option( 'footballpool_page_id_' . $atts['slug'] );
			if ( $id ) {
				$link = get_page_link( $id );
			}
		}
		return $link;
	}
	
	//[webmaster]
	public function shortcode_webmaster( $atts ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_webmaster' );
	}

	//[bank]
	public function shortcode_bank( $atts ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_bank' );
	}

	//[money]
	public function shortcode_money( $atts ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_money' );
	}

	//[start]
	public function shortcode_start( $atts ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_start' );
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
