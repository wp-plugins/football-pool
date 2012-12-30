<?php
class Football_Pool_Teams {
	private $extra_teams;
	public $team_types;
	public $team_names;
	public $team_flags;
	public $show_team_links;
	
	const CACHE_KEY_TEAMS = 'fp_get_teams';
	
	public function __construct() {
		// get the team_names
		$this->team_types = $this->get_team_types();
		// get the team_names
		$this->team_names = $this->get_team_names();
		// get the flags
		$this->team_flags = $this->get_team_flags();
		// show links?
		$this->show_team_links = Football_Pool_Utils::get_fp_option( 'show_team_link', true );
	}
	
	// returns array
	public function get_team_by_id( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
//@todo: extra testing
		$sql = $wpdb->prepare( "SELECT 
									t.id, t.name, t.photo, t.flag, t.link, g.id AS groupId, 
									g.name AS groupName, t.groupOrder AS group_order, 
									t.is_real, t.is_active, t.comments
								FROM {$prefix}teams t
								LEFT OUTER JOIN {$prefix}groups g ON t.groupId = g.id
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
									id, name, photo, flag, link, groupId AS group_id, 
									groupOrder AS group_order, is_real, is_active, comments
								FROM {$prefix}teams WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$photo = $flag = $link = '';
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
								( name, photo, flag, link, groupId, groupOrder, is_real, is_active, comments ) 
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
			wp_cache_delete( self::CACHE_KEY_TEAMS );
		}
		
		return $result;
	}
	
	public function get_group_order( $team ) {
		if ( ! is_integer( $team ) ) return 0;
		
		$cache_key = 'fp_group_order_' . $team;
		$group_order = wp_cache_get( $cache_key );
		
		if ( $group_order === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = $wpdb->prepare( "SELECT groupOrder FROM {$prefix}teams WHERE id = %d", $team );
			$group_order = $wpdb->get_var( $sql );
			wp_cache_set( $cache_key, $group_order );
		}
		
		return ( $group_order ) ? (integer) $group_order : 0;
	}
	
	public function print_lines( $teams ) {
		$output = '';
		while ( $team = array_shift( $teams ) ) {
			if ( $team->is_real == 1 && $team->is_active == 1 ) {
				$output .= sprintf( '<li><a href="%s">%s</a></li>'
									, add_query_arg( array( 'team' => $team->id ) )
									, $team->name
							);
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
										SET name = %s, groupOrder = %d WHERE id = %d",
										$name, $order, $team
								);
				$wpdb->query( $sql );
			}
		}
	}
	
	/* return IMG tag for team flag or logo */
	public function flag_image( $id ) {
		if ( is_array( $this->team_flags ) && isset( $this->team_flags[$id] ) && $this->team_flags[$id] != '' ) {
			$flag = $this->team_flags[$id];
			$team_name = esc_attr( $this->team_names[$id] );
			
			if ( stripos( $flag, 'http://' ) === false && stripos( $flag, 'https://' ) === false ) {
				$flag = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/flags/' . $flag;
			}
			
			return sprintf( '<img src="%s" title="%s" alt="%s" class="flag" />'
							, $flag, $team_name, $team_name
					);
		} else {
			return '';
		}
	}
	
	public function get_teams() {
		$rows = wp_cache_get( self::CACHE_KEY_TEAMS );
		
		if ( $rows === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = "SELECT 
						t.id, t.name, t.photo, t.flag, t.link, g.id AS groupId, g.name as groupName, t.groupOrder,
						t.is_real, t.is_active, t.groupOrder AS group_order, t.comments
					FROM {$prefix}teams t
					LEFT OUTER JOIN {$prefix}groups g ON t.groupId = g.id 
					ORDER BY t.name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( self::CACHE_KEY_TEAMS, $rows );
		}
		
		$teams = array();
		foreach ( $rows as $row ) {
			$teams[] = new Football_Pool_Team( $row );
		}
		return $teams;
	}

	/* get an array containing all the team types (real or not) */
	private function get_team_types() {
		$teams = $this->get_teams();
		$team_types = array();
		while ( $team = array_shift( $teams ) ) {
			$team_types[$team->id] = ( $team->is_real == 1 );
		}
		return $team_types;
	}
	
	/* get an array containing all the team names (those that are real and active) */
	private function get_team_names() {
		$teams = $this->get_teams();
		$team_names = array();
		while ( $team = array_shift( $teams ) ) {
			$team_names[$team->id] = $team->name;
		}
		return $team_names;
	}
	
	/* get an array with all the team_flags (for real and active teams) */
	private function get_team_flags() {
		$teams = $this->get_teams();
		$flags = array();
		while ( $team = array_shift( $teams ) ) {
			$flags[$team->id] = $team->flag;
		}
		return $flags;
	}
}
?>