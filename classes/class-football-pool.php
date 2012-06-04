<?php 
class Football_Pool {
	private static $pages = array(
						array( 'slug' => 'tournament', 'title' => 'wedstrijden', 'comment' => 'closed' ),
							array( 'slug' => 'teams', 'title' => 'teams', 'parent' => 'tournament', 'comment' => 'closed' ),
							array( 'slug' => 'groups', 'title' => 'poules', 'parent' => 'tournament', 'comment' => 'closed' ),
							array( 'slug' => 'stadiums', 'title' => 'stadions', 'parent' => 'tournament', 'comment' => 'closed' ),
						'rules' => array( 'slug' => 'rules', 'title' => 'spelregels', 'text' => '' ),
						array( 'slug' => 'pool', 'title' => 'voorspelling', 'comment' => 'closed' ),
						array( 'slug' => 'ranking', 'title' => 'stand', 'comment' => 'closed' ),
						array( 'slug' => 'statistics', 'title' => 'statistieken', 'comment' => 'closed' ),
						array( 'slug' => 'user', 'title' => 'voorspellingen', 'comment' => 'closed' )
					);
	
	public function __construct() {}
	
	public function activate( $action = 'install' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$action = empty( $action ) ? 'install' : $action;
		
		// install custom tables in database
		$install_sql = self::prepare( self::read_from_file( 'data/install.txt' ) );
		self::db_actions( $install_sql );
		
		if ( $action == 'install' ) {
			$data_sql = self::prepare( self::read_from_file( 'data/data.txt' ) );
			self::db_actions( $data_sql );
			
			// insert data in custom tables
			$sql = "INSERT INTO `{$prefix}groups` (`id`, `name`) VALUES
					(1, '" . __( 'poule A', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(2, '" . __( 'poule B', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(3, '" . __( 'poule C', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(4, '" . __( 'poule D', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(5, '" . __( 'poule E', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(6, '" . __( 'poule F', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(7, '" . __( 'poule G', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(8, '" . __( 'poule H', FOOTBALLPOOL_TEXT_DOMAIN ) . "');";
			$wpdb->query( $sql );
			
			$sql = "INSERT INTO `{$prefix}matchtypes` (`id`, `name`) VALUES
					(1, '" . __( 'Voorrondes', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(2, '" . __( 'Achtste finales', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(3, '" . __( 'Kwartfinales', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(4, '" . __( 'Halve finales', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(5, '" . __( 'Wedstrijd voor de 3e plek', FOOTBALLPOOL_TEXT_DOMAIN ) . "'),
					(6, '" . __( 'Finale', FOOTBALLPOOL_TEXT_DOMAIN ) . "');";
			$wpdb->query( $sql );

			$sql = "INSERT INTO `{$prefix}leagues` (`name`, `userDefined`, `image`) VALUES
					('" . __( 'alle spelers', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 0, ''),
					('" . __( 'voor de pot', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, 'league-money-green.png'),
					('" . __( 'voor nop', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, '');";
			$wpdb->query( $sql );
		} elseif ( $action == 'update' ) {
			delete_option( 'footballpool_show_admin_bar' );
			/*
			database changes, see data/update.txt for details:
				- fix question type for bonusquestions defined before v1.3
				- alter bonusquestions and answers to text fields
			*/
			$update_sql = self::prepare( self::read_from_file( 'data/update.txt' ) );
			self::db_actions( $update_sql );
		}
		
		// define default plugin options
		global $current_user;
		get_currentuserinfo();
		
		$matches = new Football_Pool_Matches();
		$first_match = $matches->get_first_match_info();
		$date = date( 'j F', $first_match['matchTimestamp'] );
		
		add_option( 'footballpool_webmaster', $current_user->user_email );
		add_option( 'footballpool_money', '5 euro' );
		add_option( 'footballpool_bank', $current_user->user_login );
		add_option( 'footballpool_start', $date );
		add_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS );
		add_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS );
		add_option( 'footballpool_maxperiod', FOOTBALLPOOL_MAXPERIOD );
		add_option( 'footballpool_use_leagues', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_shoutbox_max_chars', FOOTBALLPOOL_SHOUTBOX_MAXCHARS );
		add_option( 'footballpool_hide_admin_bar', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT );
		add_option( 'footballpool_dashboard_image', FOOTBALLPOOL_ASSETS_URL . 'admin/images/dashboardwidget.png' );
		add_option( 'footballpool_force_locktime', '' );
		// add_option( 'footballpool_matches_locktime', '' );
		// add_option( 'footballpool_bonus_question_locktime', '' );
		// add_option( 'footballpool_remove_data_on_uninstall', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_use_favicon', 1 ); // 1: yes, 0: no
		add_option( 'footballpool_use_touchicon', 1 ); // 1: yes, 0: no
		
		update_option( 'footballpool_db_version', FOOTBALLPOOL_DB_VERSION );

		// create pages
		$rules_text = self::read_from_file( 'data/rules-page-content.txt' );
		self::$pages['rules']['text'] = $rules_text;
		foreach ( self::$pages as $page ) {
			self::create_page($page);
		}
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
		$uninstall_sql = self::prepare( self::read_from_file( 'data/uninstall.txt' ) );
		self::db_actions( $uninstall_sql );
		
		// delete plugin options
		delete_option( 'footballpool_webmaster' );
		delete_option( 'footballpool_money' );
		delete_option( 'footballpool_bank' );
		delete_option( 'footballpool_start' );
		delete_option( 'footballpool_totopoints' );
		delete_option( 'footballpool_fullpoints' );
		delete_option( 'footballpool_maxperiod' );
		delete_option( 'footballpool_use_leagues' );
		delete_option( 'footballpool_db_version' );
		delete_option( 'footballpool_shoutbox_max_chars' );
		delete_option( 'footballpool_hide_admin_bar' );
		delete_option( 'footballpool_default_league_new_user' );
		delete_option( 'footballpool_dashboard_image' );
		delete_option( 'footballpool_force_locktime' );
		// delete_option( 'footballpool_matches_locktime' );
		// delete_option( 'footballpool_bonus_question_locktime' );
		// delete_option( 'footballpool_remove_data_on_uninstall' );
		delete_option( 'footballpool_use_favicon' );
		delete_option( 'footballpool_use_touchicon' );
		
		// delete pages
		foreach ( self::$pages as $page ) {
			wp_delete_post( get_option( 'footballpool_page_id_' . $page['slug'] ), true );
			delete_option( 'footballpool_page_id_' . $page['slug'] );
		}
		
		// delete custom user meta
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'footballpool%'" );
	}

	public function show_admin_bar( $content ) {
		// normal users do not get the admin bar after log in
		$no_show = current_user_can( 'subscriber' ) 
					&& Football_Pool_Utils::get_wp_option( 'footballpool_hide_admin_bar', 1 ) == 1;
		
		return $no_show ? false : $content;
	}
	
	private function get_locale() {
		$domain = FOOTBALLPOOL_TEXT_DOMAIN;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		return $locale;
	}
	
	public function init() {
		// i18n support:
		// http://www.geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
		
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
			//highcharts
			self::include_js( 'assets/highcharts/highcharts.js', 'js-highcharts' );
			
			//fancybox
			self::include_js( 'assets/fancybox/jquery.fancybox.js', 'js-fancybox' );
			self::include_css( 'assets/fancybox/jquery.fancybox.css', 'css-fancybox' );
			
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
				case get_option( 'footballpool_page_id_ranking' ):
					$page = new Football_Pool_Ranking_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_teams' ):
					$page = new Football_Pool_Teams_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_stadiums' ):
					$page = new Football_Pool_Stadiums_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_groups' ):
					$page = new Football_Pool_Groups_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_statistics' ):
					$page = new Football_Pool_Statistics_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_tournament' ):
					$page = new Football_Pool_Tournament_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_user' ):
					$page = new Football_Pool_User_Page();
					$content .= $page->page_content();
					break;
				case get_option( 'footballpool_page_id_pool' ):
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
		$id = get_option( 'footballpool_page_id_' . $slug );
		return $id ? get_page_link( $id ) : '';
	}
	
	public function new_pool_user( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// add extra meta fields
		$default_league = Football_Pool_Utils::get_wp_option( 'footballpool_default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT, 'ínt' );
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
			echo '<p><label for="league">', __( 'Speel mee in de pool', FOOTBALLPOOL_TEXT_DOMAIN ), '<br>', 
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
				$errors->add( 'league_error', __( '<strong>ERROR:</strong> Je moet een pool kiezen waar je in gaat spelen!', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
		}
		return $errors;
	}
	
	public function update_user_options( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		$league = Football_Pool_Utils::post_int( 'league', FOOTBALLPOOL_LEAGUE_DEFAULT );
		update_user_meta( $user_id, 'footballpool_registeredforleague', $league );
	}
	
	public function add_extra_profile_fields( $user ) {
		// add extra profile fields to user edit page
		$pool = new Football_Pool_Pool();
				
		if ( $pool->has_leagues ) {
			echo '<h3>', FOOTBALLPOOL_PLUGIN_NAME, '</h3>';
			echo '<table class="form-table">';
			
			global $current_user;
			get_currentuserinfo();
			
			$league = get_the_author_meta( 'footballpool_registeredforleague', $user->ID );
			echo'<tr><th><label for="league">', __( 'Speel mee in de pool', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
			echo '<td>', $pool->league_select( $league, 'league' ); 
			if ( current_user_can( 'administrator' ) ) {
				echo '<span class="description">', __( '<strong>Let op:</strong> Als admin kan je de spelers in de voetbalpool bijwerken op de plugin admin pagina', FOOTBALLPOOL_TEXT_DOMAIN ), ' <a href="admin.php?page=footballpool-users">Users</a>.</span>';
			}
			echo '</td></tr>';
			
			$league = get_the_author_meta( 'footballpool_league', $user->ID );
			if ( $league > 1 && array_key_exists( $league, $pool->leagues ) ) {
				$league = $pool->leagues[$league]['leagueName'];
			} else {
				$league = __( 'onbekend', FOOTBALLPOOL_TEXT_DOMAIN );
			}
				
			echo '<tr><th>', __( 'De webmaster heeft je ingedeeld in', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
			echo '<td>', $league, 
				' <span class="description">(', 
				__( 'als dit afwijkt van wat je hebt aangegeven bij registratie, dan heeft de webmaster je inschrijving nog niet aangepast.', FOOTBALLPOOL_TEXT_DOMAIN ), 
				')</span></td></tr>';
			
			echo '</table>';
		}
	}
	
	public function delete_user_from_pool( $user_id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory WHERE userId = %d", $user_id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}league_users WHERE userId = %d", $user_id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}predictions WHERE userId = %d", $user_id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions_useranswers WHERE userId = %d", $user_id );
		$wpdb->query( $sql );
		// also recalculate scorehistory
		$score = new Football_Pool_Admin();
		$score->update_score_history();
	}
	
	public function countdown_texts() {
		$text_second = __( 'seconde', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_seconds = __( 'seconden', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_day = __( 'dag', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_days = __( 'dagen', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_hour = __( 'uur', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_hours = __( 'uur', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_minute = __( 'minuut', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_minutes = __( 'minuten', FOOTBALLPOOL_TEXT_DOMAIN );
		
		$text_pre_before = __( 'Nog ', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_post_before = __( ' voor het feest start!!', FOOTBALLPOOL_TEXT_DOMAIN );
		$text_pre_after = '';
		$text_post_after = __( ' geleden zijn we los gegaan.', FOOTBALLPOOL_TEXT_DOMAIN );
	
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
		$img = get_option( 'footballpool_dashboard_image' );
		
		echo '<p>', __( 'Klik hieronder om naar de voetbalpool te gaan en je voorspellingen in te voeren. Veel succes!', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
		echo '<p style="text-align:center"><a href="', Football_Pool::get_page_link( 'pool' ), '"><img src="', $img, '" alt="', __( 'Voer je voorspellingen in.', FOOTBALLPOOL_TEXT_DOMAIN ), '" /></a></p>';
	}
	
	function add_dashboard_widgets() {
		wp_add_dashboard_widget( 
				'fp_dashboard_widget', 
				__( 'Vul direct je voorspellingen in', FOOTBALLPOOL_TEXT_DOMAIN ), 
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
		
		if ( get_option( 'footballpool_use_favicon' ) == 1 ) {
			echo "\n<link rel='shortcut icon' href='{$assets_dir}favicon.ico' />";
		}
		
		if ( get_option( 'footballpool_use_touchicon' ) == 1 ) {
			echo "\n<link rel='apple-touch-icon' href='{$assets_dir}apple-touch-icon-57x57.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='72x72' href='{$assets_dir}apple-touch-icon-ipad-72x72.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='114x114' href='{$assets_dir}apple-touch-icon-iphone4-114x114.png' />";
			echo "\n<link rel='apple-touch-icon' sizes='144x144' href='{$assets_dir}apple-touch-icon-ipad-highres-144x144.png' />";
		}
	}
	
//=============================================================================================================//
	
	private function include_css( $file, $handle ) {
		$url = FOOTBALLPOOL_PLUGIN_URL . $file;
		$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		
		if ( file_exists( $dir ) ) {
			wp_register_style( $handle, $url );
			wp_enqueue_style( $handle );
		} else {
			wp_die( $dir . ' not found' );
		}
	}
	
	private function include_js( $file, $handle ) {
		$url = FOOTBALLPOOL_PLUGIN_URL . $file;
		$dir = FOOTBALLPOOL_PLUGIN_DIR . $file;
		
		if ( file_exists( $dir ) ) {
			wp_register_script( $handle, $url );
			wp_enqueue_script( $handle );
		} else {
			wp_die( $dir . ' not found' );
		}
	}
	
	private function create_page( $page, $menuOrder = null ) {
		if ( ! get_option( 'footballpool_page_id_' . $page['slug'] ) ) {
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
				$parent_ID = (integer) get_option('footballpool_page_id_' . $page['parent'] );
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
		$file = FOOTBALLPOOL_PLUGIN_DIR . $file;
		
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