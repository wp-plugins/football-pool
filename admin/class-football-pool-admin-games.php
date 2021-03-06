<?php
class Football_Pool_Admin_Games extends Football_Pool_Admin {
	public function __construct() {}
	
	public static function help() {
		$help_tabs = array(
					array(
						'id' => 'overview',
						'title' => __( 'Overview', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>On this page you can quickly edit match scores and team names for final rounds (if applicable). If you wish to change all information about a match, then click the <em>\'edit\'</em> link.</p><p>After saving the match data the pool ranking is recalculated. If you have a lot of users this may take a while. You can (temporarily) disable the automatic recalculation of scores in the Plugin Options.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
					array(
						'id' => 'import',
						'title' => __( 'Import & Export', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>Matches can be imported into the plugin using the import function (<em>\'Bulk change game schedule\'</em>). See the help page for more information about the required format.</p><p>On the import screen you can choose one of the already uploaded schedules or upload a new one (if write is enabled on the upload directory).</p><p>The import can add matches to your schedule, or completely overwrite the existing schedule. Please beware that when overwriting the schedule all existing predictions and rankings will be lost.</p><p>Existing matches can be exported using the <em>\'Download game schedule\'</em> button.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
					array(
						'id' => 'details',
						'title' => __( 'Match details', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<ul><li><em>match date</em> must be in UTC format.</li></ul>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
				);
		$help_sidebar = sprintf( '<a href="?page=footballpool-help#teams-groups-and-matches">%s</a></p><p><a href="?page=footballpool-options">%s</a>'
								, __( 'Help section about matches and the import', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( 'Plugin options page', FOOTBALLPOOL_TEXT_DOMAIN )
						);
	
		self::add_help_tabs( $help_tabs, $help_sidebar );
	}
	
	public static function screen_options() {
		$args = array(
			'label' => __( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ),
			'default' => FOOTBALLPOOL_ADMIN_MATCHES_PER_PAGE,
			'option' => 'footballpool_matches_per_page'
		);
		add_screen_option( 'per_page', $args );
	}
	
	public static function admin() {
		$action  = Football_Pool_Utils::request_string( 'action' );
		$item_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		
		self::admin_header( __( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		
		$log = '';
		$file = '';
		
		switch ( $action ) {
			case 'upload_csv':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				$uploaded_file = self::upload_csv();
				if ( Football_Pool_Utils::post_int( 'csv_import' ) == 1 ) {
					$file = $uploaded_file;
					$upload = true;
				}
			case 'import_csv':
			case 'import_csv_overwrite':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				$log = self::import_csv( $action, $file );
			case 'change-culture':
			case 'schedule':
				self::view_schedules( $log );
				break;
			case 'edit':
			case 'update':
			case 'update_single_match':
			case 'update_single_match_close':
				if ( $action != 'edit' ) {
					check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				}
				self::edit_handler( $item_id, $action );
				break;
			case 'delete':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
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
	
	private static function upload_csv() {
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
	
	private static function import_csv( $action = 'import_csv', $file = '' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$msg = $err = array();
		
		if ( $action == 'upload_csv' && $file == '' ) return array( $err, $msg );
		
		if ( $file == '' ) {
			$file = Football_Pool_Utils::post_string( 'csv_file' );
		}
		
		if ( $file != '' && ( $fp = @fopen( FOOTBALLPOOL_CSV_UPLOAD_DIR . $file, 'r' ) ) !== false ) {
			// check if metadata is set in the csv, if not it should contain the csv column definition
			$header = fgetcsv( $fp, 0, FOOTBALLPOOL_CSV_DELIMITER );
			if ( $header[0] == '/*' ) {
				while ( ( $header = fgetcsv( $fp, 0, FOOTBALLPOOL_CSV_DELIMITER ) ) !== false
						&& str_replace( array( " ", "\t" ), '', $header[0] ) != '*/' ) {
					// keep reading
				}
				// with meta gone, next line should contain the csv column definition
				$header = fgetcsv( $fp, 0, FOOTBALLPOOL_CSV_DELIMITER );
			}
			
			// check the columns
			$full_data = ( count( $header ) > 5 ) ? true : false;
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
					$teams = new Football_Pool_Teams;
					$stadiums = new Football_Pool_Stadiums;
					$matches = new Football_Pool_Matches;
					
					// if action is 'overwrite' then first empty all tables
					if ( $action == 'import_csv_overwrite' ) {
						// ranking update log
						$sql = "SELECT ranking_id FROM {$prefix}rankings_matches";
						$ranking_ids = $wpdb->get_col( $sql );
						foreach( $ranking_ids as $ranking_id ) {
							self::update_ranking_log( $ranking_id, null, null, 
										__( 'match import with overwrite', FOOTBALLPOOL_TEXT_DOMAIN )
									);
						}
						// remove all match data except matchtypes
						self::empty_table( 'scorehistory' );
						self::empty_table( 'predictions' );
						self::empty_table( 'user_updatelog_matches' );
						self::empty_table( 'user_updatelog_questions' );
						self::empty_table( 'stadiums' );
						self::empty_table( 'rankings_matches' );
						self::empty_table( 'matches' );
						self::empty_table( 'teams' );
					}
					
					while ( ( $data = fgetcsv( $fp, 0, FOOTBALLPOOL_CSV_DELIMITER ) ) !== false ) {
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
						$home_team = $teams->get_team_by_name( $data[1], 'addnew', $extra_data );
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
						$away_team = $teams->get_team_by_name( $data[2], 'addnew', $extra_data );
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
						$stadium = $stadiums->get_stadium_by_name( $data[3], 'addnew', $extra_data );
						$stadium_id = ( is_object( $stadium ) ? $stadium->id : 0 );
						if ( isset( $stadium->inserted ) && $stadium->inserted == true ) {
							$msg[] = sprintf(
										__( 'Stadium %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $stadium->id, $stadium->name
									);
						}
						// match type
						$match_type = $matches->get_match_type_by_name( $data[4], 'addnew' );
						$match_type_id = $match_type->id;
						if ( isset( $match_type->inserted ) && $match_type->inserted == true ) {
							$msg[] = sprintf(
										__( 'Match Type %d added: %s', FOOTBALLPOOL_TEXT_DOMAIN )
										, $match_type->id, $match_type->name
									);
						}
						
						// add the match
						$id = self::update_match( 
													0, $home_team_id, $away_team_id, null, null, 
													$play_date, $stadium_id, $match_type_id
												);
						$msg[] = sprintf( 
										__( 'Match %d imported: %s - %s for date "%s"', FOOTBALLPOOL_TEXT_DOMAIN )
										, $id, $home_team->name, $away_team->name, $play_date
								);
					}
				}
			} else {
				$column_count = count( $column_names );
				$header_count = count( $header );
				$err[] = sprintf( __( 'Imported csv file should contain %d columns (header contains %d columns). See help page for the correct format.', FOOTBALLPOOL_TEXT_DOMAIN ), $column_count, $header_count );
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
	
	private static function get_meta_from_csv( $file ) {
		$all_headers = array(
							'contributor'	=> 'Contributor',
							'assets'		=> 'Assets URI',
						);
		return get_file_data( $file, $all_headers );
	}
	
	private static function view_schedules( $log = '' ) {
		if ( is_array( $log ) ) {
			$errors = $log[0];
			$import_log = $log[1];
			if ( count( $errors ) > 0 ) self::notice( implode( '<br>', $errors ), 'important' );
			if ( count( $import_log ) > 0 ) self::notice( implode( '<br>', $import_log ) );
		}
		
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
			echo self::dropdown( 'culture', $locale_filter, $options );
			self::secondary_button( __( 'change', FOOTBALLPOOL_TEXT_DOMAIN ), 'change-culture' );
			echo '</div>';
			
			$handle = opendir( FOOTBALLPOOL_CSV_UPLOAD_DIR );
			$i = 0;
			echo '<table class="fp-radio-list">';
			echo '<tr>
					<th></th>
					<th>', __( 'File', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th>', __( 'Contributor', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th>', __( 'Assets', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
				</tr>';
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				$locale_check = ( $locale_filter == '*' || strpos( $entry, $locale_filter ) !== false );
				if ( $entry != '.' && $entry != '..' && $locale_check ) {
					$i++;
					$meta = self::get_meta_from_csv( FOOTBALLPOOL_CSV_UPLOAD_DIR . $entry );
					echo '<tr class="csv-file"><td><input id="csv-', $i, '" name="csv_file" type="radio" value="', esc_attr( $entry ), '"></td>';
					echo '<td><label for="csv-', $i, '">', $entry, '</label></td>';
					echo '<td>', $meta['contributor'], '</td>';
					echo '<td>';
					if ( $meta['assets'] != '' ) {
						echo '<a title="', __( 'Upload these files to your assets folder in the plugin directory', FOOTBALLPOOL_TEXT_DOMAIN ), '" href="', $meta['assets'], '">', __( 'download files', FOOTBALLPOOL_TEXT_DOMAIN ), '</a>';
					} else {
						echo '';
					}
					echo '</td>';
					echo '</tr>';
				}
			}
			echo '</table>';
			
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
						sprintf( 'return ( confirm( \'%s\' ) ? confirm( \'%s\' ) : false )'
								, __( 'Are you sure you want to overwrite the game schedule with this schedule?\n\nAll predictions and scores will also be overwritten!', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( 'Are you really, really, really sure?', FOOTBALLPOOL_TEXT_DOMAIN )
						)
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
			wp_nonce_field( FOOTBALLPOOL_NONCE_ADMIN );
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
	
	private static function view() {
		$matches = new Football_Pool_Matches();
		$rows = $matches->matches;
		
		$pagination = new Football_Pool_Pagination( count( $rows ) );
		$pagination->set_page_size( self::get_screen_option( 'per_page' ) );
		$pagination->wrap = true;
		
		$rows = array_slice( 
							$rows
							, ( $pagination->current_page - 1 ) * $pagination->get_page_size()
							, $pagination->get_page_size()
							, true
				);
		
		$full_data = ( Football_Pool_Utils::get_fp_option( 'export_format', 0, 'int' ) == 0 );
		$download_url = wp_nonce_url( FOOTBALLPOOL_PLUGIN_URL . 'admin/csv-export-matches.php'
									, FOOTBALLPOOL_NONCE_CSV );
		if ( ! $full_data ) $download_url = esc_url( add_query_arg( array( 'format' => 'minimal' ), $download_url ) );
		
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
		$pagination->show();
		self::print_matches( $rows );
		// $pagination->show();
		submit_button();
	}
	
	private static function edit_handler( $item_id, $action ) {
		switch ( $action ) {
			case 'update':
				$success = self::update();
				break;
			case 'update_single_match':
			case 'update_single_match_close':
				$success = self::update_single_match( $item_id );
				if ( $item_id == 0 ) $item_id = $success;
				if ( $success ) self::update_score_history();
				break;
			case 'edit':
				$success = self::edit( $item_id );
				break;
		}
		
		if ( $action != 'edit' ) {
			if ( $success ) {
				self::notice( __( 'Values updated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				wp_cache_delete( FOOTBALLPOOL_CACHE_MATCHES );
			}
			if ( $action == 'update_single_match' ) {
				self::edit_handler( $item_id, 'edit' );
			} else {
				self::view();
			}
		}
	}
	
	private static function delete( $item_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		do_action( 'footballpool_admin_match_delete', $item_id );
		
		// ranking update log
		$sql = $wpdb->prepare( "SELECT ranking_id FROM {$prefix}rankings_matches
								WHERE match_id = %d", $item_id );
		$ranking_ids = $wpdb->get_col( $sql );
		foreach( $ranking_ids as $ranking_id ) {
			self::update_ranking_log( $ranking_id, null, null, 
						sprintf( __( 'match %d deleted', FOOTBALLPOOL_TEXT_DOMAIN ), $item_id )
					);
		}
		
		// delete match, corresponding predictions and update linked bonus questions
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}matches WHERE id = %d", $item_id );
		$success = ( $wpdb->query( $sql ) !== false );
		if ( $success ) {
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}predictions WHERE match_id = %d", $item_id );
			$success = ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}user_updatelog_matches WHERE match_id = %d", $item_id );
			$success = ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "UPDATE {$prefix}bonusquestions SET match_id = 0 WHERE match_id = %d", $item_id );
			$success &= ( $wpdb->query( $sql ) !== false );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_matches WHERE match_id = %d", $item_id );
			$success &= ( $wpdb->query( $sql ) !== false );
			// update scorehistory
			$success &= self::update_score_history();
		}
		
		return $success;
	}
	
	private static function edit( $item_id ) {
		$values = array(
						'play_date' => '',
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
		
		$venues = new Football_Pool_Stadiums;
		$venues = $venues->get_stadiums();
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
		
		// check if there is enough information to fill a match
		if ( count( $teams ) == 0 || count( $types ) == 0 || count( $venues ) == 0 ) {
			self::notice( sprintf( __( 'You have to enter some <a href="%s">teams</a>, <a href="%s">venues</a> and <a href="%s">match types</a> first.', FOOTBALLPOOL_TEXT_DOMAIN ), '?page=footballpool-teams', '?page=footballpool-venues', '?page=footballpool-matchtypes'), 'important' );
			self::cancel_button( true, __( 'Back', FOOTBALLPOOL_TEXT_DOMAIN ) );
			return;
		}
		
		$matchdate = new DateTime( $values['play_date'] );
		$matchdate = $matchdate->format( 'Y-m-d H:i' );
		$matchdate_local = Football_Pool_Utils::date_from_gmt( $values['play_date'] );
		$cols = array(
					array( 'text', __( 'match date (UTC)', FOOTBALLPOOL_TEXT_DOMAIN ), 'match_date', $matchdate, sprintf( '<span title="%s">%s</span>', __( 'time of the match in local time (WordPress setting)', FOOTBALLPOOL_TEXT_DOMAIN ), sprintf( __( 'local time is %s', FOOTBALLPOOL_TEXT_DOMAIN ), $matchdate_local ) ) ),
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
		self::secondary_button( __( 'Save', FOOTBALLPOOL_TEXT_DOMAIN ), 'update_single_match' );
		self::cancel_button();
		echo '</p>';
	}
	
	private static function update_single_match( $item_id ) {
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
	
	private static function update() {
		$matches = new Football_Pool_Matches;
		$rows = $matches->matches;
		$match_saved = false;
		
		// update scores for all matches
		foreach( $rows as $row ) {
			$match_id = $row['id'];
			$match_on_form = ( Football_Pool_Utils::post_integer( '_match_id_' . $match_id, 0 ) == $match_id );
			$home_score = Football_Pool_Utils::post_integer( '_home_score_' . $match_id, 'NULL' );
			$away_score = Football_Pool_Utils::post_integer( '_away_score_' . $match_id, 'NULL' );
			$home_team = Football_Pool_Utils::post_integer( '_home_team_' . $match_id, -1 );
			$away_team = Football_Pool_Utils::post_integer( '_away_team_' . $match_id, -1 );
			$match_date = Football_Pool_Utils::post_string( '_match_date_' . $match_id, '1900-01-01 00:00' );
			
			if ( $match_on_form ) {
				$match_saved = self::update_match( $match_id, $home_team, $away_team, 
													$home_score, $away_score, $match_date );
			}
		}
		
		if ( $match_saved ) $match_saved = $match_saved && self::update_score_history();
		
		return $match_saved;
	}
	
	private static function print_matches( $rows ) {	
		$date_title = '';
		$matchtype = '';
		
		if ( ! is_array( $rows ) || count( $rows ) == 0 ) {
			printf( '<div class="no-matches-notice"><img src="%sassets/admin/images/matches-import-here.png" alt="%s" title="%s"></div>'
				, FOOTBALLPOOL_PLUGIN_URL
				, __( 'import a new schedule here', FOOTBALLPOOL_TEXT_DOMAIN )
				, __( 'import a new schedule here', FOOTBALLPOOL_TEXT_DOMAIN )
			);
		} else {
			echo '<table id="matchinfo" class="wp-list-table widefat matchinfo"><tbody id="the-list">';
			foreach( $rows as $row ) {
				if ( $matchtype != $row['matchtype'] ) {
					$matchtype = $row['matchtype'];
					echo '<tr><td class="sidebar-name" colspan="8"><h3>', $matchtype, '</h3></td></tr>';
				}
				
				$matchdate = new DateTime( $row['play_date'] );
				$matchdate = $matchdate->format( 'Y-m-d H:i' );
				$localdate = new DateTime( Football_Pool_Utils::date_from_gmt( $matchdate ) );
				// $localdate = new DateTime( Football_Pool_Matches::format_match_time( $matchdate, 'Y-m-d H:i' ) );
				$localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN )
												, $localdate->format( 'U' ) );
				if ( $date_title != $localdate_formatted ) {
					$date_title = $localdate_formatted;
					echo '<tr><td class="sidebar-name"></td>',
							'<td class="sidebar-name" title="', __( 'time of the match in local time (WordPress setting)', FOOTBALLPOOL_TEXT_DOMAIN ), '">', __( 'local time', FOOTBALLPOOL_TEXT_DOMAIN ), '</td>',
							'<td class="sidebar-name"><span title="Coordinated Universal Time">', __( 'UTC', FOOTBALLPOOL_TEXT_DOMAIN ), '</span></td>',
							'<td class="sidebar-name date-title" colspan="5">', $date_title, '</td>',
							'</tr>';
				}
				
				$page = wp_nonce_url( sprintf( '?page=%s&amp;item_id=%d'
												, Football_Pool_Utils::get_string( 'page' )
												, $row['id'] )
										, FOOTBALLPOOL_NONCE_ADMIN );
				$confirm = sprintf( __( 'You are about to delete match %d.', FOOTBALLPOOL_TEXT_DOMAIN )
									, $row['id'] 
								);
				$confirm .= ' ' . __( "Are you sure? `OK` to delete, `Cancel` to stop.", FOOTBALLPOOL_TEXT_DOMAIN );
				echo '<tr class="match-row match-', $row['id'], '">',
						'<td class="time column-match-id">', $row['id'], self::hidden_input( "_match_id_{$row['id']}", $row['id'], 'return' ), '</td>',
						'<td class="time local column-localtime">', $localdate->format( 'Y-m-d H:i' ), '<br><div class="row-actions"><span class="edit"><a href="', $page, '&amp;action=edit">', __( 'Edit' ), '</a></span> | <span class="delete"><a onclick="return confirm( \'', $confirm, '\' )" href="', $page, '&amp;action=delete">', __( 'Delete' ), '</a></span></div></td>',
						'<td class="time UTC column-utctime" title="', __( 'change match time', FOOTBALLPOOL_TEXT_DOMAIN ), '">', self::show_input( '_match_date_' . $row['id'], $matchdate, 16, '' ), '</td>',
						'<td class="home column-home">', self::teamname_input( (int) $row['home_team_id'], '_home_team_'.$row['id'] ), '</td>',
						'<td class="score column-home-score">', self::show_input( '_home_score_' . $row['id'], $row['home_score'] ), '</td>',
						'<td>-</td>',
						'<td class="score column-away-score">', self::show_input( '_away_score_' . $row['id'], $row['away_score'] ), '</td>',
						'<td class="away column-away">', self::teamname_input( (int) $row['away_team_id'], '_away_team_' . $row['id'] ), '</td>',
						'</tr>';
			}
			echo '</tbody></table>';
		}
	}
	
	private static function show_input( $name, $value, $max_length = 3, $class = 'score' ) {
		return sprintf( '<input type="text" name="%s" value="%s" maxlength="%s" class="%s" />', 
						$name, $value, $max_length, $class );
	}
	
	private static function teamname_input( $team, $input_name ) {
		$teams = new Football_Pool_Teams;
		if ( ! is_integer( $team ) || ! isset( $teams->team_names[$team] ) ) return '';
		
		if ( ! $teams->team_types[$team] ) {
			// for matches beyond the group phase and for non-real teams a dropdown
			return self::team_select( $team, $input_name );
		} else {
			return sprintf( '%s<input type="hidden" name="%s" id="%s" value="%s" />'
							, $teams->team_names[$team]
							, esc_attr( $input_name )
							, esc_attr( $input_name )
							, esc_attr( $team )
					);
		}
	}
	
	private static function team_select( $team, $input_name ) {
		$teams = new Football_Pool_Teams;
		
		$select = '<select name="' . $input_name . '" id="' . $input_name . '">';
		foreach ( $teams->team_names as $id => $name ) {
			$select .= '<option value="' . $id . '"' . ( $team == $id ? ' selected="selected"' : '' ) . '>' . $name . '</option>';
		}
		$select .= '</select>';
		return $select;
	}
	
	private static function update_match( $id, $home_team, $away_team, $home_score, $away_score, 
									$match_date, $stadium_id = null, $match_type_id = null ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $id == 0 ) {
			if ( ! is_integer( $home_score ) || ! is_integer( $away_score ) ) {
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}matches 
											( home_team_id, away_team_id, home_score, away_score, 
												play_date, stadium_id, matchtype_id )
										VALUES ( %d, %d, NULL, NULL, %s, %d, %d )"
									, $home_team, $away_team, $match_date, $stadium_id, $match_type_id
								);
			} else {
				self::update_ranking_log( 1, null, null, 
									__( 'match with end score added to pool', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}matches 
											( home_team_id, away_team_id, home_score, away_score, 
												play_date, stadium_id, matchtype_id )
										VALUES ( %d, %d, %d, %d, %s, %d, %d )"
									, $home_team, $away_team, $home_score, $away_score
									, $match_date, $stadium_id, $match_type_id
								);
			}
		} else {
			$matches = new Football_Pool_Matches;
			$match = $matches->matches[$id];
			$old_home_score = $match['home_score'];
			$old_away_score = $match['away_score'];
			$old_date = new DateTime( $match['date'] );
			$old_date = $old_date->format( 'Y-m-d H:i' );
			$old_home_id = $match['home_team_id'];
			$old_away_id = $match['away_team_id'];
			if ( ! is_integer( $stadium_id ) ) $stadium_id = $match['stadium_id'];
			if ( ! is_integer( $match_type_id ) ) $match_type_id = $match['match_type_id'];
			
			if ( ! is_integer( $home_score ) || ! is_integer( $away_score ) ) {
				$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
											home_team_id = %d, away_team_id = %d, 
											home_score = NULL, away_score = NULL,
											play_date = %s, stadium_id = %d, matchtype_id = %d
										WHERE id = %d",
									$home_team, $away_team, $match_date, $stadium_id, $match_type_id, $id
								);
			} else {
				$old_set = array( $old_home_id, $old_away_id, $old_home_score, $old_away_score, $old_date );
				$new_set = array( $home_team, $away_team, $home_score, $away_score, $match_date );
				if ( count( array_diff_assoc( $new_set, $old_set ) ) > 0 ) {
					$sql = $wpdb->prepare( "SELECT DISTINCT( ranking_id ) FROM {$prefix}rankings_matches
											WHERE match_id = %d", $id );
					$ranking_ids = $wpdb->get_col( $sql );
					foreach( $ranking_ids as $ranking_id ) {
						self::update_ranking_log( $ranking_id, null, null, 
									sprintf( __( 'match %d changed', FOOTBALLPOOL_TEXT_DOMAIN ), $id )
								);
					}
				}
				$sql = $wpdb->prepare( "UPDATE {$prefix}matches SET 
											home_team_id = %d, away_team_id = %d, 
											home_score = %d, away_score = %d, 
											play_date = %s, stadium_id = %d, matchtype_id = %d
										WHERE id = %d",
									$home_team, $away_team, $home_score, $away_score, 
									$match_date, $stadium_id, $match_type_id, $id
								);
			}
		}
		
		$success = ( $wpdb->query( $sql ) !== false );
		
		if ( $id  > 0 ) {
			return $success;
		} else {
			$id = $success ? $wpdb->insert_id : 0;
			do_action( 'footballpool_admin_match_save', $id );
			return $id;
		}
	}

}
