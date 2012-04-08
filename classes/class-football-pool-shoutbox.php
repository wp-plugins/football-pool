<?php
class Football_Pool_Shoutbox
{
	public function get_messages( $nr = -1 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT s.id, u.display_name AS userName, u.ID AS userId, s.shoutText, 
					DATE_FORMAT(s.dateEntered, '%%e-%%c-%%Y, %%H:%%i') as shoutDate 
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.userId = u.ID 
				ORDER BY s.dateEntered DESC, s.id DESC";
		if ( $nr > 0 )
			$sql .= " LIMIT %d";
		$sql = $wpdb->prepare( $sql, $nr );
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	public function get_message( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT s.id, u.display_name AS userName, u.ID AS userId, s.shoutText, 
					DATE_FORMAT(s.dateEntered, '%%e-%%c-%%Y, %%H:%%i') as shoutDate 
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.userId = u.ID AND s.id = %d";
		$sql = $wpdb->prepare( $sql, $id );
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function save_shout( $text, $user, $max_chars ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( ! $this->is_double_post( $text, $user ) && $user > 0 ) {
			if ( strlen( $text ) > $max_chars )
				$text = substr( $text, 0, $max_chars );
			
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}shoutbox (userId, shoutText, dateEntered) 
									VALUES (%d, %s, NOW())",
									$user, $text );
			$wpdb->query( $sql );
		}
	}
	
	private function is_double_post( $text, $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$interval = 2 * 60 * 60; // 2 hours in seconds
		
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}shoutbox 
								WHERE userId = %d AND shoutText = %s 
									AND (%d - UNIX_TIMESTAMP(dateEntered)) < %d",
								$user, $text, time(), $interval );
		
		$result = $wpdb->get_var( $sql );
		return ( $result > 0 );
	}
}
?>