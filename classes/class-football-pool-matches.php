<?php
class Football_Pool_Matches {
	private $joker_blocked;
	private $teams;
	private $matches_are_editable;
	public $joker_value;
	private $force_lock_time = false;
	private $lock;
	public $matches;
	public $always_show_predictions = 0;
	private $use_spin_controls = true;
	public $has_matches = false;
	private $time_format;
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
		// override hiding of predictions for editable matches?
		$this->always_show_predictions = (int) Football_Pool_Utils::get_fp_option( 'always_show_predictions' );
		// HTML5 number inputs?
		$this->use_spin_controls = ( Football_Pool_Utils::get_fp_option( 'use_spin_controls', 1, 'int' ) == 1 );
		
		// cache match info
		$this->matches = $this->match_info();
		$this->has_matches = ( count( $this->matches ) > 0 );
		
		$this->time_format = get_option( 'time_format', FOOTBALLPOOL_TIME_FORMAT );
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
				$order = 'matchtype ASC, m.play_date DESC, m.id DESC';
				break;
			case 2:
				$order = 'matchtype DESC, m.play_date ASC, m.id ASC';
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
	
	private function matches_query( $where_clause = '' ) {
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sorting = self::get_match_sorting_method();
		
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
				{$where_clause}
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
			
			$linked_questions = array();
			$sql = "SELECT COUNT( * ) FROM {$prefix}bonusquestions WHERE match_id > 0";
			$linked_questions_present = ( $wpdb->get_var( $sql ) > 0 );
			
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
						isset( $teams->team_names[(integer) $row['home_team_id'] ] ) ? 
							$teams->team_names[(integer) $row['home_team_id'] ] : 
							'' 
						);
				$match_info[$i]['away_team'] = ( 
						isset( $teams->team_names[(int) $row['away_team_id'] ] ) ? 
							$teams->team_names[(int) $row['away_team_id'] ] : 
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
				
				if ( $linked_questions_present ) {
					$sql = $wpdb->prepare( "SELECT id FROM {$prefix}bonusquestions WHERE match_id = %d", $row['id'] );
					$linked_questions = $wpdb->get_col( $sql );
				}
				$match_info[$i]['linked_questions'] = $linked_questions;
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
		$order = self::get_match_sorting_method();
		
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
				return ( $this->always_show_predictions == 1 ? $value : '' );
			}
		} else {
			return $value;
		}
	}
	
	public function print_matches( $matches, $form_id = '', $user_id = '' ) {
		if ( Football_Pool_Utils::get_fp_option( 'responsive_layout', 1, 'int' ) == 1 )
			return $this->print_matches_new( $matches, $form_id, $user_id );
		else
			return $this->print_matches_old( $matches );
	}
	
	private function print_matches_new( $matches, $form_id = '', $user_id = '' ) {
		$teams = new Football_Pool_Teams;
		$pool = new Football_Pool_Pool;
		
		$date_title = $matchtype = $joker = $output = '';
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$is_input_form = ( $form_id != '' && $user_id != '' );
		
		$grid_size_team = ( $is_input_form ) ? '1-4' : '1-3';
		
		foreach ( $matches as $row ) {
			$match_url = esc_url( add_query_arg(
									array( 'view' => 'matchpredictions', 'match' => $row['id'] ), 
									$statisticspage ) );
			
			if ( $is_input_form ) {
				$info = $this->get_match_info( (int) $row['id'] );
				
				if ( (int) $row['has_joker'] == 1 ) $joker = (int) $row['id'];
				
				$home_score = $this->show_pool_input( '_home_' . $info['id'], $row['home_score'], $info['match_timestamp'] );
				$away_score = $this->show_pool_input( '_away_' . $info['id'], $row['away_score'], $info['match_timestamp'] );
				
				$match_class = ( $info['match_is_editable'] ? 'match open' : 'match closed' );
			} else {
				$home_score = sprintf( '<a href="%s">%s</a>', $match_url, $row['home_score'] );
				$away_score = sprintf( '<a href="%s">%s</a>', $match_url, $row['away_score'] );
				
				$match_class = ( $row['match_is_editable'] ? 'match open' : 'match closed' );
			}
			
			$matchdate = new DateTime( $row['play_date'] );
			$localdate = new DateTime( $this->format_match_time( $matchdate, 'Y-m-d H:i' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN )
											, $localdate->format( 'U' ) );
			$match_time = $localdate->format( $this->time_format );
			
			// output the match table
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= sprintf( '<div class="pure-g match-table"><div class="pure-u-1 match-type">%s</div></div>'
									, $matchtype );
			}
			
			// desktop & tablet
			if ( $date_title != $localdate_formatted ) {
				$date_title = $localdate_formatted;
				$output .= sprintf( '<div class="pure-g match-table pure-hidden-phone" title="%s"><div class="pure-u-1 match-date">%s</div></div>'
									// Translators: this is a date format string (see http://php.net/date)
									, date_i18n( _x( 'l', 'a date format string (see http://php.net/date)', FOOTBALLPOOL_TEXT_DOMAIN )
												, $localdate->format( 'U' ) )
									, $date_title );
			}
			
			$output .= sprintf( '<div class="pure-g match-table match-date pure-hidden-tablet pure-hidden-desktop"><div class="pure-u-1-2 match-time">%s</div><div class="pure-u-1-2 match-date">%s</div></div>'
								, $match_time
								, $date_title
						);
			$output .= sprintf( '<div class="match" id="match-%d%s" class="%s" title="%s %s">'
								, $row['id']
								, ( $form_id != '' ? "-{$form_id}" : '' )
								, $match_class
								, __( 'match', FOOTBALLPOOL_TEXT_DOMAIN )
								, $row['id']
						);
			$output .= '<div class="pure-g-r match-table pure-hidden-phone">';
			$output .= sprintf( '<div class="pure-u-1-8 match-time">%s</div>'
								, $match_time
						);
			$output .= sprintf( '<div class="pure-u-%s home-team">%s</div>'
								, $grid_size_team
								, $teams->teams[(int) $row['home_team_id']]->get_team_link( $is_input_form )
						);
			$output .= sprintf( '<div class="pure-u-1-24 home-team flag">%s</div>'
								, $teams->flag_image( (int) $row['home_team_id'] )
						);
			$output .= sprintf( '<div class="pure-u-1-24 home-team result"><div>%s</div></div>'
								, $home_score
						);
			$output .= '<div class="pure-u-1-24 match-result">-</div>';
			$output .= sprintf( '<div class="pure-u-1-24 away-team result"><div>%s</div></div>'
								, $away_score
						);
			$output .= sprintf( '<div class="pure-u-1-24 away-team flag">%s</div>'
								, $teams->flag_image( (int) $row['away_team_id'] )
						);
			$output .= sprintf( '<div class="pure-u-%s away-team">%s</div>'
								, $grid_size_team
								, $teams->teams[(int) $row['away_team_id']]->get_team_link( $is_input_form )
						);
			
			if ( $is_input_form ) {
				$output .= sprintf( '<div class="pure-u-1-12 match-extra joker">%s</div>'
									, $this->show_pool_joker( 
														$joker, (int) $info['id'], 
														$info['match_timestamp'], $form_id 
													)
							);
				$output .= sprintf( '<div class="pure-u-1-24 match-extra score" title="%s">%s</div>'
									, __( 'score', FOOTBALLPOOL_TEXT_DOMAIN )
									, $this->show_score( 
												$info['home_score'], $info['away_score'], 
												$row['home_score'], $row['away_score'], 
												$row['has_joker'], $info['match_timestamp'] 
											)
							);
				$output .= sprintf( '<div class="pure-u-1-24 match-extra">%s</div>'
										, $this->show_users_link( $info['id'], $info['match_timestamp'] )
							);
			}
			
			$output .= '</div>';
			// end desktop
			
			// mobile
			$output .= '<div class="pure-g match-table pure-hidden-tablet pure-hidden-desktop">';
			$output .= sprintf( '<div class="pure-u-11-24 home-team"><div>%s</div></div>'
								, $teams->teams[(int) $row['home_team_id']]->get_team_link( $is_input_form )
						);
			$output .= '<div class="pure-u-1-12 match-result"><div>-</div></div>';
			$output .= sprintf( '<div class="pure-u-11-24 away-team"><div>%s</div></div>'
								, $teams->teams[(int) $row['away_team_id']]->get_team_link( $is_input_form )
						);
			$output .= sprintf( '<div class="pure-u-11-24 home-team flag"><div>%s</div></div>'
								, $teams->flag_image( (int) $row['home_team_id'] )
						);
			$output .= '<div class="pure-u-1-12 match-result"></div>';
			$output .= sprintf( '<div class="pure-u-11-24 away-team flag"><div>%s</div></div>'
								, $teams->flag_image( (int) $row['away_team_id'] )
						);
			$output .= sprintf( '<div class="pure-u-11-24 home-team result"><div>%s</div></div>'
								, $home_score
						);
			$output .= '<div class="pure-u-1-12 match-result"><div>-</div></div>';
			$output .= sprintf( '<div class="pure-u-11-24 away-team result"><div>%s</div></div>' 
								, $away_score
						);
			$output .= '</div>';
			// end mobile
			
			// linked questions
			if ( $is_input_form ) {
				if ( is_array( $info['linked_questions'] ) && count( $info['linked_questions'] ) > 0 ) {
					$questions = $pool->get_bonus_questions_for_user( $user_id, $info['linked_questions'] );
					foreach( $questions as $question ) {
						$output .= sprintf( '<div id="match-%d-%d-question-%d" class="pure-g-r match-table"><div class="pure-u-1 match-linked-question">%s</div></div>'
											, $info['id']
											, $form_id
											, $question['id']
											, $pool->print_bonus_question( $question, '' )
									);
					}
				}
			}
			
			$output .= '</div>';
			// end match
		}
		
		$this->joker_value = $joker;
		
		return $output;
	}
	
	private function print_matches_old( $matches ) {
		$matchtype = '';
		$date_title = '';
		
		$teams = new Football_Pool_Teams;
		$teamspage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$output = '<table class="matchinfo">';
		foreach ( $matches as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= sprintf( '<tr><td class="matchtype" colspan="6">%s</td></tr>'
									, __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ) 
								);
			}
			
			$matchdate = new DateTime( $row['play_date'] );
			$localdate = new DateTime( $this->format_match_time( $matchdate, 'Y-m-d H:i' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN )
											, $localdate->format( 'U' ) );
			if ( $date_title != $localdate_formatted ) {
				$date_title = $localdate_formatted;
				$output .= sprintf( '<tr><td class="matchdate" colspan="6" title="%s">%s</td></tr>'
									// Translators: this is a date format string (see http://php.net/date)
									, date_i18n( _x( 'l', 'a date format string (see http://php.net/date)', FOOTBALLPOOL_TEXT_DOMAIN )
												, $localdate->format( 'U' ) )
									, $date_title );
			}
			
			$team_name = ( isset( $teams->team_names[ (int) $row['home_team_id'] ] ) ?
								$teams->team_names[ (int) $row['home_team_id'] ] : '' );
			if ( $teams->show_team_links ) {
				$team_name = sprintf( '<a href="%s">%s</a>'
										, esc_url( 
												add_query_arg( 
													array( 'team' => $row['home_team_id'] ), 
													$teamspage 
												) 
											)
										, $team_name
								);
			}
			$output .= sprintf( '<tr id="match-%d" class="%s" title="%s %s">
									<td class="time">%s</td>
									<td class="home">%s</td>
									<td class="flag">%s</td>',
							$row['id'],
							( $row['match_is_editable'] ? 'match open' : 'match closed' ),
							__( 'match', FOOTBALLPOOL_TEXT_DOMAIN ),
							$row['id'],
							$localdate->format( $this->time_format ),
							$team_name,
							$teams->flag_image( (integer) $row['home_team_id'] )
						);
			$output .= sprintf( '<td class="score"><a href="%s">%s - %s</a></td>',
							esc_url( 
								add_query_arg(
									array( 'view' => 'matchpredictions', 'match' => $row['id'] ), 
									$statisticspage 
								)
							),
							$row['home_score'],
							$row['away_score']
						);
			$team_name = ( isset( $teams->team_names[ (int) $row['away_team_id'] ] ) ?
								$teams->team_names[ (int) $row['away_team_id'] ] : '' );
			if ( $teams->show_team_links ) {
				$team_name = sprintf( '<a href="%s">%s</a>'
										, esc_url( 
												add_query_arg( 
													array( 'team' => $row['away_team_id'] ), 
													$teamspage 
												) 
											)
										, $team_name
								);
			}
			$output .= sprintf( '<td class="flag">%s</td>
								<td class="away">%s</td>
								</tr>',
							$teams->flag_image( (integer) $row['away_team_id'] ),
							$team_name
						);
		}
		$output .= '</table>';
		
		return $output;
	}
	
	public function print_matches_for_input( $matches, $form_id, $user_id ) {
		if ( Football_Pool_Utils::get_fp_option( 'responsive_layout', 1, 'int' ) == 1 )
			return $this->print_matches_new( $matches, $form_id, $user_id );
		else
			return $this->print_matches_for_input_old( $matches, $form_id, $user_id );
	}
	
	public function print_matches_for_input_old( $matches, $form_id, $user_id ) {
		$teams = new Football_Pool_Teams;
		$pool = new Football_Pool_Pool;
		$date_title = '';
		$matchtype = '';
		$joker = '';
		
		$output = sprintf( '<table id="matchinfo-%d" class="matchinfo input">', $form_id );
		
		foreach ( $matches as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= sprintf( '<tr><td class="matchtype" colspan="11">%s</td></tr>'
									, __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ) 
							);
			}
			
			$matchdate = new DateTime( $row['play_date'] );
			$localdate = new DateTime( $this->format_match_time( $matchdate, 'Y-m-d H:i' ) );
			// Translators: this is a date format string (see http://php.net/date)
			$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN )
											, $localdate->format( 'U' ) );
			if ( $date_title != $localdate_formatted ) {
				$date_title = $localdate_formatted;
				$output .= sprintf( '<tr><td class="matchdate" colspan="11">%s</td></tr>', $date_title );
			}
			
			if ( (int) $row['has_joker'] == 1 ) {
				$joker = (int) $row['id'];
			}
			
			$info = $this->get_match_info( (int) $row['id'] );
			
			$home_team = isset( $teams->team_names[ (int) $info['home_team_id'] ] ) ?
							htmlentities( $teams->team_names[ (int) $info['home_team_id'] ], null, 'UTF-8' ) :
							'';
			$away_team = isset( $teams->team_names[ (int) $info['away_team_id'] ] ) ?
							htmlentities( $teams->team_names[ (int) $info['away_team_id'] ], null, 'UTF-8' ) :
							'';
			$output .= sprintf( '<tr id="match-%d-%d" class="%s">
								<td class="time">%s</td>
								<td class="home">%s</td>
								<td class="flag">%s</td>
								<td class="score">%s</td>
								<td>-</td>
								<td class="score">%s</td>
								<td class="flag">%s</td>
								<td class="away">%s</td>
								%s
								<td title="%s" class="numeric">%s</td>
								<td>%s</td>
								</tr>',
							$info['id'],
							$form_id,
							( $info['match_is_editable'] ? 'match open' : 'match closed' ),
							$localdate->format( $this->time_format ),
							$home_team,
							$teams->flag_image( (int) $info['home_team_id'] ),
							$this->show_pool_input( '_home_' . $info['id'], $row['home_score'], $info['match_timestamp'] ),
							$this->show_pool_input( '_away_' . $info['id'], $row['away_score'], $info['match_timestamp'] ),
							$teams->flag_image( (int) $info['away_team_id'] ),
							$away_team,
							$this->show_pool_joker( 
												$joker, (int) $info['id'], 
												$info['match_timestamp'], $form_id 
											),
							__( 'score', FOOTBALLPOOL_TEXT_DOMAIN ),
							$this->show_score( 
										$info['home_score'], $info['away_score'], 
										$row['home_score'], $row['away_score'], 
										$row['has_joker'], $info['match_timestamp'] 
									),
							$this->show_users_link( $info['id'], $info['match_timestamp'] )
						);
			
			if ( is_array( $info['linked_questions'] ) && count( $info['linked_questions'] ) > 0 ) {
				$questions = $pool->get_bonus_questions_for_user( $user_id, $info['linked_questions'] );
				foreach( $questions as $question ) {
					$output .= sprintf( '<tr id="match-%d-%d-question-%d" class="linked-question"><td colspan="11">%s</td><tr>'
										, $info['id']
										, $form_id
										, $question['id']
										, $pool->print_bonus_question( $question, '' )
								);
				}
			}
		}
		$output .= '</table>';
		
		$this->joker_value = $joker;
		return $output;
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
	
	public function get_match_types() {
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
	
	private function show_score( $home, $away, $user_home, $user_away, $joker, $ts ) {
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
	
	private function show_pool_joker( $joker, $match, $ts, $form_id = 1 ) {
		if ( ! $this->pool_has_jokers ) return '<td></td>';
		
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
				$add_joker = ' onclick="footballpool_change_joker( this.id )" title="' . __( 'use your joker?', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
			}
		} else {
			$class .= ' readonly';
		}
		
		return sprintf( '<td class="%s"%s id="match_%d_%d"></td>', $class, $add_joker, $match, $form_id );
	}

}
