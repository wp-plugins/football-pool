<?php
class Football_Pool_Admin_Match_Types extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Match Types', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		self::intro( __( 'Add, change or delete match types.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$match_type_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'save':
				// new or updated venue
				$match_type_id = self::update( $match_type_id );
				self::notice( __("match type saved.", FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str('submit') == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $match_type_id );
				break;
			case 'delete':
				if ( $match_type_id > 0 ) {
					self::delete( $match_type_id );
					self::notice( sprintf( __("Match type id:%s deleted.", FOOTBALLPOOL_TEXT_DOMAIN ), $match_type_id ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s match types deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit( $id ) {
		$values = array(
						'name' => ''
						);
		
		$match_type = self::get_match_type( $id );
		if ( $match_type && $id > 0 ) {
			$values = $match_type;
		}
		$cols = array(
					array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', $values['name'], '' ),
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
	
	private function get_match_type( $id ) {
		$match_type = Football_Pool_Matches::get_match_type_by_id( $id );
		if ( is_object( $match_type ) ) {
			$output = array(
							'name' => $match_type->name
							);
		} else {
			$output = null;
		}
		
		return $output;
	}
	
	private function get_match_types() {
		$match_types = Football_Pool_Matches::get_match_types();
		$output = array();
		foreach ( $match_types as $match_type ) {
			$output[] = array(
							'id' => $match_type->id, 
							'name' => $match_type->name
						);
		}
		return $output;
	}
	
	private function view() {
		$items = self::get_match_types();
		
		$cols = array(
					array( 'text', __( 'match type', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', '' ),
				);
		
		$rows = array();
		foreach( $items as $item ) {
			$rows[] = array(
						$item['name'], 
						$item['id'],
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete' ), __( 'You are about to delete one or more match types.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to delete, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions );
	}
	
	private function update( $item_id ) {
		$item = array(
						$item_id,
						Football_Pool_Utils::post_string( 'name' )
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
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}matchtypes WHERE id = %d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_item( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$id = $input[0];
		$name = $input[1];
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}matchtypes ( name )
									VALUES ( %s )",
									$name
								);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}matchtypes SET
										name = %s
									WHERE id = %d",
									$name, $id
								);
		}
		
		$wpdb->query( $sql );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

}
?>