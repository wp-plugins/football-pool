<?php
class Football_Pool_Pool {
	public $leagues;
	public $has_bonus_questions = false;
	public $has_leagues;
	
	public function __construct() {
		$this->leagues = $this->get_leagues();
		$this->has_leagues = ( get_option('footballpool_use_leagues') == '1' ) && ( count( $this->leagues ) > 1 );
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
				$score = (integer) Football_Pool_Utils::get_wp_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
			} else {
				$score = (integer) Football_Pool_Utils::get_wp_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
			}
		}
		
		if ( $joker == 1 ) $score *= 2;
		
		return $score;
	}
	
	public function get_users( $league ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT u.ID AS userId, u.display_name AS userName, u.user_email AS email, ";
		$sql .= ( $this->has_leagues ? "l.leagueId, " : "" );
		$sql .= "0 AS points, 0 AS full, 0 AS toto, 0 AS bonus FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users l 
						ON (u.ID = l.userId" . ( $league > 1 ? ' AND l.leagueId = ' . $league : '' ) . ") ";
		}
		$sql .= "ORDER BY userName ASC";
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	// use league=0 to include all users
	public function get_ranking_from_score_history( $league, $score_date = '', $type = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT u.id AS userId, u.display_name AS userName, u.user_email AS email, " 
			. ( $this->has_leagues ? "l.leagueId, " : "" ) 
			. "		COALESCE(MAX(s.totalScore), 0) AS points, 
					COUNT(IF(full=1,1,NULL)) AS full, 
					COUNT(IF(toto=1,1,NULL)) AS toto,
					COUNT(IF(type=1 AND score>0,1,NULL)) AS bonus 
				FROM {$wpdb->users} u ";
		if ( $this->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users l 
						ON (
								u.id=l.userId
								AND (" . ($league <= FOOTBALLPOOL_LEAGUE_ALL ? "1=1 OR " : "") . "l.leagueId = %d)
							) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}scorehistory s ON 
					(
						s.userId=u.id
						AND (" . ($score_date == '' ? "1=1 OR " : "") . "s.scoreDate <= %s)
						AND (" . ($type == 0 ? "1=1 OR " : "") . "s.type = %d)
					) 
				GROUP BY u.ID
				ORDER BY points DESC, full DESC, toto DESC, bonus DESC, " . ( $this->has_leagues ? "l.leagueId ASC, " : "" ) . "LOWER(u.display_name) ASC";
		
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
		global $wpdb;
		$sql = $this->get_ranking_from_score_history( $league );
		return $wpdb->get_results( $sql, ARRAY_A );
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
				$output .= '<p>' . __( 'Nog geen resultaten. Hieronder zie je een lijst met alle spelers.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
				foreach ( $rows as $row ) {
					$ranking[] = $row;
				}
			} else {
				$output .= '<p>'. __( 'Er hebben zich (nog) geen spelers geregistreerd.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
			}
		}
		
		if ( count( $ranking ) > 0 ) {
			$userpage = Football_Pool::get_page_link( 'user' );
			$i = 1;
			$output .= '<table style="width:300px;" class="poolranking">';
			foreach ( $ranking as $row ) {
				$class = ( $i % 2 != 0 ? 'even' : 'odd' );
				if ( $row['userId'] == $user ) $class .= ' currentuser';
				$output .= sprintf( '<tr class="%s"><td style="width:3em; text-align: right;">%d.</td>
									<td><a href="%s?user=%d">%s</a></td>
									<td>%d</td>
									%s
									<!-- full: %d || toto: %d -->
									</tr>',
								$class,
								$i++,
								$userpage,
								$row['userId'],
								$row['userName'],
								$row['points'],
								( $league == FOOTBALLPOOL_LEAGUE_ALL && $this->has_leagues 
										? $this->league_image( $row['leagueId'] ) : '' ),
								$row['full'],
								$row['toto']
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
		return $leagues;
	}
	
	public function leagueFilter( $league = 0, $select = 'league' ) {
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
	
	public function get_bonus_questions( $user = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $user == 0 ) {
			// just the questions
			$sql = "SELECT id, question, answer, points, UNIX_TIMESTAMP(answerBeforeDate) AS questionDate, 
					DATE_FORMAT(scoreDate,'%Y-%m-%d %H:%i') AS scoreDate, 
					DATE_FORMAT(answerBeforeDate,'%Y-%m-%d %H:%i') AS answerBeforeDate, matchNr 
					FROM {$prefix}bonusquestions ORDER BY answerBeforeDate ASC";
		} else {
			// also user answers
			$sql = $wpdb->prepare( "
									SELECT 
										q.id, q.question, a.answer, 
										q.points, a.points AS userPoints, 
										UNIX_TIMESTAMP(q.answerBeforeDate) AS questionDate, 
										DATE_FORMAT(q.scoreDate,'%%Y-%%m-%%d %%H:%%i') AS scoreDate, 
										DATE_FORMAT(q.answerBeforeDate,'%%Y-%%m-%%d %%H:%%i') AS answerBeforeDate, 
										q.matchNr, a.correct 
									FROM {$prefix}bonusquestions q 
									LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a
										ON (a.questionId = q.id AND a.userId = %d)
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
		
		$sql = $wpdb->prepare( "SElECT id, question, answer, points, 
									DATE_FORMAT(answerBeforeDate, '%%Y-%%m-%%d %%H:%%i') AS answerBeforeDate, 
									DATE_FORMAT(scoreDate, '%%Y-%%m-%%d %%H:%%i') AS scoreDate, 
									matchNr, 
									UNIX_TIMESTAMP(answerBeforeDate) as questionDate 
								FROM {$prefix}bonusquestions 
								WHERE id=%d", 
							$id
							);
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function get_bonus_question_info( $id ) {
		$info = $this->get_bonus_question( $id );
		if ( $info ) $info['bonus_is_editable'] = $this->bonus_is_editable( $info['questionDate'] );
		return $info;
	}
	
	public function print_bonus_question( $question, $nr ) {
		$output = sprintf( '<div class="bonus" id="q%d"><p>%d. %s<br />', $question['id'], $nr, $question['question'] );
		$output .= sprintf( '<span class="bonus points">%d %s</span>', $question['points'], __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ) );
		if ( $this->bonus_is_editable( $question['questionDate'] ) ) {
			$pre = '';
			// remind a player if there is only 1 day left to answer the question.
			if ( ( $question['questionDate'] - time() ) <= ( 24 * 60 * 60 ) ) {
				$pre .= sprintf( '<span class="bonus reminder">%s </span>', __( 'Let op:', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			$pre .= sprintf( '<span class="bonus eindtijd" title="%s">%s ' . $question['answerBeforeDate'] . '</span>',
							__( 'beantwoord deze vraag vóór deze datum', FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'beantwoorden vóór', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$pre .= sprintf( '</p><p><input maxlength="200" class="bonus" name="_bonus_%d" type="text" value="',
							$question['id']
					);
			$post = '" /></p>';
			$answer = $question['answer'];
		} else {
			$pre  = sprintf( '<span class="bonus eindtijd" title="%s">%s %s</span>',
							__( 'je kan deze vraag niet meer beantwoorden', FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'gesloten op', FOOTBALLPOOL_TEXT_DOMAIN ),
							$question['answerBeforeDate']
					);
			$pre .= sprintf( '</p><p class="bonus" id="bonus-%d">%s: ',
							$question['id'],
							__( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$post = '</p>';
			$answer = ( $question['answer'] != '' ? $question['answer'] : '...' );
		}
		
		$output .= sprintf( '%s%s%s</div>', $pre, $answer, $post );
		
		return $output;
	}
	
	public function print_bonus_question_for_user( $questions ) {
		$output = '';
		$nr = 1;
		$statspage = Football_Pool::get_page_link( 'statistics' );
		foreach ( $questions as $question ) {
			if ( ! $this->bonus_is_editable( $question['questionDate'] ) ) {
				$output .= sprintf( '<div class="bonus userview"><p class="question">%d. %s</p><span class="bonus points">', 
									$nr++, $question['question'] );
				if ( $question['scoreDate'] ) {
					$output .= sprintf( '%d %s ', 
									( $question['correct'] * $question['points'] ),
									__( 'punten', FOOTBALLPOOL_TEXT_DOMAIN )
								);
				}
				$output .= sprintf( '<a title="%s" href="%s?view=bonusquestion&amp;question=%d">', 
									__( 'bekijk antwoorden van andere spelers', FOOTBALLPOOL_TEXT_DOMAIN ), $statspage, $question['id'] );
				$output .= sprintf( '<img alt="%s" src="%sassets/images/site/charts.png" />',
									__( 'bekijk antwoorden van andere spelers', FOOTBALLPOOL_TEXT_DOMAIN ), FOOTBALLPOOL_PLUGIN_URL );
				$output .= '</a></span>';
				$output .= sprintf( '<p>%s: %s</p></div>',
									__( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ),
									( $question['answer'] != '' ? $question['answer'] : '...' )
							);
			}
		}
		
		return $output;
	}
	
	public function bonus_is_editable( $ts ) {
		$diff = $ts - time();
		return ( $diff > 0 );
	}

	public function get_bonus_question_answers_for_users( $question = 0 ) {
		if ( $question == 0 ) return array();
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT u.ID AS userId, u.display_name AS name, a.answer, a.correct, a.points
				FROM {$prefix}bonusquestions_useranswers a 
				RIGHT OUTER JOIN {$wpdb->users} u
					ON (a.questionId = %d AND a.userId = u.ID) ";
		if ( $this->has_leagues ) {
			$sql .= "JOIN {$prefix}league_users lu ON (u.ID = lu.userId) ";
		}
		$sql .= "ORDER BY u.display_name ASC";
		$sql = $wpdb->prepare( $sql, $question );
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return $rows;
	}

}
?>