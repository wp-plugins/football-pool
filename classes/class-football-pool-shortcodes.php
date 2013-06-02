<?php
// shortcodes
add_shortcode( 'fp-user-score', array( 'Football_Pool_Shortcodes', 'shortcode_user_score' ) );
add_shortcode( 'fp-predictionform', array( 'Football_Pool_Shortcodes', 'shortcode_predictionform' ) );
add_shortcode( 'fp-link', array( 'Football_Pool_Shortcodes', 'shortcode_link' ) );
add_shortcode( 'fp-webmaster', array( 'Football_Pool_Shortcodes', 'shortcode_webmaster' ) );
add_shortcode( 'fp-bank', array( 'Football_Pool_Shortcodes', 'shortcode_bank' ) );
add_shortcode( 'fp-money', array( 'Football_Pool_Shortcodes', 'shortcode_money' ) );
add_shortcode( 'fp-start', array( 'Football_Pool_Shortcodes', 'shortcode_start' ) );
add_shortcode( 'fp-totopoints', array( 'Football_Pool_Shortcodes', 'shortcode_totopoints' ) );
add_shortcode( 'fp-fullpoints', array( 'Football_Pool_Shortcodes', 'shortcode_fullpoints' ) );
add_shortcode( 'fp-goalpoints', array( 'Football_Pool_Shortcodes', 'shortcode_goalpoints' ) );
add_shortcode( 'fp-countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );
add_shortcode( 'fp-ranking', array( 'Football_Pool_Shortcodes', 'shortcode_ranking' ) );
add_shortcode( 'fp-group', array( 'Football_Pool_Shortcodes', 'shortcode_group' ) );
add_shortcode( 'fp-register', array( 'Football_Pool_Shortcodes', 'shortcode_register_link' ) );

// deprecated shortcodes
add_shortcode( 'link', array( 'Football_Pool_Shortcodes', 'shortcode_link' ) );
add_shortcode( 'webmaster', array( 'Football_Pool_Shortcodes', 'shortcode_webmaster' ) );
add_shortcode( 'bank', array( 'Football_Pool_Shortcodes', 'shortcode_bank' ) );
add_shortcode( 'money', array( 'Football_Pool_Shortcodes', 'shortcode_money' ) );
add_shortcode( 'start', array( 'Football_Pool_Shortcodes', 'shortcode_start' ) );
add_shortcode( 'totopoints', array( 'Football_Pool_Shortcodes', 'shortcode_totopoints' ) );
add_shortcode( 'fullpoints', array( 'Football_Pool_Shortcodes', 'shortcode_fullpoints' ) );
add_shortcode( 'goalpoints', array( 'Football_Pool_Shortcodes', 'shortcode_goalpoints' ) );
add_shortcode( 'countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );

class Football_Pool_Shortcodes {
	private function date_helper( $date ) {
		if ( $date == 'postdate' ) {
			$the_date = get_the_date( 'Y-m-d H:i' );
		} elseif ( ( $the_date = date_create( $date ) ) !== false ) {
			$the_date = $the_date->format( 'Y-m-d H:i' );
		} else {
			$the_date = '';
		}
		
		return $the_date;
	}
	
	//[fp-user-score] 
	//  Displays the score for a given user in the given ranking.  
	//
	//    user    : user Id, defaults to the logged in user 
	//    ranking : ranking Id, defaults to the default ranking
	//    date    : show score up until this date, 
	//              possible values 'now', 'postdate', a datetime value formatted like this 'Y-m-d H:i',
	//              defaults to 'now'
	//    text    : text to display if no user or no score is found, defaults to "0"
	public function shortcode_user_score( $atts ) {
		extract( shortcode_atts( array(
					'user' => '',
					'ranking' => FOOTBALLPOOL_RANKING_DEFAULT,
					'date' => 'now',
					'text' => '0',
				), $atts ) );
		
		$output = $text;
		
		if ( $user == '' || ! is_numeric( $user ) ) {
			$user = get_current_user_id();
		}
		
		if ( ( int ) $user > 0 ) {
			$pool = new Football_Pool_Pool;
			$score = $pool->get_user_score( $user, $ranking, self::date_helper( $date ) );
			if ( $score != null ) $output = $score;
		}
		
		return $output;
	}
	
	//[fp-predictionform] 
	//    All arguments can be entered in the following formats (example for matches:
	//        match 1               -> match="1"
	//        matches 1 to 5        -> match="1-5"
	//        matches 1, 3 and 6    -> match="1,3,6"
	//        matches 1 to 5 and 10 -> match="1-5,10"
	//    If an argument is left empty it is ignored. Matches are always displayed first.
	//
	//    match     : collection of match ids 
	//    question  : collection of question ids
	//    matchtype : collection of match type ids
	public function shortcode_predictionform( $atts ) {
		extract( shortcode_atts( array(
					'match' => '',
					'question' => '',
					'matchtype' => '',
				), $atts ) );
		
		global $current_user;
		get_currentuserinfo();
		// $questions = new Football_Pool_Questions;
		$pool = new Football_Pool_Pool;
		$matches = new Football_Pool_Matches;
		
		// save user input
		$id = Football_Pool_Utils::get_counter_value( 'fp_predictionform_counter' );
		$output = $pool->prediction_form_update();
		
		// extract all ids from the arguments
		$match_ids = Football_Pool_Utils::extract_ids( $match );
		$question_ids = Football_Pool_Utils::extract_ids( $question );
		$matchtype_ids = Football_Pool_Utils::extract_ids( $matchtype );
		// add all matches in the match types collection to the match_ids
		$match_ids = array_merge( $match_ids, $matches->get_matches_for_match_type( $matchtype_ids ) );
		
		$matches = $matches->get_match_info_for_user( $current_user->ID, $match_ids );
		$questions = $pool->get_bonus_questions_for_user( $current_user->ID, $question_ids );
		
		// display form(s)
		$output .= $pool->prediction_form_start( $id );
		$output .= $pool->prediction_form_matches( $matches, true, $id );
		$output .= $pool->prediction_form_questions( $questions, true, $id );
		$output .= $pool->prediction_form_end();
		
		return $output;
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
	//		ranking	: only show points from this ranking, defaults to complete ranking
	//		num 	: number of users to show, defaults to 5
	//		date	: show ranking up until this date, 
	//				  possible values 'now', 'postdate', a datetime value formatted like this 'Y-m-d H:i',
	//				  defaults to 'now'
	public function shortcode_ranking( $atts ) {
		$default_num = 5;
		
		extract( shortcode_atts( array(
					'league' => FOOTBALLPOOL_LEAGUE_ALL,
					'num' => $default_num,
					'ranking' => FOOTBALLPOOL_RANKING_DEFAULT,
					'date' => 'now',
				), $atts ) );
		
		global $current_user;
		get_currentuserinfo();
		$pool = new Football_Pool_Pool;
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		if ( ! is_numeric( $num ) || $num <= 0 ) {
			$num = $default_num;
		}
		
		if ( ! is_numeric( $ranking ) || $ranking <= 0 ) {
			$ranking = FOOTBALLPOOL_RANKING_DEFAULT;
		}
		
		$rows = $pool->get_pool_ranking_limited( $league, $num, $ranking, self::date_helper( $date ) );
		
		$output = '';
		if ( count( $rows ) > 0 ) {
			$i = 1;
			$output .= '<table class="pool-ranking ranking-shortcode">';
			foreach ( $rows as $row ) {
				$class = ( $i % 2 == 0 ? 'even' : 'odd' );
				if ( $row['userId'] == $current_user->ID ) $class .= ' currentuser';
				
				$url = esc_url( add_query_arg( array( 'user' => $row['userId'] ), $userpage ) );
				$output .= '<tr class="' . $class . '"><td>' . $i++ . '.</td>'
						. '<td><a href="' . $url . '">' . $row['userName']
						. '</a>' . Football_Pool::user_name( $row['userId'], 'label' ) . '</td>' . '<td class="score">' . $row['points'] . '</td></tr>';
			}
			$output .= '</table>';
		} else {
			$output .= '<p>' . __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
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
		
		$id = Football_Pool_Utils::get_counter_value( 'fp_countdown_id' );
		
		$countdown_date = 0;
		if ( (int) $match > 0 ) {
			$match_info = $matches->get_match_info( (int) $match );
			if ( array_key_exists( 'playDate', $match_info ) )
				$countdown_date = new DateTime( Football_Pool_Utils::date_from_gmt( $match_info['playDate'] ) );
		}
		
		if ( ! is_object( $countdown_date ) ) {
			$countdown_date = date_create( $date );
			if ( $date == '' || $countdown_date === false ) {
				$first_match = $matches->get_first_match_info();
				$countdown_date = new DateTime(
											Football_Pool_Utils::date_from_gmt( $first_match['playDate'] ) 
										);
			}
		}
		
		if ( $texts == 'none' ) $texts = ';;;'; // 4 empty strings overwriting the default texts
		
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
			$id = Football_Pool_Utils::get_fp_option( 'page_id_' . $atts['slug'] );
			if ( $id ) {
				$link = get_page_link( $id );
			}
		}
		return $link;
	}
	
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
	
	//[webmaster]
	public function shortcode_webmaster( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'webmaster' );
	}

	//[bank]
	public function shortcode_bank( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'bank' );
	}

	//[money]
	public function shortcode_money( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'money' );
	}

	//[start]
	public function shortcode_start( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'start' );
	}

	//[totopoints]
	public function shortcode_totopoints( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
	}

	//[fullpoints]
	public function shortcode_fullpoints( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
	}

	//[goalpoints]
	public function shortcode_goalpoints( $atts ) {
		return Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
	}
}
?>
