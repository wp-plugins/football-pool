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
	
	// use league=0 to include all users
	public function get_ranking_from_score_history( $league, $score_date = '', $type = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT u.ID AS userId, u.display_name AS userName, u.user_email AS email, " 
			. ( $this->has_leagues ? "lu.leagueId, " : "" ) 
			. "		COALESCE(MAX(s.totalScore), 0) AS points, 
					COUNT(IF(full=1,1,NULL)) AS full, 
					COUNT(IF(toto=1,1,NULL)) AS toto,
					COUNT(IF(type=1 AND score>0,1,NULL)) AS bonus 
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
					qt.type, qt.options, qt.image
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
										qt.type, qt.options, qt.image
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
									qt.type, qt.options, qt.image
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
	
	private function bonus_question_types( $question ) {
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
			$options = explode( ';', $question['options'] );
			foreach ( $options as $option ) {
				if ( str_replace( ' ', '', $option ) != '' ) {
					if ( $type == 'checkbox' ) {
						$checked = in_array( $option, explode( ';', $question['answer'] ) ) ? 'checked="checked" ' : '';
						$backets = '[]';
					} else {
						$checked = $question['answer'] == $option ? 'checked="checked" ' : '';
						$backets = '';
					}
					$output .= sprintf( '<label><input type="%1$s" name="_bonus_%2$d%5$s" value="%3$s" %4$s/> %3$s</label>'
										, $type
										, esc_attr( $question['id'] )
										, esc_attr( $option )
										, $checked
										, $backets 
								);
					$output .= '<br />';
				}
			}
		}
		
		return $output;
	}
	
	public function print_bonus_question( $question, $nr ) {
		// the question with optional image
		$output = sprintf( '<div class="bonus" id="q%d"><p>%d. %s</p>', $question['id'], $nr, $question['question'] );
		if ( $question['image'] != '' ) {
			$output .= sprintf( '<p class="bonus image"><img src="%s" alt="%s" /></p>'
								, $question['image']
								, __( 'fotovraag', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		}
		
		if ( $this->bonus_is_editable( $question['questionDate'] ) ) {
			$output .= sprintf( '<p>%s</p>', $this->bonus_question_types( $question ) );
			
			// remind a player if there is only 1 day left to answer the question.
			$output .= '<p>';
			if ( ( $question['questionDate'] - time() ) <= ( 24 * 60 * 60 ) ) {
				$output .= sprintf( '<span class="bonus reminder">%s </span>', __( 'Let op:', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			$output .= sprintf( '<span class="bonus eindtijd" title="%s">%s ' . $question['answerBeforeDate'] . '</span>',
							__( 'beantwoord deze vraag vóór deze datum', FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'beantwoorden vóór', FOOTBALLPOOL_TEXT_DOMAIN )
					);
		} else {
			$output .= sprintf( '<p class="bonus" id="bonus-%d">%s: ',
							$question['id'],
							__( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$output .= ( $question['answer'] != '' ? $question['answer'] : '...' );
			$output .= '</p>';
			
			$output .= sprintf( '<p><span class="bonus eindtijd" title="%s">%s %s</span>',
							__( 'je kan deze vraag niet meer beantwoorden, of je antwoord wijzigen', FOOTBALLPOOL_TEXT_DOMAIN ),
							__( 'gesloten op', FOOTBALLPOOL_TEXT_DOMAIN ),
							$question['answerBeforeDate']
					);
		}
		
		$output .= sprintf( '<span class="bonus points">%d %s</span></p>', $question['points'], __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$output .= '</div>';
		
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