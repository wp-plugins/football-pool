<?php
class Football_Pool_Admin_Games extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		self::intro( __( 'On this page you can quickly edit match scores and team names for final rounds (if applicable). If you wish to change all information about a match, then click the \'edit\' link.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::intro( __( 'After saving the match data the pool ranking is recalculated. If you have a lot of users this may take a while.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$action  = Football_Pool_Utils::request_string( 'action' );
		$item_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		
		switch ( $action ) {
			case 'schedule':
				self::view_schedules();
				break;
			case 'edit':
			case 'update':
			case 'update_single_match':
			case 'update_single_match_close':
				self::edit_handler( $item_id, $action );
				break;
			case 'delete':
				$success = self::delete( $item_id );
				if ( $success )
					self::notice( sprintf( __( 'Game %d deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), $item_id ) );
				else
					self::notice( __( 'Error performing the requested action.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
				$action = 'view';
			case 'view':
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function view_schedules() {
		echo '<h2>hier komt de import voor wedstrijddata</h2>';
	}
	
	private function view() {
		$matches = new Football_Pool_Matches();
		$rows = $matches->get_info();
		
		echo '<p class="submit">';
		submit_button( null, 'primary', 'submit', false );
		self::secondary_button( __( 'Change game schedule', FOOTBALLPOOL_TEXT_DOMAIN ), 'schedule', false );
		echo '</p>';
		self::print_matches( $rows );
		submit_button();
	}
	
	private function edit_handler( $item_id, $action ) {
		switch ( $action ) {
			case 'update':
				$success = self::update();
				break;
			case 'update_single_match':
			case 'update_single_match_close':
				$success = self::update_single_match( $item_id );
				if ( $item_id == 0 ) $item_id = $success;
				if ( $success ) $success = self::update_score_history();
				break;
			case 'edit':
				$success = self::edit( $item_id );
				break;
		}
		
		if ( $action != 'edit' ) {
			if ( $success ) {
				self::notice( __( 'Values updated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			} else {
				self::notice( __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
			}
			
			if ( $action == 'update_single_match' ) {
				self::edit_handler( $item_id, 'edit' );
			} else {
				self::view();
			}
		}
	}
	
	private function delete( $item_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// delete match, corresponding predictions and linked bonus questions
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}matches WHERE nr = %d", $item_id );
		$success = ( $wpdb->query( $sql ) !== false );
		if ( $success ) {
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}predictions WHERE matchNr = %d", $item_id );
			$success = ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions_type 
									WHERE question_id IN (
										SELECT id FROM {$prefix}bonusquestions WHERE matchNr = %d
									)"
								, $item_id );
			$success &= ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions_useranswers
									WHERE questionId IN (
										SELECT id FROM {$prefix}bonusquestions WHERE matchNr = %d
									)"
								, $item_id );
			$success &= ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions WHERE matchNr = %d", $item_id );
			$success &= ( $wpdb->query( $sql ) !== false );
			// update scorehistory
			$success &= self::update_score_history();
		}
		
		return $success;
	}
	
	private function edit( $item_id ) {
		$values = array(
						'date' => '',
						'home_team_id' => '',
						'away_team_id' => '',
						'home_score' => '',
						'away_score' => '',
						'stadium_id' => 0,
						'match_type_id' => 0
						);
		
		$matches = new Football_Pool_Matches;
		$match = isset( $matches->matches[$item_id] ) ? $matches->matches[$item_id] : false;
		if ( $match && $item_id > 0 ) {
			$values = $match;
		}
		
		$types = $matches->get_match_types();
		$options = array();
		foreach ( $types as $type ) {
			$options[] = array( 'value' => $type['id'], 'text' => $type['name'] );
		}
		$types = $options;
		
		$venues = Football_Pool_Stadiums::get_stadiums();
		$options = array();
		foreach ( $venues as $venue ) {
			$options[] = array( 'value' => $venue->id, 'text' => $venue->name );
		}
		$venues = $options;
		
		$teams = new Football_Pool_Teams;
		$teams = $teams->team_names;
		$options = array();
		foreach( $teams as $id => $name ) {
			$options[] = array( 'value' => $id, 'text' => $name );
		}
		$teams = $options;
		
		$matchdate = new DateTime( $values['date'] );
		$matchdate_local = self::date_from_gmt( $matchdate->format( 'Y-m-d H:i' ) );
		$cols = array(
					array( 'text', __( 'match date (UTC)', FOOTBALLPOOL_TEXT_DOMAIN ), 'match_date', $values['date'], sprintf( __( 'local time is %s', FOOTBALLPOOL_TEXT_DOMAIN ), $matchdate_local ) ),
					array( 'dropdown', __( 'home team', FOOTBALLPOOL_TEXT_DOMAIN ), 'home_team_id', $values['home_team_id'], $teams, '' ),
					array( 'dropdown', __( 'away team', FOOTBALLPOOL_TEXT_DOMAIN ), 'away_team_id', $values['away_team_id'], $teams, '' ),
					array( 'text', __( 'home score', FOOTBALLPOOL_TEXT_DOMAIN ), 'home_score', $values['home_score'], '' ),
					array( 'text', __( 'away score', FOOTBALLPOOL_TEXT_DOMAIN ), 'away_score', $values['away_score'], '' ),
					array( 'dropdown', __( 'stadium', FOOTBALLPOOL_TEXT_DOMAIN ), 'stadium_id', $values['stadium_id'], $venues, '' ),
					array( 'dropdown', __( 'match type', FOOTBALLPOOL_TEXT_DOMAIN ), 'match_type_id', $values['match_type_id'], $types, '' ),
					array( 'hidden', '', 'item_id', $item_id )
				);
		self::value_form( $cols );
		echo '<p class="submit">';
		self::primary_button( __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ), 'update_single_match_close' );
		self::secondary_button( __( 'Save' ), 'update_single_match' );
		self::cancel_button();
		echo '</p>';
	}
	
	private function update_single_match( $item_id ) {
		$home_score = Football_Pool_Utils::post_integer( 'home_score', 'NULL' );
		$away_score = Football_Pool_Utils::post_integer( 'away_score', 'NULL' );
		$home_team = Football_Pool_Utils::post_integer( 'home_team_id', -1 );
		$away_team = Football_Pool_Utils::post_integer( 'away_team_id', -1 );
		$match_date = Football_Pool_Utils::post_string( 'match_date', '0000-00-00 00:00' );
		$stadium_id = Football_Pool_Utils::post_integer( 'stadium_id', -1 );
		$match_type_id = Football_Pool_Utils::post_integer( 'match_type_id', -1 );
		
		$success = self::update_match( $item_id, $home_team, $away_team, $home_score, $away_score, 
										$match_date, $stadium_id, $match_type_id );
		
		return $success;
	}
	
	private function update() {
		$matches = new Football_Pool_Matches;
		$rows = $matches->get_info();
		
		// update scores for all matches
		foreach( $rows as $row ) {
			$match = $row['nr'];
			$home_score = Football_Pool_Utils::post_integer( '_home_score_' . $match, 'NULL' );
			$away_score = Football_Pool_Utils::post_integer( '_away_score_' . $match, 'NULL' );
			$home_team = Football_Pool_Utils::post_integer( '_home_team_' . $match, -1 );
			$away_team = Football_Pool_Utils::post_integer( '_away_team_' . $match, -1 );
			$match_date = Football_Pool_Utils::post_string( '_match_date' . $match, '0000-00-00 00:00' );
			
			$success = self::update_match( $match, $home_team, $away_team, 
											$home_score, $away_score, $match_date );
		}
		
		if ( $success ) $success &= self::update_score_history();
		
		return $success;
	}
	
	private function print_matches( $rows ) {
		$datetitle = '';
		$matchtype = '';
		
		echo '<table id="matchinfo" class="widefat matchinfo">';
		foreach( $rows as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				echo '<tr><td class="sidebar-name" colspan="11"><h3>', __( $matchtype, FOOTBALLPOOL_TEXT_DOMAIN ), '</h3></td></tr>';
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $datetitle != $matchdate->format( 'd M Y' ) ) {
				$datetitle = $matchdate->format( 'd M Y' );
				echo '<tr><td class="sidebar-name" colspan="7">', $datetitle, '</td>',
						'<td class="sidebar-name">', __( 'UTC', FOOTBALLPOOL_TEXT_DOMAIN ), '</td>',
						'<td class="sidebar-name">', __( 'local time', FOOTBALLPOOL_TEXT_DOMAIN ), '</td>',
						'<td class="sidebar-name" colspan="2"></td>',
						'</tr>';
			}
			
			$page = '?page=' . esc_attr( Football_Pool_Utils::get_string( 'page' ) ) . '&amp;item_id=' . $row['nr'];
			$confirm = sprintf( __( 'You are about to delete match %d.', FOOTBALLPOOL_TEXT_DOMAIN )
								, $row['nr'] 
							);
			$confirm .= ' ' . __( "Are you sure? `OK` to delete, `Cancel` to stop.", FOOTBALLPOOL_TEXT_DOMAIN );
			echo '<tr>',
					'<td class="time">', $row['nr'], '</td>',
					'<td class="time">', $matchdate->format( 'H:i' ), '</td>',
					'<td class="home">', self::teamname_input( (integer) $row['homeTeamId'], (integer) $row['typeId'], '_home_team_'.$row['nr'] ), '</td>',
					'<td class="score">', self::show_input( '_home_score_' . $row['nr'], $row['homeScore'] ), '</td>',
					'<td>-</td>',
					'<td class="score">', self::show_input( '_away_score_' . $row['nr'], $row['awayScore'] ), '</td>',
					'<td class="away">', self::teamname_input( (integer) $row['awayTeamId'], (integer) $row['typeId'], '_away_team_' . $row['nr'] ), '</td>',
					'<td title="', __( 'change match time', FOOTBALLPOOL_TEXT_DOMAIN ), '">', self::show_input( '_match_date' . $row['nr'], $matchdate->format( 'Y-m-d H:i' ), 16, '' ), '</td>',
					'<td class="time local">', self::date_from_gmt( $matchdate->format( 'Y-m-d H:i' ) ), '</td>',
					'<td><a href="', $page, '&amp;action=edit">', __( 'edit' ), '</a></td>',
					'<td><a onclick="return confirm( \'', $confirm, '\' )" href="', $page, '&amp;action=delete">', __( 'delete' ), '</a></td>',
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
	
	private function update_match( $nr, $home_team, $away_team, $home_score, $away_score, $match_date, $stadium_id = null, $match_type_id = null ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $nr == 0 ) {
			if ( ! is_integer( $home_score ) || ! is_integer( $away_score ) ) {
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}matches 
											( homeTeamId, awayTeamId, homeScore, awayScore, 
												playDate, stadiumId, matchtypeId )
										VALUES ( %d, %d, NULL, NULL, %s, %d, %d )"
									, $home_team, $away_team, $match_date, $stadium_id, $match_type_id
								);
			} else {
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}matches 
											( homeTeamId, awayTeamId, homeScore, awayScore, 
												playDate, stadiumId, matchtypeId )
										VALUES ( %d, %d, %d, %d, %s, %d, %d )"
									, $home_team, $away_team, $home_score, $away_score
									, $match_date, $stadium_id, $match_type_id
								);
			}
		} else {
			if ( ! is_integer( $stadium_id ) || ! is_integer( $match_type_id ) ) {
				$matches = new Football_Pool_Matches;
				$match = $matches->matches[ $nr ];
				$stadium_id = $match['stadium_id'];
				$match_type_id = $match['match_type_id'];
			}
			
			if ( ! is_integer( $home_score ) || ! is_integer( $away_score ) ) {
				$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
											homeTeamId = %d, awayTeamId = %d, 
											homeScore = NULL, awayScore = NULL,
											playDate = %s, stadiumId = %d, matchtypeId = %d
										WHERE nr = %d",
									$home_team, $away_team, $match_date, $stadium_id, $match_type_id, $nr
								);
			} else {
				$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
											homeTeamId = %d, awayTeamId = %d, 
											homeScore = %d, awayScore = %d, 
											playDate = %s, stadiumId = %d, matchtypeId = %d
										WHERE nr = %d",
									$home_team, $away_team, $home_score, $away_score, 
									$match_date, $stadium_id, $match_type_id, $nr
								);
			}
		}
		
		$success = ( $wpdb->query( $sql ) !== false );
		
		if ( $nr  > 0 ) {
			return $success;
		} else {
			return $success ? $wpdb->insert_id : 0;
		}
	}

}
?>