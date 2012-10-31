<?php
class Football_Pool_Groups {
	public function add( $name ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "INSERT INTO {$prefix}groups ( name )
								VALUES ( %s )",
								$name
							);
		$wpdb->query( $sql );
		return $wpdb->insert_id;
	}
	
	public function update( $id, $name ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $id == 0 ) {
			$id = $this->add( $name );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}groups SET
										name = %s
									WHERE id = %d",
									$name, $id
								);
			$wpdb->query( $sql );
		}
		
		return $id;
	}
	
	public function get_groups() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT id, name FROM {$prefix}groups ORDER BY name ASC";
		return $wpdb->get_results( $sql );
	}
	
	public function get_group_by_id( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name FROM {$prefix}groups WHERE id = %d", $id );
		return $wpdb->get_row( $sql );
	}
	
	public function get_group_by_name( $name, $addnew = '' ) {
		if ( $name == '' ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name FROM {$prefix}groups WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$id = self::add( $name );
			$result = (object) array( 
									'id'          => $id, 
									'name'        => $name,
									'inserted'    => true
									);
		}
		
		return $result;
	}
	
	public function get_group_names() {
		//@todo: remove auto translation of group name??
		$group_names = array();
		
		$rows = $this->get_group_composition();
		foreach ( $rows as $row ) {
			$group_names[ (integer) $row['id'] ] = htmlentities( __( $row['name'], FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		return $group_names;
	}
	
	public function get_ranking_array() {
		$ranking = $this->get_standings();
		$group_ranking = array();
		
		$rows = $this->get_group_composition();
		foreach ( $rows as $row ) {
			$group_ranking[ $row['id'] ][ (integer) $row['teamId'] ] = $this->get_standing_for_team( $ranking, $row['teamId'] );
		}
		
		return $this->order_groups( $group_ranking );
	}
	
	private function order_groups( $arr ) {
		foreach ( $arr as $group => $teams ) {
			uasort( $arr[$group], array( 'Football_Pool_Groups', 'compare_teams' ) );
		}
		return $arr;
	}
	

	/* alternative way to get all the scores: combine results of these 2 queries
	
		select 
		  homeTeamId,  
		  count(*) as games, 
		  sum(homeScore) as goals, 
		  sum(awayScore) as against, 
		  count(if(homeScore>awayScore,1,null)) as wins, 
		  count(if(homeScore=awayScore,1,null)) as draws, 
		  count(if(homeScore<awayScore,1,null)) as losses 
		from pool_matches 
		group by homeTeamId 
		
		select 
		  awayTeamId,  
		  count(*) as games, 
		  sum(awayScore) as goals, 
		  sum(homeScore) as against, 
		  count(if(homeScore<awayScore,1,null)) as wins, 
		  count(if(homeScore=awayScore,1,null)) as draws, 
		  count(if(homeScore>awayScore,1,null)) as losses 
		from pool_matches 
		group by awayTeamId
	*/
	private function get_standings()
	{
		$wins = array();
		$draws = array();
		$losses = array();
		$for = array();
		$against = array();
		
		$matches = new Football_Pool_Matches;
		$rows = $matches->get_info( 1 );
		
		foreach ( $rows as $row ) {
			if ( ( $row['homeScore'] != null ) && ( $row['awayScore'] != null ) ) {
				// set goals
				$this->set_goals_array( $for, $against, $row['homeTeamId'], $row['awayTeamId'], $row['homeScore'], $row['awayScore'] );
				// set wins, draws and losses
				if ( (integer) $row['homeScore'] > (integer) $row['awayScore'] ) {
					$wins   = $this->set_standing_array( $wins, $row['homeTeamId'] );
					$losses = $this->set_standing_array( $losses, $row['awayTeamId'] );
				} elseif ( (integer) $row['homeScore'] < (integer) $row['awayScore'] ) {
					$losses = $this->set_standing_array( $losses, $row['homeTeamId'] );
					$wins   = $this->set_standing_array( $wins, $row['awayTeamId'] );
				} elseif ( (integer) $row['homeScore'] == (integer) $row['awayScore'] ) {
					$draws = $this->set_standing_array( $draws, $row['homeTeamId'] );
					$draws = $this->set_standing_array( $draws, $row['awayTeamId'] );
				} else {
					echo 'what the fuck? this shouldn\'t happen: ', $row['homeTeamId'], '-', $row['awayTeamId'], '<br />';
				}
			}
		}
		return array( $wins, $draws, $losses, $for, $against );
	}
	
	private function get_standing_for_team( $ranking, $id ) {
		$wins =    ( isset( $ranking[0][$id] ) ? $ranking[0][$id] : 0 );
		$draws =   ( isset( $ranking[1][$id] ) ? $ranking[1][$id] : 0 );
		$losses =  ( isset( $ranking[2][$id] ) ? $ranking[2][$id] : 0 );
		$for =     ( isset( $ranking[3][$id] ) ? $ranking[3][$id] : 0 );
		$against = ( isset( $ranking[4][$id] ) ? $ranking[4][$id] : 0 );
		$points =  ( $wins * 3 ) + $draws;
		$plays =   $wins + $draws + $losses;
		return array(
					'team' => $id, 
					'plays' => $plays, 
					'wins' => $wins, 
					'draws' => $draws, 
					'losses' => $losses, 
					'points' => $points, 
					'for' => $for, 
					'against' => $against
					);
	}
	
	// only return games for the first round
	public function get_plays_for_group( $group ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT DISTINCT
									UNIX_TIMESTAMP(m.playDate) AS match_timestamp, 
									m.homeTeamId, 
									m.awayTeamId, 
									m.homeScore, 
									m.awayScore, 
									s.id AS stadiumId, 
									s.name AS stadiumName, 
									t.name AS matchtype, 
									m.nr,
									m.playDate
								FROM {$prefix}matches m, {$prefix}stadiums s, 
									{$prefix}matchtypes t, {$prefix}teams tm 
								WHERE m.stadiumId = s.id 
									AND m.matchtypeId = t.id 
									AND t.id = 1 -- only round 1
									AND ( m.homeTeamId = tm.id OR m.awayTeamId = tm.id )
									AND tm.groupId = %d
								ORDER BY m.playDate ASC, m.nr ASC",
							$group
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	private function compare_teams( $a, $b ) {
		// if points are equal
		if ( $a['points'] == $b['points'] ) {
			// check if they have the same number of plays
			if ( $a['plays'] == $b['plays'] ) {
				// if so, check if they played each other
				$matches = new Football_Pool_Matches;
				$gameresult = $matches->get_match_info_for_teams( $a['team'], $b['team'] );
				
				if ( is_array( $gameresult ) ) {
					// check the result
					if ( $gameresult[ $a['team'] ] != $gameresult[ $b['team'] ] ) {
						// reverse the ordering (descending order) 'cos the team that wins gets the advantage
						return ( $gameresult[ $a['team'] ] < $gameresult[ $b['team'] ] ) ? +1 : -1;
					}
				}
				
				// it was a draw or the teams didn't play each other, now check goal difference
				if ( ( $a['for'] - $a['against'] ) == ( $b['for'] - $b['against'] ) ) {
					// now check the goals scored
					if ( $a['for'] == $b['for'] ) {
						// all failed, so we check a hardcoded ordering
						$teams = new Football_Pool_Teams;
						return ( $teams->get_group_order( (integer) $a['team'] ) > $teams->get_group_order( (integer) $b['team'] ) ? +1 : -1 );
					}
					// the one with more goals wins (descending order)
					return ( $a['for'] < $b['for'] ) ? +1 : -1;
				}
				// the one with more goals wins (descending order)
				return ( ( $a['for'] - $a['against'] ) < ( $b['for'] - $b['against'] ) ? +1 : -1 );
			}
			// the one with the least plays has the advantage
			return ( $a['plays'] > $b['plays'] ) ? +1 : -1;
		}
		// order descending
		return ( $a['points'] < $b['points'] ) ? +1 : -1;
	}
	
	private function set_goals_array( &$for, &$against, $home_team, $away_team, $home_score, $away_score ) {
		$home_team = (integer) $home_team;
		$away_team = (integer) $away_team;
		
		$for[$home_team]     = $this->set_goals( $for, $home_team, $home_score );
		$for[$away_team]     = $this->set_goals( $for, $away_team, $away_score );
		$against[$home_team] = $this->set_goals( $against, $home_team, $away_score );
		$against[$away_team] = $this->set_goals( $against, $away_team, $home_score );
	}
	
	private function set_goals( $goals, $team, $score ) {
		if ( ! isset( $goals[$team] ) ) {
			return $score;
		} else {
			return $goals[$team] + $score;
		}
	}
	
	private function set_standing_array( $arr, $id ) {
		$id = (integer) $id;
		
		if ( isset( $arr[$id] ) && $arr[$id] != null ) {
			$arr[$id]++;
		} else {
			$arr[$id] = 1;
		}
		
		return $arr;
	}
	
	private function get_group_composition() {
		$cache_key = 'fp_get_group_composition';
		$rows = wp_cache_get( $cache_key );
		
		if ( $rows === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
//@todo: extra testing
			$sql = "SELECT t.id AS teamId, t.name AS teamName, g.id, g.name 
					FROM {$prefix}teams t, {$prefix}groups g 
					WHERE t.groupId = g.id AND t.is_real = 1 AND t.is_active = 1
					ORDER BY g.name ASC, t.groupOrder ASC, t.id ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $rows );
		}
		
		return $rows;
	}
	
	public function print_group_standing( $group_id, $layout = 'wide', $class = '' ) {
		if ( $class != '' ) $class = ' ' . $class;
		
		$output = '';
		$teams = new Football_Pool_Teams;
		$team_names = $teams->team_names;

		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();

		$ranking = $groups->get_ranking_array();

		if ( $layout == 'wide' ) {
			$wdl = '<th class="wins"><span title="wins">w</span></th><th class="draws">'
				 . '<span title="draws">d</span></th><th class="losses"><span title="losses">l</span></th>';
			$th1 = '';
			$th2 = '';
			$format = '<tr>
							<td class="team">%s</td>
							<td class="plays">%d</td>
							<td class="wins">%d</td>
							<td class="draws">%d</td>
							<td class="losses">%d</td>
							<td class="points">%d</td>
							<td class="goals">(%d-%d)</td>
						</tr>';
		} else {
			$wdl = '';
			$th1 = '<span title="matches">m</span>';
			$th2 = '<span title="points">p</span>';
			$format = '<tr>
							<td class="team">%s</td>
							<td class="plays">%d</td>
							<td class="points">%d</td>
							<td class="goals">(%d-%d)</td>
						</tr>';
		}
		
		$teampage = Football_Pool::get_page_link( 'teams' );
		
		foreach ( $ranking as $group => $rank ) {
			if ( $group_id == '' || $group_id == $group ) {
				$output .= sprintf( '<div class="ranking%s"><h2>%s</h2>', $class, $group_names[$group] );
				$output .= '<table class="ranking">
								<thead>
									<tr>
										<th class="team"></th>
										<th class="plays">' . $th1 . '</th>';
				$output .= $wdl;
				$output .= '			<th class="points">' . $th2 . '</th>
										<th class="goals"></th>
									</tr>
								</thead>
								<tbody>';
				foreach ( $rank as $teamranking ) {
					if ( $teams->show_team_links ) {
						$team_name = sprintf( '<a href="%s">%s</a>'
												, esc_url( 
														add_query_arg( 
															array( 'team' => $teamranking['team'] ), 
															$teampage 
														) 
													)
												, $team_names[$teamranking['team']]
											);
					} else {
						$team_name = $team_names[$teamranking['team']];
					}
					
					if ( $layout == 'wide' ) { 
						$args_array = array(
											$team_name,
											$teamranking['plays'],
											$teamranking['wins'],
											$teamranking['draws'],
											$teamranking['losses'],
											$teamranking['points'],
											$teamranking['for'],
											$teamranking['against']
										);
					} else {
						$args_array = array(
											$team_name,
											$teamranking['plays'],
											$teamranking['points'],
											$teamranking['for'],
											$teamranking['against']
										);
					}
					
					$output .= vsprintf( $format, $args_array );
				}
				$output .= '</tbody></table></div>';
			}
		}
		
		return $output;
	}
}
?>