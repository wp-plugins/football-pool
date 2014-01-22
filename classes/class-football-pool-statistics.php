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
		$single_match = ( $match > 0 ) ? '' : '--';
		$sql = $wpdb->prepare( sprintf( "SELECT COUNT(*) FROM {$prefix}scorehistory 
								WHERE ranking_id = %%d %s AND type = 0 AND source_id = %%d", $single_match )
								, $ranking_id, $match
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
		$matches = new Football_Pool_Matches;
		
		if ( count( $info ) > 0 ) {
			if ( $matches->always_show_predictions || $info['match_is_editable'] == false ) {
				$output .= sprintf( '<h2>%s - %s', $info['home_team'], $info['away_team'] );
				if ( is_integer( $info['home_score'] ) && is_integer( $info['away_score'] ) ) {
					$output .= sprintf( ' (%d - %d)', $info['home_score'], $info['away_score'] );
				}
				$output .= '</h2>';
				$this->stats_visible = true;
			} else {
				$output .= sprintf('<h2>%s - %s</h2>', $info['home_team'], $info['away_team']);
				$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
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
			if ( $pool->always_show_predictions || $info['question_is_editable'] == false ) {
				$output .= sprintf( '<p>%s: %s<br/>%s: %d</p>',
									__( 'answer', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['answer'],
									__( 'points', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['points']
								);
				$this->stats_visible = true;
			} else {
				$output .= sprintf( '<p>%s</p>', __( 'This data is not (yet) available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$this->stats_visible = false;
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
		
		$output = sprintf( '<table class="statistics prediction-table-questions">
							<tr><th>%s</th><th>%s</th><th class="correct">%s</th></tr>'
							, __( 'user', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'answer', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'correct?', FOOTBALLPOOL_TEXT_DOMAIN )
				);
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		foreach ( $answers as $answer ) {
			if ( $answer['correct'] == 1 ) {
				$class = 'correct fa fa-check-circle';
				$title = __( 'correct answer', FOOTBALLPOOL_TEXT_DOMAIN );
			} else {
				$class = 'wrong fa fa-times-circle';
				$title = __( 'wrong answer', FOOTBALLPOOL_TEXT_DOMAIN );
			}
			$output .= sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td>'
								, esc_url( add_query_arg( array( 'user' => $answer['user_id'] ), $userpage ) )
								, $answer['name']
								, $answer['answer'] 
						);
			$output .= sprintf( '<td class="score"><span class="score %s" title="%s"></span></td></tr>'
								, $class 
								, $title
						);
		}
		$output .= '</table>';
		
		return $output;
	}
	
	function show_predictions_for_match( $match_info ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		
		$sql = "SELECT
					m.home_team_id, m.away_team_id, 
					p.home_score, p.away_score, p.has_joker, u.ID AS user_id, ";
		$sql .= ( $pool->has_leagues ? "l.id AS league_id, " : "" );
		$sql .= "	u.display_name AS user_name
				FROM {$prefix}matches m 
				LEFT OUTER JOIN {$prefix}predictions p 
					ON ( p.match_id = m.id AND m.id = %d ) 
				RIGHT OUTER JOIN {$wpdb->users} u 
					ON ( u.ID = p.user_id ) ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( u.ID = lu.user_id )
					INNER JOIN {$prefix}leagues l ON ( l.id = lu.league_id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
			$sql .= "WHERE ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		}
		$sql .= "ORDER BY u.display_name ASC";
		$sql = $wpdb->prepare( $sql, $match_info['id'] );
		
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
									esc_url( add_query_arg( array( 'user' => $row['user_id'] ), $userpage ) ),
									$row['user_name']
							);
				$output .= sprintf( '<td class="home">%s</td><td style="text-align: center;">-</td><td class="away">%s</td>',
									$row['home_score'],
									$row['away_score']
							);
				$output .= sprintf( '<td class="nopointer %s">&nbsp;</td>', 
									( $row['has_joker'] == 1 ? 'fp-joker' : 'fp-nojoker' ) );
				$score = $pool->calc_score(
									$match_info['home_score'], 
									$match_info['away_score'], 
									$row['home_score'], 
									$row['away_score'], 
									$row['has_joker']
								);
				$output .= sprintf( '<td class="score">%s&nbsp;</td></tr>', $score);
			}
			$output .= '</table>';
		}
		
		return $output;
	}
	
}
