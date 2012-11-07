<?php
class Football_Pool_Matches {
	private $joker_blocked;
	private $teams;
	private $matches_are_editable;
	public $joker_value;
	private $force_lock_time = false;
	private $lock;
	public $matches;
	
	public function __construct() {
		$this->joker_blocked = false;
		$this->enable_edits();
		
		$datetime = Football_Pool_Utils::get_fp_option( 'matches_locktime', '' );
		$this->force_lock_time = 
			( Football_Pool_Utils::get_fp_option( 'stop_time_method_matches', 0, 'int' ) == 1 )
			&& ( $datetime != '' );
		if ( $this->force_lock_time ) {
			//$date = DateTime::createFromFormat( 'Y-m-d H:i', $datetime );
			$date = new DateTime( Football_Pool_Utils::date_from_gmt( $datetime ) );
			$this->lock = $date->getTimestamp();
		} else {
			$this->lock = Football_Pool_Utils::get_fp_option( 'maxperiod', FOOTBALLPOOL_MAXPERIOD, 'int' );
		}
		
		// cache match info
		$this->matches = $this->match_info();
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
		
		$next_match = null;
		foreach ( $this->matches as $match ) {
			if ( $match['match_timestamp'] > $ts ) {
				$next_match = $match;
				break;
			}
		}
		
		return $next_match; // null if no match is found
	}
	
	public function get_last_matches ( $num_games = 4 ) {
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
		return "SELECT 
					m.nr, 
					UNIX_TIMESTAMP(m.playDate) AS match_timestamp, m.playDate,
					m.homeTeamId, m.awayTeamId, 
					m.homeScore, m.awayScore, 
					s.name AS stadiumName, s.id AS stadiumId,
					t.name AS matchtype, t.id AS typeId 
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
	
	private function match_info() {
		$cache_key = 'fp_match_info';
		$match_info = wp_cache_get( $cache_key );
		
		if ( $match_info === false ) {
			global $wpdb;
			$match_info = array();
			$teams = new Football_Pool_Teams;
			
			$rows = $wpdb->get_results( $this->matches_query(), ARRAY_A );
			foreach ( $rows as $row ) {
				$i = $row['nr'];
				$matchdate = new DateTime( $row['playDate'] );
				$ts = $matchdate->format( 'U' );
				
				$match_info[$i] = array();
				$match_info[$i]['match_datetime'] = $matchdate->format( 'd M Y H:i' );
				// $match_info[$i]['match_timestamp'] = $row['match_timestamp'];
				$match_info[$i]['match_timestamp'] = $ts;
				$match_info[$i]['playDate'] = $row['playDate'];
				$match_info[$i]['date'] = $row['playDate'];
				$match_info[$i]['home_score'] = $row['homeScore'];
				$match_info[$i]['away_score'] = $row['awayScore'];
				$match_info[$i]['home_team'] = $teams->team_names[(integer) $row['homeTeamId'] ];
				$match_info[$i]['away_team'] = $teams->team_names[(integer) $row['awayTeamId'] ];
				$match_info[$i]['home_team_id'] = $row['homeTeamId'];
				$match_info[$i]['away_team_id'] = $row['awayTeamId'];
				$match_info[$i]['match_is_editable'] = $this->match_is_editable( $ts );
				$match_info[$i]['nr'] = $row['nr'];
				$match_info[$i]['stadium_id'] = $row['stadiumId'];
				$match_info[$i]['stadium_name'] = $row['stadiumName'];
				$match_info[$i]['match_type_id'] = $row['typeId'];
				$match_info[$i]['match_type'] = $row['matchtype'];
			}
			
			wp_cache_set( $cache_key, $match_info );
		}
		
		return $match_info;
	}
	
	public function get_match_info( $match ) {
		if ( is_integer( $match ) && array_key_exists( $match, $this->matches ) ) 
			return $this->matches[ $match ];
		else
			return array();
	}
	
	public function get_match_info_for_user( $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT 
									UNIX_TIMESTAMP(m.playDate) AS match_timestamp, 
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
		$sql = $wpdb->prepare( "SELECT m.nr FROM {$prefix}matches m 
								LEFT OUTER JOIN {$prefix}predictions p 
									ON ( p.matchNr = m.nr AND p.userId = %d )
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
		$sql = $wpdb->prepare( "SELECT homeTeamId, awayTeamId, homeScore, awayScore 
								FROM {$prefix}matches 
								WHERE ( homeTeamId = %d AND awayTeamId = %d ) 
									OR ( homeTeamId = %d AND awayTeamId = %d )",
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
	
	/**
	 * Shows pool input for games that are still editable. For games where the edit period has expired 
	 * only the value is shown.
	 * The property matches_are_editable is used for the users page. It prevents the display of inputs
	 * and it prevents the display of games that are still editable. The latter to make sure users do 
	 * not copy results from each other.
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
				$output .= sprintf( '<tr><td class="matchtype" colspan="6">%s</td></tr>', __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $date_title != $matchdate->format( 'd M Y' ) ) {
				$date_title = $matchdate->format( 'd M Y' );
				$output .= sprintf( '<tr><td class="matchdate" colspan="6" title="%s">%s</td></tr>',
									$matchdate->format( 'l' ), $date_title );
			}
			
			if ( $teams->show_team_links ) {
				$team_name = sprintf( '<a href="%s">%s</a>'
										, esc_url( add_query_arg( array( 'team' => $row['homeTeamId'] ), $teamspage ) )
										, $teams->team_names[ (int) $row['homeTeamId'] ]
								);
			} else {
				$team_name = $teams->team_names[ (int) $row['homeTeamId'] ];
			}
			$output .= sprintf( '<tr title="%s %s">
									<td class="time">%s</td>
									<td class="home">%s</td>
									<td class="flag">%s</td>',
							__( 'match', FOOTBALLPOOL_TEXT_DOMAIN ),
							$row['nr'],
							$this->format_match_time( $matchdate ),
							$team_name,
							$teams->flag_image( (integer) $row['homeTeamId'] )
						);
			$output .= sprintf( '<td class="score"><a href="%s">%s - %s</a></td>',
							esc_url( 
								add_query_arg( 
									array( 'view' => 'matchpredictions', 'match' => $row['nr'] ), 
									$statisticspage 
								)
							),
							$row['homeScore'],
							$row['awayScore']
						);
			if ( $teams->show_team_links ) {
				$team_name = sprintf( '<a href="%s">%s</a>'
										, esc_url( add_query_arg( array( 'team' => $row['awayTeamId'] ), $teamspage ) )
										, $teams->team_names[ (int) $row['awayTeamId'] ]
								);
			} else {
				$team_name = $teams->team_names[ (int) $row['awayTeamId'] ];
			}
			$output .= sprintf( '<td class="flag">%s</td>
								<td class="away">%s</td>
								</tr>',
							$teams->flag_image( (integer) $row['awayTeamId'] ),
							$team_name
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
				$output .= sprintf( '<tr><td class="matchtype" colspan="11">%s</td></tr>', __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ) );
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
							$info['nr'],
							$this->format_match_time( $matchdate ),
							$teams->team_names[ (integer) $info['home_team_id'] ],
							$teams->flag_image( (integer) $info['home_team_id'] ),
							$this->show_pool_input( '_home_' . $info['nr'], $info['home_score'], $info['match_timestamp'] ),
							$this->show_pool_input( '_away_' . $info['nr'], $info['away_score'], $info['match_timestamp'] ),
							$teams->flag_image( (integer) $info['away_team_id'] ),
							$teams->team_names[ (integer) $info['away_team_id'] ],
							$this->show_pool_joker( $joker, (integer) $info['nr'], $info['match_timestamp'] ),
							__( 'score', FOOTBALLPOOL_TEXT_DOMAIN ),
							$this->show_score( $info['home_score'], $info['away_score'], $row['homeScore'], $row['awayScore'], $row['hasJoker'], $info['match_timestamp'] ),
							$this->show_users_link( $info['nr'], $info['match_timestamp'] )
						);
		}
		$output .= '</table>';
		
		$this->joker_value = $joker;
		return $output;
	}
	
	private function format_match_time( $datetime ) {
		$display = Football_Pool_Utils::get_fp_option( 'match_time_display' );
		if ( $display == 0 ) { // WordPress setting
			$datetime = new DateTime( Football_Pool_Utils::date_from_gmt( $datetime->format( 'Y-m-d H:i' ) ) );
		} elseif ( $display == 2 ) { // custom setting
			$offset = 60 * 60 * (float)Football_Pool_Utils::get_fp_option( 'match_time_offset' );
			if ( $offset >= 0 ) $offset = '+' . $offset;
			$datetime->modify( $offset . ' seconds' );
		} // else UTC
		
		return $datetime->format( 'H:i' );
	}
	
	public function get_match_types() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name FROM {$prefix}matchtypes ORDER BY id ASC" );
		return $wpdb->get_results( $sql );
	}
	
	public function get_match_type_by_id( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name FROM {$prefix}matchtypes WHERE id = %d", $id );
		return $wpdb->get_row( $sql );
	}
	
	public function get_match_type_by_name( $name, $addnew = 'no' ) {
		if ( $name == '' ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name FROM {$prefix}matchtypes WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}matchtypes ( name ) VALUES ( %s )", $name );
			$wpdb->query( $sql );
			$id = $wpdb->insert_id;
			$result = (object) array( 
									'id' => $id, 
									'name' => $name, 
									'inserted' => true 
									);
		}
		
		return $result;
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
				$add_joker = ' onclick="footballpool_change_joker( this.id )" title="' . __( 'use your joker?', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
			}
		} else {
			//$style = ' style="cursor: text!important;"';
		}
		return '<td class="' . $class . '"' . $add_joker . ' id="match_' . $match . '"></td>';
	}

}
?>