<?php
//@todo: add options for leaving out links to teampages, links to venuepages, icons/flags for teams 
class Football_Pool_Admin_Options extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$date = date_i18n( 'Y-m-d H:i' );
		
		$options = array(
						//array( 'text', __( 'Remove data on uninstall', FOOTBALLPOOL_TEXT_DOMAIN ), 'remove_data_on_uninstall', __( '', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'webmaster' => 
							array( 'text', __( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ), 'webmaster', __( 'This value is used for the shortcode [webmaster].', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'money' => 
							array( 'text', __( 'Money', FOOTBALLPOOL_TEXT_DOMAIN ), 'money', __( 'If you play for money, then this is the sum users have to pay. The shortcode [money] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'bank' => 
							array( 'text', __( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ), 'bank', __( 'If you play for money, then this is the person you have to give the money. The shortcode [bank] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'start' =>
							array( 'text', __( 'Start date', FOOTBALLPOOL_TEXT_DOMAIN ), 'start', __( 'The start date of the tournament or pool. The shortcode [start] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'fullpoints' => 
							array( 'text', __( 'Full score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'fullpoints', __( 'The points a user gets for getting the exact outcome of a match. The shortcode [fullpoints] adds this value in the content. This value is also used for the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'totopoints' => 
							array( 'text', __( 'Toto score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'totopoints', __( 'The points a user gets for guessing the outcome of a match (win, loss or draw) without also getting the exact amount of goals. The shortcode [totopoints] adds this value in the content. This value is also used in the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
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
							array( 'checkbox', __( 'Use leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues', __( 'Set this if you want to use leagues in your pool. You can use this (e.g.) for paying and non-paying users, or different departments. Important: if you change this value when there are allready points given, then the scoretable will not be automatically recalculated. Use the recalculate button on this page for that.', FOOTBALLPOOL_TEXT_DOMAIN ), 'onclick="jQuery(\'#r-default_league_new_user\').toggle()"' ),
						'default_league_new_user' => 
							array( 'text', __( 'Standard league for new users.', FOOTBALLPOOL_TEXT_DOMAIN ), 'default_league_new_user', __( 'The standard league (<a href="?page=footballpool-leagues">fill in the league ID</a>) a new user will be placed after registration.', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues' ),
						'dashboard_image' =>
							array( 'text', __( 'Image for Dashboard Widget', FOOTBALLPOOL_TEXT_DOMAIN ), 'dashboard_image', '<a href="' . get_admin_url() . '">Dashboard</a>' ),
						'hide_admin_bar' => 
							array( 'checkbox', __( 'Hide Admin Bar for subscribers', FOOTBALLPOOL_TEXT_DOMAIN ), 'hide_admin_bar', __( 'After logging in a subscriber may see an Admin Bar on top of your blog (a user option). With this plugin option you can override this user option and never show the Admin Bar.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_favicon' =>
							array( 'checkbox', __( 'Use favicon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_favicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'use_touchicon' =>
							array( 'checkbox', __( 'Use Apple touch icon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_touchicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'show_team_link' =>
							array( 'checkbox', __( 'Show team names as links', FOOTBALLPOOL_TEXT_DOMAIN ), 'show_team_link', __( "Switch off if you don't want to link the team names to a team info page.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						'show_venues_on_team_page' =>
							array( 'checkbox', __( 'Show venues on team page', FOOTBALLPOOL_TEXT_DOMAIN ), 'show_venues_on_team_page', __( "Switch off if you don't want to show all venues a team plays in during a season or tournament (in national competitions the venue list is a bit useless).", FOOTBALLPOOL_TEXT_DOMAIN ) ),
					);
		
		$donate = '<div class="donate">' 
				. __( 'If you want, you can buy me an espresso (doppio please ;))', FOOTBALLPOOL_TEXT_DOMAIN )
				. self::donate_button( 'return' ) . '</div>';
		self::admin_header( __( 'Plugin Options', FOOTBALLPOOL_TEXT_DOMAIN ), null, null, $donate );
		
		if ( Football_Pool_Utils::post_string( 'recalculate' ) == 'Recalculate Scores' ) {
			$success = self::update_score_history();
			if ( $success )
				self::notice( __( 'Scores recalculated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			else
				self::notice( __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
		} elseif ( Football_Pool_Utils::post_string( 'form_action' ) == 'update' ) {
			foreach ( $options as $option ) {
				if ( $option[0] == 'text' ) {
					$value = Football_Pool_Utils::post_string( $option[2] );
				} elseif ( $value != '' && $option[0] == 'date' || $option[0] == 'datetime' ) {
					$value = self::gmt_from_date( self::make_date_from_input( $option[2], $option[0] ) );
				} else {
					$value = Football_Pool_Utils::post_integer( $option[2] );
				}
				
				self::set_value( $option[2], $value );
			}
			self::notice( __( 'Changes saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		self::intro( __( 'If values in the fields marked with an asterisk are left empty, then the plugin will default to the initial values.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		self::admin_sectiontitle( __( 'Prediction Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['fullpoints'],
									$options['totopoints'], 
									$options['stop_time_method_matches'],
									$options['maxperiod'],
									$options['matches_locktime'],
									$options['stop_time_method_questions'],
									$options['bonus_question_locktime'],
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
									$options['use_favicon'],
									$options['use_touchicon'], 
									$options['hide_admin_bar'], 
								) 
							);
		submit_button( null, 'primary', null, true );
		
		self::admin_sectiontitle( __( 'Other Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( array( 
									$options['show_team_link'],
									$options['show_venues_on_team_page'],
									$options['shoutbox_max_chars'],
									$options['dashboard_image'], 
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