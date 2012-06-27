<?php
class Football_Pool_Teams {
	private $extra_teams;
	public $team_names;
	public $team_flags;

	public function __construct() {
		// get the team_names
		$this->team_names = $this->get_team_names();
		// get the flags
		$this->team_flags = $this->get_team_flags();
	}
	
	public function get_team_by_ID( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT t.id, t.name, t.photo, t.flag, t.link, g.id AS groupId, g.name AS groupName 
								FROM {$prefix}teams t, {$prefix}groups g 
								WHERE t.groupId = g.id AND t.id = %d",
								$id
							);
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? new Football_Pool_Team( $row ) : null;
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
			$output .= sprintf( '<li><a href="%s">%s</a></li>'
								, add_query_arg( array( 'team' => $team->id ) )
								, $team->name 
						);
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
	
	/* return IMG tag for national flag */
	public function flag_image( $id ) {
		if ( is_array( $this->team_flags ) && isset( $this->team_flags[$id] ) ) {
			return '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/flags/' . $this->team_flags[$id] . '" title="' . $this->team_names[$id] . '" alt="' . __( 'national flag for', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $this->team_names[$id] . '" class="flag" />';
		} else {
			return '';
		}
	}
	
	public function get_extra_teams() {
		$cache_key = 'fp_get_extra_teams';
		$rows = wp_cache_get( $cache_key );
		
		if ( $rows === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = "SELECT id, name 
					FROM {$prefix}teams
					WHERE id < 0
					ORDER BY id DESC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $rows );
		}
		
		$teams = array();
		foreach ( $rows as $row ) {
			$teams[ $row['id'] ] = __( $row['name'], FOOTBALLPOOL_TEXT_DOMAIN );
		}
		return $teams;
	}
	
	public function get_teams() {
		$rows = wp_cache_get( 'fp_get_teams' );
		
		if ( $rows === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$sql = "SELECT t.id, t.name, t.photo, t.flag, t.link, g.id AS groupId, g.name as groupName, t.groupOrder 
					FROM {$prefix}teams t, {$prefix}groups g
					WHERE t.groupId = g.id AND t.id > 0
					ORDER BY t.name ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( 'fp_get_teams', $rows );
		}
		
		$teams = array();
		foreach ( $rows as $row ) {
			$teams[] = new Football_Pool_Team( $row );
		}
		return $teams;
	}

	/* get an array containing all the team names */
	private function get_team_names() {
		$teams = $this->get_teams();
		$team_names = array();
		while ( $team = array_shift( $teams ) ) {
			$team_names[$team->id] = $team->name;
		}
		// don't use array_merge, because we want to preserve the keys
		return $team_names + $this->get_extra_teams();
	}
	
	/* get an array with all the team_flags */
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