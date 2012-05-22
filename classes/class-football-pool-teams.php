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
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT groupOrder FROM {$prefix}teams WHERE id = %d", $team );
		$groupOrder = $wpdb->get_var( $sql );
		
		return ( $groupOrder ) ? (integer) $groupOrder : 0;
	}
	
	public function print_lines( $teams ) {
		$output = '';
		while ( $team = array_shift( $teams ) ) {
			$output .= sprintf( '<li><a href="?team=%d">%s</a></li>', $team->id, $team->name );
		}
		return $output;
	}
	
	public function update_teams() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		foreach ( $this->team_names as $team => $name ) {
			if (Football_Pool_Utils::post_string( '_name_' . $team) != '' ) {
				$name = Football_Pool_Utils::post_string( '_name_' . $team, 'unknown' . $team );
				//if ( get_magic_quotes_gpc() === 1 ) $name = stripslashes( $name );
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
			return '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/flags/' . $this->team_flags[$id] . '" title="' . $this->team_names[$id] . '" alt="' . __( 'nationale vlag van', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $this->team_names[$id] . '" class="flag" />';
		} else {
			return '';
		}
	}
	
	public function get_extra_teams() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT id, name 
				FROM {$prefix}teams
				WHERE id < 0
				ORDER BY id DESC";
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		$teams = array();
		foreach ( $rows as $row ) {
			$teams[ $row['id'] ] = __( $row['name'], FOOTBALLPOOL_TEXT_DOMAIN );
		}
		return $teams;
	}
	
	public function get_teams() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT t.id, t.name, t.photo, t.flag, t.link, g.id AS groupId, g.name as groupName, t.groupOrder 
				FROM {$prefix}teams t, {$prefix}groups g
				WHERE t.groupId = g.id AND t.id > 0
				ORDER BY t.name ASC";
		$rows = $wpdb->get_results( $sql, ARRAY_A );

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