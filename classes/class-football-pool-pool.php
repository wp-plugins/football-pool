<?php
class Football_Pool_Pool {
	public $leagues;
	public $has_bonus_questions = false;
	public $has_leagues;
	public $force_lock_time = false;
	private $lock_timestamp;
	private $lock_datestring;
	
	public function __construct() {
		$this->leagues = $this->get_leagues();
		$this->has_leagues = ( Football_Pool_Utils::get_wp_option( 'footballpool_use_leagues' ) == '1' ) && ( count( $this->leagues ) > 1 );
		
		$this->lock_datestring = Football_Pool_Utils::get_wp_option( 'footballpool_bonus_question_locktime', '' );
		$this->force_lock_time = 
			( Football_Pool_Utils::get_wp_option( 'footballpool_stop_time_method_questions', 0, 'int' ) == 1 )
			&& ( $this->lock_datestring != '' );
		if ( $this->force_lock_time ) {
			//$date = DateTime::createFromFormat( 'Y-m-d H:i', $this->lock_datestring );
			$date = new DateTime( Football_Pool_Utils::date_from_gmt( $this->lock_datestring ) );
			$this->lock_timestamp = $date->getTimestamp();
		} else {
			$this->lock_timestamp = 0; // bonus questions have no time threshold
		}
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
		if ( $home == '' || $away == '' )
			return '';
		if ( $user_home == '' || $user_away == '' )
			return 0;
		
		$score = 0;
		// check for toto result
		if ( $this->is_toto_result( $home, $away, $user_home, $user_away ) == true ) {
			// check for exact match
			if ( $home == $user_home && $away == $user_away ) {
				$score = (int) Football_Pool_Utils::get_wp_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
			} else {
				$score = (int) Football_Pool_Utils::get_wp_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
			}
		}
		
		if ( $joker == 1 ) $score *= 2;
		
		return $score;
	}
	
	public function get_users( $league ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT u.ID AS userId, u.display_name AS userName, u.user_email AS email, ";
		$sql .= ( $this->has_leagues ? "lu.leagueId, " : "" );
		$sql .= "0 AS points, 0 AS full, 0 AS toto, 0 AS bonus FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu 
						ON (u.ID = lu.userId" . ( $league > 1 ? ' AND lu.leagueId = ' . $league : '' ) . ") ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON (lu.userId = u.ID) ";
			$sql .= "WHERE ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		}
		$sql .= "ORDER BY userName ASC";
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function user_is_player( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $this->has_leagues ) {
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}league_users lu
									INNER JOIN {$wpdb->users} u ON ( u.ID = lu.userId )
									WHERE u.ID = %d AND lu.leagueId <> 0"
									, $user_id );
		} else {
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}league_users lu
									RIGHT OUTER JOIN {$wpdb->users} u ON ( u.ID = lu.userId )
									WHERE u.ID = %d AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL )"
									, $user_id );
		}
		
		return ( $wpdb->get_var( $sql ) == 1 );
	}
	
	// returns null if no leagues are available or user does not exist
	public function get_league_for_user( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $this->has_leagues ) {
			$sql = $wpdb->prepare( "SELECT leagueId FROM {$prefix}league_users WHERE userId = %d", $user_id );
			$league = $wpdb->get_var( $sql );
		} else {
			$league = null;
		}
		
		return (int) $league;
	}
	
	// use league=0 to include all users
	public function get_ranking_from_score_history( $league, $score_date = '', $type = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT u.ID AS userId, u.display_name AS userName, u.user_email AS email, " 
			. ( $this->has_leagues ? "lu.leagueId, " : "" ) 
			. "		COALESCE( MAX( s.totalScore ), 0 ) AS points, 
					COUNT( IF( s.full=1, 1, NULL ) ) AS full, 
					COUNT( IF( s.toto=1, 1, NULL ) ) AS toto,
					COUNT( IF( s.type=1 AND score>0, 1, NULL ) ) AS bonus 
				FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu 
						ON (
							u.ID=lu.userId
							AND (" . ($league <= FOOTBALLPOOL_LEAGUE_ALL ? "1=1 OR " : "") . "lu.leagueId = %d)
							) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}scorehistory s ON 
					(
						s.userId=u.ID
						AND (" . ($score_date == '' ? "1=1 OR " : "") . "s.scoreDate <= %s)
						AND (" . ($type == 0 ? "1=1 OR " : "") . "s.type = %d)
					) ";
		if ( ! $this->has_leagues ) $sql .= "WHERE ( leagueId <> 0 OR leagueId IS NULL ) ";
		$sql .= "GROUP BY u.ID
				ORDER BY points DESC, full DESC, toto DESC, bonus DESC, " . ( $this->has_leagues ? "lu.leagueId ASC, " : "" ) . "LOWER(u.display_name) ASC";
		
		if ( $this->has_leagues ) 
			return $wpdb->prepare( $sql, $league, $score_date, $type );
		else
			return $wpdb->prepare( $sql, $score_date, $type );
	}
	
	public function get_pool_ranking_limited( $league, $num_users, $score_date = '' ) {
		global $wpdb;
		$sql = $wpdb->prepare( $this->get_ranking_from_score_history( $league, $score_date ) . ' LIMIT %d', 
								$num_users );
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function get_pool_ranking( $league ) {
		$cache_key = 'fp_get_pool_ranking';
		$rows = wp_cache_get( $cache_key );
		
		if ( $rows === false ) {
			global $wpdb;
			$sql = $this->get_ranking_from_score_history( $league );
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $rows );
		}
		
		return $rows;
	}
	
	public function print_pool_ranking( $league, $user ) {
		$output = '';
		
		$rows = $this->get_pool_ranking( $league );
		$ranking = array();
		if ( count( $rows ) > 0 ) {
			// there are results in the database, so get the ranking
			foreach ( $rows as $row ) {
				$ranking[] = $row;
			}
		} else {
			// no results, show a list of users
			$rows = $this->get_users( $league );
			if ( count( $rows ) > 0 ) {
				$output .= '<p>' . __( 'No results yet. Below is a list of all users.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
				foreach ( $rows as $row ) {
					$ranking[] = $row;
				}
			} else {
				$output .= '<p>'. __( 'No users have registered for this pool (yet).', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
			}
		}
		
		if ( count( $ranking ) > 0 ) {
			$userpage = Football_Pool::get_page_link( 'user' );
			$i = 1;
			$output .= '<table style="width:300px;" class="poolranking">';
			foreach ( $ranking as $row ) {
				$class = ( $i % 2 != 0 ? 'even' : 'odd' );
				$all_user_view = ( $league == FOOTBALLPOOL_LEAGUE_ALL && $this->has_leagues );
				if ( $all_user_view ) $class .= ' league-' . $row['leagueId'];
				if ( $row['userId'] == $user ) $class .= ' currentuser';
				$output .= sprintf( '<tr class="%s"><td style="width:3em; text-align: right;">%d.</td>
									<td><a href="%s">%s</a></td>
									<td>%d</td>
									%s
									</tr>',
								$class,
								$i++,
								esc_url( add_query_arg( array( 'user' => $row['userId'] ), $userpage ) ),
								$row['userName'],
								$row['points'],
								( $all_user_view ? $this->league_image( $row['leagueId'] ) : '' )
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
							$this->leagues[$id]['leagueName'],
							$this->leagues[$id]['leagueName']
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
			
			$filter = $only_user_defined ? 'WHERE userDefined=1' : '';
			
			$sql = "SELECT id AS leagueId, name AS leagueName, userDefined, image 
					FROM {$prefix}leagues {$filter} ORDER BY userDefined ASC, name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			$leagues = array();
			foreach ( $rows as $row ) {
				$leagues[$row['leagueId']] = $row;
			}
			wp_cache_set( $cache_key, $leagues );
		}
		
		return $leagues;
	}
	
	public function league_filter( $league = 0, $select = 'league' ) {
		$output = '';
		
		if ( $this->has_leagues ) {
			$output .= sprintf( '<select name="%s" id="%s">', $select, $select );
			foreach ( $this->leagues as $row ) {
				$output .= sprintf('<option value="%d"%s>%s</option>',
								$row['leagueId'],
								($row['leagueId'] == $league ? ' selected="selected"' : ''),
								$row['leagueName']
							);
			}
			$output .= '</select>';
		}
		return $output;
	}
	
	public function league_select( $league = 0, $select = 'league' ) {
		$output = '';
		
		if ( $this->has_leagues ) {
			$output .= sprintf('<select name="%s" id="%s">', $select, $select);
			$output .= '<option value="0"></option>';
			foreach ( $this->leagues as $row ) {
				if ( $row['userDefined'] == 1 ) {
					$output .= sprintf( '<option value="%d"%s>%s</option>',
									$row['leagueId'],
									( $row['leagueId'] == $league ? ' selected="selected"' : '' ),
									$row['leagueName']
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
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( userId, leagueId ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE leagueId = leagueId", 
									$user_id, $new_league_id
								);
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( userId, leagueId ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE leagueId = %d", 
									$user_id, $new_league_id, $new_league_id
								);
		}
		$wpdb->query( $sql );
	}
	
	public function get_bonus_questions( $user = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $user == 0 ) {
			// just the questions
			$sql = "SELECT q.id, q.question, q.answer, q.points, UNIX_TIMESTAMP(q.answerBeforeDate) AS questionDate, 
					DATE_FORMAT(q.scoreDate,'%Y-%m-%d %H:%i') AS scoreDate, 
					DATE_FORMAT(q.answerBeforeDate,'%Y-%m-%d %H:%i') AS answerBeforeDate, q.matchNr,
					qt.type, qt.options, qt.image, qt.max_answers
					FROM {$prefix}bonusquestions q 
					INNER JOIN {$prefix}bonusquestions_type qt
						ON ( q.id = qt.question_id )
					ORDER BY q.answerBeforeDate ASC";
		} else {
			// also user answers
			$sql = $wpdb->prepare( "SELECT 
										q.id, q.question, a.answer, 
										q.points, a.points AS userPoints, 
										UNIX_TIMESTAMP(q.answerBeforeDate) AS questionDate, 
										DATE_FORMAT(q.scoreDate,'%%Y-%%m-%%d %%H:%%i') AS scoreDate, 
										DATE_FORMAT(q.answerBeforeDate,'%%Y-%%m-%%d %%H:%%i') AS answerBeforeDate, 
										q.matchNr, a.correct,
										qt.type, qt.options, qt.image, qt.max_answers
									FROM {$prefix}bonusquestions q 
									INNER JOIN {$prefix}bonusquestions_type qt
										ON ( q.id = qt.question_id )
									LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a
										ON ( a.questionId = q.id AND a.userId = %d )
									ORDER BY q.answerBeforeDate ASC",
								$user
							);
		}
		
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $rows ) > 0 ) $this->has_bonus_questions = true;
		return $rows;
	}
	
	public function get_bonus_question( $id = 0 ) {
		if ( $id == 0 ) return false;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SElECT q.id, q.question, q.answer, q.points, 
									DATE_FORMAT(q.answerBeforeDate, '%%Y-%%m-%%d %%H:%%i') AS answerBeforeDate, 
									DATE_FORMAT(q.scoreDate, '%%Y-%%m-%%d %%H:%%i') AS scoreDate, 
									q.matchNr, 
									UNIX_TIMESTAMP(q.answerBeforeDate) as questionDate,
									qt.type, qt.options, qt.image, qt.max_answers
								FROM {$prefix}bonusquestions q
								INNER JOIN {$prefix}bonusquestions_type qt
									ON ( q.id = qt.question_id )
								WHERE q.id = %d", 
							$id
							);
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function get_bonus_question_info( $id ) {
		$info = $this->get_bonus_question( $id );
		if ( $info ) $info['bonus_is_editable'] = $this->bonus_is_editable( $info['questionDate'] );
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
			// @todo
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
				if ( str_replace( array( ' ', "\t", "\r", "\n" ), array( '', '', '', '' ), $option ) != '' ) {
					$answer = $question['answer'];
					$js = sprintf( 'onclick="jQuery( \'#_bonus_%d_userinput\' ).val( \'\' )" ', $question['id'] );
					
					if ( $type == 'checkbox' ) {
						$checked = in_array( $option, explode( ';', $answer ) ) ? 'checked="checked" ' : '';
						$brackets = '[]';
						$user_input = '';
					} else {
						// @todo: very hacky (and therefore undocumented) feature of adding a text input
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
		$output = sprintf( '<div class="bonus" id="q%d"><p><span class="nr">%d.</span> %s</p>'
							, $question['id']
							, $nr, $question['question'] 
					);
		if ( $question['image'] != '' ) {
			$output .= sprintf( '<p class="bonus image"><img src="%s" alt="%s" /></p>'
								, $question['image']
								, __( 'photo question', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		}
		
		$lock_time = ( $this->force_lock_time ) ? $this->lock_datestring : $question['answerBeforeDate'];
		// to local time
		$lock_time = Football_Pool_Utils::date_from_gmt( $lock_time );
		
		if ( $this->bonus_is_editable( $question['questionDate'] ) ) {
			$output .= sprintf( '<p>%s</p>', $this->bonus_question_form_input( $question ) );
			
			$output .= '<p>';
			
			// remind a player if there is only 1 day left to answer the question.
			$timestamp = ( $this->force_lock_time ? $this->lock_timestamp : $question['questionDate'] );
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
			$output .= ( $question['answer'] != '' ? $question['answer'] : '...' );
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
	
	public function print_bonus_question_for_user( $questions ) {
		$output = '';
		$nr = 1;
		$statspage = Football_Pool::get_page_link( 'statistics' );
		
		foreach ( $questions as $question ) {
			if ( ! $this->bonus_is_editable( $question['questionDate'] ) ) {
				$output .= '<div class="bonus userview">';
				$output .= sprintf( '<p class="question"><span class="nr">%d.</span> %s</p>'
									, $nr++
									, $question['question'] 
							);
				$output .= '<span class="bonus points">';
				if ( $question['scoreDate'] ) {
					// standard points or alternate points as reward for question?
					$points = ( $question['userPoints'] != 0 ) ? $question['userPoints'] : $question['points'];
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
	
	public function bonus_is_editable( $ts ) {
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
		
		$sql = "SELECT u.ID AS userId, u.display_name AS name, a.answer, a.correct, a.points
				FROM {$prefix}bonusquestions_useranswers a 
				RIGHT OUTER JOIN {$wpdb->users} u
					ON ( a.questionId = %d AND a.userId = u.ID ) ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( u.ID = lu.userId ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			$sql .= "WHERE ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		}
		$sql .= "ORDER BY u.display_name ASC";
		$sql = $wpdb->prepare( $sql, $question );
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return $rows;
	}
}
?>