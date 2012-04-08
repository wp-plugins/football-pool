<?php
class Football_Pool_Stadiums {
	public function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT id, name, photo FROM {$prefix}stadiums ORDER BY name ASC";
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		$stadiums = array();
		foreach ( $rows as $row ) {
			$stadiums[] = new Football_Pool_Stadium($row);
		}
		return $stadiums;
	}
	
	public function print_lines( $stadiums ) {
		$output = '';
		while ( $stadium = array_shift( $stadiums ) ) {
			$output .= sprintf( '<li><a href="?stadium=%d">%s</a></li>', $stadium->id, htmlentities( $stadium->name ) );
		}
		return $output;
	}
	
	public function get_stadium_by_ID( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT id, name, photo FROM {$prefix}stadiums WHERE id = %d", $id );
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? new Football_Pool_Stadium( $row ) : null;
	}
}
?>