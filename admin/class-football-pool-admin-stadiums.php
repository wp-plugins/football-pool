<?php
class Football_Pool_Admin_Stadiums extends Football_Pool_Admin {
	public function __construct() {}
	
	public function help() {
		$help_tabs = array(
					array(
						'id' => 'overview',
						'title' => __( 'Overview', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>On this page you can add, change or delete venues.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
				);
		$help_sidebar = '';
	
		self::add_help_tabs( $help_tabs, $help_sidebar );
	}
	
	public function admin() {
		self::admin_header( __( 'Venues', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		
		$venue_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'save':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				// new or updated venue
				$venue_id = self::update( $venue_id );
				self::notice( __( 'Venue saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit') == __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ) ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $venue_id );
				break;
			case 'delete':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				if ( $venue_id > 0 ) {
					self::delete( $venue_id );
					self::notice( sprintf( __( 'Venue id:%s deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), $venue_id ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s venues deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit( $id ) {
		$values = array(
						'name' => '',
						'photo' => '',
						'comments' => '',
					);
		
		$venue = self::get_venue( $id );
		if ( is_array( $venue ) && $id > 0 ) {
			$values = $venue;
		}
		$cols = array(
					array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', $values['name'], '' ),
					array( 'image', __( 'photo', FOOTBALLPOOL_TEXT_DOMAIN ), 'photo', $values['photo'], '' ),
					array( 'multiline', __( 'comments', FOOTBALLPOOL_TEXT_DOMAIN ), 'comments', $values['comments'], __( 'An optional text with extra information about the venue that is displayed on the venue\'s page.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
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
	
	private function get_venue( $id ) {
		if ( $id > 0 ) {
			$venue = new Football_Pool_Stadium( $id );
			if ( isset( $venue->id ) ) {
				$output = array(
								'name' => $venue->name,
								'photo' => $venue->photo,
								'comments' => $venue->comments,
							);
			}
		} else {
			$output = null;
		}
		
		return $output;
	}
	
	private function get_venues() {
		$venues = Football_Pool_Stadiums::get_stadiums();
		$output = array();
		foreach ( $venues as $venue ) {
			$output[] = array(
							'id' => $venue->id, 
							'name' => $venue->name, 
							'photo' => $venue->photo,
							'comments' => $venue->comments,
						);
		}
		return $output;
	}
	
	private function view() {
		$items = self::get_venues();
		
		$cols = array(
					array( 'text', __( 'venue', FOOTBALLPOOL_TEXT_DOMAIN ), 'venue', '' ),
					array( 'text', __( 'photo', FOOTBALLPOOL_TEXT_DOMAIN ), 'photo', '' ),
					array( 'text', __( 'venue nr', FOOTBALLPOOL_TEXT_DOMAIN ), 'nr', '' ),
				);
		
		$rows = array();
		foreach( $items as $item ) {
			$rows[] = array(
						$item['name'], 
						$item['photo'], 
						$item['id'], 
						$item['id'],
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete' ), __( 'You are about to delete one or more venues.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to delete, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions );
	}
	
	private function update( $item_id ) {
		$item = array(
						$item_id,
						Football_Pool_Utils::post_string( 'name' ),
						Football_Pool_Utils::post_string( 'photo' ),
						Football_Pool_Utils::post_string( 'comments' ),
					);
		
		$id = self::update_item( $item );
		return $id;
	}
	
	private function delete( $item_id ) {
		if ( is_array( $item_id ) ) {
			foreach ( $item_id as $id ) self::delete_item( $id );
		} else {
			self::delete_item( $item_id );
		}
	}
	
	private function delete_item( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}stadiums WHERE id = %d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_item( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		list ( $id, $name, $photo, $comments ) = $input;
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}stadiums ( name, photo, comments )
									VALUES ( %s, %s, %s )",
									$name, $photo, $comments
								);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}stadiums SET
										name = %s,
										photo = %s,
										comments = %s
									WHERE id = %d",
									$name, $photo, $comments, $id
								);
		}
		
		$wpdb->query( $sql );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

}
?>