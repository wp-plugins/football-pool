<?php
class Football_Pool_Admin_Shoutbox extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), '', true );
		self::intro( __( 'Berichten in de shoutbox toevoegen, wijzigen of verwijderen.', FOOTBALLPOOL_TEXT_DOMAIN ) );// See help for more information.'));
		
		$shout_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'save':
				// new or updated message
				$league_id = self::update( $shout_id );
				self::notice( __( "Bericht opgeslagen.", FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit' ) == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $shout_id );
				break;
			case 'delete':
				if ( $shout_id > 0 ) {
					self::delete( $shout_id );
					self::notice( sprintf( __("Bericht id:%s verwijderd.", FOOTBALLPOOL_TEXT_DOMAIN ), $shout_id ) );
				}
				if ( count( $bulk_ids ) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s berichten verwijderd.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit( $id ) {
		global $current_user;
		
		$values = array(
						'userName' => $current_user->display_name,
						'shoutText' => '',
						'shoutDate' => __( 'nu', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		
		$message = self::get_message( $id );
		if ( $message && $id > 0 ) {
			$values = $message;
		}
		$cols = array(
					array( 'no_input', __( 'naam', FOOTBALLPOOL_TEXT_DOMAIN ), 'user_name', $values['userName'], '' ),
					array( 'text', __( 'bericht', FOOTBALLPOOL_TEXT_DOMAIN ), 'message', $values['shoutText'], '' ),
					array( 'no_input', __( 'tijd', FOOTBALLPOOL_TEXT_DOMAIN ), 'time', $values['shoutDate'], '' ),
					array( 'hidden', '', 'item_id', $id ),
					array( 'hidden', '', 'action', 'save' )
				);
		self::value_form( $cols );
		echo '<p>';
		submit_button( 'Save & Close', 'primary', 'submit', false );
		submit_button( null, 'secondary', 'save', false );
		echo '</p>';
	}
	
	private function get_message( $id ) {
		$shoutbox = new Football_Pool_Shoutbox();
		return $shoutbox->get_message( $id );
	}
	
	private function view() {
		$shoutbox = new Football_Pool_Shoutbox;
		$messages = $shoutbox::get_messages();
		
		$cols = array(
					array( 'text', __( 'naam', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', '' ),
					array( 'text', __( 'bericht', FOOTBALLPOOL_TEXT_DOMAIN ), 'message', '' ),
					array( 'text', __( 'tijd', FOOTBALLPOOL_TEXT_DOMAIN ), 'time', '' )
				);
		
		$rows = array();
		foreach( $messages as $message ) {
			$rows[] = array(
						$message['userName'], 
						$message['shoutText'],
						$message['shoutDate'],
						$message['id']
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions );
	}
	
	private function update( $shout_id ) {
		$message = array(
						$shout_id,
						Football_Pool_Utils::post_string( 'message' )
					);
		
		$id = self::update_message( $message );
		return $id;
	}
	
	private function delete( $shout_id ) {
		if ( is_array( $shout_id ) ) {
			foreach ( $shout_id as $id ) self::delete_shout( $id );
		} else {
			self::delete_shout( $shout_id );
		}
	}
	
	private function delete_shout( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}shoutbox WHERE id = %d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_message( $input ) {
		global $wpdb, $current_user;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$id = $input[0];
		$message = $input[1];
		
		$shoutbox = new Football_Pool_Shoutbox;
		
		if ( $id == 0 ) {
			$shoutbox->save_shout( $message, $current_user->ID, 150 );
		} else {
			$sql = "UPDATE {$prefix}shoutbox SET shoutText = %s WHERE id = %d";
			$sql = $wpdb->prepare( $sql, $message, $id );
			$wpdb->query( $sql );
		}
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

}
?>