<?php
class Football_Pool_Statistics {
	public $data_available = false;
	public $stats_visible = false;
	
	public function __construct() {
		$this->data_available = $this->check_data();
	}
	
	public function page_content() {
		$output = new Football_Pool_Statistics_Page();
		return $output->page_content();
	}
	
	private function check_data( $match = 0 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "
								SELECT COUNT(*) FROM {$prefix}scorehistory 
								WHERE (" . ( $match > 0 ? '' : '1=1 OR ' ) . "(type=0 AND scoreOrder=%d))", 
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
			$output = sprintf( '<p>%s</p>', __( 'Speler onbekend.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$this->stats_visible = false;
		}
		
		return $output;
	}
	
	public function show_match_info( $info ) {
		$output = '';
		$this->stats_visible = false;
		
		if ( count( $info ) > 0 ) {
			if ( $info['match_is_editable'] == true ) {
				$output .= sprintf('<h1>%s - %s</h1>', $info['teamHome'], $info['teamAway']);
				$output .= sprintf( '<p>%s</p>', __( 'Deze gegevens zijn nog niet beschikbaar. Nog even geduld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			} else {
				$output .= sprintf( '<h2>%s - %s', $info['teamHome'], $info['teamAway'] );
				if ( $info['matchHomeScore'] != '' && $info['matchAwayScore'] != '' ) {
					$output .= sprintf( ' (%d - %d)', $info['matchHomeScore'], $info['matchAwayScore'] );
				}
				$output .= '</h2>';
				$this->stats_visible = true;
			}
		} else {
			$output .= sprintf( '<p>%s</p>', __( 'Deze gegevens zijn nog niet beschikbaar. Nog even geduld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		return $output;
	}
	
	public function show_bonus_question_info( $question ) {
		$output = '';
		$pool = new Football_Pool_Pool;
		$info = $pool->get_bonus_question_info( $question );
		if ( $info ) {
			$output .= sprintf( '<h1>Bonusvraag</h1><h2>%s</h2>', $info['question'] );
			if ( $info['bonus_is_editable'] == true ) {
				$output .= sprintf( '<p>%s</p>', __( 'Deze gegevens zijn nog niet beschikbaar. Nog even geduld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$this->stats_visible = false;
			} else {
				$output .= sprintf( '<p>%s: %s<br/>%s: %d</p>',
									__( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['answer'],
									__( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ),
									$info['points']
								);
				$this->stats_visible = true;
			}
		} else {
			$output .= sprintf( '<p>%s</p>', __( 'Deze gegevens zijn nog niet beschikbaar. Nog even geduld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$this->stats_visible = false;
		}
		
		return $output;
	}
	
	public function show_answers_for_bonus_question( $id ) {
		$pool = new Football_Pool_Pool;
		$answers = $pool->get_bonus_question_answers_for_users( $id );
		
		$output = '<table class="statistics">
					<tr><th>' . __( 'speler', FOOTBALLPOOL_TEXT_DOMAIN ) . '</th><th>' . __( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ) . '</th><th class="correct">' . __( 'goed', FOOTBALLPOOL_TEXT_DOMAIN ) . '?</th></tr>';
		
		$img = '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/site/correct.jpg" 
					title="' . __( 'antwoord is goed', FOOTBALLPOOL_TEXT_DOMAIN ) . '" alt="' . __( 'goed', FOOTBALLPOOL_TEXT_DOMAIN ) . '" width="16" height="16" />';
		
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
					UNIX_TIMESTAMP(m.playDate) AS matchTimestamp, m.homeTeamId, m.awayTeamId, 
						p.homeScore, p.awayScore, p.hasJoker, u.id AS userId, "
			. ( $pool->has_leagues ? "l.id AS leagueId, " : "" ) 
			. "			u.name AS userName
					FROM {$prefix}matches m 
					LEFT OUTER JOIN {$prefix}predictions p 
						ON (p.matchNr = m.nr AND m.nr = %d) 
					RIGHT OUTER JOIN {$prefix}users u 
						ON (u.id = p.userId) ";
		if ( $pool->has_leagues ) {
			$sql .= "JOIN {$prefix}league_users lu 
						ON (u.id = lu.userId)
					JOIN {$prefix}leagues l 
						ON (l.id = lu.leagueId) ";
		}
		$sql .= "ORDER BY u.name ASC";
		$sql = $wpdb->prepare( $sql, $match_info['nr'] );
		
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$output = '';
		if ( count( $rows ) > 0 ) {
			$output .= '<table class="matchinfo statistics">';
			$output .= sprintf( '<tr><th class="username">%s</th>
									<th colspan="4">%s</th><th>%s</th></tr>',
									__( 'naam', FOOTBALLPOOL_TEXT_DOMAIN ),
									__( 'voorspelling', FOOTBALLPOOL_TEXT_DOMAIN ),
									__( 'score', FOOTBALLPOOL_TEXT_DOMAIN )
							);
			
			$userpage = Football_Pool::get_page_link( 'user' );
			foreach ( $rows as $row ) {
				$output .= sprintf( '<tr><td><a href="%s?user=%d">%s</a></td>',
									$userpage,
									$row['userId'],
									$row['userName']
							);
				$output .= sprintf( '<td class="home">%d</td><td style="text-align: center;">-</td><td class="away">%d</td>',
									$row['homeScore'],
									$row['awayScore']
							);
				$output .= sprintf( '<td class="nopointer %s">&nbsp;</td>', 
									( $row['hasJoker'] == 1 ? 'joker' : 'nojoker' ) );
				$score = $pool->calc_score(
									$match_info['matchHomeScore'], 
									$match_info['matchAwayScore'], 
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
	
	public function getNumScores() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT COUNT(*) FROM {$prefix}scorehistory GROUP BY userId LIMIT 1";
		return (integer) $wpdb->get_var( $sql );
	}
}
?>