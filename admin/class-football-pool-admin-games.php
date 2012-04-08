<?php
class Football_Pool_Admin_Games extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Wedstrijden', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		if ( Football_Pool_Utils::post_string( 'form_action' ) == 'update' ) {
			self::update();
			self::notice( 'Values updated.' );
		}
		
		self::intro( __( 'Bij het wijzigen van wedstrijduitslagen worden ook de totalen van spelers en de stand in de pool bijgewerkt. Bij veel deelnemers kan dit enige tijd in beslag nemen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$matches = new Matches();
		$rows = $matches->get_info();
		
		submit_button();
		self::print_matches( $rows );
		self::hidden_input( 'action', 'update' );
		submit_button();
		
		self::admin_footer();
	}
	
	private function update() {
		$matches = new Matches;
		$rows = $matches->get_info();
		
		// update scores for all matches
		foreach( $rows as $row ) {
			$match = $row['nr'];
			$homescore = Football_Pool_Utils::post_integer( '_home_score_'.$match, 'NULL' );
			$awayscore = Football_Pool_Utils::post_integer( '_away_score_'.$match, 'NULL' );
			$hometeam = Football_Pool_Utils::post_integer( '_home_team_'.$match, -1 );
			$awayteam = Football_Pool_Utils::post_integer( '_away_team_'.$match, -1 );
			
			self::update_match( $match, $hometeam, $awayteam, $homescore, $awayscore );
		}
		
		// scorehistory table for statistics
		self::update_score_history();
	}
	
	private function print_matches( $rows ) {
		$datetitle = '';
		$matchtype = '';
		
		echo '<table id="matchinfo" class="widefat matchinfo">';
		foreach( $rows as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				echo '<tr><td class="sidebar-name" colspan="7"><h3>', $matchtype, '</h3></td></tr>';
			}
			
			if ( $datetitle != date( 'd M Y', $row['matchTimestamp'] ) ) {
				$datetitle = date( 'd M Y', $row['matchTimestamp'] );
				echo '<tr><td class="sidebar-name" colspan="7">', $datetitle, '</td></tr>';
			}
			
			echo '<tr>',
					'<td class="time">', $row['nr'], '</td>',
					'<td class="time">', date( 'H:i', $row['matchTimestamp'] ), '</td>',
					'<td class="home">', self::teamname_input( (integer) $row['homeTeamId'], (integer) $row['typeId'], '_home_team_'.$row['nr'] ), '</td>',
					'<td class="score">', self::show_input( '_home_score_' . $row['nr'], $row['homeScore'] ), '</td>',
					'<td>-</td>',
					'<td class="score">', self::show_input( '_away_score_' . $row['nr'], $row['awayScore'] ), '</td>',
					'<td class="away">', self::teamname_input( (integer) $row['awayTeamId'], (integer) $row['typeId'], '_away_team_' . $row['nr'] ), '</td>',
					'</tr>';
		}
		echo '</table>';
	}
	
	private function show_input( $name, $value ) {
		return '<input type="text" name="' . $name . '" value="' . $value . '" maxlength="2" class="score" />';
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
	
	private function update_match( $match, $home, $away, $homescore, $awayscore ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		if ( ! is_integer( $homescore ) || ! is_integer( $awayscore ) ) {
			$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
										homeTeamId = %d, 
										awayTeamId = %d, 
										homeScore = NULL, 
										awayScore = NULL 
									WHERE nr = %d",
								$home, $away, $match
							);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
										homeTeamId = %d, 
										awayTeamId = %d, 
										homeScore = %d, 
										awayScore = %d 
									WHERE nr = %d",
								$home, $away, $homescore, $awayscore, $match
							);
		}
		$wpdb->query( $sql );
	}

}
?>