<?php
class Football_Pool_Admin_Users extends Football_Pool_Admin {
	public function __construct() {}

	public function admin() {
		self::admin_header( __( 'Users', FOOTBALLPOOL_TEXT_DOMAIN ), '', '' );
		self::intro( __( 'Change users in the football pool.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			self::intro( __( 'You are using leagues. To exclude users from the pool you have to take them out of any league.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		} else {
			self::intro( __( 'To exclude users tick the appropiate column', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		$user_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );

		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		if ( Football_Pool_Utils::request_string( 'submit' ) == __( 'Save Changes' ) ) {
			$action = 'save';
		}
		
		switch ( $action ) {
			case 'save':
				self::update();
				self::notice( __( 'Changes saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				break;
			case 'remove':
				if ( $user_id > 0 ) {
					self::remove( $user_id );
					$user = get_userdata( $user_id );
					self::notice( sprintf( __( '%s deleted as a user.', FOOTBALLPOOL_TEXT_DOMAIN ), $user->display_name ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::remove( $bulk_ids );
					self::notice( sprintf( __( '%s users removed as user.', FOOTBALLPOOL_TEXT_DOMAIN )
											, count( $bulk_ids )
										)
								);
				}
				break;
			case 'add':
				if ( $user_id > 0 ) {
					self::add( $user_id );
					$user = get_userdata( $user_id );
					self::notice( sprintf( __( '%s added as a user.', FOOTBALLPOOL_TEXT_DOMAIN ), $user->display_name ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::add( $bulk_ids );
					self::notice( sprintf( __( '%s users added as user.', FOOTBALLPOOL_TEXT_DOMAIN )
											, count( $bulk_ids )
										)
								);
				}
				break;
		}
		
		if ( $action != 'list' ) {
			$success = self::update_score_history();
			if ( $success )
				self::notice( __( 'Scores recalculated.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
			else
				self::notice( __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database.', FOOTBALLPOOL_TEXT_DOMAIN ), 'important' );
		}
		
		self::view();
		self::admin_footer();
	}

	private function get_users() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$pool = new Football_Pool_Pool;
		
		$output = array();
		$excluded_players = array();
		$league_users = array();
		
		$sql = "SELECT * FROM {$prefix}league_users";
		$users = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $users as $user ) {
			$league_users[$user['userId']] = $user['leagueId'];
			if ( $user['leagueId'] == 0 )
				$excluded_players[] = $user['userId'];
		}
		
		$users = get_users( '' );
		foreach ( $users as $user ) {
			$league_id = get_the_author_meta( 'footballpool_registeredforleague', $user->ID );
			$league_name = array_key_exists( $league_id, $pool->leagues ) ? 
									$pool->leagues[$league_id]['leagueName'] : 
									__( 'unknown', FOOTBALLPOOL_TEXT_DOMAIN );
			
			$is_no_player = in_array( $user->ID, $excluded_players ) ? 1 : 0;
			$plays_in_league = array_key_exists( $user->ID, $league_users ) ? $league_users[$user->ID] : 0;
			
			$output[] = array(
							'id'					=> $user->ID,
							'name'					=> $user->display_name,
							//'plays_in_league'		=> get_the_author_meta( 'footballpool_league', $user->ID ),
							'plays_in_league'		=> $plays_in_league,
							'subscribed_for_league'	=> $league_name,
							'is_no_player'			=> $is_no_player,
							'payed_for_pool'		=> get_the_author_meta( 'footballpool_payed', $user->ID ),
							'email_address'			=> $user->user_email,
						);
		}
		return $output;
	}

	private function view() {
		$pool = new Football_Pool_Pool;
		$has_leagues = $pool->has_leagues;
		$users = self::get_users();
		
		$cols = array();
		$cols[] = array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', '' );
		if ( $has_leagues ) {
			$cols[] = array( 'select', __( 'plays in league', FOOTBALLPOOL_TEXT_DOMAIN ), 'plays_in_league', '' );
			$cols[] = array( 'text', __( 'registered for league', FOOTBALLPOOL_TEXT_DOMAIN ), 'subscribed_for_league', '' );
		} else {
			$cols[] = array( 'checkbox', __( 'not a user in the pool', FOOTBALLPOOL_TEXT_DOMAIN ), 'is_no_player', '' );
		}
		$cols[] = array( 'checkbox', __( 'payed?', FOOTBALLPOOL_TEXT_DOMAIN ), 'payed_for_pool', '' );

		$rows = array();
		foreach( $users as $user ) {
			$temp = array();
			$temp[] = $user['name'];
			if ( $has_leagues ) {
				$temp[] = $user['plays_in_league'];
				$temp[] = $user['subscribed_for_league'];
			} else {
				$temp[] = $user['is_no_player'];
			}
			$temp[] = $user['payed_for_pool'];
			$temp[] = $user['id'];
			
			$rows[] = $temp;
		}

		$rowactions[] = array( 'add', __( 'Add', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$rowactions[] = array( 'remove', __( 'Remove', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'add', __( 'Add to football pool', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'You are about to add one or more users to the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? \'OK\' to delete, \'Cancel\' to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'remove', __( 'Remove from football pool', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'You are about to remove one or more users from the pool.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? \'OK\' to delete, \'Cancel\' to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions, $rowactions );
		
		submit_button();
		
		self::list_email_addresses( $users );
	}

	private function list_email_addresses( $users ) {
		$players = $not_players = array();
		
		foreach ( $users as $user ) {
			if ( $user['is_no_player'] == 1 ) {
				$not_players[] = $user['email_address'];
			} else {
				$players[] = $user['email_address'];
			}
		}
		
		printf( '<h3>%s</h3>', __( 'Email addresses', FOOTBALLPOOL_TEXT_DOMAIN ) );
		printf( '<div class="email-addresses players">
					<label for="player-addresses">%s</label>
					<textarea id="player-addresses" onfocus="this.select()">%s</textarea>
					</div>'
				, __( 'Player', FOOTBALLPOOL_TEXT_DOMAIN )
				, implode( '; ', $players ) 
		);
		printf( '<div class="email-addresses not-players">
					<label for="not-player-addresses">%s</label>
					<textarea id="not-player-addresses" onfocus="this.select()">%s</textarea>
					</div>'
				, __( 'Not a player', FOOTBALLPOOL_TEXT_DOMAIN )
				, implode( '; ', $not_players ) 
		);
	}
	
	private function update() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool();
		$has_leagues = $pool->has_leagues;
		$default_league = Football_Pool_Utils::get_wp_option( 'footballpool_default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT );
		
		$users = get_users();
		foreach ( $users as $user ) {
			$payed = Football_Pool_Utils::post_integer( 'payed_for_pool_' . $user->ID );
			update_user_meta( $user->ID, 'footballpool_payed', $payed );
			
			if ( $has_leagues ) {
				$plays_in_league = Football_Pool_Utils::post_integer( 'plays_in_league_' . $user->ID, $default_league );
				update_user_meta( $user->ID, 'footballpool_league', $plays_in_league );
				$pool->update_league_for_user( $user->ID, $plays_in_league );
			} else {
				$is_no_player = Football_Pool_Utils::post_integer( 'is_no_player_' . $user->ID );
				if ( $is_no_player == 1 ) 
					self::remove_user( $user->ID );
				else
					self::add_user( $user->ID );
			}
		}
	}

	private function remove( $user_id ) {
		if ( is_array( $user_id ) ) {
			foreach ( $user_id as $id ) self::remove_user( $id );
		} else {
			self::remove_user( $user_id );
		}
	}

	private function remove_user( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			update_user_meta( $id, 'footballpool_league', 0 );
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}league_users WHERE userId = %d", $id );
			$wpdb->query( $sql );
		} else {
			$pool->update_league_for_user( $id, 0 );
		}
	}

	private function add( $user_id ) {
		if ( is_array( $user_id ) ) {
			foreach ( $user_id as $id ) self::add_user( $id );
		} else {
			self::add_user( $user_id );
		}
	}

	private function add_user( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$pool = new Football_Pool_Pool();
		if ( $pool->has_leagues ) {
			$default_league = Football_Pool_Utils::get_wp_option( 'footballpool_default_league_new_user', FOOTBALLPOOL_LEAGUE_DEFAULT, 'ínt' );

			update_user_meta( $id, 'footballpool_league', $default_league );
			// if user is in a non-existing league, then force the update
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}league_users lu 
									LEFT OUTER JOIN {$prefix}leagues l
										ON ( lu.leagueId = l.id )
									WHERE lu.userId = %d AND l.id IS NULL"
									, $id
							);
			$non_existing_league = ( $wpdb->get_var( $sql ) == 1 );
			if ( $non_existing_league )
				$pool->update_league_for_user( $id, $default_league, 'update league' );
			else
				$pool->update_league_for_user( $id, $default_league, 'no update' );
		} else {
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}league_users WHERE userId = %d AND leagueId = 0", $id );
			$wpdb->query( $sql );
		}
	}

	protected function list_table( $cols, $rows, $bulkactions = array(), $rowactions = array() ) {
		parent::bulk_actions( $bulkactions, 'action' );
		echo "<table cellspacing='0' class='wp-list-table widefat fixed user-list'>";
		parent::list_table_def( $cols, 'head' );
		parent::list_table_def( $cols, 'foot' );
		self::list_table_body( $cols, $rows, $rowactions );
		echo '</table>';
		parent::bulk_actions( $bulkactions, 'action2' );
	}

	protected function list_table_field( $type, $value, $name = '', $source = '' ) {
		switch ( $type ) {
			case 'checkbox':
				$checked = $value == 1 ? 'checked="checked" ' : '';
				$output = '<input type="checkbox" value="1" name="' . $name . '" ' . $checked . '/>';
				break;
			case 'select':
				$pool = new Football_Pool_Pool;
				$output = $pool->league_select( $value, $name );
				//@todo: make a generic method that can be used with different data-sources for the select
				// if ( is_array( $source ) && count( $source ) > 0 ) {
					// $output = '<select></select>';
				// } else {
					// $output = $value;
				// }
				break;
			case 'text':
			default:
				$output = $value;
		}

		return $output;
	}

	protected function list_table_body( $cols, $rows, $rowactions ) {
		echo "<tbody id='the-list'>";

		$r = count( $rows );
		$c = count( $cols );
		$page = Football_Pool_Utils::get_string( 'page' );

		if ( $r == 0 ) {
			echo "<tr><td colspan='", $c+1, "'>", __( 'no data', FOOTBALLPOOL_TEXT_DOMAIN ), "</td></tr>";
		} else {
			for ( $i = 0; $i < $r; $i++ ) {
				$row_class = ( $i % 2 == 0 ) ? 'alternate' : '';
				echo "
					<tr valign='middle' class='{$row_class}' id='row-{$i}'>
					<th class='check-column' scope='row'>
						<input type='checkbox' value='{$rows[$i][$c]}' name='itemcheck[]'>
					</th>";
				for ( $j = 0; $j < $c; $j++ ) {
					echo "<td class='column-{$cols[$j][2]}'>";
					if ( $j == 0 ) {
						echo '<strong><a title="Edit “', esc_attr( $rows[$i][$j] ), '”" href="user-edit.php?user_id=', esc_attr( $rows[$i][$c] ), '" class="row-title">';
					}
					$name = $cols[$j][2] . '_' . $rows[$i][$c];
					echo self::list_table_field( $cols[$j][0], $rows[$i][$j], $name, $cols[$j][3] );

					if ( $j == 0 ) {
						echo '</a></strong><br>
								<div class="row-actions">
									<span class="edit">
										<a href="user-edit.php?user_id=', esc_attr( $rows[$i][$c] ), '">Edit</a>
									</span>';
						foreach ( $rowactions as $action ) {
							$span_class = $action[0] == 'remove' ? 'delete' : 'edit';
							echo '<span class="', $span_class, '">
									| <a href="?page=', esc_attr( $page ), '&amp;action=', esc_attr( $action[0] ), '&amp;item_id=', esc_attr( $rows[$i][$c] ), '">', $action[1], '</a>
								</span>';
						}
						echo "</div>";
					}

					echo "</td>";
				}
				echo "</tr>";
			}
		}
		echo '</tbody>';
	}

}
?>