<?php
// shortcodes
add_shortcode( 'fp-predictions', array( 'Football_Pool_Shortcodes', 'shortcode_predictions' ) );
add_shortcode( 'fp-predictionform', array( 'Football_Pool_Shortcodes', 'shortcode_predictionform' ) );
add_shortcode( 'fp-matches', array( 'Football_Pool_Shortcodes', 'shortcode_matches' ) );
add_shortcode( 'fp-user-score', array( 'Football_Pool_Shortcodes', 'shortcode_user_score' ) );
add_shortcode( 'fp-user-ranking', array( 'Football_Pool_Shortcodes', 'shortcode_user_ranking' ) );
add_shortcode( 'fp-ranking', array( 'Football_Pool_Shortcodes', 'shortcode_ranking' ) );
add_shortcode( 'fp-countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );
add_shortcode( 'fp-group', array( 'Football_Pool_Shortcodes', 'shortcode_group' ) );
add_shortcode( 'fp-register', array( 'Football_Pool_Shortcodes', 'shortcode_register_link' ) );
add_shortcode( 'fp-link', array( 'Football_Pool_Shortcodes', 'shortcode_link' ) );
add_shortcode( 'fp-totopoints', array( 'Football_Pool_Shortcodes', 'shortcode_totopoints' ) );
add_shortcode( 'fp-fullpoints', array( 'Football_Pool_Shortcodes', 'shortcode_fullpoints' ) );
add_shortcode( 'fp-goalpoints', array( 'Football_Pool_Shortcodes', 'shortcode_goalpoints' ) );
add_shortcode( 'fp-diffpoints', array( 'Football_Pool_Shortcodes', 'shortcode_diffpoints' ) );
add_shortcode( 'fp-jokermultiplier', array( 'Football_Pool_Shortcodes', 'shortcode_jokermultiplier' ) );
add_shortcode( 'fp-league-info', array( 'Football_Pool_Shortcodes', 'shortcode_league_info' ) );
add_shortcode( 'fp-stats-settings', array( 'Football_Pool_Shortcodes', 'shortcode_stats_settings' ) );

// deprecated since v2.4.0, these will be removed in a future version
add_shortcode( 'fp-webmaster', array( 'Football_Pool_Shortcodes', 'shortcode_webmaster' ) );
add_shortcode( 'fp-bank', array( 'Football_Pool_Shortcodes', 'shortcode_bank' ) );
add_shortcode( 'fp-money', array( 'Football_Pool_Shortcodes', 'shortcode_money' ) );
add_shortcode( 'fp-start', array( 'Football_Pool_Shortcodes', 'shortcode_start' ) );

// removed shortcodes
// add_shortcode( 'link', array( 'Football_Pool_Shortcodes', 'shortcode_link' ) );
// add_shortcode( 'webmaster', array( 'Football_Pool_Shortcodes', 'shortcode_webmaster' ) );
// add_shortcode( 'bank', array( 'Football_Pool_Shortcodes', 'shortcode_bank' ) );
// add_shortcode( 'money', array( 'Football_Pool_Shortcodes', 'shortcode_money' ) );
// add_shortcode( 'start', array( 'Football_Pool_Shortcodes', 'shortcode_start' ) );
// add_shortcode( 'totopoints', array( 'Football_Pool_Shortcodes', 'shortcode_totopoints' ) );
// add_shortcode( 'fullpoints', array( 'Football_Pool_Shortcodes', 'shortcode_fullpoints' ) );
// add_shortcode( 'goalpoints', array( 'Football_Pool_Shortcodes', 'shortcode_goalpoints' ) );
// add_shortcode( 'countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );

class Football_Pool_Shortcodes {
	private static function date_helper( $date ) {
		if ( $date == 'postdate' ) {
			$the_date = get_the_date( 'Y-m-d H:i' );
		} elseif ( $date != 'now' && ( $the_date = date_create( $date ) ) !== false ) {
			$the_date = $the_date->format( 'Y-m-d H:i' );
		} else {
			$the_date = '';
		}
		
		return $the_date;
	}
	
	private static function format_helper( $input, $format) {
		if ( isset( $format ) && is_string( $format ) ) {
			$input = sprintf( $format, $input );
		}
		
		return $input;
	}
	
	//[fp-stats-settings] 
	//    Displays a link to the stats settings (only works on the statistics page when needed, otherwise it 
	//    returns an empty string).
	public static function shortcode_stats_settings() {
		return Football_Pool_Statistics_Page::the_title( '' );
	}
	
	//[fp-league-info] 
	//    Displays info about a league. E.g the total points or the average points (points divided by the number of players) of a league.
	//
	//    league  : league ID
	//    info    : what info to show (name, points, avgpoints, numplayers, playernames)
	//    ranking : optional ranking ID (defaults to the default ranking) when used in conjunction with the points or avgpoints
	//    format  : optional format for the output (uses sprintf notation: http://php.net/sprintf)
	public static function shortcode_league_info( $atts ) {
		extract( shortcode_atts( array(
					'league' => FOOTBALLPOOL_LEAGUE_ALL,
					'info' => 'name',
					'ranking' => FOOTBALLPOOL_RANKING_DEFAULT,
					'format' => null,
				), $atts ) );
		
		$output = '';
		
		if ( is_numeric( $league ) && in_array( $info, array( 'name', 'points', 'avgpoints', 'numplayers', 'playernames' ) ) ) {
			$pool = new Football_Pool_Pool;
			if ( $pool->has_leagues && array_key_exists( $league, $pool->leagues ) ) {
				if ( $info == 'name' ) {
					$output = $pool->leagues[$league]['league_name'];
				} else {
					$rows = $pool->get_pool_ranking( $league, $ranking );
					$numplayers = count( $rows );
					if ( $info == 'numplayers' ) {
						$output = $numplayers;
					} elseif ( $info == 'points' || $info == 'avgpoints' ) {
						$points = 0;
						foreach ( $rows as $row ) {
							$points += $row['points'];
						}
						$output = ( $info == 'avgpoints' ) ? ( $points / $numplayers ) : $points;
					} elseif ( $info == 'playernames' ) {
						$output = '<ul class="fp-player-list shortcode">';
						foreach ( $rows as $row ) {
							$output .= '<li>' . $pool->user_name( $row['user_id'] ) . '</li>';
						}
						$output .= '</ul>';
					}
				}
			}
		}
		
		return apply_filters( 'footballpool_shortcode_html_fp-league-info', self::format_helper( $output, $format ) );
	}
	
	//[fp-matches] 
	//    Displays a matches table for a given collection of matches or match types. 
	//    All arguments (except group) can be entered in the following formats (example for matches:
	//        match 1               -> match="1"
	//        matches 1 to 5        -> match="1-5"
	//        matches 1, 3 and 6    -> match="1,3,6"
	//        matches 1 to 5 and 10 -> match="1-5,10"
	//    If an argument is left empty it is ignored. If group is given, all other arguments are ignored.
	//
	//    match     : collection of match ids 
	//    matchtype : collection of match type ids
	//    group     : a group ID
	public static function shortcode_matches( $atts ) {
		extract( shortcode_atts( array(
					'match' => null,
					'matchtype' => null,
					'group' => null,
				), $atts ) );
		
		$output = '';
		
		$matches = new Football_Pool_Matches;
		$the_matches = array();
		
		if ( is_numeric( $group ) ) {
			$groups = new Football_Pool_Groups;
			$the_matches = $groups->get_plays( (int) $group );
		} else {
			// extract all ids from the arguments
			$match_ids = Football_Pool_Utils::extract_ids( $match );
			$matchtype_ids = Football_Pool_Utils::extract_ids( $matchtype );
			// add all matches in the match types collection to the match_ids
			$match_ids = array_merge( $match_ids, $matches->get_matches_for_match_type( $matchtype_ids ) );
			
			foreach ( $matches->matches as $match ) {
				if ( in_array( $match['id'], $match_ids ) ) $the_matches[] = $match;
			}
		}
		
		if ( count( $the_matches ) > 0 ) $output .= $matches->print_matches( $the_matches );
		return apply_filters( 'footballpool_shortcode_html_fp-matches', $output );
	}
	
	//[fp-predictions] 
	//  Displays the prediction and score table for a given match or question. 
	//  If an invalid match or question is given, the shortcode returns the default text.
	//
	//    match    : match Id
	//    question : question Id
	//    text     : a text to show if no prediction table can be displayed, defaults to no text
	public static function shortcode_predictions( $atts ) {
		extract( shortcode_atts( array(
					'match' => null,
					'question' => null,
					'text' => '',
				), $atts ) );
		
		$output = '';
		
		if ( is_numeric( $match ) || is_numeric( $question ) ) {
			$stats = new Football_Pool_Statistics;
			
			$match = (int) $match;
			if ( $match > 0 ) {
				$matches = new Football_Pool_Matches;
				$match_info = $matches->get_match_info( $match );
				if ( count( $match_info ) > 0 ) {
					if ( $matches->always_show_predictions || $match_info['match_is_editable'] == false ) {
						$output .= $stats->show_predictions_for_match( $match_info );
					}
				}
			}
			
			$question = (int) $question;
			if ( $question > 0 ) {
				$pool = new Football_Pool_Pool;
				$question_info = $pool->get_bonus_question_info( $question );
				if ( $question_info ) {
					if ( $pool->always_show_predictions || $question_info['question_is_editable'] == false ) {
						$output .= $stats->show_answers_for_bonus_question( $question );
					}
				}
			}
			
			if ( $output == '' ) {
				$output = $text;
			}
		}
		
		return apply_filters( 'footballpool_shortcode_html_fp-predictions', $output );
	}
	
	//[fp-user-ranking] 
	//  Displays the ranking for a given user in the given ranking.  
	//
	//    user    : user Id, defaults to the logged in user 
	//    ranking : ranking Id, defaults to the default ranking
	//    date    : show score up until this date, 
	//              possible values 'now', 'postdate', a datetime value formatted like this 'Y-m-d H:i',
	//              defaults to 'now'
	//    text    : text to display if no user or no ranking is found, defaults to ""
	public static function shortcode_user_ranking( $atts ) {
		extract( shortcode_atts( array(
					'user' => '',
					'ranking' => FOOTBALLPOOL_RANKING_DEFAULT,
					'date' => 'now',
					'text' => '',
				), $atts ) );
		
		$output = $text;
		
		if ( $user == '' || ! is_numeric( $user ) ) {
			$user = get_current_user_id();
		}
		
		if ( ( int ) $user > 0 ) {
			$pool = new Football_Pool_Pool;
			$rank = $pool->get_user_rank( $user, $ranking, self::date_helper( $date ) );
			if ( $rank != null ) $output = $rank;
		}
		
		return apply_filters( 'footballpool_shortcode_html_fp-user-ranking', $output );
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
	public static function shortcode_user_score( $atts ) {
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
		
		return apply_filters( 'footballpool_shortcode_html_fp-user-score', $output );
	}
	
	//[fp-predictionform] 
	//    All arguments can be entered in the following formats (example for matches):
	//        match 1               -> match="1"
	//        matches 1 to 5        -> match="1-5"
	//        matches 1, 3 and 6    -> match="1,3,6"
	//        matches 1 to 5 and 10 -> match="1-5,10"
	//    If an argument is left empty it is ignored. Matches are always displayed first.
	//    If the current visitor is not logged in, the shortcode returns a message to log on or register.
	//
	//    match     : collection of match ids 
	//    question  : collection of question ids
	//    matchtype : collection of match type ids
	public static function shortcode_predictionform( $atts ) {
		$default_message = 
			sprintf( __( 'You have to be a registered user and <a href="%s">logged in</a> to play in this pool.', FOOTBALLPOOL_TEXT_DOMAIN ), 
							wp_login_url(
								apply_filters( 'the_permalink', get_permalink( get_the_ID() ) )
							)
						);
		extract( shortcode_atts( array(
					'match' => '',
					'question' => '',
					'matchtype' => '',
					'text' => $default_message,
				), $atts ) );
		
		if ( ! is_user_logged_in() ) {
			return $text;
		}
		
		global $current_user;
		get_currentuserinfo();
		// $questions = new Football_Pool_Questions;
		$pool = new Football_Pool_Pool;
		$matches = new Football_Pool_Matches;
		
		// save user input
		$id = Football_Pool_Utils::get_counter_value( 'fp_predictionform_counter' );
		$output = $pool->prediction_form_update( $id );
		
		// extract all ids from the arguments
		$match_ids = Football_Pool_Utils::extract_ids( $match );
		$question_ids = Football_Pool_Utils::extract_ids( $question );
		$matchtype_ids = Football_Pool_Utils::extract_ids( $matchtype );
		// add all matches in the match types collection to the match_ids
		ChromePhp::log($match_ids);
		$match_ids = array_merge( $match_ids, $matches->get_matches_for_match_type( $matchtype_ids ) );


		ChromePhp::log($matches->get_matches_for_match_type( $matchtype_ids ));
		
		$matches = $matches->get_match_info_for_user( $current_user->ID, $match_ids );
		$questions = $pool->get_bonus_questions_for_user( $current_user->ID, $question_ids );
		
		// display form(s)
		$output .= $pool->prediction_form_start( $id );
		$output .= $pool->prediction_form_matches( $matches, true, $id );
		$output .= $pool->prediction_form_questions( $questions, true, $id );
		$output .= $pool->prediction_form_end();
		
		return apply_filters( 'footballpool_shortcode_html_fp-predictionform', $output );
	}
	
	//[fp-group]
	//		id	: show the standing for the group with this id, defaults to a non-existing group and thus
	//			  will not show anything when none is given.
	public static function shortcode_group( $atts ) {
		extract( shortcode_atts( array(
					'id' => 1,
				), $atts ) );
		
		$output = '';
		
		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();
		
		if ( is_numeric( $id ) && array_key_exists( $id, $group_names ) ) {
			$output = $groups->print_group_standing( $id, 'wide', 'shortcode' );
		}
		
		return apply_filters( 'footballpool_shortcode_html_fp-group', $output );
	}
	
	//[fp-ranking] 
	//		league	: only show users in this league, defaults to all
	//		ranking	: only show points from this ranking, defaults to complete ranking
	//		num 	: number of users to show, defaults to 5
	//		date	: show ranking up until this date, 
	//				  possible values 'now', 'postdate', a datetime value formatted like this 'Y-m-d H:i',
	//				  defaults to 'now'
	public static function shortcode_ranking( $atts ) {
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
		
		if ( ! is_numeric( $num ) || $num <= 0 ) {
			$num = $default_num;
		}
		
		if ( ! is_numeric( $ranking ) || $ranking <= 0 ) {
			$ranking = FOOTBALLPOOL_RANKING_DEFAULT;
		}
		
		$rows = $pool->get_pool_ranking_limited( $league, $num, $ranking, self::date_helper( $date ) );
		$filtered_rows = apply_filters( 'footballpool_ranking_array', $rows );
		
		$output = '';
		if ( count( $filtered_rows ) > 0 ) {
			$users = array();
			foreach ( $filtered_rows as $row ) $users[] = $row['user_id'];
			
			$output .= $pool->print_pool_ranking( $league, $current_user->ID, $ranking, $users, $rows, 'shortcode' );
		} else {
			$output .= '<p>' . __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
		}
		
		return apply_filters( 'footballpool_shortcode_html_fp-ranking', $output, $rows, $filtered_rows );
	}
	
	//[fp-countdown]
	public static function shortcode_countdown( $atts ) {
		extract( shortcode_atts( array(
					'date' => '',
					'match' => '',
					'texts' => '',
					'display' => 'block',
					'format' => 2,
				), $atts ) );
		
		$matches = new Football_Pool_Matches();
		
		$id = Football_Pool_Utils::get_counter_value( 'fp_countdown_id' );
		
		$countdown_date = 0;
		if ( (int) $match > 0 ) {
			$match_info = $matches->get_match_info( (int) $match );
			if ( array_key_exists( 'play_date', $match_info ) )
				$countdown_date = new DateTime( Football_Pool_Utils::date_from_gmt( $match_info['play_date'] ) );
		}
		
		if ( ! is_object( $countdown_date ) ) {
			$countdown_date = date_create( $date );
			if ( $date == '' || $countdown_date === false ) {
				$first_match = $matches->get_first_match_info();
				$countdown_date = new DateTime(
											Football_Pool_Utils::date_from_gmt( $first_match['play_date'] ) 
										);
			}
		}
		
		if ( $texts == 'none' ) $texts = ';;;'; // 4 empty strings overwriting the default texts
		
		$texts = explode( ';', $texts );
		
		if ( is_array( $texts ) && count( $texts ) == 4 ) {
			$extra_text = "{'pre_before':'{$texts[0]}', 'post_before':'{$texts[1]}', 'pre_after':'{$texts[2]}', 'post_after':'{$texts[3]}'}";
		} else {
			$extra_text = 'null';
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
		
		$output .= "<script>
					FootballPool.countdown( '#countdown-{$id}', {$extra_text}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, {$format} );
					window.setInterval( function() { FootballPool.countdown( '#countdown-{$id}', {$extra_text}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, {$format} ); }, 1000 );
					</script>";
		
		return apply_filters( 'footballpool_shortcode_html_fp-countdown', $output );
	}
	
	//[fp-link slug=""]
	public static function shortcode_link( $atts ) {
		$output = '';
		if ( isset( $atts['slug'] ) ) {
			$id = Football_Pool_Utils::get_fp_option( 'page_id_' . $atts['slug'] );
			if ( $id ) {
				$output = get_page_link( $id );
			}
		}
		return apply_filters( 'footballpool_shortcode_html_fp-link', $output );
	}
	
	//[fp-register]
	//		title	: title parameter for the <a href>
	public static function shortcode_register_link( $atts, $content = '' ) {
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
		
		$output = sprintf( '<a href="%s/wp-login.php?action=register%s"%s%s>%s</a>'
						, $site_url
						, $redirect
						, $title
						, $target
						, $content
					);
		return apply_filters( 'footballpool_shortcode_html_fp-register', $output );
	}
	
	//[fp-webmaster]
	public static function shortcode_webmaster( $atts ) {
		trigger_error( 'Shortcode [fp-webmaster] is deprecated as of Football Pool v2.4.0. Do not use anymore.', E_USER_NOTICE );
		$output = Football_Pool_Utils::get_fp_option( 'webmaster' );
		return apply_filters( 'footballpool_shortcode_html_fp-webmaster', $output );
	}

	//[fp-bank]
	public static function shortcode_bank( $atts ) {
		trigger_error( 'Shortcode [fp-bank] is deprecated as of Football Pool v2.4.0. Do not use anymore.', E_USER_NOTICE );
		$output = Football_Pool_Utils::get_fp_option( 'bank' );
		return apply_filters( 'footballpool_shortcode_html_fp-bank', $output );
	}

	//[fp-money]
	public static function shortcode_money( $atts ) {
		trigger_error( 'Shortcode [fp-money] is deprecated as of Football Pool v2.4.0. Do not use anymore.', E_USER_NOTICE );
		$output = Football_Pool_Utils::get_fp_option( 'money' );
		return apply_filters( 'footballpool_shortcode_html_fp-money', $output );
	}

	//[fp-start]
	public static function shortcode_start( $atts ) {
		trigger_error( 'Shortcode [fp-start] is deprecated as of Football Pool v2.4.0. Do not use anymore.', E_USER_NOTICE );
		$output = Football_Pool_Utils::get_fp_option( 'start' );
		return apply_filters( 'footballpool_shortcode_html_fp-start', $output );
	}

	//[fp-totopoints]
	public static function shortcode_totopoints( $atts ) {
		$output = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
		return apply_filters( 'footballpool_shortcode_html_fp-totopoints', $output );
	}

	//[fp-fullpoints]
	public static function shortcode_fullpoints( $atts ) {
		$output = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
		return apply_filters( 'footballpool_shortcode_html_fp-fullpoints', $output );
	}

	//[fp-goalpoints]
	public static function shortcode_goalpoints( $atts ) {
		$output = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
		return apply_filters( 'footballpool_shortcode_html_fp-goalpoints', $output );
	}

	//[fp-diffpoints]
	public static function shortcode_diffpoints( $atts ) {
		$output = Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' );
		return apply_filters( 'footballpool_shortcode_html_fp-diffpoints', $output );
	}
	
	//[fp-joker-multiplier]
	public static function shortcode_jokermultiplier( $atts ) {
		$output = Football_Pool_Utils::get_fp_option( 'joker_multiplier', FOOTBALLPOOL_JOKERMULTIPLIER, 'int' );
		return apply_filters( 'footballpool_shortcode_html_fp-jokermultiplier', $output );
	}
}
