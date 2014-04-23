<?php
class Football_Pool_Admin_Rankings extends Football_Pool_Admin {
	public function __construct() {}
	
	public static function help() {
		$help_tabs = array(
					array(
						'id' => 'overview',
						'title' => __( 'Overview', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>On this page you can add, change or delete custom rankings.</p><p>A ranking is calculated for the total points scored for the matches and/or bonus questions in the ranking. Change the matches and bonus questions for a ranking via the <em>\'Ranking composition\'</em> link beneath the ranking name.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
					array(
						'id' => 'log',
						'title' => __( 'Log', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>The log shows actions in the plugin that may have an affect on a  particular ranking (e.g. when a score is entered for a match). If a ranking shows a log you may want to consider doing a recalculation of that ranking.</p><p>Log is only effective when <em>Automatic Calculation</em> is off</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
					array(
						'id' => 'exclude',
						'title' => __( 'Exclude from recalculation', FOOTBALLPOOL_TEXT_DOMAIN ),
						'content' => __( '<p>If you don\'t want a ranking to be recalculated everytime a score calculation is done, you can set the  <em>calculate</em> option for the ranking to "no". Rankings with this option set to "no" will only be recalculated with a single calculation in the Ranking overview screen or in a full recalculation.</p>', FOOTBALLPOOL_TEXT_DOMAIN )
					),
				);
		$help_sidebar = sprintf( '<a href="?page=footballpool-help#rankings">%s</a>'
								, __( 'Help section about rankings', FOOTBALLPOOL_TEXT_DOMAIN )
						);
	
		self::add_help_tabs( $help_tabs, $help_sidebar );
	}
	
	public static function admin() {
		self::admin_header( __( 'User defined rankings', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		
		$item_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'set_calculate_option':
			case 'unset_calculate_option':
				if ( count( $bulk_ids) > 0 ) {
					$value = ( $action == 'set_calculate_option' ) ? 1 : 0;
					self::set_calculate_option( $bulk_ids, $value );
					self::notice( sprintf( _n( '%d ranking changed.', '%d rankings changed', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
				self::view();
				break;
			case 'save-definition':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				self::save_ranking_definition( $item_id );
				// self::update_score_history();
				
				self::notice( 'Ranking updated.', FOOTBALLPOOL_TEXT_DOMAIN );
				if ( Football_Pool_Utils::post_str( 'submit' ) == __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ) ) {
					self::view();
					break;
				}
			case 'define':
				self::define_ranking( $item_id );
				break;
			case 'save':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				// new or updated ranking
				$item_id = self::update( $item_id );
				self::notice( __( 'Ranking saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit' ) 
							== __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ) ) {
					self::view();
					break;
				}
				if ( Football_Pool_Utils::post_str( 'define' ) 
							== __( 'Save & Define', FOOTBALLPOOL_TEXT_DOMAIN ) ) {
					self::define_ranking( $item_id );
					break;
				}
			case 'edit':
				self::edit( $item_id );
				break;
			case 'delete':
				check_admin_referer( FOOTBALLPOOL_NONCE_ADMIN );
				if ( $item_id > 0 ) {
					self::delete( $item_id );
					self::notice( sprintf( __( 'Ranking id:%s deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), $item_id ) );
				}
				if ( count( $bulk_ids) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __( '%s rankings deleted.', FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::view();
		}
		
		self::admin_footer();
	}
	
	private static function set_calculate_option( $ids, $value ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$ids = implode( ',', $ids );
		$sql = "UPDATE {$prefix}rankings SET calculate = {$value} WHERE id IN ( {$ids} )";
		$wpdb->query( $sql );
	}
	
	private static function define_ranking( $id ) {
		echo '<div class="ranking-definition">';
		$ranking = self::get_ranking( $id );
		if ( $ranking != null ) {
			printf( '<h3>%s: %s<h3>', __( 'Definition of', FOOTBALLPOOL_TEXT_DOMAIN ), $ranking['name'] );
			
			printf( '<h4>%s</h4>', __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
			self::print_matches( $id );
			
			printf( '<h4>%s</h4>', __( 'bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ) );
			self::print_questions( $id );
			
			echo '<p class="submit">';
			submit_button( __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ), 'primary', 'submit', false );
			submit_button( null, 'secondary', 'save', false );
			self::cancel_button();
			echo '</p>';
			
			self::hidden_input( 'item_id', $id );
			self::hidden_input( 'action', 'save-definition' );
		} else {
			self::notice( __( 'Not a valid ranking.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		echo '</div>';
	}
	
	private static function print_questions( $id ) {
		$pool = new Football_Pool_Pool;
		
		$ranking_questions = array();
		$ranking_definition = $pool->get_ranking_questions( $id );
		if ( $ranking_definition != null ) {
			foreach( $ranking_definition as $val ) {
				$ranking_questions[] = $val['question_id'];
			}
		}
		
		$rows = $pool->get_bonus_questions();
		if ( count( $rows ) > 0 ) {
			foreach( $rows as $row ) {
				$checked = ( in_array( $row['id'], $ranking_questions ) );
				$checked = $checked ? 'checked="checked"' : '';
				printf( '<div class="question"><label><input type="checkbox" name="question-%d" value="1" %s>
							%s</label></div>'
						, $row['id']
						, $checked
						, $row['question']
				);
			}
		} else {
			printf( '<div>%s</div>', __( 'no questions found', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
	}
	
	private static function print_matches( $id ) {
		$matchtype = null;
		
		$teams = new Football_Pool_Teams;
		$pool = new Football_Pool_Pool;
		$matches = new Football_Pool_Matches;
		
		$ranking_matches = array();
		$ranking_definition = $pool->get_ranking_matches( $id );
		if ( $ranking_definition != null ) {
			foreach( $ranking_definition as $val ) {
				$ranking_matches[] = $val['match_id'];
			}
		}
		
		$rows = $matches->matches;
		if ( count( $rows ) > 0 ) {
			foreach( $rows as $row ) {
				if ( $matchtype != $row['matchtype'] ) {
					$matchtype = $row['matchtype'];
					printf( '<div class="matchtype"><label><input type="checkbox" id="matchtype-%d">
								%s</label></div>'
							, $row['match_type_id']
							, $matchtype
					);
				}
				
				// $matchdate = new DateTime( $row['play_date'] );
				// $localdate = new DateTime( Football_Pool_Utils::date_from_gmt( $matchdate->format( 'Y-m-d H:i' ) ) );
				// $localdate = new DateTime( Football_Pool_Matches::format_match_time( $matchdate, 'Y-m-d H:i' ) );
				// $localdate_formatted = date_i18n( __( 'M d, Y', FOOTBALLPOOL_TEXT_DOMAIN )
												// , $localdate->format( 'U' ) );
				
				$checked = ( in_array( $row['id'], $ranking_matches ) );
				$checked = $checked ? 'checked="checked"' : '';
				printf( '<div class="match matchtype-%d"><label><input type="checkbox" name="match-%d" value="1" %s>
							%s - %s</label></div>'
						, $row['match_type_id']
						, $row['id']
						, $checked
						, $teams->team_names[$row['home_team_id']]
						, $teams->team_names[$row['away_team_id']]
				);
			}
		} else {
			printf( '<div>%s</div>', __( 'no matches found', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
	}
	
	private static function save_ranking_definition( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		// save the matches
		$sql = $wpdb->prepare( "SELECT match_id FROM {$prefix}rankings_matches WHERE ranking_id = %d", $id );
		$old_set = $wpdb->get_col( $sql );
		$new_set = array();
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_matches WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		
		$matches = new Football_Pool_Matches();
		$rows = $matches->matches;
		foreach ( $rows as $row ) {
			$checked = Football_Pool_Utils::post_int( 'match-' . $row['id'], 0 );
			if ( $checked == 1 ) {
				$new_set[] = $row['id'];
			}
		}
		
		self::update_ranking_log( $id, $old_set, $new_set, 
								__( 'matches added or removed from ranking', FOOTBALLPOOL_TEXT_DOMAIN )
								);
		
		foreach ( $new_set as $match_id ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}rankings_matches ( ranking_id, match_id )
									VALUES ( %d, %d )"
									, $id, $match_id
								);
			$wpdb->query( $sql );
		}
		
		// save the questions
		$sql = $wpdb->prepare( "SELECT question_id FROM {$prefix}rankings_bonusquestions 
								WHERE ranking_id = %d", $id );
		$old_set = $wpdb->get_col( $sql );
		$new_set = array();
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_bonusquestions WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		
		$pool = new Football_Pool_Pool;
		$questions = $pool->get_bonus_questions();
		foreach ( $questions as $question ) {
			$checked = Football_Pool_Utils::post_int( 'question-' . $question['id'], 0 );
			if ( $checked == 1 ) {
				$new_set[] = $question['id'];
			}
		}
		
		self::update_ranking_log( $id, $old_set, $new_set, 
								__( 'questions added or removed from ranking', FOOTBALLPOOL_TEXT_DOMAIN )
								);
		
		foreach ( $new_set as $question_id ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}rankings_bonusquestions 
										( ranking_id, question_id )
									VALUES ( %d, %d )"
									, $id, $question_id
								);
			$wpdb->query( $sql );
		}
	}
	
	private static function edit( $id ) {
		$values = array(
						'name' => '',
						'calculate' => 1,
						// 'active' => 1,
					);
		
		$ranking = self::get_ranking( $id );
		if ( $ranking && $id > 0 ) {
			$values = $ranking;
		}
		$cols = array(
					array( 'text', __( 'name', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', $values['name'], '' ),
					array( 'checkbox', __( 'calculate', FOOTBALLPOOL_TEXT_DOMAIN ), 'calculate', $values['calculate'], '' ),
					// array( 'checkbox', __( 'visible on the website', FOOTBALLPOOL_TEXT_DOMAIN ), 'active', $values['active'], '' ),
					array( 'hidden', '', 'item_id', $id ),
					array( 'hidden', '', 'action', 'save' )
				);
		self::value_form( $cols );
		echo '<p class="submit">';
		submit_button( __( 'Save & Close', FOOTBALLPOOL_TEXT_DOMAIN ), 'primary', 'submit', false );
		submit_button( __( 'Save & Define', FOOTBALLPOOL_TEXT_DOMAIN ), 'secondary', 'define', false );
		submit_button( null, 'secondary', 'save', false );
		self::cancel_button();
		echo '</p>';
	}
	
	private static function get_ranking_log( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT log_date, log_message FROM {$prefix}rankings_updatelog 
								WHERE ranking_id = %d AND is_single_calculation = 0 ORDER BY log_date DESC"
								, $id );
		return $wpdb->get_results( $sql, ARRAY_A );
	}
	
	private static function get_ranking( $id ) {
		$pool = new Football_Pool_Pool;
		$ranking = $pool->get_ranking_by_id( $id );
		$log = self::get_ranking_log( $id );
		if ( $ranking != null && is_array( $ranking ) ) {
			$output = array(
							'name' => $ranking['name'],
							'calculate' => $ranking['calculate'],
							'log' => $log,
							// 'active' => $ranking['active'],
							);
		} else {
			$output = null;
		}
		
		return $output;
	}
	
	private static function ranking_log_summary( $log ) {
		$summary = '';
		$log_count = count( $log );
		$j = 2;
		if ( $log_count > 0 ) {
			$summary .= '<div class="ranking-log ranking-log-summary">';
			foreach ( $log as $line ) {
				if ( $j-- == 0 ) {
					$summary .= '</div>';
					$summary .= sprintf( '<a class="ranking-log-summary" href="">%s &raquo;</a>'
											, __( 'show more', FOOTBALLPOOL_TEXT_DOMAIN ) 
										);
					$summary .= '<div class="ranking-log ranking-log-rest">';
				}
				$date = new DateTime( $line['log_date'] );
				$summary .= sprintf( '<span>%s</span><span>%s</span><span>%s</span><br />'
									, $date->format( 'Y-m-d' )
									, $date->format( 'H:i' )
									, $line['log_message'] 
							);
			}
			
			$summary .= '</div>';
		}
		return $summary;
	}
	
	private static function get_rankings() {
		$pool = new Football_Pool_Pool;
		$rankings = $pool->get_rankings( 'user defined' );
		$output = array();
		foreach ( $rankings as $ranking ) {
			$log = self::ranking_log_summary( self::get_ranking_log( $ranking['id'] ) );
			$output[] = array(
							'id' => $ranking['id'], 
							'name' => $ranking['name'],
							'calculate' => $ranking['calculate'],
							'log' => $log,
							// 'active' => $ranking['active'],
						);
		}
		return $output;
	}
	
	private static function view() {
		$items = self::get_rankings();
		
		$cols = array(
					array( 'text', __( 'ranking', FOOTBALLPOOL_TEXT_DOMAIN ), 'name', '' ),
					array( 'boolean', __( 'calculate', FOOTBALLPOOL_TEXT_DOMAIN ), 'calculate', '' ),
					array( 'text', __( 'log', FOOTBALLPOOL_TEXT_DOMAIN ), 'log', '' ),
					array( 'text', __( 'single calculation', FOOTBALLPOOL_TEXT_DOMAIN ), 'single_calculation', '' ),
					// array( 'boolean', __( 'active', FOOTBALLPOOL_TEXT_DOMAIN ), 'active', '' ),
				);
		
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			$nonce = wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC );
			$link = self::link_button( 
						__( 'single calculation', FOOTBALLPOOL_TEXT_DOMAIN )
						, array( "admin.php?page=footballpool-score-calculation&single_ranking=%d&fp_recalc_nonce={$nonce}" )
						, false
						, array( 'id' => 'button-calculate-single-ranking-%d' )
					);
		} else {
			$link = self::link_button( 
						__( 'single calculation', FOOTBALLPOOL_TEXT_DOMAIN )
						, array( '', 'FootballPoolAdmin.calculate( 0, %d )' )
						, false
						, array( 'id' => 'button-calculate-single-ranking-%d' )
					);
		}
		$rows = array();
		foreach( $items as $item ) {
			$rows[] = array(
						$item['name'], 
						$item['calculate'], 
						sprintf( '<div id="log-ranking-%d">%s</div>', $item['id'], $item['log'] ),
						( $item['log'] != '' ) ? sprintf( $link, $item['id'], $item['id'] ) : '', 
						// $item['active'], 
						$item['id'],
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete' ), __( 'You are about to delete one or more rankings.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to delete, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'set_calculate_option', __( 'Enable calculation', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'You are about to set the calculate option on one or more rankings.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to continue, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$bulkactions[] = array( 'unset_calculate_option', __( 'Disable calculation', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'You are about to exclude one or more rankings from the calculation.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . __( 'Are you sure? `OK` to continue, `Cancel` to stop.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$rowactions[] = array( 'define', __( 'Ranking composition', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions, $rowactions );
	}
	
	private static function update( $item_id ) {
		$item = array(
						$item_id,
						Football_Pool_Utils::post_string( 'name' ),
						Football_Pool_Utils::post_int( 'calculate' ),
						// Football_Pool_Utils::post_int( 'active' ),
					);
		
		$id = self::update_item( $item );
		return $id;
	}
	
	private static function delete( $item_id ) {
		if ( is_array( $item_id ) ) {
			foreach ( $item_id as $id ) self::delete_item( $id );
		} else {
			self::delete_item( $item_id );
		}
	}
	
	private static function delete_item( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_updatelog WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_bonusquestions WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_matches WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory WHERE ranking_id = %d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings WHERE id = %d", $id );
		$wpdb->query( $sql );
	}
	
	private static function update_item( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$id = $input[0];
		$name = $input[1];
		$calculate = $input[2];
		// $active = $input[3];
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}rankings ( name, calculate )
									VALUES ( %s, %d )"
									, $name
									, $calculate
								);
		} else {
			$sql = $wpdb->prepare( "UPDATE {$prefix}rankings SET name = %s, calculate = %d
									WHERE id = %d",
									$name, $calculate, $id
								);
		}
		
		$wpdb->query( $sql );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}
}
