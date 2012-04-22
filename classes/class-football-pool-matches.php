<?php
class Matches {
	private $joker_blocked;
	private $teams;
	private $matches_are_editable;
	public $joker_value;
	
	public function __construct() {
		$this->joker_blocked = false;
		$this->enable_edits();
	}
	
	public function disable_edits() {
		$this->matches_are_editable = false;
		$this->joker_blocked = true;
	}
	
	private function enable_edits() {
		$this->matches_are_editable = true;
	}
	
	public function get_next_match( $ts = -1 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $ts == -1 ) $ts = time();
		
		$sql = "SELECT nr, homeTeamId, awayTeamId, UNIX_TIMESTAMP( playDate ) AS ts
				FROM {$prefix}matches
				WHERE UNIX_TIMESTAMP( playDate ) > {$ts}
				ORDER BY playDate ASC
				LIMIT 1";
		$match = $wpdb->get_row( $sql, ARRAY_A );
		return $match; // null if no match is found
	}
	
	public function get_last_matches ( $num_games ) {
		return $this->get_last_games( $num_games );
	}
	public function get_last_games( $num_games = 4 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT nr, homeTeamId, awayTeamId, homeScore, awayScore 
								FROM {$prefix}matches 
								WHERE playDate <= now() AND homeScore IS NOT NULL AND awayScore IS NOT NULL 
								ORDER BY playDate DESC, nr DESC 
								LIMIT %d", $num_games
					);
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	private function matches_query( $where_clause = '' ) {
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		return "SELECT UNIX_TIMESTAMP(m.playDate) AS matchTimestamp, m.homeTeamId, m.awayTeamId, 
							m.homeScore, m.awayScore, m.playDate,
							s.name AS stadiumName, t.name AS matchtype, t.id AS typeId, m.nr 
				FROM {$prefix}matches m, {$prefix}stadiums s, {$prefix}matchtypes t 
				WHERE m.stadiumId = s.id AND m.matchtypeId = t.id {$where_clause}
				ORDER BY m.playDate ASC, nr ASC";
	}
	
	public function get_first_match_info() {
		global $wpdb;
		$sql = $this->matches_query();
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function get_info( $type = -1 ) {
		global $wpdb;
		if ( $type != -1 ) {
			$sql = $wpdb->prepare( $this->matches_query( ' AND t.id = %d ' ), $type );
		} else {
			$sql = $this->matches_query();
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function get_match_info( $match ) {
		global $wpdb;
		$info = array();
		
		$teams = new Football_Pool_Teams;
		
		if ( ! is_integer( $match ) ) return $info;
		
		$sql = $wpdb->prepare( $this->matches_query( 'AND m.nr = %d' ), $match );
		$row = $wpdb->get_row( $sql, ARRAY_A );
		if ( $row ) {
			$info['matchDateTime'] = date( 'd M Y  H:i', $row['matchTimestamp'] );
			$info['matchHomeScore'] = $row['homeScore'];
			$info['matchAwayScore'] = $row['awayScore'];
			$info['teamHome'] = $teams->team_names[(integer) $row['homeTeamId'] ];
			$info['teamAway'] = $teams->team_names[(integer) $row['awayTeamId'] ];
			$info['matchTimestamp'] = $row['matchTimestamp'];
			$info['match_is_editable'] = $this->match_is_editable( $row['matchTimestamp'] );
			$info['nr'] = $row['nr'];
		}
		
		return $info;
	}
	
	public function get_match_info_for_user( $user ) {
		if ( ! is_integer( $user ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "
								SELECT 
									UNIX_TIMESTAMP(m.playDate) AS matchTimestamp, 
									m.homeTeamId, 
									m.awayTeamId, 
									p.homeScore, 
									p.awayScore, 
									p.hasJoker, 
									t.name AS matchtype, 
									m.nr,
									m.playDate
								FROM {$prefix}matches m 
								JOIN {$prefix}matchtypes t 
									ON (m.matchtypeId = t.id)
								LEFT OUTER JOIN {$prefix}predictions p 
									ON (p.matchNr = m.nr AND p.userId = %d) 
								ORDER BY m.playDate ASC, nr ASC",
								$user
							);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function first_empty_match_for_user( $user ) {
		if ( ! is_integer( $user ) ) return 0;

		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "
								SELECT m.nr FROM {$prefix}matches m 
								LEFT OUTER JOIN {$prefix}predictions p 
									ON (p.matchNr = m.nr AND p.userId = %d)
								WHERE p.userId IS NULL
								ORDER BY m.playDate ASC, nr ASC LIMIT 1",
								$user
							);
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? $row['nr'] : 0;
	}
	
	public function get_match_info_for_teams( $a, $b ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "
								SELECT homeTeamId, awayTeamId, homeScore, awayScore 
								FROM {$prefix}matches 
								WHERE (homeTeamId = %d AND awayTeamId = %d) 
									OR (homeTeamId = %d AND awayTeamId = %d)",
								$a, $b,
								$b, $a
							);
		
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		if ( $row ) {
			return array(
						$row['homeTeamId'] => $row['homeScore'], 
						$row['awayTeamId'] => $row['awayScore']
					);
		} else {
			return 0;
		}
	}
	
	/*
	Shows pool input for games that are still editable. For games where the edit period has expired only the value is
	shown.
	The property matches_are_editable is used for the users page. It prevents the display of inputs and it prevents the 
	display of games that are still editable. The latter to make sure users do not copy results from each other.
	*/
	public function show_pool_input( $name, $value, $ts ) {
		if ( $this->match_is_editable( $ts ) ) {
			if ( $this->matches_are_editable ) {
				return '<input type="number" name="' . $name . '" value="' . $value . '" maxlength="2" class="prediction" />';
			} else {
				return '';
			}
		} else {
			return $value;
		}
	}
	
	public function print_matches( $matches ) {
		$matchtype = '';
		$date_title = '';
		
		$teams = new Football_Pool_Teams;
		$teamspage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$output = '<table class="matchinfo">';
		foreach ( $matches as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= sprintf( '<tr><td class="matchtype" colspan="6">%s</td></tr>', $matchtype );
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $date_title != $matchdate->format( 'd M Y' ) ) {
				$date_title = $matchdate->format( 'd M Y' );
				$output .= sprintf( '<tr><td class="matchdate" colspan="6" title="%s">%s</td></tr>',
									date( 'l', $row['matchTimestamp'] ), $date_title );
			}
			
			$output .= sprintf( '<tr title="wedstrijd %s">
								<td class="time">%s</td>
								<td class="home"><a href="%s?team=%d">%s</a></td>
								<td class="flag">%s</td>',
							$row['nr'],
							$matchdate->format( 'H:i' ),
							$teamspage,
							$row['homeTeamId'],
							$teams->team_names[ (integer) $row['homeTeamId'] ],
							$teams->flag_image( (integer) $row['homeTeamId'] )
						);
			$output .= sprintf( '<td class="score">
								<a href="%s?view=matchpredictions&match=%d">%s - %s</a></td>',
							$statisticspage,
							$row['nr'],
							$row['homeScore'],
							$row['awayScore']
						);
			$output .= sprintf( '<td class="flag">%s</td>
								<td class="away"><a href="%s?team=%d">%s</a></td>
								</tr>',
							$teams->flag_image( (integer) $row['awayTeamId'] ),
							$teamspage,
							$row['awayTeamId'],
							$teams->team_names[ (integer) $row['awayTeamId'] ]
						);
		}
		$output .= '</table>';
		
		return $output;
	}
	
	public function print_matches_for_input( $matches ) {
		$teams = new Football_Pool_Teams;
		$date_title = '';
		$matchtype = '';
		$joker = '';
		
		$output = '<table id="matchinfo" class="matchinfo input" border="1">';
		foreach ( $matches as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				$output .= sprintf( '<tr><td class="matchtype" colspan="11">%s</td></tr>', $matchtype );
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $date_title != $matchdate->format( 'd M Y' ) ) {
				$date_title = $matchdate->format( 'd M Y' );
				$output .= sprintf( '<tr><td class="matchdate" colspan="11">%s</td></tr>', $date_title );
			}
			
			if ( (integer) $row['hasJoker'] == 1 ) {
				$joker = (integer) $row['nr'];
			}
			
			$info = $this->get_match_info( (integer) $row['nr'] );
			
			$output .= sprintf( '<tr id="match-%d">
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
							$row['nr'],
							$matchdate->format( 'H:i' ),
							$teams->team_names[ (integer) $row['homeTeamId'] ],
							$teams->flag_image( (integer) $row['homeTeamId'] ),
							$this->show_pool_input( '_home_' . $row['nr'], $row['homeScore'], $row['matchTimestamp'] ),
							$this->show_pool_input( '_away_' . $row['nr'], $row['awayScore'], $row['matchTimestamp'] ),
							$teams->flag_image( (integer) $row['awayTeamId'] ),
							$teams->team_names[ (integer) $row['awayTeamId'] ],
							$this->show_pool_joker( $joker, (integer) $row['nr'], $row['matchTimestamp'] ),
							__( 'score', FOOTBALLPOOL_TEXT_DOMAIN ),
							$this->show_score( $info['matchHomeScore'], $info['matchAwayScore'], $row['homeScore'], $row['awayScore'], $row['hasJoker'], $row['matchTimestamp'] ),
							$this->show_users_link( $row['nr'], $row['matchTimestamp'] )
						);
		}
		$output .= '</table>';
		
		$this->joker_value = $joker;
		return $output;
	}
	
	private function show_score( $home, $away, $user_home, $user_away, $joker, $ts ) {
		if ( ! $this->match_is_editable( $ts ) ) {
			$pool = new Football_Pool_Pool;
			return $pool->calc_score( $home, $away, $user_home, $user_away, $joker );
		}
	}
	
	private function show_users_link( $match, $ts ) {
		$output = '';
		if ( ! $this->match_is_editable( $ts ) ) {
			$title = __( 'bekijk de voorspellingen van andere spelers', FOOTBALLPOOL_TEXT_DOMAIN );
			$output .= sprintf( '<a href="%s?view=matchpredictions&amp;match=%d" title="%s">',
							Football_Pool::get_page_link( 'statistics' ),
							$match,
							$title
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
		$diff = $ts - time();
		return ( $diff > Football_Pool_Utils::get_wp_option( 'footballpool_maxperiod', FOOTBALLPOOL_MAXPERIOD, 'int' ) );
	}
	
	private function block_joker() {
		$this->joker_blocked = true;
	}
	
	private function show_pool_joker( $joker, $match, $ts ) {
		$add_joker = '';
		$style = '';
		
		$class = ( $joker == $match && $joker > 0 && $match > 0 ) ? 'joker' : 'nojoker';
		/*
		Make sure joker is not shown for matches that are editable in case 
		the matches_are_editable property is set to false.
		*/
		if ( ! $this->matches_are_editable && $this->match_is_editable( $ts ) ) {
			$class = 'nojoker';
		}
		
		if ( $class == 'joker' && ! $this->match_is_editable( $ts ) ) {
			$this->block_joker();
		}
		
		if ( ! $this->joker_blocked ) {
			if ( $this->match_is_editable( $ts ) ) {
				$add_joker = ' onclick="change_joker( this.id )" title="' . __( 'joker inzetten?', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
			}
		} else {
			//$style = ' style="cursor: text!important;"';
		}
		return '<td class="' . $class . '"' . $add_joker . ' id="match_' . $match . '"></td>';
	}

}
?>