<?php
class Football_Pool_Admin_Options extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$date = date_i18n( 'Y-m-d H:i' );
		
		$options = array(
						//array( 'text', __( 'Verwijder data bij deïnstallatie', FOOTBALLPOOL_TEXT_DOMAIN ), 'remove_data_on_uninstall', __( '', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ), 'webmaster', __( 'This value is used for the shortcode [webmaster].', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Money', FOOTBALLPOOL_TEXT_DOMAIN ), 'money', __( 'If you play for money, then this is the sum users have to pay. The shortcode [money] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ), 'bank', __( 'If you play for money, then this is the person you have to give the money. The shortcode [bank] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Start date', FOOTBALLPOOL_TEXT_DOMAIN ), 'start', __( 'The start date of the tournament or pool. The shortcode [start] adds this value in the content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Full score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'fullpoints', __( 'The points a user gets for getting the exact outcome of a match. The shortcode [fullpoints] adds this value in the content. This value is also used for the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Toto score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'totopoints', __( 'The points a user gets for guessing the outcome of a match (win, loss or draw) without also getting the exact amount of goals. The shortcode [totopoints] adds this value in the content. This value is also used in the calculations in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Prediction stop (in seconds) *', FOOTBALLPOOL_TEXT_DOMAIN ), 'maxperiod', __( 'A user may change his/her predictions untill this amount of time before game kick-off. The time is in seconds, e.g. 15 minutes is 900 seconds.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'datetime', __( 'One prediction lock *', FOOTBALLPOOL_TEXT_DOMAIN ), 'force_locktime', __( 'If a valid date and time [Y-m-d H:i] is given here, then this date/time will be used as a single value before all predictions have to be entered by users. (local time is:', FOOTBALLPOOL_TEXT_DOMAIN ) . ' <a href="options-general.php">' . $date . '</a>)' ),
						array( 'text', __( 'Maximum length for a shoutbox message *', FOOTBALLPOOL_TEXT_DOMAIN ), 'shoutbox_max_chars', __( 'Maximum length (number of characters) a message in the shoutbox may have.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Use leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues', __( 'Set this if you want to use leagues in your pool. You can use this (e.g.) for paying and non-paying users, or different departments. Important: if you change this value when there are allready points given, then the scoretable will not be automatically recalculated. Use the recalculate button on this page for that.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Standard league for new users.', FOOTBALLPOOL_TEXT_DOMAIN ), 'default_league_new_user', __( 'The standard league (<a href="?page=footballpool-leagues">fill in the league ID</a>) a new user will be placed after registration.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Image for Dashboard Widget', FOOTBALLPOOL_TEXT_DOMAIN ), 'dashboard_image', '<a href="' . get_admin_url() . '">Dashboard</a>' ),
						array( 'checkbox', __( 'Hide Admin Bar for subscribers', FOOTBALLPOOL_TEXT_DOMAIN ), 'hide_admin_bar', __( 'After logging in a subscriber may see an Admin Bar on top of your blog (a user option). With this plugin option you can override this user option and never show the Admin Bar.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Use favicon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_favicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Use Apple touch icon', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_touchicon', __( "Switch off if you don't want to use the icons in the plugin.", FOOTBALLPOOL_TEXT_DOMAIN ) ),
					);
		
		self::admin_header( __( 'Plugin Options', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
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
				} elseif ( $value[0] == 'date' || $option[0] == 'datetime' ) {
					$y = Football_Pool_Utils::post_integer( $option[2] . '_y' );
					$m = Football_Pool_Utils::post_integer( $option[2] . '_m' );
					$d = Football_Pool_Utils::post_integer( $option[2] . '_d' );
					$value = ( $y != 0 && $m != 0 && $d != 0 ) ? sprintf( '%04d-%02d-%02d', $y, $m, $d ) : '';
					
					if ( $value != '' && $option[0] == 'datetime' ) {
						$h = Football_Pool_Utils::post_integer( $option[2] . '_h', -1 );
						$i = Football_Pool_Utils::post_integer( $option[2] . '_i', -1 );
						$value = ( $h != -1 && $i != -1 ) ? sprintf( '%s %02d:%02d', $value, $h, $i ) : '';
					}
				} else {
					$value = Football_Pool_Utils::post_integer( $option[2] );
				}
				
				self::set_value( $option[2], $value );
			}
			self::notice( __( 'Wijzigingen opgeslagen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}

		
		self::intro( __( 'If values in the fields marked with an asterisk are left empty, then the plugin will default to the initial values.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		self::options_form( $options );
		
		submit_button( null, 'primary', null, false );
		submit_button( 'Recalculate Scores', 'secondary', 'recalculate', false );
		
		self::admin_footer();
	}
}
?>