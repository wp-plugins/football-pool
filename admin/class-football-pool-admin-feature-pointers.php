<?php
class Football_Pool_Admin_Feature_Pointers {
	private static $pointers = array();
	private static $dismissed = array();
	
	private function define_pointers() {
		// define the pointers for v2.4.0
		$version = '240';
		self::add_pointer( $version
							, 'resplayout'
							, __( 'Responsive layout', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'A new layout option that is optimized for mobile devices.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '#responsive_layout'
						);
		self::add_pointer( $version
							, 'recalc'
							, 'Important'
							, 'After upgrading the Football Pool plugin to version 2.4.0 you have to do a full recalculation: '
							, '#adminmenu'
							, 'left'
							, 'top'
							, '<a href="admin.php?page=footballpool-options" onclick="calculate_score_history(); return false;">recalculate</a>.'
						);
		self::add_pointer( $version
							, 'redirecturl'
							, __( 'Page after registration', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'You can set the page where users must be redirected to after registration (and first time login).', FOOTBALLPOOL_TEXT_DOMAIN )
							, '#redirect_url_after_login'
						);
		self::add_pointer( $version
							, 'jokermultiplier'
							, __( 'Joker multiplier', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'Alter the default multiplier for the joker.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '#joker_multiplier'
						);
		// define the pointers for v2.3.0
		$version = '230';
		self::add_pointer( $version
							, 'listingphotos'
							, __( 'Extra layout options', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'Show photo\'s and/or info about your teams and venues in the listing on the teams and venues pages.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.toplevel_page_footballpool-options #listing_show_team_thumb'
						);
		self::add_pointer( $version
							, 'shortcode'
							, __( 'New shortcodes', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'New shortcodes to display the score of a single user, to display the predictions for a match or question and to display a table of matches. Use the button in the toolbar to include them:', FOOTBALLPOOL_TEXT_DOMAIN )
							, '#wp-content-editor-container'
							, 'middle', 'middle'
							, sprintf( ' <img src="%sadmin/tinymce/footballpool-tinymce-16.png">', FOOTBALLPOOL_ASSETS_URL )
						);
		self::add_pointer( $version
							, 'rankinglog'
							, __( 'Ranking changes', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'The plugin keeps track of changes in the data that might affect the ranking. The changes are displayed in the log and this log is used for the new smart recalculation of the score table.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.football-pool_page_footballpool-rankings #log-head'
						);
		self::add_pointer( $version
							, 'keepdata'
							, __( 'Keep data', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'Keep your data in the database when deactivating the plugin.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '#keep_data_on_uninstall'
						);
		self::add_pointer( $version
							, 'pointstournament'
							, __( 'Tournament / competition ranking', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'Change the points for wins and draws if your sport doesn\'t use the 3/1 point rule.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.toplevel_page_footballpool-options #team_points_win'
						);
		self::add_pointer( $version
							, 'disablejokers'
							, __( 'Disable jokers', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'You can completely disable jokers if you don\'t want to use them.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.toplevel_page_footballpool-options #number_of_jokers'
						);
		self::add_pointer( $version
							, 'goaldiffbonus'
							, __( 'Goal difference bonus', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'A new scoring option: reward a player with a bonus if the correct difference in goals is predicted.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.toplevel_page_footballpool-options #diffpoints'
						);
		self::add_pointer( $version
							, 'linkedquestions'
							, __( 'Linked questions', FOOTBALLPOOL_TEXT_DOMAIN )
							, __( 'You can now link questions directly to a match.', FOOTBALLPOOL_TEXT_DOMAIN )
							, '.football-pool_page_footballpool-bonus #match_id'
						);
	}
	
	public function init() {
		// array of pointers the user already clicked away
		self::$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		// define the pointers
		self::define_pointers();
		
		$active_pointers = false;
		foreach ( self::$pointers as $pointer => $pointer_definition ) {
			if ( $pointer_definition['active'] ) {
				$active_pointers = true;
				break;
			}
		}
		
		if ( $active_pointers ) {
			add_action( 'admin_print_footer_scripts'
						, array( 'Football_Pool_Admin_Feature_Pointers', 'insert_pointers_script' ) );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}
	}
	
	private function add_pointer( $version, $feature, $title, $content, $anchor_id
								, $edge = 'top', $align = 'left', $unescaped_content = '' ) {
		$feature = "fp{$version}_{$feature}";
		$plugin_version = explode( '.', FOOTBALLPOOL_DB_VERSION );
		$plugin_version = "{$plugin_version[0]}{$plugin_version[1]}";
		
		self::$pointers[$feature] = array(
										'content' => sprintf( '<h3>%s</h3><p>%s%s</p>'
															, esc_attr( $title )
															, esc_attr( $content )
															, $unescaped_content
													),
										'anchor_id' => $anchor_id,
										'edge' => $edge,
										'align' => $align,
										// not active if the user already clicked the feature pointer
										// and if plugin version is not in the same release (version X.Y)
										'active' => ( ! in_array( $feature, self::$dismissed ) 
														 && strpos( $feature, "fp{$plugin_version}" ) !== false ),
									);
	}
	
	public function insert_pointers_script() {
		echo '<script>';
		echo 'jQuery( document ).ready( function() { if ( typeof( jQuery().pointer ) != "undefined" ) { ';
		foreach( self::$pointers as $pointer => $pointer_definition ) {
			if ( $pointer_definition['active'] ) {
				printf(	"jQuery( '%s' ).pointer( { content: '%s', position: { edge: '%s', align: '%s' }, close: function() { jQuery.post( ajaxurl, { pointer: '%s', action: 'dismiss-wp-pointer' } ) } } ).pointer( 'open' );"
						, $pointer_definition['anchor_id']
						, $pointer_definition['content']
						, $pointer_definition['edge']
						, $pointer_definition['align']
						, $pointer
				);
			}
		}
		echo ' } } );</script>';
	}
	
}
