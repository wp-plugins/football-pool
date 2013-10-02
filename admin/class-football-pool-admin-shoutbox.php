<?php
class Football_Pool_Admin_Shoutbox extends Football_Pool_Admin {
	public function __construct() {}
	
	public function help() {
		$help_tabs = array(
					array(
						'id' => 'overview',
						'title' => __( 'Overview', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>On this page you can add, change or delete shoutbox messages. Shoutbox messages are displayed in the plugin\'s Shoutbox widget.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
				);
		$help_sidebar = '';
	
		self::add_help_tabs( $help_tabs, $help_sidebar );
	}
	
	public function admin() {
		self::admin_header( __( 'Shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		
		$shout_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'save':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				// new or updated message
				$shout_id = self::update( $shout_id );
				self::notice( __( "Message saved.", FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit' ) == __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ) ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $shout_id );
				break;
			case 'delete':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				if ( $shout_id > 0 ) {
					self::delete( $shout_id );
					self::notice( sprintf( __("Message id:%s deleted.", FOOTBALLPOOL_TEXT_DOMAIN ), $shout_id ) );
				}
				if ( count( $bulk_ids ) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s messages deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit( $id ) {
		global $current_user;
		
		$values = array(
						'user_name' => $current_user->display_name,
						'shout_text' => '',
						'shout_date' => __( 'now', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		
		$message = self::get_message( $id );
		if ( $message && $id > 0 ) {
			$values = $message;
			$values['shout_date'] = Football_Pool_Utils::date_from_gmt( $values['shout_date'] );
		}
		$cols = array(
					array( 'no_input', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'user_name', $values['user_name'], '' ),
					array( 'text', __( 'message', FOOTBALLPOOL_TEXT_DOMAIN ), 'message', $values['shout_text'], '' ),
					array( 'no_input', __( 'time', FOOTBALLPOOL_TEXT_DOMAIN ), 'time', $values['shout_date'], '' ),
					array( 'hidden', '', 'item_id', $id ),
					array( 'hidden', '', 'action', 'save' )
				);
		self::value_form( $cols );
		echo '<p class="submit">';
		submit_button( __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ), 'primary', 'submit', false );
		submit_button( null, 'secondary', 'save', false );
		self::cancel_button();
		echo '</p>';
	}
	
	private function get_message( $id ) {
		$shoutbox = new Football_Pool_Shoutbox();
		return $shoutbox->get_message( $id );
	}
	
	private function view() {
		$shoutbox = new Football_Pool_Shoutbox;
		$messages = $shoutbox->get_messages();
		
		$cols = array(
					array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', '' ),
					array( 'text', __( 'message', FOOTBALLPOOL_TEXT_DOMAIN ), 'shouttext', '' ),
					array( 'text', __( 'time', FOOTBALLPOOL_TEXT_DOMAIN ), 'time', '' )
				);
		
		$rows = array();
		foreach( $messages as $message ) {
			$rows[] = array(
						$message['user_name'], 
						$message['shout_text'],
						Football_Pool_Utils::date_from_gmt( $message['shout_date'] ),
						$message['id']
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete' ), __( 'You are about to delete one or more shoutbox messages.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to delete, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
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
			$sql = $wpdb->prepare( "UPDATE {$prefix}shoutbox SET shout_text = %s WHERE id = %d", $message, $id );
			$wpdb->query( $sql );
		}
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

}
