<?php
class Football_Pool_Admin {
	/**
	 * Somewhat hacky way of replacing "Insert into Post" with "Use Image"
	 *
	 * @param string $translated_text text that has already been translated (normally passed straight through)
	 * @param string $source_text text as it is in the code
	 * @param string $domain domain of the text
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	public function replace_text_in_thickbox($translated_text, $source_text, $domain) {
		if ( Football_Pool_Utils::get_string( 'football_pool_admin' ) == 'footballpool-bonus' ) {
			if ('Insert into Post' == $source_text) {
				return __('Use Image', FOOTBALLPOOL_TEXT_DOMAIN );
			}
		}
		return $translated_text;
	}
	
	public function init() {
		$slug = 'footballpool-options';
		
		add_menu_page(
			__( 'Football Pool', FOOTBALLPOOL_TEXT_DOMAIN ),
			__( 'Football Pool', FOOTBALLPOOL_TEXT_DOMAIN ),
			'administrator',
			$slug,
			array( 'Football_Pool_Admin_Options', 'admin' ),
			'div'
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit users', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Users', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-users',
			array( 'Football_Pool_Admin_Users', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-games',
			array( 'Football_Pool_Admin_Games', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Questions', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-bonus',
			array( 'Football_Pool_Admin_Bonus_Questions', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-shoutbox',
			array( 'Football_Pool_Admin_Shoutbox', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit team position', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Teams', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-groups',
			array( 'Football_Pool_Admin_Groups', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Edit leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-leagues',
			array( 'Football_Pool_Admin_Leagues', 'admin' )
		);
		
		add_submenu_page(
			$slug,
			__( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'administrator', 
			'footballpool-help',
			array( 'Football_Pool_Admin_Help', 'admin' )
		);
	}
	
	public function add_plugin_settings_link( $links, $file ) {
		if ( $file == plugin_basename( dirname( FOOTBALLPOOL_ERROR_LOG ) . '/football-pool.php' ) ) {
			$links[] = '<a href="admin.php?page=footballpool-options">' . __( 'Settings', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
			$links[] = '<a href="admin.php?page=footballpool-help">' . __( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
			$links[] = '<a href="' . FOOTBALLPOOL_DONATE_LINK . '">' . __( 'Donate', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
		}

		return $links;
	}
	
	public function get_value( $key, $default = '' ) {
		return Football_Pool_Utils::get_wp_option( 'footballpool_' . $key, $default );
	}
	
	public function set_value( $key, $value, $type = 'text' ) {
		update_option( 'footballpool_' . $key, $value );
	}
	
	// use type 'updated' for yellow message and type 'error' or 'important' for the red one
	public function notice( $msg, $type = 'updated', $fade = true ) {
		if ( $type == 'important' ) $type = 'error';
		echo '<div class="', esc_attr( $type ), ( $fade ? ' fade' : '' ), '"><p>', $msg, '</p></div>';
	}
	
	public function image_input( $label, $key, $value, $description = '', $type = 'regular-text' ) {
		$key = esc_attr( $key );
		echo '<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( "#', $key, '_button" ).click( function() {
					post_id = jQuery( "#post_ID" ).val();
					tb_show( "", "media-upload.php?football_pool_admin=footballpool-bonus&amp;post_id=0&amp;type=image&amp;TB_iframe=true" );
					return false;
				});
				 
				window.send_to_editor = function( html ) {
					imgurl = jQuery( "img", html ).attr( "src" );
					if ( imgurl == "" && jQuery( "#src" ) ) imgurl = jQuery( "#src" ).val();
					
					jQuery( "#', $key, '" ).val( imgurl );
					tb_remove();
				}
			});
			</script>';
		
		echo '<tr id="r-', esc_attr( $key ), '" valign="top">
			<th scope="row"><label for="', $key, '">', $label, '</label></th>
			<td><input name="', $key, '" type="text" id="', $key, '" value="', esc_attr( $value ), '" class="', esc_attr( $type ), '" />
			<input id="', $key, '_button" type="button" value="', __( 'Choose Image', FOOTBALLPOOL_TEXT_DOMAIN ), '" /></td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
	}
	
	public function text_input( $label, $key, $value, $description = '', $type = 'regular-text', $depends_on = '' ) {
		$hide = ( $depends_on != '' && (int)self::get_value( $depends_on ) == 0 ) ? ' style="display:none;"' : '';
		
		echo '<tr', $hide, ' id="r-', esc_attr( $key ), '" valign="top">
			<th scope="row"><label for="', esc_attr( $key ), '">', $label, '</label></th>
			<td><input name="', esc_attr( $key ),'" type="text" id="', esc_attr( $key ), '" value="', esc_attr( $value ), '" class="', esc_attr( $type ), '" /></td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
	}
	
	public function checkbox_input( $label, $key, $checked, $description = '', $extra_attr = '' ) {
		echo '<tr id="r-', esc_attr( $key ), '" valign="top">
			<th scope="row"><label for="', esc_attr( $key ), '">', $label, '</label></th>
			<td><input name="', esc_attr( $key ),'" type="checkbox" id="', esc_attr( $key ), '" value="1" ', ($checked ? 'checked="checked" ' : ''), ' ', $extra_attr, '/></td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
	}
	
	public function radiolist_input( $label, $key, $value, $options, $description = '' ) {
		$i = 1;
		echo '<tr id="r-', esc_attr( $key ), '" valign="top"><th scope="row"><label for="answer_1">', $label, '</label></th><td>';
		foreach ( $options as $option ) {
			echo '<label class="radio"><input name="', esc_attr( $key ),'" type="radio" id="answer_', $i++, '" value="', esc_attr( $option['value'] ), '" ', ( $option['value'] == $value ? 'checked="checked" ' : '' ), '/> ', $option['text'], '</label><br />';
		}
		echo '</td><td><span class="description">', $description, '</span></td></tr>';
	}
	
	public function hidden_input( $key, $value ) {
		echo '<input type="hidden" name="', esc_attr( $key ), '" id="', esc_attr( $key ), '" value="', esc_attr( $value ), '" />';
	}
	
	public function no_input( $label, $value, $description ) {
		echo '<tr valign="top">
			<th scope="row"><label>', $label, '</label></th>
			<td>', $value, '</td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
	}
	
	public function datetime_input( $label, $key, $value, $description = '', $extra_attr = '' ) {
		echo '<tr id="r-', esc_attr( $key ), '" valign="top"><th scope="row"><label for="', esc_attr( $key ), '_y">', $label, '</label></th><td>';
		if ( $value != '' ) {
			//$date = DateTime::createFromFormat( 'Y-m-d H:i', $value );
			$date = new DateTime( $value );
			$year = $date->format( 'Y' );
			$month = $date->format( 'm' );
			$day = $date->format( 'd');
			$hour = $date->format( 'H' );
			$minute = $date->format( 'i' );
		} else {
			$year = $month = $day = $hour = $minute = '';
		}
		echo '<input name="', esc_attr( $key ),'_y" type="text" id="', esc_attr( $key ), '_y" value="', esc_attr( $year ), '" class="with-hint date-y" title="yyyy" maxlength="4" />';
		echo '-';
		echo '<input name="', esc_attr( $key ),'_m" type="text" id="', esc_attr( $key ), '_m" value="', esc_attr( $month ), '" class="with-hint date-m" title="mm" maxlength="2" />';
		echo '-';
		echo '<input name="', esc_attr( $key ),'_d" type="text" id="', esc_attr( $key ), '_d" value="', esc_attr( $day ), '" class="with-hint date-d" title="dd" maxlength="2" />';
		echo '&nbsp;';
		echo '<input name="', esc_attr( $key ),'_h" type="text" id="', esc_attr( $key ), '_m" value="', esc_attr( $hour ), '" class="with-hint date-h" title="hr" maxlength="2" />';
		echo ':';
		echo '<input name="', esc_attr( $key ),'_i" type="text" id="', esc_attr( $key ), '_d" value="', esc_attr( $minute ), '" class="with-hint date-i" title="mn" maxlength="2" />';
		
		echo '</td><td><span class="description">', $description, '</span></td></tr>';
	}
	
	public function show_option( $option ) {
		switch ( $option[0] ) {
			case 'checkbox':
				self::checkbox_input( $option[1], $option[2], (boolean) self::get_value( $option[2] ), $option[3], ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
			case 'datetime':
				self::datetime_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
			case 'integer':
			case 'string':
			case 'text':
			default:
				self::text_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], 'regular-text', ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
		}
	}
	
	public function show_value( $option ) {
		switch ( $option[0] ) {
			case 'no_input':
				self::no_input( $option[1], $option[3], $option[4] );
				break;
			case 'radiolist':
				self::radiolist_input( $option[1], $option[2], $option[3], $option[4], $option[5] );
				break;
			case 'checkbox':
				self::checkbox_input( $option[1], $option[2], $option[3], $option[4] );
				break;
			case 'hidden':
				self::hidden_input( $option[2], $option[3] );
				break;
			case 'image':
				self::image_input( $option[1], $option[2], $option[3], $option[4] );
				break;
			case 'integer':
			case 'date':
			case 'string':
			case 'text':
			default:
				self::text_input( $option[1], $option[2], $option[3], $option[4] );
				break;
		}
	}
	
	public function intro( $txt ) {
		echo sprintf( '<p>%s</p>', $txt );
	}
	
	public function help( $id, $title, $content ) {
		// why won't this work???
		get_current_screen()->add_help_tab( array(
												'id'		=> $id,
												'title'		=> $title,
												'content'	=> '<p>' . $content . '</p>'
											) 
								);
	}
	
	public function admin_sectiontitle( $title ) {
		echo '<h3>', $title, '</h3>';
	}
	
	public function admin_header( $title, $subtitle = '', $addnew = '' ) {
		$page = Football_Pool_Utils::get_string( 'page' );
		if ( $addnew == 'add new' ) {
			$addnew = "<a class='add-new-h2' href='?page={$page}&amp;action=edit'>" . __( 'Add New', FOOTBALLPOOL_TEXT_DOMAIN ) . "</a>";
		}
		echo '<div class="wrap">';
		screen_icon();
		if ( $subtitle != '' ) {
			$subtitle = sprintf( '<span class="subtitle">%s</span>', $subtitle );
		}
		printf( '<h2>%s%s%s</h2>', $title, $subtitle, $addnew );
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="form_action" id="form_action" value="update" />';
	}
	
	public function admin_footer() {
		echo '</form></div>';
	}
	
	public function bulk_actions( $actions, $name = 'action' ) {
		if ( count($actions) > 0 ) {
			echo '<div class="tablenav top"><div class="alignleft actions"><select name="', $name, '">';
			echo '<option selected="selected" value="-1">Bulk Actions</option>';
			foreach ( $actions as $action ) {
				printf( '<option value="%s">%s</option>', $action[0], $action[1] );
			}
			echo '</select><input type="submit" value="Apply" class="button-secondary action" id="do', $name, '" name="" />';
			echo '</div><br class="clear"></div>';
		}
	}
	
	protected function list_table( $cols, $rows, $bulkactions = array(), $rowactions = array() ) {
		self::bulk_actions( $bulkactions, 'action' );
		echo "<table cellspacing='0' class='wp-list-table widefat fixed'>";
		self::list_table_def( $cols, 'head' );
		self::list_table_def( $cols, 'foot' );
		self::list_table_body( $cols, $rows, $rowactions );
		echo '</table>';
		self::bulk_actions( $bulkactions, 'action2' );
	}
	
	protected function list_table_def( $cols, $tag ) {
		echo "<t{$tag}><tr>";
		echo '
			<th class="manage-column column-cb check-column" id="cb" scope="col">
				<input type="checkbox">
			</th>';
		
		foreach ( $cols as $col ) {
			echo '<th id="', esc_attr( $col[2] ), '" class="manage-column column-', esc_attr( $col[2] ), '" scope="col">', $col[1], '</th>';
		}
		echo "</tr></t{$tag}>";
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
						echo '<strong><a title="Edit “', esc_attr( $rows[$i][$j] ), '”" href="?page=', esc_attr( $page ), '&amp;action=edit&amp;item_id=', esc_attr( $rows[$i][$c] ), '" class="row-title">';
					}
					echo $rows[$i][$j];
					
					if ( $j == 0 ) {
						echo '</a></strong><br>
								<div class="row-actions">
									<span class="edit">
										<a href="?page=', esc_attr( $page ), '&amp;action=edit&amp;item_id=', esc_attr( $rows[$i][$c] ), '">Edit</a> | 
									</span>';
						foreach ( $rowactions as $action ) {
							echo '<span class="edit">
									<a href="?page=', esc_attr( $page ), '&amp;action=', esc_attr( $action[0] ), '&amp;item_id=', esc_attr( $rows[$i][$c] ), '">', $action[1], '</a> | 
								</span>';
						}
						echo "<span class='delete'>
									<a onclick=\"if ( confirm( 'You are about to delete this item. \'Cancel\' to stop, \'OK\' to delete.' ) ) { return true;}return false;\" href='?page={$page}&amp;action=delete&amp;item_id={$rows[$i][$c]}' class='submitdelete'>Delete</a>
								</span>
							</div>";
					}
					
					echo "</td>";
				}
				echo "</tr>";
			}
		}
		echo '</tbody>';
	}
	
	public function value_form( $values ) {
		echo '<table class="form-table">';
		foreach ( $values as $value ) {
			self::show_value( $value );
		}
		echo '</table>';
	}

	public function options_form( $values ) {
		echo '<table class="form-table">';
		foreach ( $values as $value ) {
			self::show_option( $value );
		}
		echo '</table>';
	}
	
	public function update_score_history() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool;
		
		// 1. empty table
		$sql  = "TRUNCATE TABLE {$prefix}scorehistory";
		$check = $wpdb->query( $sql );
		// fix if user has no TRUNCATE rights
		if ( $check === false ) {
			$sql  = "DELETE FROM {$prefix}scorehistory";
			$check = $wpdb->query( $sql );
		}
		$result = $check;
		if ( $check !== false ) {
			// 2. check predictions with actual match result (score type = 0)
			$sql = "INSERT INTO {$prefix}scorehistory
						(type, scoreDate, scoreOrder, userId, score, full, toto, ranking) 
					SELECT 0, m.playDate, m.nr, u.ID, 
									IF (p.hasJoker = 1, 2, 1) AS score,
									IF (m.homeScore = p.homeScore AND m.awayScore = p.awayScore, 1, NULL) AS full,
									IF (m.homeScore = p.homeScore AND m.awayScore = p.awayScore, NULL, 
										IF (
													IF (m.homeScore > m.awayScore, 1, IF (m.homeScore = m.awayScore, 3, 2) )
													=
													IF (p.homeScore > p.awayScore, 1, IF (p.homeScore = p.awayScore, 3, 2) )
											, IF (p.homeScore IS NULL OR p.awayScore IS NULL, NULL, 1)
											, NULL)
									) AS toto,
									0
					FROM {$wpdb->users} u ";
			if ( $pool->has_leagues ) {
				$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
				$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
			} else {
				$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			}
			$sql .= "LEFT OUTER JOIN {$prefix}matches m ON ( 1 = 1 )
					LEFT OUTER JOIN {$prefix}predictions p
						ON ( p.matchNr = m.nr AND ( p.userId = u.ID OR p.userId IS NULL ) )
					WHERE m.homeScore IS NOT NULL AND m.awayScore IS NOT NULL ";
			if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
			$check = $wpdb->query( $sql );
			$result &= ( $check !== false );
			// 3. update score for matches
			$full = Football_Pool_Utils::get_wp_option( 'footballpool_fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
			$toto = Football_Pool_Utils::get_wp_option( 'footballpool_totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
			$sql = "UPDATE {$prefix}scorehistory 
					SET score = score * ( full * " . $full . " + toto * " . $toto . " ) 
					WHERE type = 0";
			$check = $wpdb->query( $sql );
			$result &= ( $check !== false );
			// 4. add bonusquestion scores (score type = 1)
			//    make sure to take the userpoints into account (we can set an alternate score for an individual user in the admin)
			$sql = "INSERT INTO {$prefix}scorehistory 
						( type, scoreDate, scoreOrder, userId, score, full, toto, ranking ) 
					SELECT 
						1, q.scoreDate, q.id, u.ID, ( IF ( a.points <> 0, a.points, q.points ) * IFNULL( a.correct, 0 ) ), NULL, NULL, 0 
					FROM {$wpdb->users} u ";
			if ( $pool->has_leagues ) {
				$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
				$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
			} else {
				$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			}
			$sql .= "LEFT OUTER JOIN {$prefix}bonusquestions q
						ON ( 1 = 1 )
					LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a 
						ON ( a.questionId = q.id AND ( a.userId = u.ID OR a.userId IS NULL ) )
					WHERE q.scoreDate IS NOT NULL ";
			if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
			$check = $wpdb->query( $sql );
			$result &= ( $check !== false );
			// 5. update score incrementally
			/* 
			$sql = "ALTER TABLE {$prefix}scorehistory DROP INDEX totalScore";
			$wpdb->query( $sql );
			$sql = "UPDATE {$prefix}scorehistory
					SET totalScore = score+IncrementalSum(userId, scoreDate)
					ORDER BY scoreDate ASC";
			$wpdb->query( $sql );
			$sql = "ALTER TABLE {$prefix}scorehistory ADD INDEX (totalScore)";
			$wpdb->query( $sql );
			//*/
			//*
			$users = get_users( '' );
			
			foreach ( $users as $user ) {
				$sql = $wpdb->prepare( "SELECT * FROM {$prefix}scorehistory 
										WHERE userId = %d ORDER BY scoreDate ASC, type ASC, scoreOrder ASC",
										$user->ID
								);
				$rows = $wpdb->get_results( $sql, ARRAY_A );
				
				$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory WHERE userId = %d", $user->ID );
				$check = $wpdb->query( $sql );
				$result &= ( $check !== false );
				
				$score = 0;
				foreach ( $rows as $row ) {
					$score += $row['score'];
					$sql = $wpdb->prepare( "INSERT INTO {$prefix}scorehistory 
												( type, scoreDate, scoreOrder, userId, score, full, toto, totalScore, ranking ) 
											VALUES ( %d, %s, %d, %d, %d, %d, %d, %d, 0 )",
											$row['type'], $row['scoreDate'], $row['scoreOrder'], $row['userId'], 
											$row['score'], $row['full'], $row['toto'], $score
									);
					$check = $wpdb->query( $sql );
					$result &= ( $check !== false );
				}
			}
			//*/
			// 6. update ranking
			$pool = new Football_Pool_Pool;
			$sql = "SELECT scoreDate, type FROM {$prefix}scorehistory GROUP BY scoreDate, type";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $rows as $row ) {
				$sql = $pool->get_ranking_from_score_history( 0, $row['scoreDate'] );
				$rows2 = $wpdb->get_results( $sql, ARRAY_A );
				$rank = 1;
				foreach ( $rows2 as $row2 ) {
					$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory SET ranking = %d 
											WHERE userId = %d AND type = %d AND scoreDate = %s",
											$rank++,
											$row2['userId'],
											$row["type"],
											$row['scoreDate']
									);
					$check = $wpdb->query( $sql );
					$result &= ( $check !== false );
				}
			}
		}
		
		return $result;
	}
	
	public function cancel_button( $wrap = false ) {
		submit_button( __( 'Cancel', FOOTBALLPOOL_TEXT_DOMAIN ), 'secondary', 'cancel', $wrap, array( 'onclick' => "jQuery('#action').val('cancel')" ) );
	}
}
?>