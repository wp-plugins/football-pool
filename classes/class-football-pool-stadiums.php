<?php
class Football_Pool_Stadiums {
	public function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT id, name, photo, comments FROM {$prefix}stadiums ORDER BY name ASC";
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
			$output .= sprintf( '<li><a href="%s">%s</a></li>'
								, add_query_arg( array( 'stadium' => $stadium->id ) )
								, htmlentities( $stadium->name, null, 'UTF-8' )
						);
		}
		return $output;
	}
	
	public function get_stadium_by_id( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT id, name, photo, comments FROM {$prefix}stadiums WHERE id = %d", $id );
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? new Football_Pool_Stadium( $row ) : null;
	}
	
	// returns object
	public function get_stadium_by_name( $name, $addnew = 'no', $extra_data = '' ) {
		if ( $name == '' ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name, photo, comments
								FROM {$prefix}stadiums WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$photo = '';
			
			if ( is_array( $extra_data ) ) {
				$photo    = $extra_data['photo'];
				$comments = isset( $extra_data['comments'] ) ? $extra_data['comments'] : '';
			}
			
			$sql = $wpdb->prepare( 
							"INSERT INTO {$prefix}stadiums ( name, photo, comments ) 
							 VALUES ( %s, %s, %s )"
							, $name, $photo, $comments
					);
			$wpdb->query( $sql );
			$id = $wpdb->insert_id;
			$result = (object) array( 
									'id'       => $id, 
									'name'     => $name,
									'photo'    => $photo,
									'comments'    => $comments,
									'inserted' => true
								);
		}
		
		return $result;
	}
}
?>