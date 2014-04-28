<?php
class Football_Pool_Teams {
	public $teams;
	public $team_types;
	public $team_names;
	public $team_flags;
	public $team_info;
	public $show_team_links;
	public $page;
	
	public function __construct() {
		$this->teams = $this->get_teams();
		// get the team info for all teams
		$this->team_info = $this->get_team_info();
		// get the team_types
		$this->team_types = $this->get_team_types();
		// get the team_names
		$this->team_names = $this->get_team_names();
		// get the flags
		$this->team_flags = $this->get_team_flags();
		// show links?
		$this->show_team_links = Football_Pool_Utils::get_fp_option( 'show_team_link', true );
		
		$this->page = Football_Pool::get_page_link( 'teams' );
	}
	
	// returns array
	public function get_team_by_id( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		//@TODO: extra testing
		$sql = $wpdb->prepare( "SELECT 
									t.id, t.name, t.photo, t.flag, t.link, g.id AS group_id, 
									g.name AS group_name, t.group_order, 
									t.is_real, t.is_active, t.comments
								FROM {$prefix}teams t
								LEFT OUTER JOIN {$prefix}groups g ON t.group_id = g.id
								WHERE t.id = %d",
								$id
							);
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? new Football_Pool_Team( $row ) : null;
	}
	
	// returns object
	public function get_team_by_name( $name, $addnew = 'no', $extra_data = '' ) {
		if ( $name == '' ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT 
									id, name, photo, flag, link, group_id, 
									group_order, is_real, is_active, comments
								FROM {$prefix}teams WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$photo = $flag = $link = $comments = '';
			$group_id = $group_order = 0;
			$is_active = $is_real = 1;
			
			if ( is_array( $extra_data ) ) {
				$photo       = $extra_data['photo'];
				$flag        = $extra_data['flag'];
				$link        = $extra_data['link'];
				$group_id    = $extra_data['group_id'];
				$group_order = $extra_data['group_order'];
				$is_real     = $extra_data['is_real'];
				$is_active   = $extra_data['is_active'];
				$comments    = isset( $extra_data['comments'] ) ? $extra_data['comments'] : '';
			}
			
			$sql = $wpdb->prepare( 
							"INSERT INTO {$prefix}teams 
								( name, photo, flag, link, group_id, group_order, is_real, is_active, comments ) 
							 VALUES 
								( %s, %s, %s, %s, %d, %d, %d, %d, %s )"
							, $name, $photo, $flag, $link, $group_id, $group_order, $is_real, $is_active, $comments
					);
			$wpdb->query( $sql );
			$id = $wpdb->insert_id;
			$result = (object) array( 
									'id'          => $id, 
									'name'        => $name,
									'photo'       => $photo,
									'flag'        => $flag,
									'link'        => $link,
									'group_id'    => $group_id,
									'group_order' => $group_order,
									'is_real'     => $is_real,
									'is_active'   => $is_active,
									'comments'    => $comments,
									'inserted'    => true
									);
			// clear the cache
			wp_cache_delete( FOOTBALLPOOL_CACHE_TEAMS );
		}
		
		return $result;
	}
	
	public function get_group_order( $team ) {
		if ( ! is_numeric( $team ) ) return 0;
		
		$cache_key = 'fp_group_order_' . $team;
		$group_order = wp_cache_get( $cache_key );
		
		if ( $group_order === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = $wpdb->prepare( "SELECT group_order FROM {$prefix}teams WHERE id = %d", $team );
			$group_order = $wpdb->get_var( $sql );
			wp_cache_set( $cache_key, $group_order );
		}
		
		return ( $group_order ) ? (integer) $group_order : 0;
	}
	
	public function print_lines( $teams ) {
		$thumbs_in_listing = Football_Pool_Utils::get_fp_option( 'listing_show_team_thumb' );
		$comments_in_listing = Football_Pool_Utils::get_fp_option( 'listing_show_team_comments' );
		$output = '';
		while ( $team = array_shift( $teams ) ) {
			if ( $team->is_real == 1 && $team->is_active == 1 ) {
				$photo = ( $thumbs_in_listing && $team->photo != '' ) ? $team->HTML_thumb( 'thumb' ) : '';
				$comments = ( $comments_in_listing ) ? $team->comments : '';
				$line = sprintf( '<li><a href="%s">%s%s</a><br />%s</li>'
									, add_query_arg( array( 'team' => $team->id ) )
									, $photo
									, htmlentities( $team->name, null, 'UTF-8' )
									, $comments
								);
				$output .= apply_filters( 'footballpool_teams_print_line', $line, $team );
			}
		}
		return $output;
	}
	
	public function update_teams() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		foreach ( $this->team_names as $team => $name ) {
			if (Football_Pool_Utils::post_string( '_name_' . $team) != '' ) {
				$name = Football_Pool_Utils::post_string( '_name_' . $team, 'unknown' . $team );
				$order = Football_Pool_Utils::post_integer( '_order_' . $team );
				
				$sql = $wpdb->prepare( "
										UPDATE {$prefix}teams 
										SET name = %s, group_order = %d WHERE id = %d",
										$name, $order, $team
								);
				$wpdb->query( $sql );
			}
		}
	}
	
	public function get_teams() {
		$teams = wp_cache_get( FOOTBALLPOOL_CACHE_TEAMS );
		
		if ( $teams === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = "SELECT 
						t.id, t.name, t.photo, t.flag, t.link, g.id AS group_id, g.name as group_name,
						t.is_real, t.is_active, group_order, t.comments
					FROM {$prefix}teams t
					LEFT OUTER JOIN {$prefix}groups g ON t.group_id = g.id 
					ORDER BY t.name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$teams = array();
			foreach ( $rows as $row ) {
				$teams[(int)$row['id']] = new Football_Pool_Team( $row );
			}
			wp_cache_set( FOOTBALLPOOL_CACHE_TEAMS, $teams );
		}
		
		return $teams;
	}

	/* get an array containing all the team types (real or not) */
	private function get_team_types() {
		$team_types = array();
		foreach( $this->team_info as $team ) {
			$team_types[$team['id']] = $team['type'];
		}
		return $team_types;
	}
	
	/* get an array containing all the team names (those that are real and active) */
	private function get_team_names() {
		$team_names = array();
		foreach( $this->team_info as $team ) {
			$team_names[$team['id']] = $team['team_name'];
		}
		return $team_names;
	}
	
	/* get an array with all the team_flags (for real and active teams) */
	private function get_team_flags() {
		$flags = array();
		foreach( $this->team_info as $team ) {
			$flags[$team['id']] = $team['team_flag'];
		}
		return $flags;
	}
	
	/* get an array with the team info (for real and active teams) */
	private function get_team_info() {
		$team_info = array();
		foreach( $this->teams as $team ) {
			$team_info[(int) $team->id] = array(
				'id' => (int) $team->id,
				'type' => ( $team->is_real == 1 ),
				'team_name' => $team->name,
				'team_flag' => $team->flag,
				'group_id' => (int) $team->group_id,
				'group_name' => $team->group_name,
			);
		}
		return apply_filters( 'footballpool_teams', $team_info );
	}
	
	/* return IMG tag for team flag or logo */
	public function flag_image( $id ) {
		if ( is_array( $this->team_flags ) && isset( $this->team_flags[$id] ) && $this->team_flags[$id] != '' ) {
			$flag = $this->team_flags[$id];
			$team_name = esc_attr( $this->team_names[$id] );
			
			if ( stripos( $flag, 'http://' ) === false && stripos( $flag, 'https://' ) === false ) {
				$flag = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/flags/' . $flag;
			}
			
			$team_name = esc_attr( htmlentities( $team_name, null, 'UTF-8' ) );
			return sprintf( '<img src="%s" title="%s" alt="%s" class="flag" />'
							, esc_attr( $flag )
							, $team_name
							, $team_name
					);
		} else {
			return '';
		}
	}
}
