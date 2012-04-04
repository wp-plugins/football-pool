<?php
class Football_Pool_Admin_Leagues extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Pools', FOOTBALLPOOL_TEXT_DOMAIN ), '', true );
		self::intro( __( 'Pool toevoegen, wijzigen of verwijderen.', FOOTBALLPOOL_TEXT_DOMAIN ) );// See help for more information.'));
		
		$league_id = Utils::request_int( 'item_id', 0 );
		$bulk_ids = Utils::post_int_array( 'itemcheck', array() );
		$action = Utils::request_string( 'action', 'list' );
		
		switch ( $action ) {
			case 'save':
				// new or updated league
				$league_id = self::update( $league_id );
				self::notice( __("Pool opgeslagen.", FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Utils::post_str('submit') == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $league_id );
				break;
			case 'delete':
				if ( $league_id > 0 ) {
					self::delete( $league_id );
					self::notice( sprintf( __("Pool id:%s verwijderd.", FOOTBALLPOOL_TEXT_DOMAIN ), $league_id ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s pools verwijderd.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit( $id ) {
		$values = array(
						'name' => '',
						'image' => ''
						);
		
		$league = self::get_league( $id );
		if ( $league && $id > 0 ) {
			$values = $league;
		}
		$cols = array(
					array( 'text', __( 'pool', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', $values['name'], '' ),
					array( 'text', __( 'afbeelding', FOOTBALLPOOL_TEXT_DOMAIN ), 'image', $values['image'], __( 'Afbeeldingen moeten worden geplaatst in "plugin-folder/assets".', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'hidden', '', 'item_id', $id ),
					array( 'hidden', '', 'action', 'save' )
				);
		self::value_form( $cols );
		echo '<p>';
		submit_button( 'Save & Close', 'primary', 'submit', false );
		submit_button( null, 'secondary', 'save', false );
		echo '</p>';
	}
	
	private function get_league( $id ) {
		$pool = new Pool();
		$leagues = $pool->leagues;
		if ( array_key_exists( $id, $leagues ) ) {
			$output = array(
							'name' => $leagues[$id]['leagueName'],
							'image' => $leagues[$id]['image']
							);
		} else {
			$output = null;
		}
		
		return $output;
	}
	
	private function get_leagues() {
		$pool = new Pool();
		$leagues = $pool->get_leagues( true );
		$output = array();
		foreach ( $leagues as $league ) {
			$output[] = array(
							'id' => $league['leagueId'], 
							'name' => $league['leagueName'], 
							'image' => $league['image']
						);
		}
		return $output;
	}
	
	private function view() {
		$leagues = self::get_leagues();
		
		$cols = array(
					array( 'text', __( 'league', FOOTBALLPOOL_TEXT_DOMAIN ), 'league', '' ),
					array( 'text', __( 'image', FOOTBALLPOOL_TEXT_DOMAIN ), 'image', '' )
				);
		
		foreach( $leagues as $league ) {
			$rows[] = array(
						$league['name'], 
						$league['image'], 
						$league['id']
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions );
	}
	
	private function update( $league_id ) {
		$league = array(
						$league_id,
						Utils::post_string( 'name' ),
						Utils::post_string( 'image' )
					);
		
		$id = self::update_league( $league );
		return $id;
	}
	
	private function delete( $league_id ) {
		if ( is_array( $league_id ) ) {
			foreach ( $league_id as $id ) self::delete_league( $id );
		} else {
			self::delete_league($league_id);
		}
	}
	
	private function delete_league( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}league_users WHERE leagueId=%d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}leagues WHERE id=%d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_league( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$id = $input[0];
		$name = $input[1];
		$image = $input[2];
		$userDefined = 1;
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "
									INSERT INTO {$prefix}leagues 
										(name, userDefined, image)
									VALUES (%s, %d, %s)",
								$name, $userDefined, $image
						);
		} else {
			$sql = $wpdb->prepare( "
									UPDATE {$prefix}leagues SET
										name = %s,
										userDefined = %d,
										image = %s
									WHERE id = %d",
								$name, $userDefined, $image, $id
						);
		}
		
		$wpdb->query( $sql );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

}
?>