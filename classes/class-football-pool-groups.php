<?php
class Football_Pool_Groups {
	public function add( $name ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "INSERT INTO {$prefix}groups ( name )
								VALUES ( %s )",
								$name
							);
		do_action( 'footballpool_groups_before_add', $name );
		$wpdb->query( $sql );
		do_action( 'footballpool_groups_after_add', $name );
		return $wpdb->insert_id;
	}
	
	public function update( $id, $name ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $id == 0 ) {
			$id = self::add( $name );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}groups SET name = %s WHERE id = %d", $name, $id );
			do_action( 'footballpool_groups_before_update', $id, $name );
			$wpdb->query( $sql );
			do_action( 'footballpool_groups_after_update', $id, $name );
		}
		
		return $id;
	}
	
	public function get_groups() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT id, name FROM {$prefix}groups ORDER BY name ASC";
		return apply_filters( 'footballpool_get_groups', $wpdb->get_results( $sql ) );
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
		$group_names = array();
		
		$rows = $this->get_group_composition();
		foreach ( $rows as $row ) {
			$group_names[(integer) $row['id']] = htmlentities( $row['name'], null, 'UTF-8' );
		}
		
		return $group_names;
	}
	
	public function get_ranking_array() {
		$ranking = $this->get_standings();
		$group_ranking = array();
		
		$rows = $this->get_group_composition();
		foreach ( $rows as $row ) {
			$group_ranking[$row['id']][(integer) $row['team_id']] = 
								$this->get_standing_for_team( $ranking, $row['team_id'] );
		}
				
		return $this->order_groups( $group_ranking );
	}
	
	private function order_groups( $arr ) {
		foreach ( $arr as $group => $teams ) {
			// ugly way to hide the "uasort() array was modified by the user comparison function" warning
			@uasort( $arr[$group], array( $this, 'compare_teams' ) );
		}
		return $arr;
	}
	
	private function compare_teams( $a, $b ) {
		// if points are equal
		if ( $a['points'] == $b['points'] ) {
			// check if they have the same number of plays
			if ( $a['plays'] == $b['plays'] ) {
				// if so, check if they played each other
				$matches = new Football_Pool_Matches;
				$match_result = $matches->get_match_info_for_teams( $a['team'], $b['team'] );
				
				if ( is_array( $match_result ) ) {
					// check the result
					if ( $match_result[ $a['team'] ] != $match_result[ $b['team'] ] ) {
						// reverse the ordering (descending order) 'cos the team that wins gets the advantage
						return ( $match_result[ $a['team'] ] < $match_result[ $b['team'] ] ) ? +1 : -1;
					}
				}
				
				// it was a draw or the teams didn't play each other, now check goal difference
				if ( ( $a['for'] - $a['against'] ) == ( $b['for'] - $b['against'] ) ) {
					// now check the goals scored
					if ( $a['for'] == $b['for'] ) {
						// all failed, so we check a hardcoded ordering
						$teams = new Football_Pool_Teams;
						return ( $teams->get_group_order( $a['team'] ) > $teams->get_group_order( $b['team'] ) ? +1 : -1 );
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
	
	private function get_standings() {
		$wins = $draws = $losses = $for = $against = array();
		
		$matches = new Football_Pool_Matches;
		$match_types = Football_Pool_Utils::get_fp_option( 
													'groups_page_match_types' 
													, array( FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE ) 
												);
		$rows = $matches->get_info( $match_types );
		
		foreach ( $rows as $row ) {
			if ( ( $row['home_score'] != null ) && ( $row['away_score'] != null ) ) {
				// set goals
				$this->set_goals_array( 
								$for, $against, 
								$row['home_team_id'], $row['away_team_id'], 
								$row['home_score'], $row['away_score'] 
						);
				// set wins, draws and losses
				if ( (int) $row['home_score'] > (int) $row['away_score'] ) {
					$wins   = $this->set_standing_array( $wins, $row['home_team_id'] );
					$losses = $this->set_standing_array( $losses, $row['away_team_id'] );
				} elseif ( (int) $row['home_score'] < (int) $row['away_score'] ) {
					$losses = $this->set_standing_array( $losses, $row['home_team_id'] );
					$wins   = $this->set_standing_array( $wins, $row['away_team_id'] );
				} elseif ( (int) $row['home_score'] == (int) $row['away_score'] ) {
					$draws = $this->set_standing_array( $draws, $row['home_team_id'] );
					$draws = $this->set_standing_array( $draws, $row['away_team_id'] );
				} else {
					echo 'what the fuck? this shouldn\'t happen: ', $row['home_team_id'], '-', $row['away_team_id'], '<br />';
				}
			}
		}
		
		return array( $wins, $draws, $losses, $for, $against );
	}
	
	private function get_standing_for_team( $ranking, $id ) {
		$team_points_win  = Football_Pool_Utils::get_fp_option( 'team_points_win', FOOTBALLPOOL_TEAM_POINTS_WIN, 'int' );
		$team_points_draw = Football_Pool_Utils::get_fp_option( 'team_points_draw', FOOTBALLPOOL_TEAM_POINTS_DRAW, 'int' );
		
		$wins =    ( isset( $ranking[0][$id] ) ? $ranking[0][$id] : 0 );
		$draws =   ( isset( $ranking[1][$id] ) ? $ranking[1][$id] : 0 );
		$losses =  ( isset( $ranking[2][$id] ) ? $ranking[2][$id] : 0 );
		$for =     ( isset( $ranking[3][$id] ) ? $ranking[3][$id] : 0 );
		$against = ( isset( $ranking[4][$id] ) ? $ranking[4][$id] : 0 );
		$points =  ( $wins * $team_points_win ) + ( $draws * $team_points_draw );
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
	public function get_plays( $group_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$matches = new Football_Pool_Matches;
		$sorting = $matches->get_match_sorting_method();
		$match_types = Football_Pool_Utils::get_fp_option( 
													'groups_page_match_types' 
													, array( FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE ) 
												);
		if ( ! is_array( $match_types ) || count( $match_types ) == 0 ) {
			$match_types = array( FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE );
		}
		$match_types = implode( ',', $match_types );
		
		$sql = $wpdb->prepare( "SELECT DISTINCT m.id
								FROM {$prefix}matches m, {$prefix}matchtypes t, {$prefix}teams tm 
								WHERE m.matchtype_id = t.id AND t.id IN ( {$match_types} )
									AND ( m.home_team_id = tm.id OR m.away_team_id = tm.id )
									AND tm.group_id = %d
								ORDER BY {$sorting}"
								, $group_id
						);
		
		$match_ids = $wpdb->get_col( $sql );
		if ( ! is_array( $match_ids ) ) $match_ids = array();
		$match_ids = apply_filters( 'footballpool_group_plays', $match_ids, $group_id );
		
		$matches = $matches->matches;
		
		$plays = array();
		foreach( $matches as $match ) {
			if ( in_array( $match['id'], $match_ids, false ) ) $plays[] = $match;
		}
		
		return $plays;
	}

	private function set_goals_array( &$for, &$against, $home_team, $away_team, $home_score, $away_score ) {
		$home_team = (int) $home_team;
		$away_team = (int) $away_team;
		
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
		$id = (int) $id;
		
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
			$sql = "SELECT t.id AS team_id, t.name AS team_name, g.id, g.name 
					FROM {$prefix}teams t, {$prefix}groups g 
					WHERE t.group_id = g.id AND t.is_real = 1 AND t.is_active = 1
					ORDER BY g.name ASC, t.group_order ASC, t.id ASC";
			$rows = apply_filters( 'footballpool_group_composition', $wpdb->get_results( $sql, ARRAY_A ) );
			wp_cache_set( $cache_key, $rows );
		}
		
		return $rows;
	}
	
	public function print_group_standing( $group_id, $layout = 'wide', $class = '' ) {
		if ( $class != '' ) $class = ' ' . $class;
		
		$output = '';
		$teams = new Football_Pool_Teams;
		$team_names = $teams->team_names;
		
		$group_names = $this->get_group_names();
		$ranking = apply_filters( 'footballpool_group_standing_array', $this->get_ranking_array(), $group_id );

		if ( $layout == 'wide' ) {
			$wdl = sprintf( '<th class="wins"><span title="%s">%s</span></th>
							<th class="draws"><span title="%s">%s</span></th>
							<th class="losses"><span title="%s">%s</span></th>'
							, esc_attr( __( 'wins', FOOTBALLPOOL_TEXT_DOMAIN ) )
							// Translators: this is a short notation for 'wins'
							, _x( 'w', 'short for \'wins\'', FOOTBALLPOOL_TEXT_DOMAIN )
							, esc_attr( __( 'draws', FOOTBALLPOOL_TEXT_DOMAIN ) )
							// Translators: this is a short notation for 'draws'
							, _x( 'd', 'short for \'draws\'', FOOTBALLPOOL_TEXT_DOMAIN )
							, esc_attr( __( 'losses', FOOTBALLPOOL_TEXT_DOMAIN ) )
							// Translators: this is a short notation for 'losses'
							, _x( 'l', 'short for \'losses\'', FOOTBALLPOOL_TEXT_DOMAIN )
					);
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
			$th1 = sprintf( '<span title="%s">%s</span>'
							, esc_attr( __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) )
							// Translators: this is a short notation for 'matches'
							, _x( 'm', 'short for \'matches\'', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$th2 = sprintf( '<span title="%s">%s</span>'
							, esc_attr( __( 'points', FOOTBALLPOOL_TEXT_DOMAIN ) )
							// Translators: this is a short notation for 'points'
							, _x( 'p', 'short for \'points\'', FOOTBALLPOOL_TEXT_DOMAIN )
					);
			$format = '<tr>
							<td class="team">%s</td>
							<td class="plays">%d</td>
							<td class="points">%d</td>
							<td class="goals">(%d-%d)</td>
						</tr>';
		}
		
		foreach ( $ranking as $group => $rank ) {
			if ( $group_id == '' || $group_id == $group ) {
				$output .= sprintf( '<div class="ranking%s"><h2>%s</h2>', $class, $group_names[$group] );
				$output .= '<table class="ranking group-ranking">';
				$thead = sprintf( '<thead><tr><th class="team"></th><th class="plays">%s</th>', $th1 );
				$thead .= $wdl;
				$thead .= sprintf( '<th class="points">%s</th><th class="goals"></th></tr></thead>', $th2 );
				$output .= apply_filters( 'footballpool_group_standing_thead', $thead, $group_id, $layout );
				$output .= '<tbody>';
				foreach ( $rank as $teamranking ) {
					if ( $teams->show_team_links ) {
						$team_name = sprintf( '<a href="%s">%s</a>'
												, esc_url( 
														add_query_arg( 
															array( 'team' => $teamranking['team'] ), 
															$teams->page 
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
					
					$output .= apply_filters( 'footballpool_group_standing_row'
												, vsprintf( $format, $args_array ), $group_id, $layout );
				}
				$output .= '</tbody></table></div>';
			}
		}
		
		return $output;
	}
}
