<?php
// dummy var for translation files
$fp_translate_this = __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'teams', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'groups', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'venues', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'rules', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'prediction sheet', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'ranking', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'statistics', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'player predictions', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool {
	private static $pages = array(
		array( 'slug' => 'tournament', 'title' => 'matches', 'comment' => 'closed' ),
			array( 'slug' => 'teams', 'title' => 'teams', 'parent' => 'tournament', 'comment' => 'closed' ),
			array( 'slug' => 'groups', 'title' => 'groups', 'parent' => 'tournament', 'comment' => 'closed' ),
			array( 'slug' => 'stadiums', 'title' => 'venues', 'parent' => 'tournament', 'comment' => 'closed' ),
		'rules' => array( 'slug' => 'rules', 'title' => 'rules', 'text' => '' ),
		array( 'slug' => 'pool', 'title' => 'prediction sheet', 'comment' => 'closed' ),
		array( 'slug' => 'ranking', 'title' => 'ranking', 'comment' => 'closed' ),
		array( 'slug' => 'statistics', 'title' => 'statistics', 'comment' => 'closed' ),
		array( 'slug' => 'user', 'title' => 'player predictions', 'comment' => 'closed' )
	);
	
	public static function get_pages() {
		return self::$pages;
	}
	
	public static function activate( $action = 'install' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// admin capabilities
		add_role( 'football_pool_admin', 'Football Pool Admin', 
					array(
						'read' => true,
						'manage_football_pool' => true,
					)
		);
		
		$role = get_role( 'administrator' );
		$role->add_cap( 'manage_football_pool' );
		$role = get_role( 'editor' );
		$role->add_cap( 'manage_football_pool' );
		// end admin capabilities
		
		$action = empty( $action ) ? 'install' : $action;
		
		// default plugin options
		global $current_user;
		get_currentuserinfo();
		
		// $matches = new Football_Pool_Matches();
		// $first_match = $matches->get_first_match_info();
		// $matchdate = new DateTime( $first_match['play_date'] );
		// $date = new DateTime( Football_Pool_Utils::date_from_gmt( $matchdate->format( 'Y-m-d H:i' ) ) );
		// $date = new DateTime( $matches->format_match_time( $matchdate, 'Y-m-d H:i' ) );
		$date = new DateTime();
		// Translators: this is a date format string (see http://php.net/date)
		$date_formatted = date_i18n( __( 'j F', FOOTBALLPOOL_TEXT_DOMAIN )
										, $date->format( 'U' ) );
		
		$options = array();
		$options['webmaster'] = $current_user->user_email;
		$options['money'] = '5 euro';
		$options['bank'] = $current_user->user_login;
		$options['start'] = $date_formatted;
		$options['fullpoints'] = FOOTBALLPOOL_FULLPOINTS;
		$options['totopoints'] = FOOTBALLPOOL_TOTOPOINTS;
		$options['goalpoints'] = FOOTBALLPOOL_GOALPOINTS;
		$options['diffpoints'] = FOOTBALLPOOL_DIFFPOINTS;
		$options['joker_multiplier'] = FOOTBALLPOOL_JOKERMULTIPLIER;
		$options['maxperiod'] = FOOTBALLPOOL_MAXPERIOD;
		$options['use_leagues'] = 1; // 1: yes, 0: no
		$options['shoutbox_max_chars'] = FOOTBALLPOOL_SHOUTBOX_MAXCHARS;
		$options['hide_admin_bar'] = 1; // 1: yes, 0: no
		$options['default_league_new_user'] = FOOTBALLPOOL_LEAGUE_DEFAULT;
		$options['dashboard_image'] = FOOTBALLPOOL_ASSETS_URL . 'admin/images/dashboardwidget.png';
		$options['matches_locktime'] = '';
		$options['bonus_question_locktime'] = '';
		$options['keep_data_on_uninstall'] = 0; // 1: yes, 0: no
		$options['use_favicon'] = 0; // 1: yes, 0: no
		$options['use_touchicon'] = 0; // 1: yes, 0: no
		$options['stop_time_method_matches'] = 0; // 0: dynamic, 1: one stop date
		$options['stop_time_method_questions'] = 0; // 0: dynamic, 1: one stop date
		$options['show_team_link'] = 1; // 1: yes, 0: no
		// $options['show_team_link_use_external'] = 0; // 1: yes, 0: no
		$options['show_venues_on_team_page'] = 1; // 1: yes, 0: no
		$options['use_charts'] = 0; // 1: yes, 0: no
		$options['export_format'] = 0; // 0: full, 1: minimal
		$options['match_time_display'] = 0; // 0: WP setting, 1: UTC, 2: custom
		$options['match_time_offset'] = 0; // time in seconds to add to the start time in the database (negative value for substraction)
		$options['csv_file_filter'] = '*'; // defaults to 'all files'
		$options['add_tinymce_button'] = 1; // 1: button, 0: disable button
		$options['always_show_predictions'] = 0; // 1: yes, 0: no
		$options['use_spin_controls'] = 0; // 1: yes, 0: no
		$options['groups_page_match_types'] = array( FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE ); // array of match type ids
		$options['match_sort_method'] = FOOTBALLPOOL_MATCH_SORT; // 0: date asc, 1: date desc
		$options['auto_calculation'] = 1; // 1: yes, 0: no
		$options['ranking_display'] = 0; // 0: default, 1: user decides, 2: admin decides
		$options['show_ranking'] = FOOTBALLPOOL_RANKING_DEFAULT;
		$options['prediction_type'] = 0; // 0: score, 1: winner/draw
		$options['prediction_type_draw'] = 1; // 1: also include draw as an option, 0: only home and away team
		$options['team_points_win'] = FOOTBALLPOOL_TEAM_POINTS_WIN;
		$options['team_points_draw'] = FOOTBALLPOOL_TEAM_POINTS_DRAW;
		$options['listing_show_team_thumb'] = 1; // 1: yes, 0: no
		$options['listing_show_venue_thumb'] = 1; // 1: yes, 0: no
		$options['listing_show_team_comments'] = 1; // 1: yes, 0: no
		$options['listing_show_venue_comments'] = 1; // 1: yes, 0: no
		$options['number_of_jokers'] = FOOTBALLPOOL_DEFAULT_JOKERS;
		$options['calculation_type_preference'] = FOOTBALLPOOL_RANKING_CALCULATION_SMART;
		$options['show_num_predictions_in_ranking'] = 0; // 1: yes, 0: no
		$options['redirect_url_after_login'] = home_url(); // redirect users to this page_id after login
		
		foreach ( $options as $key => $value ) {
			Football_Pool_Utils::update_fp_option( $key, $value, 'keep existing values' );
		}
		
		// install custom tables in database
		$install_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/install.txt' ) );
		self::db_actions( $install_sql );
		
		if ( $action == 'install' ) {
			// don't (re)install data if user option is set to keep all data (option = 1)
			if ( Football_Pool_Utils::get_fp_option( 'keep_data_on_uninstall', 0, 'int' ) == 0 ) {
				$sql = "INSERT INTO `{$prefix}leagues` ( `name`, `user_defined`, `image` ) VALUES
						( '" . __( 'all users', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 0, '' ),
						( '" . __( 'for money', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, 'league-money-green.png' ),
						( '" . __( 'for free', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, '' );";
				$wpdb->query( $sql );
				$sql = $wpdb->prepare( "INSERT INTO `{$prefix}rankings` ( `id`, `name`, `user_defined` ) 
										VALUES ( %d, %s, 0 );"
										, FOOTBALLPOOL_RANKING_DEFAULT
										, __( 'default ranking', FOOTBALLPOOL_TEXT_DOMAIN )
								);
				$wpdb->query( $sql );
			}
		} elseif ( $action == 'update' ) {
			/** UPDATES FOR PREVIOUS VERSIONS **/
			if ( ! self::is_at_least_version( '2.0.0' ) ) {
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update.txt' ) );
				self::db_actions( $update_sql );
				
				delete_option( 'footballpool_show_admin_bar' );
				delete_option( 'footballpool_force_locktime' );
				
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update-2.0.0.txt' ) );
				self::db_actions( $update_sql );
			}
			if ( ! self::is_at_least_version( '2.1.0' ) ) {
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update-2.1.0.txt' ) );
				self::db_actions( $update_sql );
			}
			if ( ! self::is_at_least_version( '2.2.0' ) ) {
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update-2.2.0.txt' ) );
				self::db_actions( $update_sql );
				$update_sql = sprintf( "UPDATE {$prefix}scorehistory SET ranking_id = %d 
										WHERE ranking_id IS NULL"
										, FOOTBALLPOOL_RANKING_DEFAULT
								);
				self::db_actions( $update_sql );
				// update plugin options to new format
				foreach ( self::$pages as $page ) {
					// migrate page_id values
					$options["page_id_{$page['slug']}"] = 0;
				}
				foreach ( $options as $key => $value ) {
					$option_value = get_option( "footballpool_{$key}", 'option not found' );
					if ( $option_value != 'option not found' ) {
						$options[$key] = $option_value;
						delete_option( "footballpool_{$key}" );
					}
				}
				// change behaviour of tinymce option
				$options['add_tinymce_button'] = 
									( (int) get_option( 'footballpool_no_tinymce', 1 ) == 1 ) ? 0 : 1;
				delete_option( 'footballpool_no_tinymce' );
				delete_option( 'footballpool_db_version' );
				update_option( FOOTBALLPOOL_OPTIONS, $options );
			}
			if ( ! self::is_at_least_version( '2.3.0' ) ) {
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update-2.3.0.txt' ) );
				self::db_actions( $update_sql );
			}
			if ( ! self::is_at_least_version( '2.4.0' ) ) {
				$update_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/update-2.4.0.txt' ) );
				self::db_actions( $update_sql );
			}
			/** END UPDATES **/
		}
		
		// don't (re)install data if user option is set to keep all data (option = 1)
		if ( Football_Pool_Utils::get_fp_option( 'keep_data_on_uninstall', 0, 'int' ) == 0 ) {
			// create pages
			$locale = self::get_locale();
			$domain = FOOTBALLPOOL_TEXT_DOMAIN;
			
			// first look for a translated text of the rules page in the languages folder of WP
			$file = WP_LANG_DIR . "/{$domain}/rules-page-content-{$locale}.txt";
			if ( ! file_exists( $file ) ) {
				// if no file found, then check the plugin's languages folder
				$file = FOOTBALLPOOL_PLUGIN_DIR . "languages/rules-page-content-{$locale}.txt";
				if ( ! file_exists( $file ) ) {
					// no translation available, revert to default English text
					$file = FOOTBALLPOOL_PLUGIN_DIR . 'languages/rules-page-content.txt';
				}
			}
			self::$pages['rules']['text'] = self::read_from_file( $file );
			foreach ( self::$pages as $page ) {
				self::create_page($page);
			}
		}
		
		// if this is a first time install, set the 'keep_data_on_uninstall' to true
		$plugin_ver = self::get_db_version();
		if ( $plugin_ver === false ) Football_Pool_Utils::update_fp_option( 'keep_data_on_uninstall', 1 );
		
		// all database installs and updates are finished, so update the db version value
		Football_Pool_Utils::update_fp_option( 'db_version', FOOTBALLPOOL_DB_VERSION );
	}
	
	// checks if plugin is at least a certain version (makes sure it has sufficient comparison decimals)
	// based on http://wikiduh.com/1611/php-function-to-check-if-wordpress-is-at-least-version-x-y-z
	private static function is_at_least_version( $is_ver ) {
		$plugin_ver = explode( '.', self::get_db_version() );
		$is_ver = explode( '.', $is_ver );
		for ( $i = 0; $i <= count( $is_ver ); $i++ )
			if( ! isset( $plugin_ver[$i] ) ) array_push( $plugin_ver, 0 );
	 
		foreach ( $is_ver as $i => $is_val )
			if ( (int) $plugin_ver[$i] < (int) $is_val ) return false;
		
		return true;
	}
	
	public static function update_db_check() {
		if ( self::get_db_version() != FOOTBALLPOOL_DB_VERSION ) {
			self::activate( 'update' );
		}
	}
	
	private static function get_db_version() {
		// new style options
		$db_version = Football_Pool_Utils::get_fp_option( 'db_version', false );
		// old style options
		if ( ! $db_version ) $db_version = get_option( 'footballpool_db_version' );
		
		return $db_version;
	}
	
	public static function deactivate() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// remove added admin privileges
		remove_role( 'football_pool_admin' );
		$role = get_role( 'administrator' );
		$role->remove_cap( 'manage_football_pool' );
		$role = get_role( 'editor' );
		$role->remove_cap( 'manage_football_pool' );
		
		// only delete data if user option is set to remove all data (option = 0, which is the default)
		if ( Football_Pool_Utils::get_fp_option( 'keep_data_on_uninstall', 0, 'int' ) == 0 ) {
			// delete custom tables from database
			$uninstall_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/uninstall.txt' ) );
			self::db_actions( $uninstall_sql );
			
			// delete pages
			foreach ( self::$pages as $page ) {
				wp_delete_post( Football_Pool_Utils::get_fp_option( 'page_id_' . $page['slug'] ), true );
			}
			
			// delete plugin options
			delete_option( FOOTBALLPOOL_OPTIONS );
			
			// delete custom user meta
			$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'footballpool%'" );
		}
	}
	
	public static function show_admin_bar( $content ) {
		// normal users do not get the admin bar after log in
		$no_show = current_user_can( 'subscriber' ) 
					&& Football_Pool_Utils::get_fp_option( 'hide_admin_bar', 1 ) == 1;
		
		return $no_show ? false : $content;
	}
	
	public static function get_locale() {
		$domain = FOOTBALLPOOL_TEXT_DOMAIN;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		return $locale;
	}
	
	public static function init() {
		// i18n support:
		//   http://www.geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$domain = FOOTBALLPOOL_TEXT_DOMAIN;
		$locale = self::get_locale();
		
		$path_to_custom_mo = WP_LANG_DIR . '/' . $domain . '/'. $domain . '-' . $locale . '.mo';
		load_textdomain( $domain, $path_to_custom_mo );
		
		$path_to_plugin_language_files = $domain . '/languages';
		load_plugin_textdomain( $domain, false, $path_to_plugin_language_files );
		// end i18n
		
		if ( ! wp_script_is( 'jquery', 'queue' ) ) {
			wp_enqueue_script( "jquery" );
		}
		
		if ( ! is_admin() ) {
			// the frontend
			
			if ( Football_Pool_Utils::get_fp_option( 'use_charts', 0, 'int' ) == 1 ) {
				//highcharts
				$highcharts_url = plugins_url() . FOOTBALLPOOL_HIGHCHARTS_API;
				$highcharts_dir = WP_PLUGIN_DIR . FOOTBALLPOOL_HIGHCHARTS_API;
				self::include_js( $highcharts_url, 'js-highcharts', null, false, $highcharts_dir );
				self::include_js( 'assets/pool-charts.min.js', 'js-pool-charts', array( 'jquery', 'js-pool' ) );
			}
			
			// pool js & css
			self::include_css( 'assets/pool.css', 'css-pool' );
			self::include_js( 'assets/pool.min.js', 'js-pool', array( 'jquery' ) );
			// localized countdown code
			wp_localize_script( 'js-pool'
								, 'FootballPool_i18n'
								, array(
									'count_second' => __( 'second', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_seconds' => __( 'seconds', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_day' => __( 'day', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_days' => __( 'days', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_hour' => __( 'hour', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_hours' => __( 'hours', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_minute' => __( 'minute', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_minutes' => __( 'minutes', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_pre_before' => __( 'Wait ', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_post_before' => __( ' before the tournament starts', FOOTBALLPOOL_TEXT_DOMAIN ),
									'count_pre_after' => '',
									'count_post_after' => __( ' ago the tournament started.', FOOTBALLPOOL_TEXT_DOMAIN ),
								)
			);
		} else {
			// the admin
			
			// global admin js & css
			self::include_css( 'assets/admin/admin.css', 'css-pool-admin' );
			self::include_js( 'assets/admin/admin.min.js', 'js-pool-admin'
								, array( 
										'jquery', 
										'jquery-ui-core', 
										'jquery-ui-progressbar', 
										) 
							);
			
			self::include_css( 'assets/admin/jquery-ui/jquery-ui-1.10.4.custom.min.css'
								, 'css-pool-admin-custom-jquery-ui' );
			wp_localize_script( 'js-pool-admin'
								, 'FootballPoolAjax'
								, array( 
									'fp_recalc_nonce' => wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC ),
									'colorbox_close' => __( 'close', FOOTBALLPOOL_TEXT_DOMAIN ),
									'colorbox_html' => '',
									'error_message' => __( 'Something went wrong while (re)calculating the scores. See the <a href="?page=footballpool-help#ranking-calculation">help page</a> for details on solving this problem.', FOOTBALLPOOL_TEXT_DOMAIN ),
									'error_label' => __( 'Error message', FOOTBALLPOOL_TEXT_DOMAIN )
								)
			);
			
			// datetimepicker
			self::include_css( 'assets/admin/datetimepicker/jquery.datetimepicker.css', 'css-datetimepicker' );
			self::include_js( 'assets/admin/datetimepicker/jquery.datetimepicker.js'
								, 'js-datetimepicker', array( 'jquery' ) );
		}
		
		// colorbox jQuery plugin for lightboxes
		self::include_js( 'assets/colorbox/jquery.colorbox-min.js', 'js-colorbox', array( 'jquery' ) );
		self::include_css( 'assets/colorbox/colorbox.css', 'css-colorbox' );
	}
	
	public static function get_page_link( $slug ) {
		$id = Football_Pool_Utils::get_fp_option( 'page_id_' . $slug );
		return $id && get_post( $id ) ? get_page_link( $id ) : '';
	}
	
	public static function new_pool_user( $user_id ) {
		// add extra meta fields
		$default_league = Football_Pool_Utils::get_fp_option( 'default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT, 'ínt' );
		$league = Football_Pool_Utils::post_int( 'league', $default_league );
		
		update_user_meta( $user_id, 'footballpool_league', $default_league );
		update_user_meta( $user_id, 'footballpool_registeredforleague', $league );
		
		$payed = Football_Pool_Utils::post_int( 'payed', 0 );
		update_user_meta( $user_id, 'footballpool_payed', $payed );
		
		self::update_user_custom_tables( $user_id, $default_league );
		do_action( 'footballpool_new_user', $user_id, $league );
	}
	
	public static function player_login_redirect( $redirect_to, $request, $user ){
		//is there a user to check?
		global $user;
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			//check for non admins
			if ( ! in_array( 'administrator', $user->roles ) ) {
				$plugin_option = Football_Pool_Utils::get_fp_option( 'redirect_url_after_login', home_url() );
				if ( $plugin_option != '' ) {
					$default_url = apply_filters( 'footballpool_login_redirect_url', $plugin_option );
					$redirect_to = ( $request == admin_url() ) ? $default_url : $request;
				}
			}
		}
		
		return $redirect_to;
	}
	
	public static function update_user_custom_tables( $user_id, $league_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		if ( $pool->has_leagues ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( user_id, league_id ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE league_id = %d" 
									, $user_id
									, $league_id
									, $league_id
							);
			$wpdb->query( $sql );
		}
	}
	
	public static function registration_form_extra_fields() {
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			echo '<p><label for="league">', __( 'Play in league', FOOTBALLPOOL_TEXT_DOMAIN ), '<br>', 
				$pool->league_select( 0, 'league' ), '</label></p><p><br></p>';
		}
	}
	
	public static function registration_form_post() {
		// handle the registration
	}
	
	public static function registration_check_fields( $errors ) {
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			// check if the new player picked a league to play in
			if ( Football_Pool_Utils::post_int( 'league', 0 ) == 0 ) {
				$errors->add( 'league_error', __( '<strong>ERROR:</strong> You must choose a league to play in!', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
		}
		return $errors;
	}
	
	// the dashboard can be a bit confusing for new users, so add a widget for an easy way to click to the homepage
	public static function dashboard_widget() {
		$img = Football_Pool_Utils::get_fp_option( 'dashboard_image' );
		
		echo '<p>', __( 'Click below to go to the football pool and predict your scores. Good luck!', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
		echo '<p style="text-align:center"><a href="', Football_Pool::get_page_link( 'pool' ), '"><img src="', $img, '" alt="', __( 'Fill in your predictions.', FOOTBALLPOOL_TEXT_DOMAIN ), '" /></a></p>';
	}
	
	public static function add_dashboard_widgets() {
		wp_add_dashboard_widget( 
				'fp_dashboard_widget', 
				__( 'Start immediately with your predictions', FOOTBALLPOOL_TEXT_DOMAIN ), 
				array( 'Football_Pool', 'dashboard_widget' )
		);
		
		// http://codex.wordpress.org/Dashboard_Widgets_API#Advanced:_Forcing_your_widget_to_the_top
		global $wp_meta_boxes;
		
		// Get the regular dashboard widgets array 
		// (which has our new widget already but at the end)
		$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		
		// Backup and delete our new dashbaord widget from the end of the array
		$widget_backup = array( 'fp_dashboard_widget' => $normal_dashboard['fp_dashboard_widget'] );
		unset( $normal_dashboard['fp_dashboard_widget'] );

		// Merge the two arrays together so our widget is at the beginning
		$sorted_dashboard = array_merge( $widget_backup, $normal_dashboard );

		// Save the sorted array back into the original metaboxes 
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;	
	} 
	
	public static function the_content( $content ) {
		if ( is_page() && is_main_query() ) { // http://pippinsplugins.com/playing-nice-with-the-content-filter/
			$page_id = get_the_ID();
			switch ( $page_id ) {
				case Football_Pool_Utils::get_fp_option( 'page_id_ranking' ):
					$page = new Football_Pool_Ranking_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_teams' ):
					$page = new Football_Pool_Teams_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_stadiums' ):
					$page = new Football_Pool_Stadiums_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_groups' ):
					$page = new Football_Pool_Groups_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_statistics' ):
					$page = new Football_Pool_Statistics_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_tournament' ):
					$page = new Football_Pool_Tournament_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_user' ):
					$page = new Football_Pool_User_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_pool' ):
					$page = new Football_Pool_Pool_Page();
					$content .= apply_filters( 'footballpool_pages_html', $page->page_content(), $page_id );
					break;
				default:
					// nothing
			}
		}
		
		return $content;
	}
	
	// http://codex.wordpress.org/Template_Tags/wp_title#Customizing_with_the_filter
	public static function change_wp_title( $title, $sep ) {
		if ( is_page() && is_main_query() ) { // http://pippinsplugins.com/playing-nice-with-the-content-filter/
			$page_id = get_the_ID();
			switch ( $page_id ) {
				case Football_Pool_Utils::get_fp_option( 'page_id_teams' ):
					$team = new Football_Pool_Team( Football_Pool_Utils::get_int( 'team' ) );
					if ( $team->id != 0 ) $title = "{$team->name} {$sep} {$title}"; 
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_stadiums' ):
					$stadium = new Football_Pool_Stadium( Football_Pool_Utils::get_int( 'stadium' ) );
					if ( $stadium->id != 0 ) $title = "{$stadium->name} {$sep} {$title}"; 
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_groups' ):
					$group = Football_Pool_Groups::get_group_by_id( Football_Pool_Utils::get_int( 'group' ) );
					if ( $group != null ) $title = "{$group->name} {$sep} {$title}"; 
					break;
			}
		}
		
		return $title;
	}
	
	// if theme supports the wp_head action then add some images
	public static function change_html_head() {
		$assets_dir = esc_url( FOOTBALLPOOL_ASSETS_URL . 'images/site/' );
		
		if ( Football_Pool_Utils::get_fp_option( 'use_favicon' ) == 1 ) {
			// made with http://iconifier.net/
			echo "\n<link rel='shortcut icon' href='{$assets_dir}favicon.ico' type='image/x-icon' />";
		}
		
		if ( Football_Pool_Utils::get_fp_option( 'use_touchicon' ) == 1 ) {
			// made with http://iconifier.net/
			echo "\n<link rel='apple-touch-icon' href='{$assets_dir}apple-touch-icon.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='57x57' href='{$assets_dir}apple-touch-icon-57x57.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='72x72' href='{$assets_dir}apple-touch-icon-72x72.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='76x76' href='{$assets_dir}apple-touch-icon-76x76.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='114x114' href='{$assets_dir}apple-touch-icon-114x114.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='120x120' href='{$assets_dir}apple-touch-icon-120x120.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='144x144' href='{$assets_dir}apple-touch-icon-144x144.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='152x152' href='{$assets_dir}apple-touch-icon-152x152.png' />";
		}
	}
	
	public static function admin_notice() {
		if ( ! is_admin() || ! current_user_can( 'install_plugins' ) ) return;
		
		global $pagenow;
		
		if ( $pagenow == 'plugins.php' || $pagenow == 'update-core.php' || $pagenow == 'update.php' ) {
			$chart = new Football_Pool_Chart;
			if ( $chart->stats_enabled && ! $chart->API_loaded ) {
				$notice = '<strong>' . sprintf( __( 'Football Pool', FOOTBALLPOOL_TEXT_DOMAIN ) 
						. ':</strong> ' . __( 'Charts are enabled but Highcharts API was not found! See <a href="%s">Help page</a> for details.', FOOTBALLPOOL_TEXT_DOMAIN ), 'admin.php?page=footballpool-help#charts' );
				Football_Pool_Admin::notice( $notice , 'important' );
			}
		} 
	}
	
//======================================================================================================//
	
	private static function include_css( $file, $handle, $deps = null, $forced_exit = true
										, $custom_path = '', $external = false, $pages = null ) {
		$external = ( $external === 'external' );
		if ( $external || $custom_path != '' ) {
			$url = $external ? esc_url_raw( $file ) : $file;
			$dir = $custom_path;
		} else {
			$url = FOOTBALLPOOL_PLUGIN_URL . $file;
			$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		}
		
		if ( $external || file_exists( $dir ) ) {
			wp_register_style( $handle, $url, $deps, FOOTBALLPOOL_DB_VERSION );
			wp_enqueue_style( $handle );
		} else {
            if ( $forced_exit ) wp_die( $dir . ' not found' );
		}
	}
	
	private static function include_js( $file, $handle, $deps = null, $forced_exit = true, $custom_path = ''
								, $external = false, $pages = null ) {
		$external = ( $external === 'external' );
		if ( $external || $custom_path != '' ) {
			$url = $external ? esc_url_raw( $file ) : $file;
			$dir = $custom_path;
		} else {
			$url = FOOTBALLPOOL_PLUGIN_URL . $file;
			$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		}
		
		if ( $external || file_exists( $dir ) ) {
			wp_register_script( $handle, $url, $deps, FOOTBALLPOOL_DB_VERSION );
			wp_enqueue_script( $handle );
		} else {
            if ( $forced_exit ) wp_die( $dir . ' not found' );
		}
	}
	
	private static function create_page( $page, $menu_order = null ) {
		if ( Football_Pool_Utils::get_fp_option( "page_id_{$page['slug']}", false ) === false ) {
			global $current_user;
			
			$newpage = array();
			$newpage['post_title'] = __( $page['title'], FOOTBALLPOOL_TEXT_DOMAIN );
			$newpage['post_name'] = $page['slug'];
			$newpage['post_content'] = isset( $page['text'] ) ? $page['text'] : '';
			$newpage['post_status'] = 'publish';
			$newpage['post_type'] = 'page';
			$newpage['post_author'] = $current_user->ID;
			if ( isset( $menu_order ) ) {
				$newpage['menu_order'] = $menu_order;
			}
			if ( isset( $page['parent'] ) ) {
				$parent_ID = (int) Football_Pool_Utils::get_fp_option( "page_id_{$page['parent']}" );
				if ( $parent_ID ) {
					$newpage['post_parent'] = $parent_ID;
				}
			}
			if ( isset( $page['comment'] ) ) {
				$newpage['comment_status'] = $page['comment'];
			}
			$page_id = wp_insert_post( $newpage );
			
			Football_Pool_Utils::update_fp_option( "page_id_{$page['slug']}", $page_id );
			return $page_id;
		}
	}
	
	private static function read_from_file( $file ) {
		if ( file_exists( $file ) ) {
			return file_get_contents( $file );
		} else {
			wp_die( $file . ' not found' );
		}
	}
	
	// replaces {$prefix} in string with actual database prefix
	private static function prepare( $sql ) {
		return str_replace( '{$prefix}', FOOTBALLPOOL_DB_PREFIX, $sql );
	}
	
	private static function db_actions( $text ) {
		global $wpdb;
		$array = explode( ';', $text );
		if ( count( $array ) > 0 ) {
			foreach ( $array as $sql ) {
				// check if string contains data other than spaces, tabs and/or newlines
				$check = str_replace( array( " ", "\n", "\r", "\t" ), "", $sql );
				if ( ! empty( $check ) ) {
					$wpdb->query( $sql );
				}
			}
		}
	}
}
