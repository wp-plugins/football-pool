<?php
// Based on Highcharts
class Football_Pool_Chart_Data {
	/************************************************
	 All the functions to get the data for the charts
	*************************************************/
	
	public function predictions_pie_chart_data( $match ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT
									COUNT( IF( full = 1, 1, NULL ) ) AS scorefull, 
									COUNT( IF( toto = 1, 1, NULL ) ) AS scoretoto, 
									COUNT( IF( goal_bonus = 1, 
												IF( toto = 1, NULL, 1 ), 
												NULL ) 
									) AS goalbonus, 
									COUNT( userId ) AS scoretotal
								FROM {$prefix}scorehistory 
								WHERE `type` = 0 
								GROUP BY scoreOrder HAVING scoreOrder = %d", 
							$match
						);
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function score_chart_data( $users = array() ) {
		$data = array();
		
		$pool = new Football_Pool_Pool;
		
		if ( count( $users ) > 0 ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = "SELECT 
						COUNT( IF( s.full = 1, 1, NULL ) ) AS scorefull, 
						COUNT( IF( s.toto = 1, 1, NULL ) ) AS scoretoto, 
						COUNT( IF( s.goal_bonus = 1, IF( s.toto = 1, NULL, 1 ), NULL ) ) AS single_goal_bonus, 
						COUNT( s.scoreOrder ) AS scoretotal, 
						u.display_name AS username 
					FROM {$prefix}scorehistory s 
					INNER JOIN {$wpdb->users} u ON ( u.ID = s.userId ) ";
			if ( $pool->has_leagues ) {
				$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
				$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
			} else {
				$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			}
			$sql .= "WHERE s.type = 0 AND s.userId IN ( " . implode( ',', $users ) . " ) ";
			if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
			$sql .= "GROUP BY s.userId";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $rows as $row ) {
				$data[ $row['username'] ] = array(
												'scorefull'  => $row['scorefull'],
												'scoretoto'  => $row['scoretoto'],
												'scoretotal' => $row['scoretotal'],
												'goalbonus' => $row['single_goal_bonus'],
												);
			}
		}
		
		return $data;
	}
	
	public function bonus_question_for_users_pie_chart_data( $users = array() ) {
		$data = array();
		if ( count( $users ) > 0 ) {
			$pool = new Football_Pool_Pool;
			$questions = $pool->get_bonus_questions();
			$numquestions = count( $questions );
			
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = "SELECT
						COUNT(IF(s.score>0,1,NULL)) AS bonuscorrect, 
						COUNT(IF(s.score=0,1,NULL)) AS bonuswrong,
						COUNT(s.scoreOrder) AS bonustotal,
						u.display_name AS username
					FROM {$prefix}scorehistory s
					INNER JOIN {$wpdb->users} u ON (u.ID=s.userId) ";
			if ( $pool->has_leagues ) {
				$sql .= "INNER JOIN {$prefix}league_users lu ON (lu.userId=u.ID) ";
				$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
			} else {
				$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON (lu.userId=u.ID) ";
			}
			$sql .= "WHERE s.type = 1 AND s.userId IN (" . implode(',', $users) . ") ";
			if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
			$sql .= "GROUP BY s.userId";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			foreach ( $rows as $row ) {
				$data[ $row['username'] ] = array(
												'bonustotal'   => $numquestions,
												'bonuscorrect' => $row['bonuscorrect'],
												'bonuswrong'   => $row['bonuswrong']
												);
			}
		}
		
		return $data;
	}

	public function bonus_question_pie_chart_data( $question ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		$sql = "SELECT 
					COUNT(IF(ua.correct>0,1,NULL)) AS bonuscorrect, 
					COUNT(IF(ua.correct=0,1,NULL)) AS bonuswrong,
					COUNT(u.ID) AS totalusers 
				FROM {$prefix}bonusquestions_useranswers AS ua 
				RIGHT OUTER JOIN {$wpdb->users} AS u
					ON (u.ID = ua.userId AND questionId = %d) ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON (lu.userId = u.ID) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON (lu.userId = u.ID) ";
			$sql .= "WHERE ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		}
		$sql = $wpdb->prepare( $sql, $question );
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		$data = array(
					'totalusers'   => $row['totalusers'],
					'bonuscorrect' => $row['bonuscorrect'],
					'bonuswrong'   => $row['bonuswrong']
					);

		return $data;
	}
	
	public function points_total_pie_chart_data( $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$output = array();
		// get the user's score
		$sql = $wpdb->prepare( "SELECT totalScore FROM {$prefix}scorehistory 
								WHERE userId = %d ORDER BY scoreDate DESC, scoreOrder DESC, type DESC LIMIT 1", 
								$user
							);
		$data = $wpdb->get_row( $sql, ARRAY_A );
		$output['totalScore'] = $data['totalScore'];
		// get the number of matches for which there are results
		$sql = $wpdb->prepare( "SELECT COUNT(*) AS numMatches FROM {$prefix}scorehistory
								WHERE type = 0 AND userId = %d", $user);
		$data = $wpdb->get_row( $sql, ARRAY_A );
		
		$full = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' ) +
				( 2 * Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' ) );
		$output['maxScore'] = $full * 2; // count first match with joker
		$output['maxScore'] += ( (int) $data['numMatches'] - 1 ) * $full; // all other matches
		// add the bonusquestions
		$sql = "SELECT SUM(points) AS `maxPoints` FROM {$prefix}bonusquestions WHERE scoreDate IS NOT NULL";
		$data = $wpdb->get_row( $sql, ARRAY_A );
		$output['maxScore'] += (int) $data['maxPoints'];
		
		return $output;
	}
	
	public function score_per_match_line_chart_data( $users ) {
		return $this->per_match_line_chart_data( $users, 'totalScore' );
	}
	
	public function ranking_per_match_line_chart_data( $users ) {
		return $this->per_match_line_chart_data( $users, 'ranking' );
	}
	
	private function per_match_line_chart_data( $users, $history_data_to_plot ) {
		$data = array();
		if ( count( $users ) > 0 ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = "SELECT h.scoreOrder, h." . $history_data_to_plot . ", u.display_name, h.type 
					FROM {$prefix}scorehistory h, {$wpdb->users} u 
					WHERE u.ID = h.userId AND h.userId IN (" . implode(',', $users) . ")
					ORDER BY h.scoreDate ASC, h.type ASC, h.scoreOrder ASC, h.userId ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			foreach ( $rows as $row ) {
				$data[] = array(
								'match'    => $row['scoreOrder'],
								'type'     => $row['type'],
								'value'    => $row[$history_data_to_plot],
								'username' => $row['display_name']
								);
			}
		}
		
		return $data;
	}
	
	/*****************************************
	Build data arrays for the series option 
	******************************************/
	public function score_chart_series( $rows ) {
		$goal_bonus = ( Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' ) > 0 );
		$data = array();
		foreach ( $rows as $name => $row ) {
			$data[$name] = array(
								array( __( 'full score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scorefull'] ),
								array( __( 'toto score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scoretoto'] ),
								array( __( 'no score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scoretotal'] - $row['scorefull'] - $row['scoretoto'] - ( $goal_bonus ? $row['goalbonus'] : 0 ) ),
							);
			if ( $goal_bonus ) {
				$data[$name][] = array( __( 'just the goal bonus', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['goalbonus'] );
			}
		}
		return $data;
	}
	
	public function predictions_pie_series( $row ) {
		$goal_bonus = ( Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' ) > 0 );
		$data = array(
					array( __( 'full score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scorefull'] ),
					array( __( 'toto score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scoretoto'] ),
					array( __( 'no score', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['scoretotal'] - $row['scorefull'] - $row['scoretoto'] - ( $goal_bonus ? $row['goalbonus'] : 0 ) )
				);
		if ( $goal_bonus ) {
			$data[] = array( __( 'just the goal bonus', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['goalbonus'] );
		}
		return $data;
	}
	
	public function points_total_pie_series( $row ) {
		$data = array(
					array( __( 'points scored', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['totalScore'] ),
					array( __( 'points missed', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['maxScore'] - $row['totalScore'] )
				);
		return $data;
	}
	
	public function bonus_question_pie_series( $rows ) {
		$data = array();
		foreach ( $rows as $name => $row ) {
			$data[$name] = array(
								array( __( 'correct', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonuscorrect'] ), 
								array( __( 'wrong', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonuswrong'] ),
								array( __( 'still open', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonustotal'] - $row['bonuscorrect'] - $row['bonuswrong'] )
								//array( __( 'no answer', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonusnoanswer'] )
							);
		}
		return $data;
	}
	
	public function bonus_question_pie_series_one_question( $row ) {
		$data = array(
					array( __( 'correct', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonuscorrect'] ), 
					array( __( 'wrong', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['bonuswrong'] ),
					array( __( 'no answer', FOOTBALLPOOL_TEXT_DOMAIN ), (int) $row['totalusers'] - $row['bonuscorrect'] - $row['bonuswrong'] )
				);
		return $data;
	}
	
	private function per_match_line_series( $lines ) {
		if ( count( $lines ) > 0 ) {
			$categoriesdata = array();
			$seriesdata = array();
			
			$users = array();
			$matchnr = 0;
			$questionnr = 0;
			$match = '';
			$type = '';
			foreach ( $lines as $datarow ) {
				// if new user, then start a new series
				$user = $datarow['username'];
				if ( ! array_key_exists( $user, $seriesdata ) ) {
					$seriesdata[$user] = array(
												'name' => $user, 
												'data' => array() 
											);
				}
				// new match or question?
				if ( $match != $datarow['match'] || $type != $datarow['type'] ) {
					$match = (int) $datarow['match'];
					$type = $datarow['type'];
					if ( $type == 0 ) {
						// $categoriesdata[] = __( 'match', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . ++$matchnr;
						$matchinfo = new Football_Pool_Matches;
						$matchinfo = $matchinfo->get_match_info( $match );
						$category_data = __( 'match', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . ++$matchnr;
						if ( isset( $matchinfo['home_team'] ) ) {
							$category_data .= ': ' . $matchinfo['home_team'] . ' - ' . $matchinfo['away_team'];
						}
						$categoriesdata[] = $category_data;
					} else {
						$categoriesdata[] = __( 'bonus question', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . ++$questionnr;
					}
				}
				$seriesdata[$user]['data'][] = (int) $datarow['value'];
			}
			
			$output = array(
							'categories' => $categoriesdata, 
							'series' => $seriesdata
							);
		} else {
			$output = array(
							'categories' => array(), 
							'series' => array()
							);
		}
		
		return $output;	
	}
	
	public function score_per_match_line_series( $lines ) {
		return $this->per_match_line_series( $lines );
	}

	public function ranking_per_match_line_series( $lines ) {
		return $this->per_match_line_series( $lines );
	}
}
?>