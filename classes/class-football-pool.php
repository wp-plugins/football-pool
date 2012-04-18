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
		
		// install custom tables in database
		$install_sql = self::prepare( self::read_from_file( 'data/install.txt' ) );
		$data_sql = self::prepare( self::read_from_file( 'data/data.txt' ) );
		self::db_actions( $install_sql );
		
		if ( $action == 'install' ) {
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
					('" . __( 'voor de pot', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, 'league_money2.png'),
					('" . __( 'voor nop', FOOTBALLPOOL_TEXT_DOMAIN ) . "', 1, '');";
			$wpdb->query( $sql );
		} elseif ( $action == 'update' ) {
			delete_option( 'footballpool_show_admin_bar' );
		}
		
		// define default plugin options
		global $current_user;
		get_currentuserinfo();
		
		$matches = new Matches();
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
		add_option( 'footballpool_shoutbox_max_chars', 150 );
		add_option( 'footballpool_hide_admin_bar', 1 ); // 1: yes, 0: no
		
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
		$no_show = current_user_can( 'subscriber' ) && Football_Pool_Utils::get_wp_option( 'footballpool_hide_admin_bar', 1 ) == 1;
		
		return $no_show ? false : $content;
	}
	
	public function init() {
		load_plugin_textdomain( FOOTBALLPOOL_TEXT_DOMAIN, false, FOOTBALLPOOL_PLUGIN_DIR . 'languages' );
		
		if ( !wp_script_is( 'jquery', 'queue' ) ) {
			wp_enqueue_script( "jquery" );
		}
		
		if ( !is_admin() ) {
			//highcharts
			self::include_js( 'assets/highcharts/highcharts.js', 'js-highcharts' );
			
			//fancybox
			self::include_js( 'assets/fancybox/jquery.fancybox-1.3.4.pack.js', 'js-fancybox' );
			self::include_js( 'assets/fancybox/jquery.easing-1.3.pack.js', 'js-fancybox' );
			self::include_css( 'assets/fancybox/jquery.fancybox-1.3.4.css', 'css-fancybox' );
			
			//pool js
			self::include_js( 'assets/pool.js', 'js-pool' );
			
			//pool css
			self::include_css( 'assets/pool.css', 'css-pool' );
			
			//extra countdown code
			add_action( 'wp_head', array( 'Football_Pool', 'countdown_texts' ) );
		} else {
			// admin css
			self::include_css( 'assets/admin.css', 'css-admin' );
			// admin js
			self::include_js( 'assets/admin.js', 'js-admin' );
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
		$league = Football_Pool_Utils::post_int( 'league', FOOTBALLPOOL_LEAGUE_DEFAULT );
		update_user_meta( $user_id, 'footballpool_league', FOOTBALLPOOL_LEAGUE_DEFAULT );
		update_user_meta( $user_id, 'footballpool_registeredforleague', $league );
		$payed = Football_Pool_Utils::post_int( 'payed', 0 );
		update_user_meta( $user_id, 'footballpool_payed', $payed );
		
		self::update_user_custom_tables( $user_id, FOOTBALLPOOL_LEAGUE_DEFAULT );
	}
	
	private function update_user_custom_tables( $user_id, $league ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// add user to custom tables
		$user = get_userdata( $user_id );
		// display_name is only added for easy debugging
		$sql = $wpdb->prepare( "INSERT INTO {$prefix}users (id, name, wantsLeague) 
								VALUES (%d, %s, %d)
								ON DUPLICATE KEY UPDATE name=%s", 
							$user_id, $user->display_name, $league, $user->display_name
					);
		$wpdb->query( $sql );
		// @todo: fix hardcoded free league
		$sql = $wpdb->prepare( "INSERT INTO {$prefix}league_users (userId, leagueId) 
								VALUES (%d, %d)
								ON DUPLICATE KEY UPDATE leagueId=%d", 
								$user_id, $league, $league
							);
		$wpdb->query( $sql );
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
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		$league = Football_Pool_Utils::post_int( 'league', FOOTBALLPOOL_LEAGUE_DEFAULT );
		update_user_meta( $user_id, 'footballpool_registeredforleague', $league );
		
		self::update_user_custom_tables( $user_id, $league );
		
		if ( current_user_can( 'administrator' ) ) {
			update_user_meta( $user_id, 'footballpool_league', $league );
			update_user_meta( $user_id, 'footballpool_payed', Football_Pool_Utils::post_int( 'payed', 0 ) );
			
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = $wpdb->prepare( "UPDATE {$prefix}league_users SET leagueId=%d WHERE userId=%d",
									$league, $user_id
								);
			$wpdb->query( $sql );
		}
	}
	
	public function add_extra_profile_fields( $user ) {
		// add extra profile fields to user edit page
		echo '<h3>', FOOTBALLPOOL_TEXT_DOMAIN, '</h3>';
		echo '<table class="form-table">';
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			global $current_user;
			get_currentuserinfo();
			
			if ( $user->ID == $current_user->ID ) {
				$league = get_the_author_meta( 'footballpool_registeredforleague', $user->ID );
			} else {
				$league = get_the_author_meta( 'footballpool_league', $user->ID );
			}
			echo'<tr><th><label for="league">', __( 'Speel mee in de pool', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
			echo '<td>', $pool->league_select( $league, 'league' ), '</td></tr>';

			if ( $user->ID == $current_user->ID ) {
				$league = get_the_author_meta( 'footballpool_league', $user->ID );
				if ( $league > 1 ) {
					$league = $pool->leagues[$league]['leagueName'];
				} else {
					$league = __( 'onbekend', FOOTBALLPOOL_TEXT_DOMAIN );
				}
					
				echo '<tr><th>', __( 'De webmaster heeft je ingedeeld in', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
				echo '<td>', $league, 
					' <span class="description">(', __( 'als dit afwijkt van wat je hebt aangegeven bij registratie, dan heeft de webmaster je inschrijving nog niet aangepast.', FOOTBALLPOOL_TEXT_DOMAIN ),')</span></td></tr>';
			}
		}
		if ( current_user_can( 'administrator' ) ) {
			$league = get_the_author_meta( 'footballpool_registeredforleague', $user->ID );
			
			if ( $league > 1 ) {
				$league = $pool->leagues[$league]['leagueName'];
			} else {
				$league = __( 'onbekend', FOOTBALLPOOL_TEXT_DOMAIN );
			}
			echo '<tr><th>', __( 'Speler heeft geregistreerd voor pool', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
			echo '<td style="font-style:italic">', $league, '</td></tr>';
			
			$checked = get_the_author_meta( 'footballpool_payed', $user->ID ) == 1 ? "checked='checked'" : "";
			echo '<tr><th><label for="payed">', __( 'Speler heeft betaald?', FOOTBALLPOOL_TEXT_DOMAIN ), '</label></th>';
			echo "<td><input {$checked} type='checkbox' id='payed' name='payed' value='1' /></td></tr>";
		}
		echo '</table>';
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
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}users WHERE id = %d", $user_id );
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
				var footballpool_countdown_text = new Array();
				footballpool_countdown_text['second'] = '{$text_second}';
				footballpool_countdown_text['seconds'] = '{$text_seconds}';
				footballpool_countdown_text['day'] = '{$text_day}';
				footballpool_countdown_text['days'] = '{$text_days}';
				footballpool_countdown_text['hour'] = '{$text_hour}';
				footballpool_countdown_text['hours'] = '{$text_hours}';
				footballpool_countdown_text['minute'] = '{$text_minute}';
				footballpool_countdown_text['minutes'] = '{$text_minutes}';
				footballpool_countdown_text['pre_before'] = '{$text_pre_before}';
				footballpool_countdown_text['post_before'] = '{$text_post_before}';
				footballpool_countdown_text['pre_after'] = '{$text_pre_after}';
				footballpool_countdown_text['post_after'] = '{$text_post_after}';
				</script>";
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
		if ( ! get_option( 'footballpool_page_id_' . $page['slug'] ) )
		{
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
		$dir = FOOTBALLPOOL_PLUGIN_DIR.$file;
		
		if ( file_exists( $dir ) ) {
			return file_get_contents( $dir );
		} else {
			wp_die( $dir . ' not found' );
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