<?php
// dummy var for translation files
$fp_translate_this = __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'teams', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'groups', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'venues', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'rules', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'prediction', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'ranking', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'charts', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'predictions', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool {
	private static $pages = array(
						array( 'slug' => 'tournament', 'title' => 'matches', 'comment' => 'closed' ),
							array( 'slug' => 'teams', 'title' => 'teams', 'parent' => 'tournament', 'comment' => 'closed' ),
							array( 'slug' => 'groups', 'title' => 'groups', 'parent' => 'tournament', 'comment' => 'closed' ),
							array( 'slug' => 'stadiums', 'title' => 'venues', 'parent' => 'tournament', 'comment' => 'closed' ),
						'rules' => array( 'slug' => 'rules', 'title' => 'rules', 'text' => '' ),
						array( 'slug' => 'pool', 'title' => 'prediction', 'comment' => 'closed' ),
						array( 'slug' => 'ranking', 'title' => 'ranking', 'comment' => 'closed' ),
						array( 'slug' => 'statistics', 'title' => 'charts', 'comment' => 'closed' ),
						array( 'slug' => 'user', 'title' => 'predictions', 'comment' => 'closed' )
					);
	
	public function __construct() {}
	
	public function get_pages() {
		return self::$pages;
	}
	
	public function activate( $action = 'install' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$action = empty( $action ) ? 'install' : $action;
		
		// install custom tables in database
		$install_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/install.txt' ) );
		self::db_actions( $install_sql );
		
		if ( $action == 'install' ) {
			$sql = "INSERT INTO `{$prefix}leagues` (`name`, `userDefined`, `image`) VALUES
					('" . __( 'all users', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 0, ''),
					('" . __( 'for money', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, 'league-money-green.png'),
					('" . __( 'for free', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, '');";
			$wpdb->query( $sql );
		} elseif ( $action == 'update' ) {
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
		}
		
		// define default plugin options
		global $current_user;
		get_currentuserinfo();
		
		$matches = new Football_Pool_Matches();
		$first_match = $matches->get_first_match_info();
		$date = date( 'j F', $first_match['match_timestamp'] );
		
		add_option( 'footballpool_webmaster', $current_user->user_email );
		add_option( 'footballpool_money', '5 euro' );
		add_option( 'footballpool_bank', $current_user->user_login );
		add_option( 'footballpool_start', $date );
		add_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS );
		add_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS );
		add_option( 'footballpool_goalpoints', FOOTBALLPOOL_GOALPOINTS );
		add_option( 'footballpool_maxperiod', FOOTBALLPOOL_MAXPERIOD );
		add_option( 'footballpool_use_leagues', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_shoutbox_max_chars', FOOTBALLPOOL_SHOUTBOX_MAXCHARS );
		add_option( 'footballpool_hide_admin_bar', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT );
		add_option( 'footballpool_dashboard_image', FOOTBALLPOOL_ASSETS_URL . 'admin/images/dashboardwidget.png' );
		add_option( 'footballpool_matches_locktime', '' );
		add_option( 'footballpool_bonus_question_locktime', '' );
		// add_option( 'footballpool_remove_data_on_uninstall', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_use_favicon', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_use_touchicon', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_stop_time_method_matches', 0 ); // 0: dynamic, 1: one stop date
		add_option( 'footballpool_stop_time_method_questions', 0 ); // 0: dynamic, 1: one stop date
		add_option( 'footballpool_show_team_link', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_show_venues_on_team_page', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_use_charts', 0 ); // 1: yes, 0: no
		add_option( 'footballpool_export_format', 0 ); // 0: full, 1: minimal
		add_option( 'footballpool_match_time_display', 0 ); // 0: WP setting, 1: UTC, 2: custom
		add_option( 'footballpool_match_time_offset', 0 ); // time in seconds to add to the start time in the database (negative value for substraction)
		add_option( 'footballpool_csv_file_filter', '*' ); // defaults to 'all files'
		add_option( 'footballpool_no_tinymce', 0 ); // 1: yes (disabled), 0: no (enable button)
		
		update_option( 'footballpool_db_version', FOOTBALLPOOL_DB_VERSION );

		// create pages
		$locale = self::get_locale();
		if ( file_exists( FOOTBALLPOOL_PLUGIN_DIR . "languages/rules-page-content-{$locale}.txt" ) ) {
			$file = FOOTBALLPOOL_PLUGIN_DIR . "languages/rules-page-content-{$locale}.txt";
		} else {
			$file = FOOTBALLPOOL_PLUGIN_DIR . 'languages/rules-page-content.txt';
		}
		$rules_text = self::read_from_file( $file );
		self::$pages['rules']['text'] = $rules_text;
		foreach ( self::$pages as $page ) {
			self::create_page($page);
		}
	}
	
	// checks if plugin is at least a certain version (makes sure it has sufficient comparison decimals)
	// based on http://wikiduh.com/1611/php-function-to-check-if-wordpress-is-at-least-version-x-y-z
	private function is_at_least_version( $is_ver ) {
		$plugin_ver = explode( '.', Football_Pool_Utils::get_fp_option( 'db_version' ) );
		$is_ver = explode( '.', $is_ver );
		for ( $i = 0; $i <= count( $is_ver ); $i++ )
			if( ! isset( $plugin_ver[$i] ) ) array_push( $plugin_ver, 0 );
	 
		foreach ( $is_ver as $i => $is_val )
			if ( (int) $plugin_ver[$i] < (int) $is_val ) return false;
		
		return true;
	}
	
	public function update_db_check() {
		if ( get_site_option( 'footballpool_db_version' ) != FOOTBALLPOOL_DB_VERSION ) {
			self::activate( 'update' );
		}
	}
	
	public function deactivate() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// delete custom tables from database
		$uninstall_sql = self::prepare( self::read_from_file( FOOTBALLPOOL_PLUGIN_DIR . 'data/uninstall.txt' ) );
		self::db_actions( $uninstall_sql );
		
		// delete plugin options
		delete_option( 'footballpool_webmaster' );
		delete_option( 'footballpool_money' );
		delete_option( 'footballpool_bank' );
		delete_option( 'footballpool_start' );
		delete_option( 'footballpool_totopoints' );
		delete_option( 'footballpool_fullpoints' );
		delete_option( 'footballpool_goalpoints' );
		delete_option( 'footballpool_maxperiod' );
		delete_option( 'footballpool_use_leagues' );
		delete_option( 'footballpool_db_version' );
		delete_option( 'footballpool_shoutbox_max_chars' );
		delete_option( 'footballpool_hide_admin_bar' );
		delete_option( 'footballpool_default_league_new_user' );
		delete_option( 'footballpool_dashboard_image' );
		delete_option( 'footballpool_matches_locktime' );
		delete_option( 'footballpool_bonus_question_locktime' );
		// delete_option( 'footballpool_remove_data_on_uninstall' );
		delete_option( 'footballpool_use_favicon' );
		delete_option( 'footballpool_use_touchicon' );
		delete_option( 'footballpool_stop_time_method_matches' );
		delete_option( 'footballpool_stop_time_method_questions' );
		delete_option( 'footballpool_show_team_link' );
		delete_option( 'footballpool_show_venues_on_team_page' );
		delete_option( 'footballpool_use_charts' );
		delete_option( 'footballpool_export_format' );
		delete_option( 'footballpool_match_time_display' );
		delete_option( 'footballpool_match_time_offset' );
		delete_option( 'footballpool_csv_file_filter' );
		delete_option( 'footballpool_no_tinymce' );
		
		// delete pages
		foreach ( self::$pages as $page ) {
			wp_delete_post( Football_Pool_Utils::get_fp_option( 'page_id_' . $page['slug'] ), true );
			delete_option( 'footballpool_page_id_' . $page['slug'] );
		}
		
		// delete custom user meta
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'footballpool%'" );
	}

	public function show_admin_bar( $content ) {
		// normal users do not get the admin bar after log in
		$no_show = current_user_can( 'subscriber' ) 
					&& Football_Pool_Utils::get_fp_option( 'hide_admin_bar', 1 ) == 1;
		
		return $no_show ? false : $content;
	}
	
	public function get_locale() {
		$domain = FOOTBALLPOOL_TEXT_DOMAIN;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		return $locale;
	}
	
	public function init() {
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
		
		if ( !is_admin() ) {
			if ( Football_Pool_Utils::get_fp_option( 'use_charts', 0, 'int' ) == 1 ) {
				//highcharts
				$highcharts_url = plugins_url() . FOOTBALLPOOL_HIGHCHARTS_API;
				$highcharts_dir = WP_PLUGIN_DIR . FOOTBALLPOOL_HIGHCHARTS_API;
				self::include_js( $highcharts_url, 'js-highcharts', false, $highcharts_dir );
			}
			
			//fancybox -> replaced with colorbox because of license problem
			// self::include_js( 'assets/fancybox/jquery.fancybox.js', 'js-fancybox' );
			// self::include_css( 'assets/fancybox/jquery.fancybox.css', 'css-fancybox' );
			self::include_js( 'assets/colorbox/jquery.colorbox-min.js', 'js-colorbox' );
			self::include_css( 'assets/colorbox/colorbox.css', 'css-colorbox' );
			
			//pool js
			self::include_js( 'assets/pool.js', 'js-pool' );
			//pool css
			self::include_css( 'assets/pool.css', 'css-pool' );
			
			//extra countdown code
			add_action( 'wp_head', array( 'Football_Pool', 'countdown_texts' ) );
		} else {
			// image uploader scripts
			if ( ! wp_script_is( 'media-upload', 'queue' ) ) {
				wp_enqueue_script('media-upload');
			}
			if ( ! wp_script_is( 'thickbox', 'queue' ) ) {
				wp_enqueue_script('thickbox');
			}
			if ( ! wp_style_is( 'thickbox', 'queue' ) ) {
				wp_enqueue_style('thickbox');
			}
			
			// admin css
			self::include_css( 'assets/admin/admin.css', 'css-admin' );
			// admin js
			self::include_js( 'assets/admin/admin.js', 'js-admin' );
		}
	}
	
	public function the_content( $content ) {
		if ( is_page() ) {
			$page_ID = get_the_ID();
			switch ( $page_ID ) {
				case Football_Pool_Utils::get_fp_option( 'page_id_ranking' ):
					$page = new Football_Pool_Ranking_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_teams' ):
					$page = new Football_Pool_Teams_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_stadiums' ):
					$page = new Football_Pool_Stadiums_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_groups' ):
					$page = new Football_Pool_Groups_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_statistics' ):
					$page = new Football_Pool_Statistics_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_tournament' ):
					$page = new Football_Pool_Tournament_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_user' ):
					$page = new Football_Pool_User_Page();
					$content .= $page->page_content();
					break;
				case Football_Pool_Utils::get_fp_option( 'page_id_pool' ):
					$page = new Football_Pool_Pool_Page();
					$content .= $page->page_content();
					break;
				default:
					// nothing
			}
		}
		
		return $content;
	}
	
	public function get_page_link( $slug ) {
		$id = Football_Pool_Utils::get_fp_option( 'page_id_' . $slug );
		return $id ? get_page_link( $id ) : '';
	}
	
	public function new_pool_user( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// add extra meta fields
		$default_league = Football_Pool_Utils::get_fp_option( 'default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT, 'ínt' );
		$league = Football_Pool_Utils::post_int( 'league', $default_league );
		
		update_user_meta( $user_id, 'footballpool_league', $default_league );
		update_user_meta( $user_id, 'footballpool_registeredforleague', $league );
		
		$payed = Football_Pool_Utils::post_int( 'payed', 0 );
		update_user_meta( $user_id, 'footballpool_payed', $payed );
		
		self::update_user_custom_tables( $user_id, $default_league );
	}
	
	private function update_user_custom_tables( $user_id, $league_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		if ( $pool->has_leagues ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users ( userId, leagueId ) 
									VALUES ( %d, %d )
									ON DUPLICATE KEY UPDATE leagueId = %d" 
									, $user_id
									, $league_id
									, $league_id
							);
			$wpdb->query( $sql );
		}
	}
	
	public function registration_form_extra_fields() {
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			echo '<p><label for="league">', __( 'Play in league', FOOTBALLPOOL_TEXT_DOMAIN ), '<br>', 
				$pool->league_select( 0, 'league' ), '</label></p><p><br></p>';
		}
	}
	
	public function registration_form_post() {
		// handle the registration
	}
	
	public function registration_check_fields( $errors ) {
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			// check if the new player picked a league to play in
			if (Football_Pool_Utils::post_int( 'league', 0 ) == 0 ) {
				$errors->add( 'league_error', __( '<strong>ERROR:</strong> You must choose a league to play in!', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
		}
		return $errors;
	}
	
	public function countdown_texts() {
		$text_second = __( 'second', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_seconds = __( 'seconds', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_day = __( 'day', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_days = __( 'days', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_hour = __( 'hour', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_hours = __( 'hours', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_minute = __( 'minute', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_minutes = __( 'minutes', FOOTBALLPOOL_TEXT_DOMAIN );
		
		$text_pre_before = __( 'Wait ', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_post_before = __( ' before the tournament starts', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_pre_after = '';
		$text_post_after = __( ' ago the tournament started.', FOOTBALLPOOL_TEXT_DOMAIN );
	
		echo "<script type='text/javascript'>
				var footballpool_countdown_extra_text = new Array();
				var footballpool_countdown_time_text = new Array();
				footballpool_countdown_time_text['second'] = '{$text_second}';
				footballpool_countdown_time_text['seconds'] = '{$text_seconds}';
				footballpool_countdown_time_text['day'] = '{$text_day}';
				footballpool_countdown_time_text['days'] = '{$text_days}';
				footballpool_countdown_time_text['hour'] = '{$text_hour}';
				footballpool_countdown_time_text['hours'] = '{$text_hours}';
				footballpool_countdown_time_text['minute'] = '{$text_minute}';
				footballpool_countdown_time_text['minutes'] = '{$text_minutes}';
				footballpool_countdown_extra_text['pre_before'] = '{$text_pre_before}';
				footballpool_countdown_extra_text['post_before'] = '{$text_post_before}';
				footballpool_countdown_extra_text['pre_after'] = '{$text_pre_after}';
				footballpool_countdown_extra_text['post_after'] = '{$text_post_after}';
				</script>";
	}
	
	// the dashboard can be a bit confusing for new users, so add a widget for an easy way to click to the homepage
	public function dashboard_widget() {
		$img = Football_Pool_Utils::get_fp_option( 'dashboard_image' );
		
		echo '<p>', __( 'Click below to go to the football pool and predict your scores. Good luck!', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
		echo '<p style="text-align:center"><a href="', Football_Pool::get_page_link( 'pool' ), '"><img src="', $img, '" alt="', __( 'Fill in your predictions.', FOOTBALLPOOL_TEXT_DOMAIN ), '" /></a></p>';
	}
	
	function add_dashboard_widgets() {
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
		$widget_backup = array('fp_dashboard_widget' => $normal_dashboard['fp_dashboard_widget']);
		unset($normal_dashboard['fp_dashboard_widget']);

		// Merge the two arrays together so our widget is at the beginning
		$sorted_dashboard = array_merge($widget_backup, $normal_dashboard);

		// Save the sorted array back into the original metaboxes 
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;	
	} 

	// if theme supports the wp_head action then add some images
	public function change_html_head() {
		$assets_dir = esc_url( FOOTBALLPOOL_ASSETS_URL . 'images/site/' );
		
		if ( Football_Pool_Utils::get_fp_option( 'use_favicon' ) == 1 ) {
			echo "\n<link rel='shortcut icon' href='{$assets_dir}favicon.ico' />";
		}
		
		if ( Football_Pool_Utils::get_fp_option( 'use_touchicon' ) == 1 ) {
			echo "\n<link rel='apple-touch-icon' href='{$assets_dir}apple-touch-icon-57x57.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='72x72' href='{$assets_dir}apple-touch-icon-ipad-72x72.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='114x114' href='{$assets_dir}apple-touch-icon-iphone4-114x114.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='144x144' href='{$assets_dir}apple-touch-icon-ipad-highres-144x144.png' />";
		}
	}
	
	// display a user's name including an optional label
	public function user_name( $id, $return = 'all' ) {
		$name = get_the_author_meta( 'display_name', $id );
		$label = get_the_author_meta( 'footballpool_user_label', $id );
		$wrap = sprintf( '<span class="user-label"> (%s)</span>', $label );
		
		$output = '';
		switch ( $return ) {
			case 'label':
				if ( $label != '' ) $output = $wrap;
				break;
			case 'label-only':
				$output = $label;
				break;
			case 'name':
				$output = $name;
				break;
			case 'all':
				$output = $name;
				if ( $label != '' ) $output .= $wrap;
		}
		
		return $output;
	}
	
//=============================================================================================================//
	
	private function include_css( $file, $handle, $forced_exit = true, $custom_path = '' ) {
		if ( $custom_path != '' ) {
			$url = $file;
			$dir = $custom_path;
		} else {
			$url = FOOTBALLPOOL_PLUGIN_URL . $file;
			$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		}
		
		if ( file_exists( $dir ) ) {
			wp_register_style( $handle, $url );
			wp_enqueue_style( $handle );
		} else {
            if ( $forced_exit ) wp_die( $dir . ' not found' );
		}
	}
	
	private function include_js( $file, $handle, $forced_exit = true, $custom_path = '' ) {
		if ( $custom_path != '' ) {
			$url = $file;
			$dir = $custom_path;
		} else {
			$url = FOOTBALLPOOL_PLUGIN_URL . $file;
			$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		}
		
		if ( file_exists( $dir ) ) {
			wp_register_script( $handle, $url );
			wp_enqueue_script( $handle );
		} else {
            if ( $forced_exit ) wp_die( $dir . ' not found' );
		}
	}
	
	private function create_page( $page, $menuOrder = null ) {
		if ( ! Football_Pool_Utils::get_fp_option( 'page_id_' . $page['slug'] ) ) {
			global $current_user;
			
			$newpage = array();
			$newpage['post_title'] = __( $page['title'], FOOTBALLPOOL_TEXT_DOMAIN );
			$newpage['post_name'] = $page['slug'];
			$newpage['post_content'] = isset( $page['text'] ) ? $page['text'] : '';
			$newpage['post_status'] = 'publish';
			$newpage['post_type'] = 'page';
			$newpage['post_author'] = $current_user->ID;
			if ( isset( $menuOrder ) ) {
				$newpage['menu_order'] = $menuOrder;
			}
			if ( isset( $page['parent'] ) ) {
				$parent_ID = (int) Football_Pool_Utils::get_fp_option('page_id_' . $page['parent'] );
				if ( $parent_ID ) {
					$newpage['post_parent'] = $parent_ID;
				}
			}
			if ( isset( $page['comment'] ) ) {
				$newpage['comment_status'] = $page['comment'];
			}
			$page_ID = wp_insert_post( $newpage );
			
			add_option( 'footballpool_page_id_' . $page['slug'], $page_ID );
			return $page_ID;
		}
	}
	
	private function read_from_file( $file ) {
		if ( file_exists( $file ) ) {
			return file_get_contents( $file );
		} else {
			wp_die( $file . ' not found' );
		}
	}
	
	// replaces {prefix} in string with actual database prefix
	private function prepare( $sql ) {
		return str_replace( '{$prefix}', FOOTBALLPOOL_DB_PREFIX, $sql );
	}
	
	private function db_actions( $text ) {
		global $wpdb;
		$array = explode( ';', $text );
		if ( count( $array ) > 0 ) {
			foreach ( $array as $sql ) {
				// check if string contains data other than spaces, tabs and/or newlines
				$check = str_replace(
							array( " ", "\n", "\r", "\t" ), 
							array( "",  "",   "",   "" ), 
							$sql
						);
				
				if ( !empty( $check ) ) {
					$wpdb->query( $sql );
				}
			}
		}
	}
}
?>