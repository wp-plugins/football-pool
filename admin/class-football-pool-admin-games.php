<?php
class Football_Pool_Admin_Games extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$action  = Football_Pool_Utils::request_string( 'action' );
		$item_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		
		self::admin_header( __( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		self::intro( __( 'On this page you can quickly edit match scores and team names for final rounds (if applicable). If you wish to change all information about a match, then click the \'edit\' link.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::intro( __( 'After saving the match data the pool ranking is recalculated. If you have a lot of users this may take a while.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				
		$log = '';
		$file = '';
		
		switch ( $action ) {
			case 'upload_csv':
				$uploaded_file = self::upload_csv();
				if ( Football_Pool_Utils::post_int( 'csv_import' ) == 1 ) {
					$file = $uploaded_file;
					$upload = true;
				}
			case 'import_csv':
			case 'import_csv_overwrite':
				$log = self::import_csv( $action, $file );
			case 'change-culture':
			case 'schedule':
				self::view_schedules( $log );
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
			case 'view':
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function upload_csv() {
		$err = false;
		if ( is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
			$new_file = FOOTBALLPOOL_CSV_UPLOAD_DIR . $_FILES['csv_file']['name'];
			if ( move_uploaded_file( $_FILES['csv_file']['tmp_name'], $new_file ) === false ) {
				$err = true;
			}
		} else {
			$err = true;
		}
		
		if ( $err ) {
			self::notice( __( 'Upload of csv file failed.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
			return '';
		} else {
			self::notice( __( 'Upload of csv file successful.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			return $_FILES['csv_file']['name'];
		}
	}
	
	private function import_csv( $action = 'import_csv', $file = '' ) {
		$msg = $err = array();
		
		if ( $action == 'upload_csv' && $file == '' ) return array( $err, $msg );
		
		if ( $file == '' ) {
			$file = Football_Pool_Utils::post_string( 'csv_file' );
		}
		
		if ( $file != '' && ( $fp = @fopen( FOOTBALLPOOL_CSV_UPLOAD_DIR . $file, 'r' ) ) !== false ) {
			if ( $action == 'import_csv_overwrite' ) {
				// remove all match data except matchtypes
				self::empty_table( 'scorehistory' );
				self::empty_table( 'predictions' );
				self::empty_table( 'stadiums' );
				self::empty_table( 'matches' );
				self::empty_table( 'teams' );
			}
			
			$header = fgetcsv( $fp, 1000, FOOTBALLPOOL_CSV_DELIMITER );
			// check the columns
			$full_data = ( count( $header ) != 5 ) ? true : false;
			if ( $full_data ) {
				$column_names = explode(
									FOOTBALLPOOL_CSV_DELIMITER,	'play_date;home_team;away_team;stadium;match_type;home_team_photo;home_team_flag;home_team_link;home_team_group;home_team_group_order;home_team_is_real;away_team_photo;away_team_flag;away_team_link;away_team_group;away_team_group_order;away_team_is_real;stadium_photo'
								);
			} else {
				$column_names = explode(
									FOOTBALLPOOL_CSV_DELIMITER, 
									'play_date;home_team;away_team;stadium;match_type' 
								);
			}
			
			if ( count( $header ) == count( $column_names ) ) {
				for ( $i = 0; $i < count( $header ); $i++ ) {
					if ( $header[$i] != $column_names[$i] ) {
						$err[] = sprintf( 
										__( 'Column %d header should be "%s" &rArr; not "%s"', FOOTBALLPOOL_TEXT_DOMAIN )
										, ( $i + 1 )
										, $column_names[$i]
										, $header[$i]
								);
					}
				}
				if ( count( $err ) == 0 ) {
					// import the data (note: teams are always imported as active)
					while ( ( $data = fgetcsv( $fp, 1000, FOOTBALLPOOL_CSV_DELIMITER ) ) !== false ) {
						$play_date = $data[0];
						// home
						$extra_data = '';
						if ( $full_data ) {
							$group = Football_Pool_Groups::get_group_by_name( $data[8], 'addnew' );
							$group_id = ( is_object( $group ) ? $group->id : 0 );
							
							$extra_data = array(
												'photo' => $data[5],
												'flag' => $data[6],
												'link' => $data[7],
												'group_id' => $group_id,
												'group_order' => $data[9],
												'is_real' => $data[10],
												'is_active' => 1,
												);
						}
						$home_team = Football_Pool_Teams::get_team_by_name( $data[1], 'addnew', $extra_data );
						$home_team_id = $home_team->id;
						if ( isset( $home_team->inserted ) && $home_team->inserted == true ) {
							$msg[] = sprintf(
										__( 'Team %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $home_team->id, $home_team->name
									);
						}
						// away
						$extra_data = '';
						if ( $full_data ) {
							$group = Football_Pool_Groups::get_group_by_name( $data[14], 'addnew' );
							$group_id = ( is_object( $group ) ? $group->id : 0 );
							
							$extra_data = array(
												'photo' => $data[11],
												'flag' => $data[12],
												'link' => $data[13],
												'group_id' => $group_id,
												'group_order' => $data[15],
												'is_real' => $data[16],
												'is_active' => 1,
												);
						}
						$away_team = Football_Pool_Teams::get_team_by_name( $data[2], 'addnew', $extra_data );
						$away_team_id = $away_team->id;
						if ( isset( $away_team->inserted ) && $away_team->inserted == true ) {
							$msg[] = sprintf(
										__( 'Team %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $away_team->id, $away_team->name
									);
						}
						// stadium
						$extra_data = '';
						if ( $full_data ) {
							$extra_data = array( 'photo' => $data[17] );
						}
						$stadium = Football_Pool_Stadiums::get_stadium_by_name( $data[3], 'addnew', $extra_data );
						$stadium_id = ( is_object( $stadium ) ? $stadium->id : 0 );
						if ( isset( $stadium->inserted ) && $stadium->inserted == true ) {
							$msg[] = sprintf(
										__( 'Stadium %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $stadium->id, $stadium->name
									);
						}
						// match type
						$match_type = Football_Pool_Matches::get_match_type_by_name( $data[4], 'addnew' );
						$match_type_id = $match_type->id;
						if ( isset( $match_type->inserted ) && $match_type->inserted == true ) {
							$msg[] = sprintf(
										__( 'Match Type %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $match_type->id, $match_type->name
									);
						}
						
						// add the match
						$nr = self::update_match( 
													0, $home_team_id, $away_team_id, null, null, 
													$play_date, $stadium_id, $match_type_id
												);
						$msg[] = sprintf( 
										__( 'Match %d imported: %s - %s for date "%s"', FOOTBALLPOOL_TEXT_DOMAIN )
										, $nr, $home_team->name, $away_team->name, $play_date
								);
					}
				}
			} else {
				$err[] = sprintf( __( 'Imported csv file should contain %d columns. See help page for the correct format.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $column_names ) );
			}
		} else {
			if ( $file == '' ) 
				$err[] = __( 'No csv file selected.', FOOTBALLPOOL_TEXT_DOMAIN );
			else
				$err[] = __( 'Please check if the csv file exists and is readable.', FOOTBALLPOOL_TEXT_DOMAIN );
		}
		
		if ( isset( $fp ) ) @fclose( $fp );
		
		// log is an array containing error messages and/or import messages
		$log = array( $err, $msg );
		return $log;
	}
	
	private function view_schedules( $log = '' ) {
		if ( is_array( $log ) ) {
			$errors = $log[0];
			$import_log = $log[1];
			if ( count( $errors ) > 0 ) self::notice( implode( '<br>', $errors ), 'important' );
			if ( count( $import_log ) > 0 ) self::notice( implode( '<br>', $import_log ) );
		}
		
		self::intro( sprintf( __( 'More information about the csv file import can be found in the <a href="%s">help file</a>.', FOOTBALLPOOL_TEXT_DOMAIN ), '?page=footballpool-help#teams-groups-and-matches' ) );
		
		// check if upload dir exists and is writable
		$upload_is_readable = is_readable( FOOTBALLPOOL_CSV_UPLOAD_DIR );
		$upload_is_writable = is_writable( FOOTBALLPOOL_CSV_UPLOAD_DIR );
		
		if ( ! $upload_is_readable ) {
			self::notice( __( "Please make sure that the directory 'upload' exists in the plugin directory and that it is readable!", FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
			return;
		} elseif ( ! $upload_is_writable ) {
			self::notice( __( "Uploading of new csv files is not possible at the moment. Directory 'upload' is not writable.", FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		if ( $upload_is_readable ) {
			echo '<h3>', __( 'Choose a new game schedule', FOOTBALLPOOL_TEXT_DOMAIN ), '</h3>';
			echo '<p>', __( 'Import any of the following files. Overwrite the existing game schedule or add to the existing schedule.', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
			
			$locale = Football_Pool::get_locale();
			$locale_filter = Football_Pool_Utils::post_string( 'culture', Football_Pool_Utils::get_fp_option( 'csv_file_filter', '*' ) );
			self::set_value( 'csv_file_filter', $locale_filter );
			
			$options = array(
							array( 'value' => '*', 'text' => __( 'all files', FOOTBALLPOOL_TEXT_DOMAIN ) ),
							array( 'value' => $locale, 'text' => sprintf( __( 'only \'%s\' files', FOOTBALLPOOL_TEXT_DOMAIN ), $locale ) ),
						);
			
			echo '<div class="import culture-select">';
			self::dropdown( 'culture', $locale_filter, $options );
			self::secondary_button( __( 'change', FOOTBALLPOOL_TEXT_DOMAIN ), 'change-culture' );
			echo '</div>';
			
			$handle = opendir( FOOTBALLPOOL_CSV_UPLOAD_DIR );
			$i = 0;
			echo '<div class="fp-radio-list">';
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				$locale_check = ( $locale_filter == '*' || strpos( $entry, $locale_filter ) !== false );
				if ( $entry != '.' && $entry != '..' && $locale_check ) {
					$i++;
					echo '<label for="csv-', $i, '">';
					echo '<input id="csv-', $i, '" name="csv_file" type="radio" value="', esc_attr( $entry ), '"> ';
					echo $entry, '</label>';
				}
			}
			echo '</div>';
			
			if ( $i > 0 ) {
				echo '<p class="submit">';
				self::primary_button( 
					__( 'Import CSV', FOOTBALLPOOL_TEXT_DOMAIN ), 
					array(
						'import_csv',
						'return confirm(\'' . __( 'Are you sure you want to add these matches to the existing schedule?', FOOTBALLPOOL_TEXT_DOMAIN ) . '\')' 
					), 
					false 
				);
				self::secondary_button( 
					__( 'Import CSV & Overwrite', FOOTBALLPOOL_TEXT_DOMAIN ), 
					array( 
						'import_csv_overwrite', 
						'return confirm(\'' . __( 'Are you sure you want to overwrite the game schedule with this schedule?\nAll predictions and scores will also be overwritten!', FOOTBALLPOOL_TEXT_DOMAIN ) . '\')' 
					), 
					false 
				);
				self::cancel_button();
				echo '</p>';
			} else {
				self::notice( __( "No csv files found in 'upload' directory.", FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
		}
	
		if ( $upload_is_writable ) {
			// set the right the enctype for the upload
			echo '</form><form method="post" enctype="multipart/form-data" action="">';
			echo '<input type="hidden" name="action" value="upload_csv">';
			echo '<h3>', __( 'Upload new game schedule', FOOTBALLPOOL_TEXT_DOMAIN ), '</h3>';
			// link to help/data explanation and explain the extra data that is needed for teams etc (e.g. photo)
			// option to just upload, add or overwrite
			// upload file
			echo '<div>';
			echo '<input type="file" name="csv_file">';
			self::secondary_button( 
				__( 'Upload CSV', FOOTBALLPOOL_TEXT_DOMAIN ), 
				'upload_csv',
				false
			);
			echo '</div>';
		}
	}
	
	private function view() {
		$matches = new Football_Pool_Matches();
		$rows = $matches->get_info();
		
		$full_data = ( Football_Pool_Utils::get_fp_option( 'export_format', 0, 'int' ) == 0 );
		$download_url = FOOTBALLPOOL_PLUGIN_URL . 'admin/csv-export-matches.php';
		if ( ! $full_data ) $download_url .= '?format=minimal';
		
		echo '<p class="submit">';
		submit_button( null, 'primary', 'submit', false );
		echo '<span style="float: right;">';
		self::secondary_button( __( 'Bulk change game schedule', FOOTBALLPOOL_TEXT_DOMAIN ), 'schedule', false );
		self::secondary_button( 
			__( 'Download game schedule', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$download_url, 
			false, 
			'link' 
		);
		echo '</span></p>';
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
			$options[] = array( 'value' => $type->id, 'text' => $type->name );
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
		
		if ( ! is_array( $rows ) || count( $rows ) == 0 ) {
			echo '<div style="text-align:right;"><img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/admin/images/matches-import-here.png" alt="import a new schedule here" title="import a new schedule here"></div>';
		}
		
		echo '<table id="matchinfo" class="widefat matchinfo">';
		foreach( $rows as $row ) {
			if ( $matchtype != $row['matchtype'] ) {
				$matchtype = $row['matchtype'];
				echo '<tr><td class="sidebar-name" colspan="11"><h3>', $matchtype, '</h3></td></tr>';
			}
			
			$matchdate = new DateTime( $row['playDate'] );
			if ( $datetitle != $matchdate->format( 'd M Y' ) ) {
				$datetitle = $matchdate->format( 'd M Y' );
				echo '<tr><td class="sidebar-name" colspan="7">', $datetitle, '</td>',
						'<td class="sidebar-name"><span title="Coordinated Universal Time">', __( 'UTC', FOOTBALLPOOL_TEXT_DOMAIN ), '</span></td>',
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
					'<td class="home">', self::teamname_input( (int) $row['homeTeamId'], '_home_team_'.$row['nr'] ), '</td>',
					'<td class="score">', self::show_input( '_home_score_' . $row['nr'], $row['homeScore'] ), '</td>',
					'<td>-</td>',
					'<td class="score">', self::show_input( '_away_score_' . $row['nr'], $row['awayScore'] ), '</td>',
					'<td class="away">', self::teamname_input( (int) $row['awayTeamId'], '_away_team_' . $row['nr'] ), '</td>',
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
	
	private function teamname_input( $team, $input_name ) {
		$teams = new Football_Pool_Teams;
		if ( ! is_integer( $team ) || ! isset( $teams->team_names[$team] ) ) return '';
		
		if ( ! $teams->team_types[$team] ) {
			// for matches beyond the group phase and for non-real teams a dropdown
			return self::team_select( $team, $input_name );
		} else {
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
	
	private function update_match( $nr, $home_team, $away_team, $home_score, $away_score, 
									$match_date, $stadium_id = null, $match_type_id = null ) {
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