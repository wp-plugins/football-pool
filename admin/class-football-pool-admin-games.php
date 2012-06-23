<?php
class Football_Pool_Admin_Games extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		if ( Football_Pool_Utils::post_string( 'form_action' ) == 'update' ) {
			$success = self::update();
			if ( $success )
				self::notice( 'Values updated.' );
			else
				self::notice( __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
		}
		
		self::intro( __( 'After saving the match data the pool ranking is recalculated. If you have a lot of users this may take a while.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$matches = new Football_Pool_Matches();
		$rows = $matches->get_info();
		
		submit_button();
		self::print_matches( $rows );
		self::hidden_input( 'action', 'update' );
		submit_button();
		
		self::admin_footer();
	}
	
	private function update() {
		$matches = new Football_Pool_Matches;
		$rows = $matches->get_info();
		
		// update scores for all matches
		foreach( $rows as $row ) {
			$match = $row['nr'];
			$homescore = Football_Pool_Utils::post_integer( '_home_score_' . $match, 'NULL' );
			$awayscore = Football_Pool_Utils::post_integer( '_away_score_' . $match, 'NULL' );
			$hometeam = Football_Pool_Utils::post_integer( '_home_team_' . $match, -1 );
			$awayteam = Football_Pool_Utils::post_integer( '_away_team_' . $match, -1 );
			$matchdate = Football_Pool_Utils::post_string( '_match_date' . $match, '0000-00-00 00:00' );
			
			self::update_match( $match, $hometeam, $awayteam, $homescore, $awayscore, $matchdate );
		}
		
		// scorehistory table for statistics
		return self::update_score_history();
	}
	
	private function print_matches( $rows ) {
		$datetitle = '';
		$matchtype = '';
		
		echo '<table id="matchinfo" class="widefat matchinfo">';
		foreach( $rows as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				echo '<tr><td class="sidebar-name" colspan="8"><h3>', __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ), '</h3></td></tr>';
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $datetitle != $matchdate->format( 'd M Y' ) ) {
				$datetitle = $matchdate->format( 'd M Y' );
				echo '<tr><td class="sidebar-name" colspan="8">', $datetitle, '</td></tr>';
			}
			
			echo '<tr>',
					'<td class="time">', $row['nr'], '</td>',
					'<td class="time">', $matchdate->format( 'H:i' ), '</td>',
					'<td class="home">', self::teamname_input( (integer) $row['homeTeamId'], (integer) $row['typeId'], '_home_team_'.$row['nr'] ), '</td>',
					'<td class="score">', self::show_input( '_home_score_' . $row['nr'], $row['homeScore'] ), '</td>',
					'<td>-</td>',
					'<td class="score">', self::show_input( '_away_score_' . $row['nr'], $row['awayScore'] ), '</td>',
					'<td class="away">', self::teamname_input( (integer) $row['awayTeamId'], (integer) $row['typeId'], '_away_team_' . $row['nr'] ), '</td>',
					'<td title="', __( 'change match time', FOOTBALLPOOL_TEXT_DOMAIN ), '">', self::show_input( '_match_date' . $row['nr'], $matchdate->format( 'Y-m-d H:i' ), 16, '' ), '</td>',
					'</tr>';
		}
		echo '</table>';
	}
	
	private function show_input( $name, $value, $max_length = 2, $class = 'score' ) {
		return sprintf( '<input type="text" name="%s" value="%s" maxlength="%s" class="%s" />', 
						$name, $value, $max_length, $class );
	}
	
	private function teamname_input( $team, $type, $input_name ) {
		if ( ! is_integer( $team ) ) return '';
		
		if ( $type > 1 ) {
			return self::team_select( $team, $input_name );
		} else {
			$teams = new Football_Pool_Teams;
			return $teams->team_names[$team] . '<input type="hidden" name="' . $input_name . '" id="' . $input_name . '" value="' . $team . '" />';
		}
	}
	
	private function team_select( $team, $input_name ) {
		$teams = new Football_Pool_Teams;
		
		$select = '<select name="' . $input_name . '" id="' . $input_name . '">';
		foreach ( $teams->team_names as $id => $name ) {
			$select .= '<option value="' . $id . '"' . ( $team == $id ? ' selected="selected"' : '' ) . '>' . $name . '</option>';
		}
		$select .= '</select>';
		return $select;
	}
	
	private function update_match( $match, $home, $away, $homescore, $awayscore, $matchdate ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( ! is_integer( $homescore ) || ! is_integer( $awayscore ) ) {
			$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
										homeTeamId = %d, 
										awayTeamId = %d, 
										homeScore = NULL, 
										awayScore = NULL,
										playDate = %s 
									WHERE nr = %d",
								$home, $away, $matchdate, $match
							);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
										homeTeamId = %d, 
										awayTeamId = %d, 
										homeScore = %d, 
										awayScore = %d, 
										playDate = %s 
									WHERE nr = %d",
								$home, $away, $homescore, $awayscore, $matchdate, $match
							);
		}
		$wpdb->query( $sql );
	}

}
?>