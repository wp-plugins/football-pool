<?php
class Football_Pool_Matches {
	private $joker_blocked;
	private $teams;
	private $matches_are_editable;
	public $joker_value;
	private $force_lock_time = false;
	private $lock;
	public $matches;
	public $always_show_predictions = false;
	private $use_spin_controls = true;
	public $has_matches = false;
	private $time_format;
	private $date_format;
	private $num_jokers = FOOTBALLPOOL_DEFAULT_JOKERS;
	private $pool_has_jokers;
	
	public function __construct() {
		$this->num_jokers = Football_Pool_Utils::get_fp_option( 'number_of_jokers', FOOTBALLPOOL_DEFAULT_JOKERS, 'int' );
		$this->pool_has_jokers = ( $this->num_jokers > 0 );
		$this->joker_blocked = $this->pool_has_jokers ? false : true;
		$this->enable_edits();
		
		$datetime = Football_Pool_Utils::get_fp_option( 'matches_locktime', '' );
		$this->force_lock_time = 
			( Football_Pool_Utils::get_fp_option( 'stop_time_method_matches', 0, 'int' ) == 1 )
			&& ( $datetime != '' );
		if ( $this->force_lock_time ) {
			//$date = DateTime::createFromFormat( 'Y-m-d H:i', $datetime );
			$date = new DateTime( Football_Pool_Utils::date_from_gmt( $datetime ) );
			$this->lock = $date->format( 'U' );
		} else {
			$this->lock = Football_Pool_Utils::get_fp_option( 'maxperiod', FOOTBALLPOOL_MAXPERIOD, 'int' );
		}
		
		// layout options
		$this->always_show_predictions = ( (int) Football_Pool_Utils::get_fp_option( 'always_show_predictions' ) === 1 );
		$this->use_spin_controls = ( Football_Pool_Utils::get_fp_option( 'use_spin_controls', 1, 'int' ) === 1 );
		$this->time_format = get_option( 'time_format', FOOTBALLPOOL_TIME_FORMAT );
		$this->date_format = get_option( 'date_format', FOOTBALLPOOL_DATE_FORMAT );
		
		// cache match info
		$this->matches = $this->match_info();
		$this->has_matches = ( count( $this->matches ) > 0 );
	}
	
	public function disable_edits() {
		$this->matches_are_editable = false;
		$this->joker_blocked = true;
	}
	
	private function enable_edits() {
		$this->matches_are_editable = true;
	}
	
	public function get_next_match( $ts = -1, $team_id = null ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $ts == -1 ) $ts = time();
		
		$next_match = null;
		foreach ( $this->matches as $match ) {
			if ( $match['match_timestamp'] > $ts && ( $team_id == null || $team_id == $match['home_team_id'] || $team_id == $match['away_team_id'] ) ) {
				$next_match = $match;
				break;
			}
		}
		
		return $next_match; // null if no match is found
	}
	
	public function get_last_games( $num_games = 4 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT id, home_team_id, away_team_id, home_score, away_score 
								FROM {$prefix}matches 
								WHERE play_date <= NOW() AND home_score IS NOT NULL AND away_score IS NOT NULL 
								ORDER BY play_date DESC, id DESC 
								LIMIT %d", $num_games
					);
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function get_match_sorting_method() {
		$order = Football_Pool_Utils::get_fp_option( 'match_sort_method', FOOTBALLPOOL_MATCH_SORT, 'int' );
		switch ( $order ) {
			case 3:
				$order = 'm.matchtype ASC, m.play_date DESC, m.id DESC';
				break;
			case 2:
				$order = 'm.matchtype DESC, m.play_date ASC, m.id ASC';
				break;
			case 1:
				$order = 'm.play_date DESC, m.id DESC';
				break;
			case 0:
			default:
				$order = 'm.play_date ASC, m.id ASC';
		}
		
		return $order;
	}
	
	private function matches_query( $extra = '' ) {
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sorting = $this->get_match_sorting_method();
		
		return "SELECT 
					m.id, 
					m.play_date,
					m.home_team_id, m.away_team_id, 
					m.home_score, m.away_score, 
					s.name AS stadium_name, s.id AS stadium_id,
					t.name AS matchtype, t.id AS type_id, t.id AS match_type_id
				FROM {$prefix}matches m
				JOIN {$prefix}stadiums s ON ( m.stadium_id = s.id )
				JOIN {$prefix}matchtypes t ON ( m.matchtype_id = t.id AND t.visibility = 1 )
				{$extra}
				ORDER BY {$sorting}";
	}
	
	public function get_first_match_info() {
		return array_shift( $this->matches );
	}
	
	public function get_info( $types = null ) {
		global $wpdb;
		if ( is_array( $types ) && count( $types ) > 0 ) {
			$types = implode( ',', $types );
			$sql = $this->matches_query( "WHERE t.id IN ( {$types} ) " );
		} else {
			$sql = $this->matches_query();
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	private function match_info() {
		$match_info = wp_cache_get( FOOTBALLPOOL_CACHE_MATCHES );
		
		if ( $match_info === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$match_info = array();
			$teams = new Football_Pool_Teams;
			
			$rows = $wpdb->get_results( $this->matches_query(), ARRAY_A );
			
			foreach ( $rows as $row ) {
				$i = (int) $row['id'];
				$matchdate = new DateTime( $row['play_date'] );
				$ts = $matchdate->format( 'U' );
				
				$match_info[$i] = array();
				$match_info[$i]['match_datetime'] = $matchdate->format( 'd M Y H:i' );
				$match_info[$i]['match_timestamp'] = $ts;
				$match_info[$i]['play_date'] = $row['play_date'];
				$match_info[$i]['date'] = $row['play_date'];
				$match_info[$i]['home_score'] = is_numeric( $row['home_score'] ) ? (int) $row['home_score'] : $row['home_score'];
				$match_info[$i]['away_score'] = is_numeric( $row['away_score'] ) ? (int) $row['away_score'] : $row['away_score'];
				$match_info[$i]['home_team'] = ( 
						isset( $teams->team_info[(int) $row['home_team_id']]['team_name'] ) ? 
							$teams->team_info[(int) $row['home_team_id']]['team_name'] : 
							''
						);
				$match_info[$i]['away_team'] = ( 
						isset( $teams->team_info[(int) $row['away_team_id']]['team_name'] ) ? 
							$teams->team_info[(int) $row['away_team_id']]['team_name'] : 
							''
						);
				$match_info[$i]['home_team_id'] = (int) $row['home_team_id'];
				$match_info[$i]['away_team_id'] = (int) $row['away_team_id'];
				$match_info[$i]['match_is_editable'] = $this->match_is_editable( $ts );
				$match_info[$i]['id'] = (int) $row['id'];
				$match_info[$i]['stadium_id'] = (int) $row['stadium_id'];
				$match_info[$i]['stadium_name'] = $row['stadium_name'];
				$match_info[$i]['match_type_id'] = (int) $row['match_type_id'];
				$match_info[$i]['match_type'] = $row['matchtype'];
				$match_info[$i]['matchtype'] = $row['matchtype'];
				$match_info[$i]['linked_questions'] = null;
				// get group info for home team
				$match_info[$i]['group_id'] = ( 
						isset( $teams->team_info[(int) $row['home_team_id']]['group_id'] ) ? 
							(int) $teams->team_info[(int) $row['home_team_id']]['group_id'] : 
							0
						);
				$match_info[$i]['group_name'] = ( 
						isset( $teams->team_info[(int) $row['home_team_id']]['group_name'] ) ? 
							$teams->team_info[(int) $row['home_team_id']]['group_name'] : 
							''
						);
			}
			
			// get linked questions
			$sql = "SELECT id, match_id FROM {$prefix}bonusquestions WHERE match_id > 0";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			if ( $rows ) {
				$match_id = 0;
				foreach ( $rows as $row ) {
					if ( (int) $row['match_id'] != $match_id ) {
						if ( $match_id > 0 ) {
							$match_info[$match_id]['linked_questions'] = $question_ids;
						}
						$question_ids = array();
						$match_id = (int) $row['match_id'];
					}
					$question_ids[] = (int) $row['id'];
				}
				$match_info[$match_id]['linked_questions'] = $question_ids;
			}
			
			$match_info = apply_filters( 'footballpool_matches', $match_info );
			wp_cache_set( FOOTBALLPOOL_CACHE_MATCHES, $match_info );
		}
		
		return $match_info;
	}
	
	public function get_match_info( $match ) {
		if ( is_integer( $match ) && array_key_exists( $match, $this->matches ) ) {
			return $this->matches[$match];
		} else {
			return array();
		}
	}
	
	public function get_match_info_for_user( $user_id, $match_ids = array() ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$order = $this->get_match_sorting_method();
		
		$ids = '';
		if ( is_array( $match_ids ) && count( $match_ids ) > 0 ) {
			$match_ids = implode( ',', $match_ids );
			$ids = " AND m.id IN ( {$match_ids} ) ";
		}
		
		$sql = $wpdb->prepare( "SELECT m.id, p.home_score, p.away_score, p.has_joker
								FROM {$prefix}matches m 
								JOIN {$prefix}matchtypes t 
									ON ( m.matchtype_id = t.id {$ids})
								LEFT OUTER JOIN {$prefix}predictions p 
									ON ( p.match_id = m.id AND p.user_id = %d )
								WHERE t.visibility = 1
								ORDER BY {$order}",
								$user_id
							);
		
		$match_info = array();
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $rows as $row ) {
			$i = (int) $row['id'];
			// get detailed match info from cache
			$match_info[$i] = $this->get_match_info( $i );
			// save the real result
			$match_info[$i]['real_home_score'] = $match_info[$i]['home_score'];
			$match_info[$i]['real_away_score'] = $match_info[$i]['away_score'];
			// change match result to predictions from user
			$match_info[$i]['home_score'] = $row['home_score'];
			$match_info[$i]['away_score'] = $row['away_score'];
			// add joker value
			$match_info[$i]['has_joker'] = $row['has_joker'];
		}
		
		return apply_filters( 'footballpool_matches_for_user', $match_info, $user_id );
	}
	
	public function get_joker_value_for_user( $user_id ) {
		if ( ! $this->pool_has_jokers ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT match_id FROM {$prefix}predictions WHERE user_id = %d AND has_joker = 1"
								, $user_id );
		$joker = $wpdb->get_var( $sql );
		
		return $joker;
	}
	
	public function first_empty_match_for_user( $user_id ) {
		if ( ! is_integer( $user_id ) ) return 0;

		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT m.id FROM {$prefix}matches m 
								LEFT OUTER JOIN {$prefix}predictions p 
									ON ( p.match_id = m.id AND p.user_id = %d )
								WHERE p.user_id IS NULL
								ORDER BY m.play_date ASC, id ASC LIMIT 1",
								$user_id
							);
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? $row['id'] : 0;
	}
	
	public function get_match_info_for_teams( $a, $b ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT home_team_id, away_team_id, home_score, away_score 
								FROM {$prefix}matches 
								WHERE ( home_team_id = %d AND away_team_id = %d ) 
									OR ( home_team_id = %d AND away_team_id = %d )",
								$a, $b,
								$b, $a
							);
		
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		if ( $row ) {
			return array(
						$row['home_team_id'] => $row['home_score'], 
						$row['away_team_id'] => $row['away_score']
					);
		} else {
			return 0;
		}
	}
	
	public function print_matches( $matches ) {
		$teams = new Football_Pool_Teams;
		$teamspage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		$matchtype = $date_title = '';
		
		// define templates
		$template_start = '<table class="matchinfo">';
		$template_start = apply_filters( 'footballpool_match_table_template_start', $template_start );
		
		$template_end = '</table>';
		$template_end = apply_filters( 'footballpool_match_table_template_end', $template_end );
		
		$match_template = '<tr id="match-%match_id%" class="%css_class%" 
							title="' . __( 'match', FOOTBALLPOOL_TEXT_DOMAIN ) . ' %match_id%">
								<td class="time">%match_time%</td>
								<td class="home">%home_team%</td>
								<td class="flag">%home_team_flag%</td>
								<td class="score">
									<a title="' . __( 'Match statistics', FOOTBALLPOOL_TEXT_DOMAIN ) . '" href="%match_stats_url%">%home_score% - %away_score%</a>
								</td>
								<td class="flag">%away_team_flag%</td>
								<td class="away">%away_team%</td>
								</tr>';
		$match_template = apply_filters( 'footballpool_match_table_match_template', $match_template );
		
		$match_type_template = '<tr><td class="matchtype" colspan="6">%match_type%</td></tr>';
		$match_type_template = apply_filters( 'footballpool_match_table_match_type_template', $match_type_template );
		
		$date_row_template = '<tr><td class="matchdate" colspan="6" title="%match_day%">%match_datetime_formatted%</td></tr>';
		$date_row_template = apply_filters( 'footballpool_match_table_date_row_template', $date_row_template );
		
		// define the start and end template params
		$template_params = array();
		$template_params = apply_filters( 'footballpool_match_table_template_params', $template_params );
		
		// start output
		$output = Football_Pool_Utils::placeholder_replace( $template_start, $template_params );
		foreach ( $matches as $row ) {
			$matchdate = new DateTime( $row['play_date'] );
			$localdate = new DateTime( $this->format_match_time( $matchdate, 'Y-m-d H:i' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN ), $localdate->format( 'U' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$match_day = date_i18n( _x( 'l', 'a date format string (see http://php.net/date)', FOOTBALLPOOL_TEXT_DOMAIN )
									, $localdate->format( 'U' ) );
			
			// define the template param values
			$match_template_params = array(
				'match_id' => $row['id'],
				'match_type_id' => $row['match_type_id'],
				'match_type' => __( $row['match_type'], FOOTBALLPOOL_TEXT_DOMAIN ),
				'match_timestamp' => $row['match_timestamp'],
				'match_date' => $localdate->format( $this->date_format ),
				'match_time' => $localdate->format( $this->time_format ),
				'match_day' => $match_day,
				'match_datetime_formatted' => $localdate_formatted,
				'match_utcdate' => $row['play_date'],
				'match_stats_url' => esc_url(
										add_query_arg(
											array( 'view' => 'matchpredictions', 'match' => $row['id'] ),
											$statisticspage
										)
									),
				'stadium_id' => $row['stadium_id'],
				'stadium_name' => $row['stadium_name'],
				'home_team_id' => $row['home_team_id'],
				'away_team_id' => $row['away_team_id'],
				'home_team' => isset( $teams->team_names[ (int) $row['home_team_id'] ] ) ?
								htmlentities( $teams->team_names[ (int) $row['home_team_id'] ], null, 'UTF-8' ) : '',
				'away_team' => isset( $teams->team_names[ (int) $row['away_team_id'] ] ) ?
								htmlentities( $teams->team_names[ (int) $row['away_team_id'] ], null, 'UTF-8' ) : '',
				'home_team_flag' => $teams->flag_image( (int) $row['home_team_id'] ),
				'away_team_flag' => $teams->flag_image( (int) $row['away_team_id'] ),
				'home_score' => $row['home_score'],
				'away_score' => $row['away_score'],
				'group_id' => $row['group_id'],
				'group_name' => $row['group_name'],
				'css_class' => $row['match_is_editable'] ? 'match open' : 'match closed',
			);
			if ( $teams->show_team_links ) {
				$match_template_params['home_team'] = sprintf( '<a title="%s" href="%s">%s</a>'
																, $match_template_params['home_team']
																, esc_url( 
																		add_query_arg( 
																			array( 'team' => $row['home_team_id'] ), 
																			$teamspage 
																		) 
																	)
																, $match_template_params['home_team']
														);
				$match_template_params['away_team'] = sprintf( '<a title="%s" href="%s">%s</a>'
																, $match_template_params['away_team']
																, esc_url( 
																		add_query_arg( 
																			array( 'team' => $row['away_team_id'] ), 
																			$teamspage 
																		) 
																	)
																, $match_template_params['away_team']
														);
			}
			// allow for extra fields to be added to the template
			$match_template_params = 
				apply_filters( 'footballpool_match_table_match_template_params', $match_template_params, $row['id'] );
			
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= Football_Pool_Utils::placeholder_replace( $match_type_template, $match_template_params );
			}
			
			if ( $date_title != $localdate_formatted ) {
				$date_title = $localdate_formatted;
				$output .= Football_Pool_Utils::placeholder_replace( $date_row_template, $match_template_params );
			}
			
			$output .= Football_Pool_Utils::placeholder_replace( $match_template, $match_template_params );
		}
		$output .= Football_Pool_Utils::placeholder_replace( $template_end, $template_params );
		
		return $output;
	}
	
	public function print_matches_for_input( $matches, $form_id, $user_id ) {
		$teams = new Football_Pool_Teams;
		$pool = new Football_Pool_Pool;
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		$date_title = $matchtype = $joker = '';
		
		// define templates
		$template_start = '<table id="matchinfo-%form_id%" class="matchinfo input">';
		$template_start = apply_filters( 'footballpool_predictionform_template_start', $template_start );
		
		$template_end = '</table>';
		$template_end = apply_filters( 'footballpool_predictionform_template_end', $template_end );
		
		$match_template = '<tr id="match-%match_id%-%form_id%" class="%css_class%">
								<td class="time">%match_time%</td>
								<td class="home">%home_team%</td>
								<td class="flag">%home_team_flag%</td>
								<td class="score">%home_input%</td>
								<td>-</td>
								<td class="score">%away_input%</td>
								<td class="flag">%away_team_flag%</td>
								<td class="away">%away_team%</td>
								<td>%joker%</td>
								<td title="' . __( 'score', FOOTBALLPOOL_TEXT_DOMAIN ) . '" class="numeric">%user_score%</td>
								<td>%stats_link%</td>
								</tr>';
		$match_template = apply_filters( 'footballpool_predictionform_match_template', $match_template );
		
		$match_type_template = '<tr><td class="matchtype" colspan="11">%match_type%</td></tr>';
		$match_type_template = apply_filters( 'footballpool_predictionform_match_type_template', $match_type_template );
		
		$date_row_template = '<tr><td class="matchdate" colspan="11">%match_datetime_formatted%</td></tr>';
		$date_row_template = apply_filters( 'footballpool_predictionform_date_row_template', $date_row_template );
		
		$linked_question_template = '<tr id="match-%match_id-%form_id-question-%question_id%" class="linked-question">
									<td colspan="11">%question%</td></tr>';
		$linked_question_template = apply_filters( 'footballpool_predictionform_linked_questions_template'
													, $linked_question_template );
		
		// define the start and end template params
		$template_params = array(
			'form_id' => $form_id,
			'user_id' => $user_id,
		);
		$template_params = apply_filters( 'footballpool_predictionform_template_params', $template_params );
		
		// start output
		$output = Football_Pool_Utils::placeholder_replace( $template_start, $template_params );
		foreach ( $matches as $row ) {
			$info = $this->get_match_info( (int) $row['id'] );
			
			if ( (int) $row['has_joker'] === 1 ) $joker = (int) $row['id'];
			
			$matchdate = new DateTime( $row['play_date'] );
			$localdate = new DateTime( $this->format_match_time( $matchdate, 'Y-m-d H:i' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN ), $localdate->format( 'U' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$match_day = date_i18n( _x( 'l', 'a date format string (see http://php.net/date)', FOOTBALLPOOL_TEXT_DOMAIN )
									, $localdate->format( 'U' ) );
			
			// define the template param values
			$match_template_params = array(
				'form_id' => $form_id,
				'match_id' => $info['id'],
				'match_type_id' => $info['match_type_id'],
				'match_type' => __( $info['match_type'], FOOTBALLPOOL_TEXT_DOMAIN ),
				'match_timestamp' => $info['match_timestamp'],
				'match_date' => $localdate->format( $this->date_format ),
				'match_time' => $localdate->format( $this->time_format ),
				'match_day' => $match_day,
				'match_datetime_formatted' => $localdate_formatted,
				'match_utcdate' => $info['play_date'],
				'match_stats_url' => esc_url(
										add_query_arg(
											array( 'view' => 'matchpredictions', 'match' => $info['id'] ),
											$statisticspage
										)
									),
				'stadium_id' => $info['stadium_id'],
				'stadium_name' => $info['stadium_name'],
				'home_team_id' => $info['home_team_id'],
				'away_team_id' => $info['away_team_id'],
				'home_team' => isset( $teams->team_names[ (int) $info['home_team_id'] ] ) ?
								htmlentities( $teams->team_names[ (int) $info['home_team_id'] ], null, 'UTF-8' ) : '',
				'away_team' => isset( $teams->team_names[ (int) $info['away_team_id'] ] ) ?
								htmlentities( $teams->team_names[ (int) $info['away_team_id'] ], null, 'UTF-8' ) : '',
				'home_team_flag' => $teams->flag_image( (int) $info['home_team_id'] ),
				'away_team_flag' => $teams->flag_image( (int) $info['away_team_id'] ),
				'home_score' => $info['home_score'],
				'away_score' => $info['away_score'],
				'group_id' => $info['group_id'],
				'group_name' => $info['group_name'],
				'home_input' => $this->show_pool_input( '_home_' . $info['id'], $row['home_score'], $info['match_timestamp'] ),
				'away_input' => $this->show_pool_input( '_away_' . $info['id'], $row['away_score'], $info['match_timestamp'] ),
				'joker' => $this->show_pool_joker( $joker, (int) $info['id'], $info['match_timestamp'], $form_id ),
				'user_score' => $this->show_score( 
										$info['home_score'], $info['away_score'], 
										$row['home_score'], $row['away_score'], 
										$row['has_joker'], $info['match_timestamp'] 
									),
				'stats_link' => $this->show_users_link( $info['id'], $info['match_timestamp'] ),
				'css_class' => $info['match_is_editable'] ? 'match open' : 'match closed',
			);
			// allow for extra fields to be added to the template
			$match_template_params = 
				apply_filters( 'footballpool_predictionform_match_template_params', $match_template_params, $row['id'], $user_id );
			
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= Football_Pool_Utils::placeholder_replace( $match_type_template, $match_template_params );
			}
			
			if ( $date_title != $localdate_formatted ) {
				$date_title = $localdate_formatted;
				$output .= Football_Pool_Utils::placeholder_replace( $date_row_template, $match_template_params );
			}
			
			if ( (int) $row['has_joker'] === 1 ) {
				$joker = (int) $row['id'];
			}
			
			$output .= Football_Pool_Utils::placeholder_replace( $match_template, $match_template_params );
			
			if ( is_array( $info['linked_questions'] ) && count( $info['linked_questions'] ) > 0 ) {
				$questions = $pool->get_bonus_questions_for_user( $user_id, $info['linked_questions'] );
				foreach( $questions as $question ) {
					$linked_question_template_params = array(
						'form_id' => $form_id,
						'match_id' => $info['id'],
						'question_id' => $question['id'],
						'question' => $pool->print_bonus_question( $question, '' ),
					);
					// allow extra fields to be added to the template
					$linked_question_template_params = 
						apply_filters( 'footballpool_linked_question_template_params'
										, $linked_question_template_params, $question['id'], $user_id );
					
					$output .= Football_Pool_Utils::placeholder_replace( $linked_question_template
																		, $linked_question_template_params );
				}
			}
		}
		$output .= Football_Pool_Utils::placeholder_replace( $template_end, $template_params );
		
		$this->joker_value = $joker;
		return apply_filters( 'footballpool_predictionform_matches_html', $output, $matches, $user_id );
	}
	
	public function format_match_time( $datetime, $format = false ) {
		if ( $format === false ) $format = $this->time_format;
		
		$display = Football_Pool_Utils::get_fp_option( 'match_time_display' );
		if ( $display == 0 ) { // WordPress setting
			$datetime = new DateTime( Football_Pool_Utils::date_from_gmt( $datetime->format( 'Y-m-d H:i' ) ) );
		} elseif ( $display == 2 ) { // custom setting
			$offset = 60 * 60 * (float) Football_Pool_Utils::get_fp_option( 'match_time_offset' );
			if ( $offset >= 0 ) $offset = '+' . $offset;
			$datetime->modify( $offset . ' seconds' );
		} // else UTC
		
		return $datetime->format( $format );
	}
	
	public static function get_match_types() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT id, name, visibility FROM {$prefix}matchtypes ORDER BY id ASC";
		return $wpdb->get_results( $sql );
	}
	
	public function get_match_type_by_id( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name, visibility FROM {$prefix}matchtypes WHERE id = %d", $id );
		return $wpdb->get_row( $sql );
	}
	
	public function get_match_type_by_name( $name, $addnew = 'no' ) {
		if ( $name == '' ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name, visibility FROM {$prefix}matchtypes WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}matchtypes ( name ) VALUES ( %s )", $name );
			$wpdb->query( $sql );
			$id = $wpdb->insert_id;
			$result = (object) array( 
									'id' => $id, 
									'name' => $name, 
									'visibility' => 1, 
									'inserted' => true 
									);
		}
		
		return $result;
	}
	
	public function get_matches_for_match_type( $ids = array() ) {
		if ( count( $ids ) == 0 ) return array();
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$matchtype_ids = implode( ',', $ids );
		$sql = "SELECT id FROM {$prefix}matches WHERE matchtype_id IN ( {$matchtype_ids} )";
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$matches = array();
		foreach ( $results as $row ) {
			$matches[] = $row['id'];
		}
		return $matches;
	}
	
	public function show_score( $home, $away, $user_home, $user_away, $joker, $ts ) {
		if ( ! $this->match_is_editable( $ts ) ) {
			$pool = new Football_Pool_Pool;
			return $pool->calc_score( $home, $away, $user_home, $user_away, $joker );
		} else {
			return '<span class="no-score"></span>';
		}
	}
	
	private function show_users_link( $match, $ts ) {
		$output = '';
		if ( $this->always_show_predictions || ! $this->match_is_editable( $ts ) ) {
			$title = __( 'view other users predictions', FOOTBALLPOOL_TEXT_DOMAIN );
			$output .= sprintf( '<a href="%s" title="%s">'
							, esc_url(
								add_query_arg( 
										array( 'view' => 'matchpredictions', 'match' => $match ), 
										Football_Pool::get_page_link( 'statistics' ) )
								)
							, $title
						);
			$output .= sprintf( '<img src="%sassets/images/site/charts.png" alt="%s" title="%s" /></a>',
							FOOTBALLPOOL_PLUGIN_URL,
							$title,
							$title
						);
		}
		return $output;
	}
	
	public function match_is_editable( $ts ) {
		if ( $this->force_lock_time ) {
			$editable = ( current_time( 'timestamp' ) < $this->lock );
		} else {
			$diff = $ts - time();
			$editable = ( $diff > $this->lock );
		}
		
		return $editable;
	}
	
	private function block_joker() {
		$this->joker_blocked = true;
	}
	
	/**
	 * Shows pool input for games that are still editable. For games where the edit period has expired 
	 * only the value is shown.
	 * The property matches_are_editable is used for the users page. It prevents the display of inputs
	 * and it prevents the display of games that are still editable. The latter to make sure users do 
	 * not copy results from each other. This may be overridden with the always_show_predictions option.
	 */
	public function show_pool_input( $name, $value, $ts ) {
		if ( $this->match_is_editable( $ts ) ) {
			if ( $this->matches_are_editable ) {
				if ( $this->use_spin_controls ) {
					$control = 'type="number" min="0" max="999"';
				} else {
					$control = 'type="text" maxlength="3"';
				}
				return sprintf( '<input %s name="%s" value="%s" class="prediction" />'
								, $control, $name, $value
						);
			} else {
				return ( $this->always_show_predictions ? $value : '' );
			}
		} else {
			return $value;
		}
	}
	
	private function show_pool_joker( $joker, $match, $ts, $form_id = 1 ) {
		if ( ! $this->pool_has_jokers ) return '';
		
		$add_joker = '';
		$style = '';
		
		$class = ( $joker == $match && $joker > 0 && $match > 0 ) ? 'fp-joker' : 'fp-nojoker';
		/*
		Make sure joker is not shown for matches that are editable in case 
		the matches_are_editable property is set to false. Unless we have the new 
		'always display predictions' set, in that case we can ignore this.
		*/
		if ( ! $this->always_show_predictions ) {
			if ( ! $this->matches_are_editable && $this->match_is_editable( $ts ) ) {
				$class = 'fp-nojoker';
			}
		}
		
		if ( $class == 'fp-joker' && ! $this->match_is_editable( $ts ) ) {
			$this->block_joker();
		}
		
		if ( ! $this->joker_blocked ) {
			if ( $this->match_is_editable( $ts ) ) {
				$add_joker = ' onclick="FootballPool.change_joker( this.id )" title="' . __( 'use your joker?', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
			}
		} else {
			$class .= ' readonly';
		}
		
		return sprintf( '<span class="%s"%s id="match_%d_%d"></span>', $class, $add_joker, $match, $form_id );
	}

}
