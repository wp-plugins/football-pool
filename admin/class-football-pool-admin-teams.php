<?php
class Football_Pool_Admin_Teams extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Teams', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		self::intro( __( 'Add, change or delete teams.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::intro( __( 'If you delete a team all matches for the team and predictions for those matches are also deleted. After a delete action the scores in the pool are recalculated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$item_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'activate':
			case 'deactivate':
				if ( $item_id > 0 ) {
					self::activate( $user_id, $action );
					if ( $action == 'activate' )
						$notice = 'Team %d activated.';
					else
						$notice = 'Team %d deactivated.';
					
					$nr = $item_id;
				}
				if ( count( $bulk_ids) > 0 ) {
					self::activate( $bulk_ids, $action );
					if ( $action == 'activate' )
						$notice = '%d teams activated.';
					else
						$notice = '%d teams deactivated.';
					
					$nr = count( $bulk_ids );
				}
				
				if ( $notice != '' ) self::notice( sprintf( __( $notice, FOOTBALLPOOL_TEXT_DOMAIN ), $nr ) );
				self::view();
				break;
			case 'save':
				// new or updated team
				$item_id = self::update( $item_id );
				self::notice( __( 'Team saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit' ) == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $item_id );
				break;
			case 'delete':
				if ( $item_id > 0 ) {
					self::delete( $item_id );
					self::notice( sprintf( __( 'Team id:%s deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), $item_id ) );
					self::notice( __( 'Scores recalculated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s teams deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
					self::notice( __( 'Scores recalculated.', FOOTBALLPOOL_TEXT_DOMAIN ) );
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
						'flag' => '',
						'link' => '',
						'group_id' => 0,
						'group_order' => 0,
						'is_real' => 1,
						'is_active' => 1,
						);
		
		$teams = new Football_Pool_Teams;
		$team = $teams->get_team_by_id( $id );
		if ( $id > 0 && is_object( $team ) && $team->id != 0 ) {
			$values = (array) $team;
		}
		
		$groups = Football_Pool_groups::get_groups();
		$options = array();
		foreach ( $groups as $group ) {
			$options[] = array( 'value' => $group->id, 'text' => $group->name );
		}
		$groups = $options;
		
		$cols = array(
					array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', $values['name'], '' ),
					array( 'image', __( 'photo', FOOTBALLPOOL_TEXT_DOMAIN ), 'photo', $values['photo'], sprintf( __( 'Image path must be a full URL to the image. Or a path relative to %s in the plugin directory', FOOTBALLPOOL_TEXT_DOMAIN ), '/assets/images/teams/' ) ),
					array( 'image', __( 'flag', FOOTBALLPOOL_TEXT_DOMAIN ), 'flag', $values['flag'], sprintf( __( 'Image path must be a full URL to the image. Or a path relative to %s in the plugin directory', FOOTBALLPOOL_TEXT_DOMAIN ), '/assets/images/flags/' ) ),
					array( 'text', __( 'link', FOOTBALLPOOL_TEXT_DOMAIN ), 'link', $values['link'], __( 'A link to a website with information about the team. Used on the team page in de plugin.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'dropdown', __( 'group', FOOTBALLPOOL_TEXT_DOMAIN ), 'group_id', $values['group_id'], $groups, '' ),
					array( 'integer', __( 'group order', FOOTBALLPOOL_TEXT_DOMAIN ), 'group_order', $values['group_order'], __( 'If teams are placed in a group and the default ordering does not work (when teams have the same points) you can fix the ordering with this number.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'checkbox', __( 'team is not a temporary team name', FOOTBALLPOOL_TEXT_DOMAIN ), 'is_real', $values['is_real'], __( 'Set to false if the team name is not a real team, e.g. "Winner match 30".', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'checkbox', __( 'team is active', FOOTBALLPOOL_TEXT_DOMAIN ), 'is_active', $values['is_active'], '' ),
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
	
	private function view() {
		$items = self::get_teams();
		
		$cols = array(
					array( 'text', __( 'team', FOOTBALLPOOL_TEXT_DOMAIN ), 'team', '' ),
					array( 'text', __( 'active', FOOTBALLPOOL_TEXT_DOMAIN ), 'is_active', '' ),
				);
		
		$rows = array();
		foreach( $items as $item ) {
			$rows[] = array(
						$item['name'], 
						$item['is_active'], 
						$item['id'],
					);
		}
		
		$bulkactions[] = array( 'activate', __( 'Activate team(s)' ), __( 'You are about to activate one or more teams.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to activate, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'deactivate', __( 'Deactivate team(s)' ), __( 'You are about to deactivate one or more teams.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to deactivate, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'delete', __( 'Delete' ), __( 'You are about to delete one or more teams.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to delete, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions );
	}
	
	private function update( $item_id ) {
		$item = array(
						$item_id,
						Football_Pool_Utils::post_string( 'name' ),
						Football_Pool_Utils::post_string( 'photo' ),
						Football_Pool_Utils::post_string( 'flag' ),
						Football_Pool_Utils::post_string( 'link' ),
						Football_Pool_Utils::post_int( 'group_id' ),
						Football_Pool_Utils::post_int( 'group_order' ),
						Football_Pool_Utils::post_int( 'is_real' ),
						Football_Pool_Utils::post_int( 'is_active' ),
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
		// recalculate scorehistory
		self::update_score_history();
	}
	
	private function delete_item( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		// delete all teams, matches for that team and predictions made for those matches
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}predictions 
								WHERE matchNr IN 
									( SELECT nr FROM {$prefix}matches WHERE homeTeamId = %d OR awayTeamId = %d )"
								, $id, $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}matches WHERE homeTeamId = %d OR awayTeamId = %d"
								, $id, $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}teams WHERE id = %d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_item( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		list( $id, $name, $photo, $flag, $link, $group_id, $group_order, $is_real, $is_active ) = $input;
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}teams 
										( name, photo, flag, link, groupId, groupOrder, is_real, is_active )
									VALUES 
										( %s, %s, %s, %s, %d, %d, %d, %d )",
									$name, $photo, $flag, $link, $group_id, $group_order, $is_real, $is_active
								);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}teams SET
										name = %s, photo = %s, flag = %s, link = %s, 
										groupId = %d, groupOrder = %d, is_real = %d, is_active = %d
									WHERE id = %d",
									$name, $photo, $flag, $link, 
									$group_id, $group_order, $is_real, $is_active, 
									$id
								);
		}
		
		$wpdb->query( $sql );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}

	private function get_teams() {
		$teams = Football_Pool_Teams::get_teams();
		$output = array();
		foreach ( $teams as $team ) {
			$output[] = array(
							'id' => $team->id, 
							'name' => $team->name, 
							'is_active' => $team->is_active
						);
		}
		return $output;
	}
	
	private function activate( $team_id, $active = 'activate' ) {
		if ( is_array( $team_id ) ) {
			foreach ( $team_id as $id ) self::activate_team( $id, $active );
		} else {
			self::activate_team( $team_id, $active );
		}
	}

	private function activate_team( $id, $active = 'activate' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$active = ( $active == 'activate' ) ? 1 : 0;
		$sql = $wpdb->prepare( "UPDATE {$prefix}teams SET is_active = %d WHERE id = %d"
								, $active, $id );
		$wpdb->query( $sql );
	}
}
?>