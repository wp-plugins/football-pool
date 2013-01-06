<?php
class Football_Pool_Statistics {
	public $data_available = false;
	public $stats_visible = false;
	public $stats_enabled = false;
	
	public function __construct() {
		$this->data_available = $this->check_data();
		
		$chart = new Football_Pool_Chart;
		$this->stats_enabled = $chart->stats_enabled;
	}
	
	public function page_content() {
		$output = new Football_Pool_Statistics_Page();
		return $output->page_content();
	}
	
	private function check_data( $match = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$ranking_id = FOOTBALLPOOL_RANKING_DEFAULT;
		$single_match = ( $match > 0 ) ? '' : '1 = 1 OR';
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}scorehistory 
								WHERE ranking_id = {$ranking_id} 
									AND ( {$single_match} ( type = 0 AND scoreOrder = %d ) )", 
								$match
							);
		$num = $wpdb->get_var( $sql );
		
		return ( $num > 0 );
	}
	
	public function data_available_for_match( $match ) {
		return $this->check_data( $match );
	}
	
	public function get_user_info( $user ) {
		return get_userdata( $user );
	}
	
	public function show_user_info( $user ) {
		if ( $user ) {
			$output = sprintf( '<h1>%s</h1>', $user->display_name );
			$this->stats_visible = true;
		} else {
			$output = sprintf( '<p>%s</p>', __( 'User unknown.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$this->stats_visible = false;
		}
		
		return $output;
	}
	
	public function show_match_info( $info ) {
		$output = '';
		$this->stats_visible = false;
		
		if ( count( $info ) > 0 ) {
			if ( $info['match_is_editable'] == true ) {
				$output .= sprintf('<h1>%s - %s</h1>', $info['home_team'], $info['away_team']);
				$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			} else {
				$output .= sprintf( '<h2>%s - %s', $info['home_team'], $info['away_team'] );
				if ( $info['home_score'] != '' && $info['away_score'] != '' ) {
					$output .= sprintf( ' (%d - %d)', $info['home_score'], $info['away_score'] );
				}
				$output .= '</h2>';
				$this->stats_visible = true;
			}
		} else {
			$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		return $output;
	}
	
	public function show_bonus_question_info( $question ) {
		$output = '';
		$pool = new Football_Pool_Pool;
		$info = $pool->get_bonus_question_info( $question );
		if ( $info ) {
			$output .= sprintf( '<h1>%s</h1><h2>%s</h2>'
								, __( 'Bonus question', FOOTBALLPOOL_TEXT_DOMAIN )
								, $info['question'] 
						);
			if ( $info['bonus_is_editable'] == true ) {
				$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$this->stats_visible = false;
			} else {
				$output .= sprintf( '<p>%s: %s<br/>%s: %d</p>',
									__( 'answer', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['answer'],
									__( 'points', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['points']
								);
				$this->stats_visible = true;
			}
		} else {
			$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$this->stats_visible = false;
		}
		
		return $output;
	}
	
	public function show_answers_for_bonus_question( $id ) {
		$pool = new Football_Pool_Pool;
		$answers = $pool->get_bonus_question_answers_for_users( $id );
		
		$output = '<table class="statistics">
					<tr><th>' . __( 'user', FOOTBALLPOOL_TEXT_DOMAIN ) . '</th><th>' . __( 'answer', FOOTBALLPOOL_TEXT_DOMAIN ) . '</th><th class="correct">' . __( 'correct', FOOTBALLPOOL_TEXT_DOMAIN ) . '?</th></tr>';
		
		$img = '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/site/correct.jpg" 
					title="' . __( 'answer correct', FOOTBALLPOOL_TEXT_DOMAIN ) . '" alt="' . __( 'correct', FOOTBALLPOOL_TEXT_DOMAIN ) . '" width="16" height="16" />';
		
		foreach ( $answers as $answer ) {
			$output .= sprintf( '<tr><td>%s</td><td>%s</td>', $answer['name'], $answer['answer'] );
			$output .= sprintf( '<td class="score">%s</td></tr>', ( $answer['correct'] == 1 ? $img : '' ) );
		}
		$output .= '</table>';
		
		return $output;
	}
	
	function show_predictions_for_match( $match_info ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		
		$sql = "SELECT
					m.homeTeamId, m.awayTeamId, 
					p.homeScore, p.awayScore, p.hasJoker, u.ID AS userId, ";
		$sql .= ( $pool->has_leagues ? "l.id AS leagueId, " : "" );
		$sql .= "	u.display_name AS userName
				FROM {$prefix}matches m 
				LEFT OUTER JOIN {$prefix}predictions p 
					ON ( p.matchNr = m.nr AND m.nr = %d ) 
				RIGHT OUTER JOIN {$wpdb->users} u 
					ON ( u.ID = p.userId ) ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( u.ID = lu.userId )
					INNER JOIN {$prefix}leagues l ON ( l.id = lu.leagueId ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			$sql .= "WHERE ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		}
		$sql .= "ORDER BY u.display_name ASC";
		$sql = $wpdb->prepare( $sql, $match_info['nr'] );
		
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$output = '';
		if ( count( $rows ) > 0 ) {
			$output .= '<table class="matchinfo statistics">';
			$output .= sprintf( '<tr><th class="username">%s</th>
									<th colspan="4">%s</th><th>%s</th></tr>',
									__( 'name', FOOTBALLPOOL_TEXT_DOMAIN ),
									__( 'prediction', FOOTBALLPOOL_TEXT_DOMAIN ),
									__( 'score', FOOTBALLPOOL_TEXT_DOMAIN )
							);
			
			$userpage = Football_Pool::get_page_link( 'user' );
			foreach ( $rows as $row ) {
				$output .= sprintf( '<tr><td><a href="%s">%s</a></td>',
									esc_url( add_query_arg( array( 'user' => $row['userId'] ), $userpage ) ),
									$row['userName']
							);
				$output .= sprintf( '<td class="home">%s</td><td style="text-align: center;">-</td><td class="away">%s</td>',
									$row['homeScore'],
									$row['awayScore']
							);
				$output .= sprintf( '<td class="nopointer %s">&nbsp;</td>', 
									( $row['hasJoker'] == 1 ? 'joker' : 'nojoker' ) );
				$score = $pool->calc_score(
									$match_info['home_score'], 
									$match_info['away_score'], 
									$row['homeScore'], 
									$row['awayScore'], 
									$row['hasJoker']
								);
				$output .= sprintf( '<td class="score">%s&nbsp;</td></tr>', $score);
			}
			$output .= '</table>';
		}
		
		return $output;
	}
	
}
?>