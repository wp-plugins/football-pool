<?php
class Football_Pool_Pool {
	public $leagues;
	public $has_bonus_questions = false;
	public $has_matches = false;
	public $has_leagues;
	private $force_lock_time = false;
	private $lock_timestamp;
	private $lock_datestring;
	public $always_show_predictions = 0;
	public $show_avatar = false;
	private $pool_has_jokers;
	
	public function __construct() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$this->num_jokers = Football_Pool_Utils::get_fp_option( 'number_of_jokers', FOOTBALLPOOL_DEFAULT_JOKERS, 'int' );
		$this->pool_has_jokers = ( $this->num_jokers > 0 );
		
		$this->leagues = $this->get_leagues();
		$this->has_leagues = ( Football_Pool_Utils::get_fp_option( 'use_leagues' ) == '1' ) && ( count( $this->leagues ) > 1 );
		
		$this->lock_datestring = Football_Pool_Utils::get_fp_option( 'bonus_question_locktime', '' );
		$this->force_lock_time = 
			( Football_Pool_Utils::get_fp_option( 'stop_time_method_questions', 0, 'int' ) == 1 )
			&& ( $this->lock_datestring != '' );
		if ( $this->force_lock_time ) {
			$date = new DateTime( Football_Pool_Utils::date_from_gmt( $this->lock_datestring ) );
			$this->lock_timestamp = $date->format( 'U' );
		} else {
			$this->lock_timestamp = 0; // bonus questions have no time threshold
		}
		
		// override hiding of predictions for editable questions?
		$this->always_show_predictions = (int) Football_Pool_Utils::get_fp_option( 'always_show_predictions' );
		
		$matches = new Football_Pool_Matches;
		$this->has_matches = $matches->has_matches;
		
		$sql = "SELECT COUNT( * ) FROM {$prefix}bonusquestions";
		$this->has_bonus_questions = ( $wpdb->get_var( $sql ) > 0 );
		
		$this->show_avatar = ( Football_Pool_Utils::get_fp_option( 'show_avatar' ) == 1 );
	}
	
	private function is_toto_result($home, $away, $user_home, $user_away ) {
		return $this->toto( $home, $away ) == $this->toto( $user_home, $user_away );
	}
	
	private function toto( $home, $away ) {
		if ( $home == $away ) return 3;
		if ( $home > $away ) return 1;
		return 2;
	}
	
	public function calc_score( $home, $away, $user_home, $user_away, $joker ) {
		if ( ! is_int( $home ) || ! is_int( $away ) )
			return '';
		if ( $user_home == '' || $user_away == '' )
			return 0;
		
		$full = false;
		$score = 0;
		// check for toto result
		if ( $this->is_toto_result( $home, $away, $user_home, $user_away ) == true ) {
			// check for exact match
			if ( $home == $user_home && $away == $user_away ) {
				$score = (int) Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
				$full = true;
			} else {
				$score = (int) Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
			}
		}
		// check for goal bonus
		$goal_bonus = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
		if ( $home == $user_home ) $score += $goal_bonus;
		if ( $away == $user_away ) $score += $goal_bonus;
		// check for goal diff bonus
		$goal_diff_bonus = Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' );
		if ( ! $full && $home != $away && ( $home - $user_home ) == ( $away - $user_away ) ) {
			$score += $goal_diff_bonus;
		}
		
		if ( $joker == 1 ) $score *= 2;
		
		return $score;
	}
	
	public function get_users( $league ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT u.ID AS user_id, u.display_name AS user_name, u.user_email AS email, ";
		$sql .= ( $this->has_leagues ? "lu.league_id, " : "" );
		$sql .= "0 AS points, 0 AS full, 0 AS toto, 0 AS bonus FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu 
						ON (u.ID = lu.user_id" . ( $league > 1 ? ' AND lu.league_id = ' . $league : '' ) . ") ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
			$sql .= "WHERE ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		}
		$sql .= "ORDER BY user_name ASC";
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function user_is_player( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $this->has_leagues ) {
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}league_users lu
									INNER JOIN {$wpdb->users} u ON ( u.ID = lu.user_id )
									WHERE u.ID = %d AND lu.league_id <> 0"
									, $user_id );
		} else {
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}league_users lu
									RIGHT OUTER JOIN {$wpdb->users} u ON ( u.ID = lu.user_id )
									WHERE u.ID = %d AND ( lu.league_id <> 0 OR lu.league_id IS NULL )"
									, $user_id );
		}
		
		return ( $wpdb->get_var( $sql ) == 1 );
	}
	
	// returns null if no leagues are available or user does not exist
	public function get_league_for_user( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $this->has_leagues ) {
			$sql = $wpdb->prepare( "SELECT league_id FROM {$prefix}league_users WHERE user_id = %d", $user_id );
			$league = $wpdb->get_var( $sql );
		} else {
			$league = null;
		}
		
		return (int) $league;
	}
	
	public function get_user_score( $user, $ranking_id = FOOTBALLPOOL_RANKING_DEFAULT, $score_date = '' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( 
						sprintf( "SELECT total_score FROM {$prefix}scorehistory 
								WHERE user_id = %%d AND ranking_id = %%d 
								AND ( %s score_date <= %%s )
								ORDER BY score_date DESC LIMIT 1"
								, ( $score_date == '' ? '1 = 1 OR' : '' ) 
						) , $user, $ranking_id, $score_date );
		return $wpdb->get_var( $sql ); // return null if nothing found
	}
	
	// use league=0 to include all users
	public function get_ranking_from_score_history( 
									$league, 
									$ranking_id = FOOTBALLPOOL_RANKING_DEFAULT,
									$score_date = '', $type = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT u.ID AS user_id, u.display_name AS user_name, u.user_email AS email, " 
				. ( $this->has_leagues ? "lu.league_id, " : "" ) 
				. "	COALESCE( MAX( s.total_score ), 0 ) AS points, 
					COUNT( IF( s.full = 1, 1, NULL ) ) AS full, 
					COUNT( IF( s.toto = 1, 1, NULL ) ) AS toto,
					COUNT( IF( s.type = 1 AND score > 0, 1, NULL ) ) AS bonus 
				FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$league_switch = ( $league <= FOOTBALLPOOL_LEAGUE_ALL ? '1 = 1 OR' : '' );
			$sql .= "INNER JOIN {$prefix}league_users lu 
						ON (
							u.ID = lu.user_id
							AND ( {$league_switch} lu.league_id = %d )
							) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}scorehistory s ON 
					(
						s.user_id = u.ID AND s.ranking_id = %d 
						AND ( " . ( $score_date == '' ? '1 = 1 OR' : '' ) . " s.score_date <= %s )
						AND ( " . ( $type == 0 ? '1 = 1 OR' : '' ) . " s.type = %d )
					) ";
		$sql .= "WHERE s.ranking_id IS NOT NULL ";
		if ( ! $this->has_leagues ) $sql .= "AND ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		$sql .= "GROUP BY u.ID
				ORDER BY points DESC, full DESC, toto DESC, bonus DESC, " 
				. ( $this->has_leagues ? "lu.league_id ASC, " : "" ) 
				. "LOWER( u.display_name ) ASC";
		
		if ( $this->has_leagues )
			return $wpdb->prepare( $sql, $league, $ranking_id, $score_date, $type );
		else
			return $wpdb->prepare( $sql, $ranking_id, $score_date, $type );
	}
	
	public function get_pool_ranking_limited( $league, $num_users
											, $ranking_id = FOOTBALLPOOL_RANKING_DEFAULT
											, $score_date = '' ) {
		global $wpdb;
		$sql = $this->get_ranking_from_score_history( $league, $ranking_id, $score_date ) . ' LIMIT %d';
		$sql = $wpdb->prepare( $sql, $num_users );
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function get_pool_ranking( $league, $ranking_id = FOOTBALLPOOL_RANKING_DEFAULT ) {
		$cache_key = 'fp_get_pool_ranking_' . $ranking_id;
		$rows = wp_cache_get( $cache_key );
		
		if ( $rows === false ) {
			global $wpdb;
			$sql = $this->get_ranking_from_score_history( $league, $ranking_id );
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $rows );
		}
		
		return $rows;
	}
	
	public function get_prediction_count_per_user( $users, $ranking_id = FOOTBALLPOOL_RANKING_DEFAULT ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( is_array( $users ) && count( $users ) > 0 ) {
			$users = implode( ',', $users );
			$users = "WHERE p.user_id IN ( {$users} )";
		} else {
			$users = '';
		}
		
		$num_predictions = array();
		
		// matches
		if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
			$sql = "SELECT p.user_id, COUNT( * ) AS num_predictions 
					FROM {$prefix}predictions p
					{$users} GROUP BY p.user_id";
		} else {
			$sql = "SELECT p.user_id, COUNT( * ) AS num_predictions 
					FROM {$prefix}predictions p
					JOIN {$prefix}rankings_matches r 
						ON ( r.match_id = p.match_id AND r.ranking_id = {$ranking_id} ) 
					{$users} GROUP BY p.user_id";
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		foreach ( $rows as $row ) {
			$num_predictions[$row['user_id']] = $row['num_predictions'];
		}
		
		// questions
		if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
			$sql = "SELECT p.user_id, COUNT( * ) AS num_predictions 
					FROM {$prefix}bonusquestions_useranswers p
					{$users} GROUP BY p.user_id";
		} else {
			$sql = "SELECT p.user_id, COUNT(*) AS num_predictions 
					FROM {$prefix}bonusquestions_useranswers p
					JOIN {$prefix}rankings_bonusquestions r 
						ON ( r.question_id = p.question_id AND r.ranking_id = {$ranking_id} ) 
					{$users} GROUP BY p.user_id";
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		foreach ( $rows as $row ) {
			if ( array_key_exists( $row['user_id'], $num_predictions) ) {
				$num_predictions[$row['user_id']] += $row['num_predictions'];
			}
		}
		
		// return resulting array of user ids with their total number of predictions
		return $num_predictions;
	}
	
	public function print_pool_ranking( $league, $user, $ranking_id = FOOTBALLPOOL_RANKING_DEFAULT ) {
		$output = '';
		
		$rows = $this->get_pool_ranking( $league, $ranking_id );
		$ranking = $users = array();
		if ( count( $rows ) > 0 ) {
			// there are results in the database, so get the ranking
			foreach ( $rows as $row ) {
				$ranking[] = $row;
				$users[] = $row['user_id'];
			}
		} else {
			// no results, show a list of users
			$rows = $this->get_users( $league );
			if ( count( $rows ) > 0 ) {
				$output .= '<p>' . __( 'No results yet. Below is a list of all users.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
				foreach ( $rows as $row ) {
					$ranking[] = $row;
					$users[] = $row['user_id'];
				}
			} else {
				$output .= '<p>'. __( 'No users have registered for this pool (yet).', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
			}
		}
		
		if ( count( $ranking ) > 0 ) {
			// get number of predictions per user if option is set
			$show_num_predictions = ( Football_Pool_Utils::get_fp_option( 'show_num_predictions_in_ranking' ) == 1 );
			if ( $show_num_predictions ) {
				$predictions = $this->get_prediction_count_per_user( $users, $ranking_id );
			}
			
			$userpage = Football_Pool::get_page_link( 'user' );
			$all_user_view = ( $league == FOOTBALLPOOL_LEAGUE_ALL && $this->has_leagues );
			$i = 1;
			
			$output .= '<table class="pool-ranking ranking-page">';
			if ( $show_num_predictions ) {
				$output .= sprintf( '<tr>
										<th></th>
										<th class="user">%s</th>
										<th class="num-predictions">%s</th>
										<th class="score">%s</th>
										%s</tr>'
									, __( 'user', FOOTBALLPOOL_TEXT_DOMAIN )
									, __( 'predictions', FOOTBALLPOOL_TEXT_DOMAIN )
									, __( 'points', FOOTBALLPOOL_TEXT_DOMAIN )
									, ( $all_user_view ? '<th></th>' : '' )
							);
			}
			foreach ( $ranking as $row ) {
				$class = ( $i % 2 != 0 ? 'even' : 'odd' );
				if ( $all_user_view ) $class .= ' league-' . $row['league_id'];
				if ( $row['user_id'] == $user ) $class .= ' currentuser';
				if ( $show_num_predictions ) {
					if ( array_key_exists( $row['user_id'], $predictions ) ) {
						$num_predictions = $predictions[$row['user_id']];
					} else {
						$num_predictions = 0;
					}
					$num_predictions = sprintf( '<td class="num-predictions">%d</td>', $num_predictions );
				} else {
					$num_predictions = '';
				}
				$output .= sprintf( '<tr class="%s"><td style="width:3em; text-align: right;">%d.</td>
									<td><a href="%s">%s%s</a>%s</td>
									%s<td class="ranking score">%d</td>%s
									</tr>',
								$class,
								$i++,
								esc_url( add_query_arg( array( 'user' => $row['user_id'] ), $userpage ) ),
								$this->get_avatar( $row['user_id'], 'medium' ),
								$row['user_name'],
								Football_Pool::user_name( $row['user_id'], 'label' ),
								$num_predictions,
								$row['points'],
								( $all_user_view ? $this->league_image( $row['league_id'] ) : '' )
							);
				$output .= "\n";
			}
			$output .= '</table>';
		}
		
		return $output;
	}
	
	private function league_image( $id ) {
		if ( $this->has_leagues && ! empty( $this->leagues[$id]['image'] ) ) {
			$img = sprintf( '<td><img src="%sassets/images/site/%s" alt="%s" title="%s" /></td>',
							FOOTBALLPOOL_PLUGIN_URL,
							$this->leagues[$id]['image'],
							$this->leagues[$id]['league_name'],
							$this->leagues[$id]['league_name']
						);
		} else {
			$img = '<td></td>';
		}
		return $img;
	}
	
	public function get_leagues( $only_user_defined = false ) {
		$cache_key = 'fp_get_leagues_' . ( $only_user_defined ? 'user_defined' : 'all' );
		$leagues = wp_cache_get( $cache_key );
		
		if ( $leagues === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$filter = $only_user_defined ? 'WHERE user_defined = 1' : '';
			
			$sql = "SELECT id AS league_id, name AS league_name, user_defined, image 
					FROM {$prefix}leagues {$filter} ORDER BY user_defined ASC, name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			$leagues = array();
			foreach ( $rows as $row ) {
				$leagues[$row['league_id']] = $row;
			}
			wp_cache_set( $cache_key, $leagues );
		}
		
		return $leagues;
	}
	
	public function get_rankings( $which = 'all' ) {
		$only_user_defined = ( $which == 'user defined' || $which == 'user_defined' );
		$cache_key = 'fp_get_rankings_' . ( $only_user_defined ? 'user_defined' : 'all' );
		$rankings = wp_cache_get( $cache_key );
		
		if ( $rankings === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$filter = $only_user_defined ? 'WHERE user_defined = 1' : '';
			
			$sql = "SELECT id, name, user_defined 
					FROM {$prefix}rankings {$filter} ORDER BY user_defined ASC, name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			$rankings = array();
			foreach ( $rows as $row ) {
				$rankings[$row['id']] = $row;
			}
			wp_cache_set( $cache_key, $rankings );
		}
		
		return $rankings;
	}
	
	public function get_ranking_by_id( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT name, user_defined FROM {$prefix}rankings WHERE id = %d", $id );
		return $wpdb->get_row( $sql, ARRAY_A ); // returns null if no ranking found
	}
	
	public function get_ranking_matches( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT match_id FROM {$prefix}rankings_matches 
								WHERE ranking_id = %d", $id );
		return $wpdb->get_results( $sql, ARRAY_A ); // returns null if no ranking found
	}
	
	public function get_ranking_questions( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT question_id FROM {$prefix}rankings_bonusquestions 
								WHERE ranking_id = %d", $id );
		return $wpdb->get_results( $sql, ARRAY_A ); // returns null if no ranking found
	}
	
	public function league_filter( $league = 0, $select = 'league' ) {
		$output = '';
		
		if ( $this->has_leagues ) {
			$options = array();
			foreach ( $this->leagues as $row ) {
				$options[ $row['league_id'] ] = $row['league_name'];
			}
			$output .= Football_Pool_Utils::select( $select, $options, $league );
		}
		
		return $output;
	}
	
	public function league_select( $league = 0, $select = 'league' ) {
		$output = '';
		
		if ( $this->has_leagues ) {
			$output .= sprintf('<select name="%s" id="%s">', $select, $select);
			$output .= '<option value="0"></option>';
			foreach ( $this->leagues as $row ) {
				if ( $row['user_defined'] == 1 ) {
					$output .= sprintf( '<option value="%d"%s>%s</option>'
										, $row['league_id']
										, ( $row['league_id'] == $league ? ' selected="selected"' : '' )
										, $row['league_name']
								);
				}
			}
			$output .= '</select>';
		}
		return $output;
	}
	
	public function update_league_for_user( $user_id, $new_league_id, $old_league = 'update league' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $old_league == 'no update' ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( user_id, league_id ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE league_id = league_id", 
									$user_id, $new_league_id
								);
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( user_id, league_id ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE league_id = %d", 
									$user_id, $new_league_id, $new_league_id
								);
		}
		$wpdb->query( $sql );
	}
	
	public function get_bonus_questions_for_user( $user_id = 0, $question_ids = array() ) {
		if ( $user_id == 0 ) return array();
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$ids = '';
		if ( is_array( $question_ids ) && count( $question_ids ) > 0 ) {
			$ids = ' AND q.id IN ( ' . implode( ',', $question_ids ) . ' ) ';
		}
		// also include user answers
		$sql = $wpdb->prepare( "SELECT 
									q.id, q.question, a.answer, 
									q.points, a.points AS user_points, 
									q.answer_before_date AS question_date, 
									DATE_FORMAT( q.score_date,'%%Y-%%m-%%d %%H:%%i') AS score_date, 
									DATE_FORMAT( q.answer_before_date,'%%Y-%%m-%%d %%H:%%i') AS answer_before_date, 
									q.match_id, a.correct,
									qt.type, qt.options, qt.image, qt.max_answers
								FROM {$prefix}bonusquestions q 
								INNER JOIN {$prefix}bonusquestions_type qt
									ON ( q.id = qt.question_id {$ids})
								LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a
									ON ( a.question_id = q.id AND a.user_id = %d )
								ORDER BY q.answer_before_date ASC",
							$user_id
						);
		
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$questions = array();
		
		$this->has_bonus_questions = ( count( $rows ) > 0 );
		
		if ( $this->has_bonus_questions ) {
			$i = 0;
			foreach ( $rows as $row ) {
				$questions[$i] = $row;
				$ts = new DateTime( $row['question_date'] );
				$ts = $ts->format( 'U' );
				$questions[$i]['question_timestamp'] = $ts;
				$i++;
			}
		}
		
		return $questions;
	}
	
	// returns array of questions
	public function get_bonus_questions() {
		$question_info = wp_cache_get( FOOTBALLPOOL_CACHE_QUESTIONS );
		
		if ( $question_info === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
		
			$sql = "SELECT 
						q.id, q.question, q.answer, q.points, q.answer_before_date AS question_date, 
						DATE_FORMAT( q.score_date,'%Y-%m-%d %H:%i' ) AS score_date, 
						DATE_FORMAT( q.answer_before_date,'%Y-%m-%d %H:%i' ) AS answer_before_date, q.match_id,
						qt.type, qt.options, qt.image, qt.max_answers
					FROM {$prefix}bonusquestions q 
					INNER JOIN {$prefix}bonusquestions_type qt
						ON ( q.id = qt.question_id )
					ORDER BY q.answer_before_date ASC";
		
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$this->has_bonus_questions = ( count( $rows ) > 0 );
			
			$question_info = array();
			foreach ( $rows as $row ) {
				$i = (int) $row['id'];
				$question_date = new DateTime( $row['question_date'] );
				$ts = $question_date->format( 'U' );
				
				$question_info[$i] = array();
				$question_info[$i]['id'] = $i;
				$question_info[$i]['question'] = $row['question'];
				$question_info[$i]['answer'] = $row['answer'];
				$question_info[$i]['points'] = $row['points'];
				// $question_info[$i]['question_date'] = $ts;
				$question_info[$i]['question_timestamp'] = $ts;
				$question_info[$i]['score_date'] = $row['score_date'];
				$question_info[$i]['answer_before_date'] = $row['answer_before_date'];
				$question_info[$i]['match_id'] = (int) $row['match_id'];
				$question_info[$i]['type'] = (int) $row['type'];
				$question_info[$i]['options'] = $row['options'];
				$question_info[$i]['image'] = $row['image'];
				$question_info[$i]['max_answers'] = $row['max_answers'];
			}
			
			wp_cache_set( FOOTBALLPOOL_CACHE_QUESTIONS, $question_info );
		}
		
		return $question_info;
	}
	
	public function get_bonus_question( $id ) {
		return $this->get_bonus_question_info( $id );
	}
	
	public function get_bonus_question_info( $id ) {
		$info = false;
		$questions = $this->get_bonus_questions();
		if ( is_array( $questions ) && array_key_exists( $id, $questions ) ) {
			$info = $questions[$id];
			$info['question_is_editable'] = $this->question_is_editable( $info['question_timestamp'] );
		}
		return $info;
	}
	
	private function bonus_question_form_input( $question ) {
		switch ( $question['type'] ) {
			case 2: // multiple 1
				return $this->bonus_question_multiple( $question, 'radio' );
			case 3: // multiple n
				return $this->bonus_question_multiple( $question, 'checkbox' );
			case 1: // text
			default:
				return $this->bonus_question_single( $question );
		}
	}
	
	private function bonus_question_single( $question ) {
		return sprintf( '<input maxlength="200" class="bonus" name="_bonus_%d" type="text" value="%s" />'
						, esc_attr( $question['id'] )
						, esc_attr( $question['answer'] )
				);
	}
	
	// type = radio / checkbox / select
	private function bonus_question_multiple( $question, $type = 'radio' ) {
		$output = '';
		
		if ( $type == 'select' ) {
			// dropdown
			// @TODO: bonus question select/dropdown
			$output .= '<select name=""></select>';
		} else {
			// radio or checkbox
			if ( $type == 'checkbox' && $question['max_answers'] > 0 ) {
				// add some javascript for the max number of answers a user may give
				$output .= sprintf( '<script type="text/javascript">jQuery( document ).ready( function() { set_max_answers( %d, %d ); } );</script>'
									, $question['id']
									, $question['max_answers']
							);
			}
			
			$options = explode( ';', $question['options'] );
			$i = 1;
			$output .= '<ul class="multi-select">';
			foreach ( $options as $option ) {
				// strip out any empty options
				if ( str_replace( array( ' ', "\t", "\r", "\n" ), '', $option ) != '' ) {
					$answer = $question['answer'];
					$js = sprintf( 'onclick="jQuery( \'#_bonus_%d_userinput\' ).val( \'\' )" ', $question['id'] );
					
					if ( $type == 'checkbox' ) {
						$checked = in_array( $option, explode( ';', $answer ) ) ? 'checked="checked" ' : '';
						$brackets = '[]';
						$user_input = '';
					} else {
						// @TODO: change this very hacky (and therefore undocumented) feature of adding a text input
						//        after a radio input
						if ( substr( $option, -2 ) == '[]' ) {
							$js = '';
							
							$option = substr( $option, 0, -2 );
							$len = strlen( $option );
							$checked = substr( $answer, 0, $len ) == $option ? 'checked="checked" ' : '';
							
							$user_input_name = sprintf( '_bonus_%d_userinput', esc_attr( $question['id'] ) );
							$user_input_value = ( $checked ) ? substr( $answer, $len + 1 ) : '';
							$user_input = sprintf( '<span> <input type="text" id="%1$s" name="%1$s" value="%2$s" onclick="jQuery( \'#_bonus_%3$d_%4$d\' ).attr( \'checked\', \'checked\' )" /></span>'
													, $user_input_name
													, $user_input_value
													, $question['id']
													, $i
												);
						} else {
							$user_input = '';
							$checked = ( $answer == $option ) ? 'checked="checked" ' : '';
						}
						$brackets = '';
					}
					
					$user_input_class = ( $user_input != '' ) ? ' class="user-input"' : '';

					$output .= sprintf( '<li><label%9$s><input %8$sid="_bonus_%2$d_%7$d" type="%1$s" name="_bonus_%2$d%5$s" value="%3$s" %4$s/><span class="multi-option"> %3$s</span></label>%6$s</li>'
										, $type
										, esc_attr( $question['id'] )
										, esc_attr( $option )
										, $checked
										, $brackets
										, $user_input
										, $i++
										, $js
										, $user_input_class
								);
				}
			}
			
			$output .= '</ul>';
		}
		
		return $output;
	}
	
	public function print_bonus_question( $question, $nr ) {
		// the question with optional image
		if ( is_int( $nr ) ) {
			$nr = sprintf( '<span class="nr">%d.</span> ', $nr );
		}
		$output = sprintf( '<div class="bonus" id="q%d"><p>%s%s</p>'
							, $question['id']
							, $nr, $question['question'] 
					);
		if ( $question['image'] != '' ) {
			$output .= sprintf( '<p class="bonus image"><img src="%s" alt="%s" /></p>'
								, $question['image']
								, __( 'photo question', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		}
		
		$lock_time = ( $this->force_lock_time ) ? $this->lock_datestring : $question['answer_before_date'];
		// to local time
		$lock_time = Football_Pool_Utils::date_from_gmt( $lock_time );
		
		if ( $this->question_is_editable( $question['question_timestamp'] ) ) {
			$output .= sprintf( '<p>%s</p>', $this->bonus_question_form_input( $question ) );
			
			$output .= '<p>';
			
			// remind a player if there is only 1 day left to answer the question.
			$timestamp = ( $this->force_lock_time ? $this->lock_timestamp : $question['question_timestamp'] );
			if ( ( $timestamp - current_time( 'timestamp' ) ) <= ( 24 * 60 * 60 ) ) {
				$output .= sprintf( '<span class="bonus reminder">%s </span>', __( 'Important:', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			$output .= sprintf( '<span class="bonus eindtijd" title="%s">%s ' . $lock_time . '</span>',
							__( 'answer this question before this date', FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'answer before', FOOTBALLPOOL_TEXT_DOMAIN )
					);
		} else {
			$output .= sprintf( '<p class="bonus" id="bonus-%d">%s: ',
							$question['id'],
							__( 'answer', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$output .= ( $question['answer'] != '' ? $question['answer'] : '<span class="no-answer"></span>' );
			$output .= '</p>';
			
			$output .= sprintf( '<p><span class="bonus eindtijd" title="%s">%s %s</span>',
							__( "it's is no longer possible to answer this question, or change your answer", FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'closed on', FOOTBALLPOOL_TEXT_DOMAIN ),
							$lock_time
					);
		}
		
		$points = $question['points'] == 0 ? __( 'variable', FOOTBALLPOOL_TEXT_DOMAIN ) : $question['points'];
		$output .= sprintf( '<span class="bonus points">%s %s</span></p>'
							, $points
							, __( 'points', FOOTBALLPOOL_TEXT_DOMAIN ) 
					);
		
		$output .= '</div>';
		
		return $output;
	}
	
	// updates the predictions for a submitted prediction form
	public function prediction_form_update( $id = null ) {
		global $current_user;
		get_currentuserinfo();
		
		$msg = '';
		
		if ( $id == null || Football_Pool_Utils::post_int( '_fp_form_id' ) == $id ) {
			$user_is_player = $this->user_is_player( $current_user->ID );
			
			if ( $current_user->ID != 0 && $user_is_player
										&& Football_Pool_Utils::post_string( '_fp_action' ) == 'update' ) {
				$nonce = Football_Pool_Utils::post_string( FOOTBALLPOOL_NONCE_FIELD_BLOG );
				$success = ( wp_verify_nonce( $nonce, FOOTBALLPOOL_NONCE_BLOG ) !== false );
				if ( $success ) {
					$success = $this->update_predictions( $current_user->ID );
				}
				if ( $success ) {
					$msg = sprintf( '<p style="errormessage">%s</p>'
									, __( 'Changes saved.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
				} else {
					$msg = sprintf( '<p style="error">%s</p>'
									, __( 'Something went wrong during the save. Check if you are still logged in. If the problems persist, then contact your webmaster.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
				}
			}
		}
		
		return $msg;
	}
	
	private function update_bonus_user_answers( $questions, $answers, $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		foreach ( $questions as $question ) {
			if ( $this->question_is_editable( $question['question_timestamp'] ) && $answers[$question['id']] != '') {
				$sql = $wpdb->prepare( "REPLACE INTO {$prefix}bonusquestions_useranswers 
										SET user_id = %d,
											question_id = %d,
											answer = %s,
											points = 0",
										$user, $question['id'], $answers[$question['id']]
									);
				$wpdb->query( $sql );
			}
		}
	}
	
	private function update_predictions( $user ) {
		// only allow logged in users and players in the pool to update their predictions
		if ( $user <= 0 || ! $this->user_is_player( $user ) ) return false;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$matches = new Football_Pool_Matches;
		$joker = 0;
		
		// only allow setting of joker if it wasn't used before on a played match
		$sql = $wpdb->prepare( "SELECT m.play_date
								FROM {$prefix}predictions p, {$prefix}matches m 
								WHERE p.match_id = m.id AND p.has_joker = 1 AND p.user_id = %d" 
								, $user
							);
		$play_date = $wpdb->get_var( $sql );
		if ( $play_date ) {
			$play_date = new DateTime( $play_date );
			$ts = $play_date->format( 'U' );
			if ( $matches->match_is_editable( $ts ) ) {
				$joker = $this->get_joker();
			}
		} else {
			$joker = $this->get_joker();
		}
		
		// update predictions for all matches
		foreach ( $matches->matches as $row ) {
			$match = $row['id'];
			$home = Football_Pool_Utils::post_integer( '_home_' . $match, 'NULL' );
			$away = Football_Pool_Utils::post_integer( '_away_' . $match, 'NULL' );
			
			if ( $row['match_is_editable'] ) {
				if ( is_integer( $home ) && is_integer( $away ) ) {
					$sql = $wpdb->prepare( "REPLACE INTO {$prefix}predictions
											SET user_id = %d, 
												match_id = %d, 
												home_score = %d, 
												away_score = %d, 
												has_joker = %d"
											, $user, $match, $home, $away, ( $joker == $match ? 1 : 0 )
									);
				} else {
					// fix for the multiple-joker-bug
					$sql = $wpdb->prepare( "UPDATE {$prefix}predictions
											SET has_joker = %d
											WHERE user_id = %d AND match_id = %d"
											, ( $joker == $match ? 1 : 0 ), $user, $match
									);
				}
				
				$wpdb->query( $sql );
			}
		}
		
		// update bonusquestions
		$questions = $this->get_bonus_questions();
		if ( $this->has_bonus_questions ) {
			$answers = array();
			foreach ( $questions as $question ) {
				switch ( $question['type'] ) {
					case 3: // multiple n
						$user_answers = Football_Pool_Utils::post_string_array( '_bonus_' . $question['id'] );
						if ( $question['max_answers'] > 0 && count( $user_answers ) > $question['max_answers'] ) {
							// remove answers from the end of the array
							// (user is cheating or admin changed the max possible answers)
							while ( count( $user_answers ) > $question['max_answers'] ) {
								array_pop( $user_answers );
							}
						}
						$answers[ $question['id'] ] = implode( ';', $user_answers );
						break;
					case 1: // text
					case 2: // multiple 1
					default:
						$answers[$question['id']] = Football_Pool_Utils::post_string( '_bonus_' . $question['id'] );
				}
				
				// add user input to answer (for multiple choice questions) if there is some input
				$user_input = Football_Pool_Utils::post_string( '_bonus_' . $question['id'] . '_userinput' );
				if ( $user_input != '' )
					$answers[$question['id']] .= " {$user_input}";
			}
			$this->update_bonus_user_answers( $questions, $answers, $user );
		}
		
		return true;
	}
	
	private function get_joker() {
		return Football_Pool_Utils::post_integer( '_joker' );
	}
	
	// outputs a prediction form for bonus questions.
	//    wrap:        (optional) if true, wrap the questions in its own form
	//    start_at_nr: (optional) the bonus question numbering will start at that number
	public function prediction_form_questions( $questions, $wrap = false, $id = 1, $start_at_nr = 1 ) {
		$output = '';
		if ( $this->has_bonus_questions ) {
			if ( $wrap ) $output .= $this->prediction_form_start( $id );
			
			$nr = $start_at_nr;
			foreach ( $questions as $question ) {
				if ( $question['match_id'] == 0 ) {
					$output .= $this->print_bonus_question( $question, $nr++ );
				}
			}
			
			if ( $nr > $start_at_nr ) {
				$output .= $this->save_button();
			}
			
			if ( $wrap ) $output .= $this->prediction_form_end( $id );
		}
		
		return $output;
	}
	
	// outputs a prediction form for matches.
	// also includes linked questions (if any).
	//    wrap:        (optional) if true, wrap the matches in its own form
	//    id:          unique form id
	public function prediction_form_matches( $matches, $wrap = false, $id = 1 ) {
		$output = '';
		if ( $this->has_matches ) {
			
			if ( $wrap ) $output .= $this->prediction_form_start( $id );
			
			global $current_user;
			get_currentuserinfo();
			
			$m = new Football_Pool_Matches;
			$output .= $m->print_matches_for_input( $matches, $id, $current_user->ID );
			
			if ( $this->pool_has_jokers ) {
				$joker = $m->joker_value;
				$output .= sprintf( '<input type="hidden" id="_joker_%d" name="_joker" value="%d" />', $id, $joker );
			}
			
			if ( count( $matches ) > 0 ) {
				$output .= $this->save_button();
			}
			
			if ( $wrap ) $output .= $this->prediction_form_end( $id );
		}
		
		return $output;
	}
	
	public function prediction_form_start( $id = 1) {
		$action_url = '';//( is_page() ? get_page_link() : get_permalink() );
		$output = sprintf( '<form id="predictionform-%d" action="%s" method="post">'
							, $id, $action_url
					);
		$output .= wp_nonce_field( FOOTBALLPOOL_NONCE_BLOG, FOOTBALLPOOL_NONCE_FIELD_BLOG, true, false );
		$output .= sprintf( '<input type="hidden" name="_fp_form_id" value="%d" />', $id );
		return $output;
	}
	
	public function prediction_form_end( $id = 1 ) {
		return sprintf( '<input type="hidden" id="_action_%d" name="_fp_action" value="update" /></form>', $id );
	}
	
	public function print_bonus_question_for_user( $questions ) {
		$output = '';
		$nr = 1;
		$statspage = Football_Pool::get_page_link( 'statistics' );
		
		foreach ( $questions as $question ) {
			if ( $this->always_show_predictions || ! $this->question_is_editable( $question['question_timestamp'] ) ) {
				$output .= '<div class="bonus userview">';
				$output .= sprintf( '<p class="question"><span class="nr">%d.</span> %s</p>'
									, $nr++
									, $question['question'] 
							);
				$output .= '<span class="bonus points">';
				if ( $question['score_date'] ) {
					// standard points or alternate points as reward for question?
					$points = ( $question['user_points'] != 0 ) ? $question['user_points'] : $question['points'];
					$output .= sprintf( '%d %s ', 
										( $question['correct'] * $points ),
										__( 'points', FOOTBALLPOOL_TEXT_DOMAIN )
								);
				}
				$output .= sprintf( '<a title="%s" href="%s">', 
									__( 'view other users answers', FOOTBALLPOOL_TEXT_DOMAIN )
									, esc_url(
										add_query_arg( 
											array( 'view' => 'bonusquestion', 'question' => $question['id'] ), 
											$statspage
										)
									) 
							);
				$output .= sprintf( '<img alt="%s" src="%sassets/images/site/charts.png" />',
									__( 'view other users answers', FOOTBALLPOOL_TEXT_DOMAIN ), FOOTBALLPOOL_PLUGIN_URL );
				$output .= '</a></span>';
				$output .= sprintf( '<p>%s: %s</p>',
									__( 'answer', FOOTBALLPOOL_TEXT_DOMAIN ),
									( $question['answer'] != '' ? $question['answer'] : '...' )
							);
				$output .= '</div>';
			}
		}
		
		return $output;
	}
	
	public function question_is_editable( $ts ) {
		if ( $this->force_lock_time ) {
			$editable = ( current_time( 'timestamp' ) < $this->lock_timestamp );
		} else {
			$diff = $ts - time();
			$editable = ( $diff > $this->lock_timestamp );
		}
		
		return $editable;
	}

	public function get_bonus_question_answers_for_users( $question = 0 ) {
		if ( $question == 0 ) return array();
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT u.ID AS user_id, u.display_name AS name, a.answer, a.correct, a.points
				FROM {$prefix}bonusquestions_useranswers a 
				RIGHT OUTER JOIN {$wpdb->users} u
					ON ( a.question_id = %d AND a.user_id = u.ID ) ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( u.ID = lu.user_id ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
			$sql .= "WHERE ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		}
		$sql .= "ORDER BY u.display_name ASC";
		$sql = $wpdb->prepare( $sql, $question );
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return $rows;
	}
	
	private function save_button() {
		return sprintf( '<div class="buttonblock"><input type="submit" name="_submit" value="%s" /></div>',
						__( 'Save', FOOTBALLPOOL_TEXT_DOMAIN )
				);
	}
	
	public function get_avatar( $user_id, $size = 'small', $wrap = true ) {
		if ( ! $this->show_avatar ) return;
		
		if ( ! is_int( $size ) ) {
			switch ( $size ) {
				case 'large':
					$size = FOOTBALLPOOL_LARGE_AVATAR;
					break;
				case 'medium':
					$size = FOOTBALLPOOL_MEDIUM_AVATAR;
					break;
				case 'small':
				default:
					$size = FOOTBALLPOOL_SMALL_AVATAR;
			}
		}
		
		$avatar = get_avatar( $user_id, $size );
		if ( $wrap )
			return sprintf( '<span class="fp-avatar">%s</span>', $avatar );
		else
			return $avatar;
	}
}
