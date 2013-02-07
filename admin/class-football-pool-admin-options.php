<?php
class Football_Pool_Admin_Options extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$action = Football_Pool_Utils::post_string( 'action' );
		
		$date = date_i18n( 'Y-m-d H:i' );
		
		$match_time_offsets = array();
		// based on WordPress's functions.php
		$offset_range = array( 
							-12, -11.5, -11, -10.5, -10, -9.5, -9, -8.5, -8, -7.5, -7, -6.5, -6, -5.5, 
							-5, -4.5, -4, -3.5, -3, -2.5, -2, -1.5, -1, -0.5,
							0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 7.5, 8, 
							8.5, 8.75, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.75, 13, 13.75, 14
						);
		foreach ( $offset_range as $offset ) {
			if ( 0 <= $offset )
				$offset_text = '+' . $offset;
			else
				$offset_text = (string) $offset;

			$offset_text = str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $offset_text );
			$offset_text = 'UTC' . $offset_text;

			$match_time_offsets[] = array( 'value' => $offset, 'text' => $offset_text );
		}
		
		// check if offset-dropdowns must be shown
		if ( $action == 'update' ) {
			// in case of a save action
			check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
			$offset_switch = ( Football_Pool_Utils::post_int( 'match_time_display' ) !== 2 );			
		} else {
			// normal situation
			$offset_switch = ( (int)Football_Pool_Utils::get_fp_option( 'match_time_display', 0, 'int' ) !== 2 );
		}
		
		// get the match types for the groups page
		$match_types = Football_Pool_Matches::get_match_types();
		$options = array();
		foreach ( $match_types as $type ) {
			$options[] = array( 'value' => $type->id, 'text' => $type->name );
		}
		$match_types = $options;
		
		// definition of all configurable options
		$options = array(
						//array( 'text', __( 'Remove data on uninstall', FOOTBALLPOOL_TEXT_DOMAIN ), 'remove_data_on_uninstall', __( '', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'webmaster' => 
							array( 'text', __( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ), 'webmaster', __( 'This value is used for the shortcode [fp-webmaster].', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'money' => 
							array( 'text', __( 'Money', FOOTBALLPOOL_TEXT_DOMAIN ), 'money', __( 'If you play for money, then this is the sum users have to pay. The shortcode [fp-money] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'bank' => 
							array( 'text', __( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ), 'bank', __( 'If you play for money, then this is the person you have to give the money. The shortcode [fp-bank] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'start' =>
							array( 'text', __( 'Start date', FOOTBALLPOOL_TEXT_DOMAIN ), 'start', __( 'The start date of the tournament or pool. The shortcode [fp-start] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'fullpoints' =>
							array( 'text', __( 'Full score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'fullpoints', __( 'The points a user gets for getting the exact outcome of a match. The shortcode [fp-fullpoints] adds this value in the content. This value is also used for the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'totopoints' =>
							array( 'text', __( 'Toto score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'totopoints', __( 'The points a user gets for guessing the outcome of a match (win, loss or draw) without also getting the exact amount of goals. The shortcode [fp-totopoints] adds this value in the content. This value is also used in the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'goalpoints' => 
							array( 'text', __( 'Goal bonus *', FOOTBALLPOOL_TEXT_DOMAIN ), 'goalpoints', __( 'Extra points a user gets for guessing the goals correct for one of the teams. These points are added to the toto points or full points. The shortcode [fp-goalpoints] adds this value in the content. This value is also used in the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'stop_time_method_matches' =>
							array( 
								'radiolist', 
								__( 'Prediction stop method for matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'stop_time_method_matches', 
								array( 
									array( 'value' => 0, 'text' => __( 'Dynamic time', FOOTBALLPOOL_TEXT_DOMAIN ) ), 
									array( 'value' => 1, 'text' => __( 'One stop date', FOOTBALLPOOL_TEXT_DOMAIN ) ), 
								), 
								__( 'Select which method to use for the prediction stop.', FOOTBALLPOOL_TEXT_DOMAIN ),
								array(
									'onclick="toggle_linked_radio_options( \'#r-maxperiod\', [ \'#r-matches_locktime\' ] )"',
									'onclick="toggle_linked_radio_options( \'#r-matches_locktime\', [ \'#r-maxperiod\' ] )"',
								),
							),
						'maxperiod' => 
							array( 
								'text', 
								__( 'Dynamic stop threshold (in seconds) for matches *', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'maxperiod', 
								__( 'A user may change his/her predictions untill this amount of time before game kickoff. The time is in seconds, e.g. 15 minutes is 900 seconds.', FOOTBALLPOOL_TEXT_DOMAIN ), 
								array( 'stop_time_method_matches' => 1 ) 
							),
						'matches_locktime' => 
							array( 
								'datetime', 
								__( 'Prediction stop date for matches *', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'matches_locktime', 
								__( 'If a valid date and time [Y-m-d H:i] is given here, then this date/time will be used as a single value before all predictions for the matches have to be entered by users. (your local time is:', FOOTBALLPOOL_TEXT_DOMAIN ) . ' <a href="options-general.php">' . $date . '</a>)', 
								'',
								array( 'stop_time_method_matches' => 0 )
							),
						'stop_time_method_questions' =>
							array( 
								'radiolist', 
								__( 'Use one prediction stop date for questions?', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'stop_time_method_questions', 
								array( 
									array( 'value' => 0, 'text' => __( 'No', FOOTBALLPOOL_TEXT_DOMAIN ) ),
									array( 'value' => 1, 'text' => __( 'Yes', FOOTBALLPOOL_TEXT_DOMAIN ) ),
								),
								__( 'Select which method to use for the prediction stop.', FOOTBALLPOOL_TEXT_DOMAIN ),
								array(
									'onclick="toggle_linked_radio_options( \'\', [ \'#r-bonus_question_locktime\' ] )"',
									'onclick="toggle_linked_radio_options( \'#r-bonus_question_locktime\', null )"',
								),
							),
						'bonus_question_locktime' => 
							array( 
								'datetime', 
								__( 'Prediction stop date for questions *', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'bonus_question_locktime', 
								__( 'If a valid date and time [Y-m-d H:i] is given here, then this date/time will be used as a single value before all predictions for the bonus questions have to be entered by users. (your local time is:', FOOTBALLPOOL_TEXT_DOMAIN ) . ' <a href="options-general.php">' . $date . '</a>)',
								'',
								array( 'stop_time_method_questions' => 0 )
							),
						'shoutbox_max_chars' =>
							array( 'text', __( 'Maximum length for a shoutbox message *', FOOTBALLPOOL_TEXT_DOMAIN ), 'shoutbox_max_chars', __( 'Maximum length (number of characters) a message in the shoutbox may have.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_leagues' => 
							array( 'checkbox', __( 'Use leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues', __( 'Set this if you want to use leagues in your pool. You can use this (e.g.) for paying and non-paying users, or different departments. Important: if you change this value when there are already points given, then the scoretable will not be automatically recalculated. Use the recalculate button on this page for that.', FOOTBALLPOOL_TEXT_DOMAIN ), 'onclick="jQuery(\'#r-default_league_new_user\').toggle()"' ),
						'default_league_new_user' => 
							array( 'text', __( 'Standard league for new users', FOOTBALLPOOL_TEXT_DOMAIN ), 'default_league_new_user', __( 'The standard league (<a href="?page=footballpool-leagues">fill in the league ID</a>) a new user will be placed after registration.', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues' ),
						'dashboard_image' =>
							array( 'text', __( 'Image for Dashboard Widget', FOOTBALLPOOL_TEXT_DOMAIN ), 'dashboard_image', '<a href="' . get_admin_url() . '">Dashboard</a>' ),
						'hide_admin_bar' => 
							array( 'checkbox', __( 'Hide Admin Bar for subscribers', FOOTBALLPOOL_TEXT_DOMAIN ), 'hide_admin_bar', __( 'After logging in a subscriber may see an Admin Bar on top of your blog (a user option). With this plugin option you can ignore the user configuration and never show the Admin Bar.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_favicon' =>
							array( 'checkbox', __( 'Use favicon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_favicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_touchicon' =>
							array( 'checkbox', __( 'Use Apple touch icon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_touchicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'show_team_link' =>
							array( 'checkbox', __( 'Show team names as links', FOOTBALLPOOL_TEXT_DOMAIN ), 'show_team_link', __( "Switch off if you don't want to link the team names to a team info page.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'show_venues_on_team_page' =>
							array( 'checkbox', __( 'Show venues on team page', FOOTBALLPOOL_TEXT_DOMAIN ), 'show_venues_on_team_page', __( "Switch off if you don't want to show all venues a team plays in during a season or tournament (in national competitions the venue list is a bit useless).", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_charts' =>
							array( 
								'checkbox', 
								__( 'Use charts', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'use_charts', 
								sprintf( 
									__( 'The Highcharts API is needed for this feature. See the <%s>Help page<%s> for information on installing this library.', FOOTBALLPOOL_TEXT_DOMAIN ), 
									'a href="?page=footballpool-help#charts"', '/a' 
								)
							),
						'export_format' =>
							array( 
								'radiolist', 
								__( 'Format for the csv export (match schedule)', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'export_format', 
								array( 
									array( 'value' => 0, 'text' => __( 'Full data', FOOTBALLPOOL_TEXT_DOMAIN ) ),
									array( 'value' => 1, 'text' => __( 'Minimal data', FOOTBALLPOOL_TEXT_DOMAIN ) ),
								),
								sprintf( __( 'Select the format of the csv export. See the <%s>Help page<%s> for more information.', FOOTBALLPOOL_TEXT_DOMAIN ), 'a href="?page=footballpool-help#teams-groups-and-matches"', '/a' ),
							),
						'match_time_display' =>
							array( 
								'radiolist', 
								__( 'Match time setting', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'match_time_display', 
								array( 
									array( 'value' => 0, 'text' => __( 'Use WordPress setting', FOOTBALLPOOL_TEXT_DOMAIN ) ), 
									array( 'value' => 1, 'text' => __( 'Use UTC time', FOOTBALLPOOL_TEXT_DOMAIN ) ), 
									array( 'value' => 2, 'text' => __( 'Custom setting', FOOTBALLPOOL_TEXT_DOMAIN ) ), 
								), 
								__( 'Select which method to use for the display of match times.', FOOTBALLPOOL_TEXT_DOMAIN ),
								array(
									'onclick="toggle_linked_radio_options( null, \'#r-match_time_offset\' )"',
									'onclick="toggle_linked_radio_options( null, \'#r-match_time_offset\' )"',
									'onclick="toggle_linked_radio_options( \'#r-match_time_offset\', null )"',
								),
							),
						'match_time_offset' =>
							array( 
								array( 'dropdown', 'string' ), 
								__( 'Match time offset', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'match_time_offset', 
								$match_time_offsets,
								__( 'The offset in hours to add to (or extract from) the UTC start time of a match. Only used for display of the time.', FOOTBALLPOOL_TEXT_DOMAIN ),
								'',
								$offset_switch,
							),
						'add_tinymce_button' => 
							array( 'checkbox', __( 'Use shortcode button in visual editor', FOOTBALLPOOL_TEXT_DOMAIN ), 'add_tinymce_button', __( 'The plugin can add a button to the visual editor of WordPress. With this option disabled this button will not be included (uncheck if the button is causing problems).', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'always_show_predictions' => 
							array( 'checkbox', __( 'Always show predictions', FOOTBALLPOOL_TEXT_DOMAIN ), 'always_show_predictions', __( 'Normally match predictions are only shown to other players after a prediction can\'t be changed anymore. With this option enabled the predictions are visible to anyone, anytime. Works only for matches, not bonus questions.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_spin_controls' => 
							array( 'checkbox', __( 'Use HTML5 number inputs', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_spin_controls', __( 'Make use of HTML5 number inputs for the prediction form. Some browsers display these as spin controls.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'groups_page_match_types' =>
							array( 
								array( 'multi-select', 'integer array' ), 
								__( 'Groups page matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'groups_page_match_types', 
								$match_types,
								sprintf( __( 'The Groups page shows standings for the matches in these match types. Defaults to match type id: %d.', FOOTBALLPOOL_TEXT_DOMAIN ), FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE ) . ' ' . __( 'Use CTRL+click to select multiple values.', FOOTBALLPOOL_TEXT_DOMAIN ),
								'',
							),
						'match_sort_method' =>
							array( 
								'radiolist', 
								__( 'Match sorting', FOOTBALLPOOL_TEXT_DOMAIN ), 
								'match_sort_method', 
								array( 
									array( 'value' => 0, 'text' => __( 'Date ascending', FOOTBALLPOOL_TEXT_DOMAIN ) ),
									array( 'value' => 1, 'text' => __( 'Date descending', FOOTBALLPOOL_TEXT_DOMAIN ) ),
								),
								__( 'Select the order in which matches must be displayed on the matches page and the prediction page..', FOOTBALLPOOL_TEXT_DOMAIN ),
							),
						'show_avatar' => 
							array( 'checkbox', __( 'Show (gr)avatars', FOOTBALLPOOL_TEXT_DOMAIN ), 'show_avatar', __( 'Show the user\'s (gr)avatar in ranking tables (if available).', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					);
		
		$donate = sprintf( '<div class="donate">%s%s</div>'
							, __( 'If you want to support this plugin, you can buy me an espresso (doppio please ;))', FOOTBALLPOOL_TEXT_DOMAIN )
							, self::donate_button( 'return' )
					);
		
		self::admin_header( __( 'Plugin Options', FOOTBALLPOOL_TEXT_DOMAIN ), null, null, $donate );
		
		if ( Football_Pool_Utils::post_string( 'recalculate' ) == 'Recalculate Scores' ) {
			check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
			self::update_score_history();
		} elseif ( $action == 'update' ) {
			check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
			foreach ( $options as $option ) {
				if ( is_array( $option[0] ) ) {
					$value_type = $option[0][1];
				} else {
					$value_type = $option[0];
				}
				
				if ( $value_type == 'text' || $value_type == 'string' ) {
					$value = Football_Pool_Utils::post_string( $option[2] );
				} elseif ( $value != '' && $value_type == 'date' || $value_type == 'datetime' ) {
					$value = self::gmt_from_date( self::make_date_from_input( $option[2], $value_type ) );
				} elseif ( $value_type == 'integer array' ) {
					$value = Football_Pool_Utils::post_integer_array( $option[2] );
				} elseif ( $value_type == 'string array' ) {
					$value = Football_Pool_Utils::post_string_array( $option[2] );
				} else {
					$value = Football_Pool_Utils::post_integer( $option[2] );
				}
				
				self::set_value( $option[2], $value );
			}
			self::notice( __( 'Changes saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		$chart = new Football_Pool_Chart;
		if ( $chart->stats_enabled && ! $chart->API_loaded ) {
			self::notice( sprintf( __( 'Charts are enabled but Highcharts API was not found! See <a href="%s">Help page</a> for details.', FOOTBALLPOOL_TEXT_DOMAIN ), 'admin.php?page=footballpool-help#charts' ) , 'important' );
		}
		
		self::intro( __( 'If values in the fields marked with an asterisk are left empty, then the plugin will default to the initial values.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		self::admin_sectiontitle( __( 'Prediction Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['fullpoints'],
									$options['totopoints'],
									$options['goalpoints'],
									$options['stop_time_method_matches'],
									$options['maxperiod'],
									$options['matches_locktime'],
									$options['stop_time_method_questions'],
									$options['bonus_question_locktime'],
									$options['always_show_predictions'],
								)
							);
		echo '<p class="submit">';
		submit_button( null, 'primary', null, false );
		submit_button( __( 'Recalculate Scores', FOOTBALLPOOL_TEXT_DOMAIN ), 'secondary', 'recalculate', false );
		echo '</p>';
		
		self::admin_sectiontitle( __( 'League Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['use_leagues'],
									$options['default_league_new_user'],
								) 
							);
		echo '<p class="submit">';
		submit_button( null, 'primary', null, false );
		submit_button( __( 'Recalculate Scores', FOOTBALLPOOL_TEXT_DOMAIN ), 'secondary', 'recalculate', false );
		echo '</p>';
		
		self::admin_sectiontitle( __( 'Pool Layout Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['use_spin_controls'],
									$options['show_avatar'],
									$options['match_time_display'],
									$options['match_time_offset'],
									$options['show_team_link'],
									$options['show_venues_on_team_page'],
									$options['match_sort_method'],
								) 
							);
		submit_button( null, 'primary', null, true );
		
		self::admin_sectiontitle( __( 'Other Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['use_charts'],
									$options['export_format'],
									$options['shoutbox_max_chars'],
									$options['dashboard_image'], 
									$options['use_favicon'],
									$options['use_touchicon'], 
									$options['hide_admin_bar'], 
									$options['add_tinymce_button'], 
									$options['groups_page_match_types'], 
								) 
							);
		submit_button( null, 'primary', null, true );
		
		self::admin_sectiontitle( __( 'Shortcodes', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['webmaster'],
									$options['bank'], 
									$options['money'], 
									$options['start'], 
								) 
							);
		submit_button( null, 'primary', null, true );
				
		self::admin_footer();
	}
}
?>