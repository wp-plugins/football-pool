<?php
class Football_Pool_Shoutbox {
	public function get_messages( $nr = -1 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT s.id, u.display_name AS user_name, u.ID AS user_id
					, s.shout_text, s.date_entered as shout_date
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.user_id = u.ID 
				ORDER BY s.date_entered DESC, s.id DESC";
		if ( $nr > 0 ) {
			$sql .= " LIMIT %d";
			$sql = $wpdb->prepare( $sql, $nr );
		}
		return apply_filters( 'footballpool_shoutbox_messages', $wpdb->get_results( $sql, ARRAY_A ) );
	}
	
	public function get_message( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = "SELECT s.id, u.display_name AS user_name, u.ID AS user_id
					, s.shout_text, s.date_entered as shout_date
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.user_id = u.ID AND s.id = %d";
		$sql = $wpdb->prepare( $sql, $id );
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	public function save_shout( $text, $user, $max_chars ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$shout_date = Football_Pool_Utils::gmt_from_date( current_time( 'mysql' ) );
		if ( ! $this->is_double_post( $text, $user, $shout_date ) && $user > 0 ) {
			if ( strlen( $text ) > $max_chars )
				$text = substr( $text, 0, $max_chars );
			
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}shoutbox ( user_id, shout_text, date_entered ) 
									VALUES ( %d, %s, %s )",
									$user, $text, $shout_date );
			do_action( 'footballpool_shoutbox_before_save', $text, $user );
			$wpdb->query( $sql );
			do_action( 'footballpool_shoutbox_after_save', $text, $user );
		}
	}
	
	private function is_double_post( $text, $user, $date ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT COUNT( * ) FROM {$prefix}shoutbox
								WHERE user_id = %d AND shout_text = %s
									AND TIMESTAMPDIFF( SECOND, date_entered, %s ) <= %d",
								$user, $text, $date, FOOTBALLPOOL_SHOUTBOX_DOUBLE_POST_INTERVAL );
		
		$result = $wpdb->get_var( $sql );
		
		return ( $result > 0 );
	}
}
