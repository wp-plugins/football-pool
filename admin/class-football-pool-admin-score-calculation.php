<?php
class Football_Pool_Admin_Score_Calculation extends Football_Pool_Admin {
	private static $start = 0;
	
	public function process() {
		// get step number and other parameters
		$step = $sub_step = $total_steps = $progress = 0;
		$user_set = $total_user_sets = $total_users = $calculation_type = 0;
		$step_0_data = Football_Pool_Utils::post_string( 'step-0-data', '' );
		if ( $step_0_data != '' ) {
			extract( json_decode( $step_0_data, true ), EXTR_IF_EXISTS );
		} else {
			$step = self::post_int( 'step', self::$start );
			$sub_step = self::post_int( 'sub_step', 1 );
			$total_steps = self::post_int( 'total_steps', 0 );
			$progress = self::post_int( 'progress', 0 );
			$user_set = self::post_int( 'user_set', 0 );
			$total_user_sets = self::post_int( 'total_user_sets', 0 );
			$total_users = self::post_int( 'total_users', 0 );
			$calculation_type = self::post_string( 'calculation_type', FOOTBALLPOOL_RANKING_CALCULATION_FULL );
		}
		
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			if ( $step > 0 ) check_admin_referer( FOOTBALLPOOL_NONCE_SCORE_CALC, 'fp_recalc_nonce' );
		} else {
			check_ajax_referer( FOOTBALLPOOL_NONCE_SCORE_CALC, 'fp_recalc_nonce' );
		}
		$nonce = wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC );
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$params = array();
		$pool = new Football_Pool_Pool;
		$check = true;
		$result = 0;
		$output = '';
		
		// is this a single ranking calculation?
		$ranking_id = self::post_int( 'single_ranking', 0 );
		$is_single_ranking = ( $ranking_id > 0 );
		if ( ! $is_single_ranking ) {
			$ranking_id = self::post_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} elseif ( $step > 0 ) {
			// get ranking matches and ranking questions to narrow the results
			$ranking_matches = $pool->get_ranking_matches( $ranking_id );
			if ( $ranking_matches == null ) {
				$ranking_matches = '0';
			} else {
				$ids = array();
				foreach ( $ranking_matches as $key => $val ) {
					$ids[] = $val['match_id'];
				}
				$ranking_matches = implode( ',', $ids );
			}
			
			$ranking_questions = $pool->get_ranking_questions( $ranking_id );
			if ( $ranking_questions == null ) {
				$ranking_questions = '0';
			} else {
				$ids = array();
				foreach ( $ranking_questions as $key => $val ) {
					$ids[] = $val['question_id'];
				}
				$ranking_questions = implode( ',', $ids );
			}
		}

		if ( $total_user_sets > 0 ) {
			$from = ( $user_set * FOOTBALLPOOL_RECALC_STEP5_DIV ) + 1;
			$to = ( ( $user_set + 1 ) * FOOTBALLPOOL_RECALC_STEP5_DIV );
			if ( $to > $total_users ) $to = $total_users;
			$user_batch = sprintf( __( '(users %d - %d of %d)', FOOTBALLPOOL_TEXT_DOMAIN )
									, $from, $to, $total_users
							);
		} else {
			$user_batch = '';
		}

		$step_string = '';
		if ( in_array( $step, array( 2, 3, 4, 6 ) ) ) {
			$step_string = sprintf( __( '(step %d)', FOOTBALLPOOL_TEXT_DOMAIN ), $sub_step );
		}
		// steps:
		$msg = array();
		$msg[] = __( 'empty ranking table', FOOTBALLPOOL_TEXT_DOMAIN );
		$msg[] = sprintf( __( 'check user predictions with actual results %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $step_string );
		$msg[] = sprintf( __( 'update score with points %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $step_string );
		$msg[] = sprintf( __( 'add bonus question points %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $step_string );
		$msg[] = sprintf( __( 'ranking %d: update total score incrementally %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $ranking_id, $user_batch );
		$msg[] = sprintf( __( 'ranking %d: update ranking for users %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $ranking_id, $step_string );
		$msg[] = sprintf( __( 'ranking %d: calculate user ranking %s', FOOTBALLPOOL_TEXT_DOMAIN )
						, $ranking_id, $step_string );
		$msg[] = sprintf( '<strong>%s</strong>', __( 'score (re)calculation finished', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$output .= sprintf( '<h2>%s<span id="ajax-loader"></span></h2>'
							, __( 'Score (re)calculation', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$params['single_ranking'] = ( $is_single_ranking ) ? $ranking_id : 0;
		$params['message'] = ( $step > 0 ) ? sprintf( '%s...', $msg[$step - 1] ) : '&nbsp;';
		if ( $step > 0 && $sub_step == 1 ) $progress++;
		
		if ( $step > 0 && $total_steps == 0 ) {
			// determine total calculation steps (sub steps are not counted)
			if ( $is_single_ranking ) {
				// only one loop through the steps, no pre-calculation of the default ranking
				$rankings = 0;
			} else {
				if ( $calculation_type == FOOTBALLPOOL_RANKING_CALCULATION_SMART ) {
					// get number of unique ranking ids from update log
					$sql = "SELECT COUNT( DISTINCT( r.id ) ) 
							FROM {$prefix}rankings r
							JOIN {$prefix}rankings_updatelog l ON ( r.id = l.ranking_id AND l.is_single_calculation = 0 ) 
							WHERE r.user_defined = 1";
				} else {
					// get all user defined rankings
					$sql = "SELECT COUNT( * ) FROM {$prefix}rankings WHERE user_defined = 1";
				}
				$rankings = $wpdb->get_var( $sql );
			}
			
			// $users = get_users( 'orderby=ID&order=ASC' );
			// $total_users = count( $users );
			$sql = "SELECT COUNT( * ) FROM {$wpdb->users}";
			$total_users = $wpdb->get_var( $sql );
			$total_user_sets = ceil( $total_users / FOOTBALLPOOL_RECALC_STEP5_DIV ) - 1;
			$total_steps = count( $msg ) + ( $rankings * 3 ) 
							+ ( ( $rankings + 1 ) * $total_user_sets );
		}
		
		// calculation steps
		switch ( $step ) {
			case 0:
				$params['step'] = 0;
				// Football_Pool_Utils::set_fp_option( 'calculation_type_preference', $calculation_type );
				$calculation_type_preference = Football_Pool_Utils::get_fp_option( 
																			'calculation_type_preference'
																			, FOOTBALLPOOL_RANKING_CALCULATION_FULL );
				
				if ( ! FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
					$output .= '<div class="progress" id="progress">';
					$output .= sprintf( '<h3>%s</h3>'
										, __( 'Please do not interrupt this process.', FOOTBALLPOOL_TEXT_DOMAIN ) );
					$output .= sprintf( '<p>%s</p>'
										, __( 'Sit back and relax, this may take a while :-)', FOOTBALLPOOL_TEXT_DOMAIN ) );
					$output .= '<div id="progressbar"></div>';
					$output .= '<p id="calculation-message">&nbsp;</p>';
					$output .= '</div>';
				}
				
				$form_action = '';
				if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
					$form_action = ' action="admin.php?page=footballpool-score-calculation" method="post"';
				}
				$output .= sprintf( '<form id="step-0-form"%s>', $form_action );
				$data = array(
								'step' => 1,
								'ranking' => $ranking_id,
								'progress' => $progress,
								'sub_step' => $sub_step,
								'total_steps' => $total_steps,
								'total_user_sets' => $total_user_sets,
								'total_users' => $total_users,
								'single_ranking' => ( $is_single_ranking ? $ranking_id : 0 ),
								'fp_recalc_nonce' => $nonce,
							);
				$output .= self::hidden_input( 'step-0-data', json_encode( $data ), 'return' );
				$output .= self::hidden_input( 'action', 'choose_calculation_type', 'return' );
				$output .= sprintf( '<p>%s</p>', __( 'You are about to recalculate the score table for the plugin.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( ! $is_single_ranking ) {
					$output .= '<p class="calculation-type-select">';
					$output .= sprintf( '<label><input type="radio" name="calculation_type" value="%s" %s/>%s<br /><span>%s</span></label>'
										, FOOTBALLPOOL_RANKING_CALCULATION_SMART
										, ( $calculation_type_preference == FOOTBALLPOOL_RANKING_CALCULATION_SMART ? 'checked="checked" ' : '' )
										, __( 'Smart calculation', FOOTBALLPOOL_TEXT_DOMAIN )
										, __( 'A smart calculation tries to determine which rankings need an update. A smart calculation will in most cases be faster than a full calculation.', FOOTBALLPOOL_TEXT_DOMAIN )
								);
					$output .= sprintf( '<label><input type="radio" name="calculation_type" value="%s" %s/>%s<br /><span>%s</span></label>'
										, FOOTBALLPOOL_RANKING_CALCULATION_FULL
										, ( $calculation_type_preference == FOOTBALLPOOL_RANKING_CALCULATION_FULL ? 'checked="checked" ' : '' )
										, __( 'Full calculation', FOOTBALLPOOL_TEXT_DOMAIN )
										, __( 'A full calculation recalculates all rankings. If you want to be absolutely sure everything is recalculated, choose this option.', FOOTBALLPOOL_TEXT_DOMAIN )
								);
					$output .= '</p>';
				}
				
				$output .= '<p class="submit">';
				if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
					$output .= get_submit_button( __( 'Continue', FOOTBALLPOOL_TEXT_DOMAIN ), 'primary', null, false );
					$output .= wp_nonce_field( FOOTBALLPOOL_NONCE_SCORE_CALC, 'fp_recalc_nonce', true, false );
				} else {
					$output .= self::link_button( 
													__( 'Continue', FOOTBALLPOOL_TEXT_DOMAIN ), 
													array( '', 'calculate_score_history( 1 )' ), 
													false, 
													'js-button',
													null,
													'primary'
												);
					$output .= '&nbsp;';
					$output .= self::link_button( 
													__( 'Cancel', FOOTBALLPOOL_TEXT_DOMAIN ), 
													array( '', 'jQuery.colorbox.close()' ), 
													false, 
													'js-button' 
												);
				}
				$output .= '</p></form>';
				break;
			case 1:
				// empty table
				if ( $is_single_ranking ) {
					$check = self::empty_scorehistory( $ranking_id );
				} elseif ( $calculation_type == FOOTBALLPOOL_RANKING_CALCULATION_SMART ) {
					$check = self::empty_scorehistory( 'smart set' );
				} else { // full calc, so delete all
					$check = self::empty_scorehistory( 'all' );
				}
				
				$params['step'] = 2;
				break;
			case 2:
				// check predictions with actual match result (score type = 0)
				$offset = FOOTBALLPOOL_RECALC_STEP2_DIV * ( $sub_step - 1 );
				$calculate_this_ranking = ( $is_single_ranking ? $ranking_id : FOOTBALLPOOL_RANKING_DEFAULT );
				// get the user set for this step
				$user_ids = self::get_user_set( $offset, FOOTBALLPOOL_RECALC_STEP2_DIV );
				if ( is_array( $user_ids ) && count( $user_ids ) > 0 ) {
					$user_ids = implode( ',', $user_ids );
					$sql = "INSERT INTO {$prefix}scorehistory
								( type, score_date, score_order, user_id
								, score, full, toto, goal_bonus, goal_diff_bonus
								, ranking, ranking_id )
							SELECT 
								  %d AS score_type, m.play_date AS score_date, m.id AS match_id, u.ID AS user_id
								, IF ( p.has_joker = 1, 2, 1 ) AS score
								, IF ( m.home_score = p.home_score AND m.away_score = p.away_score, 1, NULL ) AS full
								, IF ( m.home_score = p.home_score AND m.away_score = p.away_score, NULL, 
									IF (
										IF ( m.home_score > m.away_score, 1, IF ( m.home_score = m.away_score, 3, 2 ) )
										=
										IF ( p.home_score > p.away_score, 1, IF ( p.home_score = p.away_score, 3, 2 ) )
										, IF ( p.home_score IS NULL OR p.away_score IS NULL, NULL, 1 )
										, NULL 
									)
								  ) AS toto
								, IF ( m.home_score = p.home_score, 
										IF ( m.away_score = p.away_score, 2, 1 ),
										IF ( m.away_score = p.away_score, 1, NULL )
								  ) AS goal_bonus
								, IF( m.home_score = p.home_score AND m.away_score = p.away_score, NULL,
									IF( 
										m.home_score <> m.away_score AND
										( CAST( m.home_score AS SIGNED ) - CAST( p.home_score AS SIGNED ) ) 
										= 
										( CAST( m.away_score AS SIGNED ) - CAST( p.away_score AS SIGNED ) )
										, 1, NULL 
									)
								  ) AS goal_diff_bonus
								, 0 AS ranking
								, %d AS ranking_id
							FROM {$wpdb->users} u ";
					if ( $pool->has_leagues ) {
						$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
						$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
					} else {
						$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
					}
					$sql .= "LEFT OUTER JOIN {$prefix}matches m ON ( 1 = 1 )
							LEFT OUTER JOIN {$prefix}predictions p
								ON ( p.match_id = m.id AND ( p.user_id = u.ID OR p.user_id IS NULL ) )
							WHERE m.home_score IS NOT NULL AND m.away_score IS NOT NULL AND u.ID IN ( {$user_ids} ) ";
					if ( ! $pool->has_leagues ) $sql .= "AND ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
					if ( $is_single_ranking ) $sql .= "AND m.id IN ( {$ranking_matches} ) ";
					$sql .= "ORDER BY 1, 2, 3, 4";
					
					$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_MATCH, $calculate_this_ranking );
					$result = $wpdb->query( $sql );			
					$check = ( $result !== false );
					
					$params['step'] = 2;
					$sub_step++;
				} else {
					$sub_step = 1;
					$params['step'] = 3;
				}
				break;
			case 3:
				// update score for matches
				$calculate_this_ranking = ( $is_single_ranking ? $ranking_id : FOOTBALLPOOL_RANKING_DEFAULT );
				$offset = FOOTBALLPOOL_RECALC_STEP3_DIV * ( $sub_step - 1 );
				// get the user set for this step
				$user_ids = self::get_user_set( $offset, FOOTBALLPOOL_RECALC_STEP3_DIV );
				if ( is_array( $user_ids ) && count( $user_ids ) > 0 ) {
					$user_ids = implode( ',', $user_ids );
					
					$full = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
					$toto = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
					$goal = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
					$diff = Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' );
					
					$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory 
											SET score = score * ( ( full * {$full} ) 
														+ ( toto * {$toto} ) 
														+ ( goal_bonus * {$goal} ) 
														+ ( goal_diff_bonus * {$diff} ) ) 
											WHERE type = %d AND ranking_id = %d 
											AND user_id >= %d AND user_id < %d"
											, FOOTBALLPOOL_TYPE_MATCH, $calculate_this_ranking
											, $offset, ( $offset + FOOTBALLPOOL_RECALC_STEP3_DIV ) );
					$result = $wpdb->query( $sql );
					$check = ( $result !== false );
					
					$params['step'] = 3;
					$sub_step++;
				} else {
					$sub_step = 1;
					$params['step'] = 4;
				}
				break;
			case 4:
				// add bonusquestion scores (score type = 1)
				// make sure to take the userpoints into account (we can set an alternate score for an 
				// individual user in the admin)
				if ( $pool->has_bonus_questions ) {
					$offset = FOOTBALLPOOL_RECALC_STEP4_DIV * ( $sub_step - 1 );
					$calculate_this_ranking = ( $is_single_ranking ? $ranking_id : FOOTBALLPOOL_RANKING_DEFAULT );
					// get the user set for this step
					$user_ids = self::get_user_set( $offset, FOOTBALLPOOL_RECALC_STEP4_DIV );
					if ( is_array( $user_ids ) && count( $user_ids ) > 0 ) {
						$user_ids = implode( ',', $user_ids );
						$sql = "INSERT INTO {$prefix}scorehistory 
									( type, score_date, score_order, user_id
									, score, full, toto, goal_bonus, goal_diff_bonus
									, ranking, ranking_id ) 
								SELECT 
									%d AS score_type, q.score_date AS score_date, q.id AS question_id,
									u.ID AS user_id, 
									IF ( a.points <> 0, a.points, q.points ) * IFNULL( a.correct, 0 ) AS score, 
									NULL, NULL, NULL, NULL, 
									0 AS ranking, %d AS ranking_id 
								FROM {$wpdb->users} u ";
						if ( $pool->has_leagues ) {
							$sql .= "INNER JOIN {$prefix}league_users lu 
										ON ( lu.user_id = u.ID AND u.ID IN ( {$user_ids} ) ) ";
							$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
						} else {
							$sql .= "LEFT OUTER JOIN {$prefix}league_users lu 
										ON ( lu.user_id = u.ID AND u.ID IN ( {$user_ids} ) ) ";
						}
						$sql .= "LEFT OUTER JOIN {$prefix}bonusquestions q
									ON ( 1 = 1 )
								LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a 
									ON ( a.question_id = q.id AND ( a.user_id = u.ID OR a.user_id IS NULL ) )
								WHERE q.score_date IS NOT NULL ";
						if ( ! $pool->has_leagues ) $sql .= "AND ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
						if ( $is_single_ranking ) $sql .= "AND q.id IN ( {$ranking_questions} ) ";
						$sql .= "ORDER BY 1, 2, 3, 4";
						
						$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_QUESTION, $calculate_this_ranking );
						$result = $wpdb->query( $sql );
						$check = ( $result !== false );
						
						$params['step'] = 4;
						$sub_step++;
					} else {
						$sub_step = 1;
						$params['step'] = 5;
					}
				} else {
					$sub_step = 1;
					$params['step'] = 5;
				}
				
				break;
			case 5:
				// update score incrementally once for every ranking, start with the default one
				if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
					$sql_user_scores = sprintf( "SELECT * FROM {$prefix}scorehistory 
												WHERE user_id = %%d AND ranking_id = %d
												ORDER BY score_date ASC, type ASC, score_order ASC"
												, $ranking_id
										);
				} else {
					$base_ranking = ( $is_single_ranking ? $ranking_id : FOOTBALLPOOL_RANKING_DEFAULT );
					$sql_user_scores = sprintf( "SELECT s.* FROM {$prefix}scorehistory s
												LEFT OUTER JOIN {$prefix}rankings_matches rm
												  ON ( s.score_order = rm.match_id 
														AND rm.ranking_id = %d AND s.type = %d )
												LEFT OUTER JOIN {$prefix}rankings_bonusquestions rq
												  ON ( s.score_order = rq.question_id 
														AND rq.ranking_id = %d AND s.type = %d )
												WHERE s.user_id = %%d AND s.ranking_id = %d 
												AND ( rm.ranking_id IS NOT NULL OR rq.ranking_id IS NOT NULL )
												ORDER BY s.score_date ASC, s.type ASC, s.score_order ASC"
												, $ranking_id, FOOTBALLPOOL_TYPE_MATCH
												, $ranking_id, FOOTBALLPOOL_TYPE_QUESTION
												, $base_ranking
										);
				}
				
				// cumulate scores for each user
				$offset = $user_set * FOOTBALLPOOL_RECALC_STEP5_DIV;
				$user_ids = self::get_user_set( $offset, FOOTBALLPOOL_RECALC_STEP5_DIV );
				// $users = get_users( "orderby=ID&order=ASC&offset={$offset}&number={$number}" );
				
				foreach ( $user_ids as $user_id ) {
					$sql = $wpdb->prepare( $sql_user_scores, $user_id );
					$rows = $wpdb->get_results( $sql, ARRAY_A );
					
					$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory 
											WHERE user_id = %d AND ranking_id = %d", $user_id, $ranking_id );
					$result = $wpdb->query( $sql );
					$check = ( $result !== false ) && $check;
					
					$score = 0;
					foreach ( $rows as $row ) {
						$score += $row['score'];
						$sql = $wpdb->prepare( "INSERT INTO {$prefix}scorehistory 
													( type, score_date, score_order
													, user_id, score
													, full, toto, goal_bonus, goal_diff_bonus
													, total_score, ranking, ranking_id ) 
												VALUES 
													( %d, %s, %d, 
													  %d, %d, 
													  %d, %d, %d, %d, 
													  %d, 0, %d )"
												, $row['type'], $row['score_date'], $row['score_order']
												, $row['user_id'], $row['score']
												, $row['full'], $row['toto'], $row['goal_bonus'], $row['goal_diff_bonus']
												, $score, $ranking_id
										);
						
						$result = $wpdb->query( $sql );
						$check = ( $result !== false ) && $check;
					}
				}
				
				// repeat step until there are no more users
				$params['step'] = ( $user_set == $total_user_sets ) ? 6 : 5;
				
				$params['ranking'] = $ranking_id;
				$params['user_set'] = ++$user_set;
				break;
			case 6:
				// update ranking order for users
				$offset = FOOTBALLPOOL_RECALC_STEP6_DIV * ( $sub_step - 1 );
				$sql = $wpdb->prepare( "SELECT score_date, type FROM {$prefix}scorehistory 
										WHERE ranking_id = %d GROUP BY score_date, type
										LIMIT %d, %d"
										, $ranking_id
										, $offset, FOOTBALLPOOL_RECALC_STEP6_DIV );
				$ranking_dates = $wpdb->get_results( $sql, ARRAY_A );
				
				if ( is_array( $ranking_dates ) && count( $ranking_dates ) > 0 ) {
					$params['step'] = 6;
					$sub_step++;
					
					foreach ( $ranking_dates as $ranking_date ) {
						$sql = $pool->get_ranking_from_score_history( 0, $ranking_id, 
																		$ranking_date['score_date'] 
																	);
						$ranking_result = $wpdb->get_results( $sql, ARRAY_A );
						$rank = 1;
						foreach ( $ranking_result as $ranking_row ) {
							$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory SET ranking = %d 
													WHERE user_id = %d AND type = %d AND score_date = %s 
													AND ranking_id = %d"
													, $rank++
													, $ranking_row['user_id']
													, $ranking_date['type']
													, $ranking_date['score_date']
													, $ranking_id
											);
							$result = $wpdb->query( $sql );
							$check = ( $result !== false ) && $check;
						}
					}
				} else {
					$params['step'] = 7;
					$sub_step = 1;
					// this ranking is finished, so clear the update log for this ranking
					if ( $check === true ) {
						$sql = "DELETE FROM {$prefix}rankings_updatelog WHERE ranking_id = %d ";
						if ( $calculation_type == FOOTBALLPOOL_RANKING_CALCULATION_SMART ) {
							$sql .= "AND is_single_calculation = 0";
						}
						$sql = $wpdb->prepare( $sql, $ranking_id );
						$wpdb->query( $sql );
					
						// if this was a single ranking calculation log this in the update log
						if ( $is_single_ranking ) {
							self::update_ranking_log( $ranking_id, null, null, 'single ranking calculation', null, 1 );
						}
					}
				}
				
				$params['ranking'] = $ranking_id;
				break;
			case 7:
				if ( $is_single_ranking ) {
					$ranking_id = null;
				} else {
					// handle user defined rankings
					if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
						$sql = "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r ";
						if ( $calculation_type == FOOTBALLPOOL_RANKING_CALCULATION_SMART ) {
							$sql .= "JOIN {$prefix}rankings_updatelog l 
										ON ( r.id = l.ranking_id AND l.is_single_calculation = 0 ) ";
						}
						$sql .= "WHERE r.user_defined = 1 ORDER BY r.id ASC LIMIT 1";
					} else {
						$sql = "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r ";
						if ( $calculation_type == FOOTBALLPOOL_RANKING_CALCULATION_SMART ) {
							$sql .= "JOIN {$prefix}rankings_updatelog l 
										ON ( r.id = l.ranking_id AND l.is_single_calculation = 0 ) ";
						}
						$sql .= "WHERE r.user_defined = 1 AND r.id > %d ORDER BY r.id ASC LIMIT 1";
						$sql = $wpdb->prepare( $sql, $ranking_id );
					}
					$ranking_id = $wpdb->get_var( $sql );
				}
				// back to step 5 in case there are rankings left to be calculated (and not in single ranking mode),
				// otherwise (re)calculation is finished.
				$params['step'] = ( $ranking_id != null ) ? 5 : 8;
				$params['ranking'] = $ranking_id;
				break;
			case 8:
				// calculation complete
				$params['step'] = 9;
		}
		
		$params['colorbox_html'] = $output;
		if ( $check === true ) {
			$params['progress'] = $progress;
			$params['sub_step'] = $sub_step;
			$params['total_steps'] = $total_steps;
			$params['total_user_sets'] = $total_user_sets;
			$params['total_users'] = $total_users;
			$params['fp_recalc_nonce'] = $nonce;
			$params['calculation_type'] = $calculation_type;
			$params['error'] = false;
		} else {
			$params['error'] = sprintf( '%s %d: %s'
										, __( 'Step', FOOTBALLPOOL_TEXT_DOMAIN )
										, ( $params['step'] - 1 )
										, __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database and try again.', FOOTBALLPOOL_TEXT_DOMAIN )
								);
		}
		
		// extra action for each step (0-8)
		do_action( "footballpool_score_calc_step{$step}", $params );
		
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			if ( $step > 0 ) {
				printf( '<p>%s...</p>', $msg[$step - 1] );
			} else {
				echo $output;
			}
			unset( $params['colorbox_html'] );
			$url = add_query_arg( $params, "{$_SERVER['PHP_SELF']}?page=footballpool-score-calculation" );
			if ( $params['step'] > 0 && $params['step'] <= 8 ) {
				printf( '<script>location.href = "%s";</script>', $url );
			}
		} else {
			header( 'Content-Type: application/json' );
			echo json_encode( $params );
			// always die when doing ajax requests
			die();
		}
	}
	
	public function admin() {
		self::$start = ( Football_Pool_Utils::post_string( 'action' ) == 'choose_calculation_type' ) ? 1 : 0;
		self::process();
	}
	
	private function get_user_set( $offset, $amount ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d, %d", $offset, $amount );
		return $wpdb->get_col( $sql );
	}
	
	private function post_int( $key, $default = 0 ) {
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			return Football_Pool_Utils::get_int( $key, $default );
		} else {
			return Football_Pool_Utils::post_int( $key, $default );
		}
	}
	
	private function post_string( $key, $default = '' ) {
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			return Football_Pool_Utils::get_str( $key, $default );
		} else {
			return Football_Pool_Utils::post_str( $key, $default );
		}
	}
}
