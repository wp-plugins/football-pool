<?php
class Football_Pool_Statistics_Page {
	public function page_content() {
		$output = '';
		$view = Football_Pool_Utils::get_string( 'view', 'stats' );
		$match = Football_Pool_Utils::get_integer( 'match' );
		$question = Football_Pool_Utils::get_integer( 'question' );
		$user = Football_Pool_Utils::get_integer( 'user' );
		
		$goal_bonus = ( Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' ) > 0 );
		$goal_diff_bonus = ( Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' ) > 0 );
		
		global $current_user;
		get_currentuserinfo();
		
		$users = Football_Pool_Utils::get_integer_array( 'users' );
		if ( $user > 0 && ! in_array( $user, $users ) ) $users[] = $user;
		if ( $current_user->ID != 0 && ! in_array( $current_user->ID, $users ) ) $users[] = $current_user->ID;
		
		$stats = new Football_Pool_Statistics;
		$pool = new Football_Pool_Pool;

		if ( ! $stats->data_available && $view != 'matchpredictions' ) {
			$output.= sprintf( '<h2>%s</h2><p>%s</p>'
								, __( 'Statistics not yet available', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( 'After the first match you can view your scores and those of other users here.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		} else {
			$chart_data = new Football_Pool_Chart_Data();
			
			$ranking_selector = '';
			if ( in_array( $view, array( 'stats', 'user' ) ) ) {
				// show the ranking selector if applicable
				$ranking_display = Football_Pool_Utils::get_fp_option( 'ranking_display', 0 );
				if ( $ranking_display == 1 ) {
					$ranking = Football_Pool_Utils::request_int( 'ranking'
																, FOOTBALLPOOL_RANKING_DEFAULT );
				} elseif ( $ranking_display == 2 ) {
					$ranking = Football_Pool_Utils::get_fp_option( 'show_ranking', FOOTBALLPOOL_RANKING_DEFAULT );
				} else {
					$ranking = FOOTBALLPOOL_RANKING_DEFAULT;
				}
				
				$user_defined_rankings = $pool->get_rankings( 'user defined' );
				if ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) {
					$ranking_selector .= sprintf( '<form action="%s" method="get">
											<div style="margin-bottom: 1em; clear: both;">'
										, get_page_link() 
								);
					$page_id = Football_Pool_Utils::get_fp_option( 'page_id_statistics' );
					$ranking_selector .= sprintf( '<input type="hidden" name="page_id" value="%d" />'
													, $page_id
											);
					$ranking_selector .= sprintf( '<input type="hidden" name="user" value="%d" />'
													, $user
											);
					$ranking_selector .= sprintf( '<input type="hidden" name="view" value="%s" />'
													, $view
											);
					foreach ( $users as $user ) {
						$ranking_selector .= sprintf( '<input type="hidden" name="users[]" value="%d" />'
														, $user
												);
					}
					
					if ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) {
						$options = array();
						$options[FOOTBALLPOOL_RANKING_DEFAULT] = '';
						foreach( $user_defined_rankings as $user_defined_ranking ) {
							$options[$user_defined_ranking['id']] = $user_defined_ranking['name'];
						}
						$ranking_selector .= sprintf( '<br />%s: %s'
											, __( 'Choose ranking', FOOTBALLPOOL_TEXT_DOMAIN )
											, Football_Pool_Utils::select( 
																	'ranking', $options, $ranking )
									);
					}
					$ranking_selector .= sprintf( '<input type="submit" value="%s" />'
										, __(  'go', FOOTBALLPOOL_TEXT_DOMAIN )
								);
					$ranking_selector .= '</div></form>';
				}
			}
			
			switch ( $view ) {
				case 'bonusquestion': 
					$questionInfo = $stats->show_bonus_question_info( $question );
					if ( $stats->stats_visible ) {
						if ( $stats->stats_enabled ) {
							// chart 1: pie, what did the players score on this bonus question?
							$raw_data = $chart_data->bonus_question_pie_chart_data( $question );
							$chart = new Football_Pool_Chart( 'chart1', 'pie', 300, 200 );
							$chart->data = $chart_data->bonus_question_pie_series_one_question( $raw_data );
							$chart->title = __( 'what dit other users score?', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'right';
							$output .= $chart->draw();
						}
						
						$output .= $stats->show_answers_for_bonus_question( $question );
					}
					break;
				case 'matchpredictions':
					$m = new Football_Pool_Matches();
					$match_info = $m->get_match_info( $match );
					$output .= $stats->show_match_info( $match_info );
					if ( $stats->stats_visible ) {
						if ( $stats->stats_enabled && $stats->data_available_for_match( $match ) ) {
							// chart 1: pie, what did the players score with the game predictions for this match?
							$raw_data = $chart_data->predictions_pie_chart_data( $match );
							$chart = new Football_Pool_Chart( 'chart1', 'pie', 300, 200 );
							$chart->data = $chart_data->predictions_pie_series( $raw_data );
							$chart->title = __( 'other users scores', FOOTBALLPOOL_TEXT_DOMAIN );
							//$chart->options[] = '';
							$chart->custom_css = 'right';
							$output .= $chart->draw();
						}
						$output .= $stats->show_predictions_for_match( $match_info );
					}
					break;
				case 'user':
					$user_info = get_userdata( $user );
					$output .= $stats->show_user_info( $user_info );
					if ( $stats->stats_visible ) {
						// can't use esc_url() here because it also strips the square brackets from users[]
						$url = add_query_arg( 
												array( 
													'user' => false,
													'view' => false,
													'users[]' => $user_info->ID
												) 
								);
						$txt = __( 'Compare the scores of %s with other users.', FOOTBALLPOOL_TEXT_DOMAIN );
						$output .= sprintf( "<p><a href='%s'>{$txt}</a></p>"
											, $url
											, $user_info->display_name
									);
						
						$output .= $ranking_selector;
						$output .= "<div>";
						
						$pool = new Football_Pool_Pool;
						$pool->get_bonus_questions_for_user( $user );
						// chart 1: pie, what did the players score with the game predictions?
						$raw_data = $chart_data->score_chart_data( array( $user ), $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart1', 'pie', 300, 300 );
							// only one user
							$chart->data = array_shift( $chart_data->score_chart_series( $raw_data ) );
							$chart->title = __( 'scores in matches', FOOTBALLPOOL_TEXT_DOMAIN );
							if ( $pool->has_bonus_questions ) $chart->custom_css = 'stats-pie left';
							$output .= $chart->draw();
						}
						if ( $pool->has_bonus_questions ) {
							// chart 4: pie, bonus questions wrong or right
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data( 
																					array( $user ), 
																					$ranking 
																				);
							if ( count( $raw_data ) > 0 ) {
								$chart = new Football_Pool_Chart( 'chart4', 'pie', 300, 300 );
								// only one user
								$chart->data = array_shift( $chart_data->bonus_question_pie_series( $raw_data ) );
								$chart->title = __( 'scores in bonus questions', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->custom_css = 'stats-pie left';
								$output .= $chart->draw();
							}
						}

						// chart 5: pie, percentage of total points scored
						$raw_data = $chart_data->points_total_pie_chart_data( $user, $ranking );
						if ( count( $raw_data ) ) {
							$chart = new Football_Pool_Chart( 'chart5', 'pie', 300, 300 );
							$chart->data = $chart_data->points_total_pie_series( $raw_data );
							/* xgettext:no-php-format */
							$chart->title = __( '% of the max points', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->options[] = sprintf( "subtitle: { text: '(%s)' }"
														, __( 'with the joker used', FOOTBALLPOOL_TEXT_DOMAIN ) );
							$chart->JS_options[] = "options.series[0].data[0].sliced = true";
							$chart->JS_options[] = "options.series[0].data[0].selected = true";
							//if ( $pool->has_bonus_questions ) $chart->custom_css = 'stats-pie left';
							$chart->custom_css = 'stats-pie left';
							$output .= $chart->draw();
						}
						$output .= "</div>";
					}
				case 'stats':
					if ( $view != 'user' ) {
						if ( count( $users ) < 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'No users selected :\'(', FOOTBALLPOOL_TEXT_DOMAIN ) );
							$output .= sprintf( '<p>%s</p>', __( 'You can select other users on the left side.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						} elseif ( count( $users ) == 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'You can select other users on the left side.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						}
					}
					
					if ( $view != 'user' ) {
						$output .= $ranking_selector;
						// column charts
						// chart6: column, what did the players score with the game predictions?
						$raw_data = $chart_data->score_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart6', 'column', 720, 300 );
							$chart->data = $chart_data->score_chart_series( $raw_data );
							$chart->title = __( 'scores', FOOTBALLPOOL_TEXT_DOMAIN );
							$axis = array();
							$axis[] = __( 'full score', FOOTBALLPOOL_TEXT_DOMAIN ); 
							$axis[] = __( 'toto score', FOOTBALLPOOL_TEXT_DOMAIN ); 
							$axis[] = __( 'no score', FOOTBALLPOOL_TEXT_DOMAIN );
							if ( $goal_bonus ) {
								$axis[] = __( 'just the goal bonus', FOOTBALLPOOL_TEXT_DOMAIN );
							}
							if ( $goal_diff_bonus ) {
								$axis[] = __( 'toto score with goal difference bonus', FOOTBALLPOOL_TEXT_DOMAIN );
							}
							$axis_definition = implode( "', '", $axis );
							$chart->options[] = "xAxis: { 
														categories: [ '{$axis_definition}' ]
												}";
							$chart->options[] = "tooltip: {
													formatter: function() {
														return this.x + '<br>'
															+ '<b>' + this.series.name + '</b>: '
															+ this.y;
													}
												}";
							$output .= $chart->draw();
						}
						// chart7: bonus questions
						$raw_data = $chart_data->bonus_question_for_users_pie_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart7', 'column', 720, 300 );
							$chart->data = $chart_data->bonus_question_pie_series( $raw_data, 'no open questions' );
							$chart->title = __( 'bonus question', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->options[] = sprintf( "xAxis: { categories: [ '%s', '%s' ] }"
														, __( 'correct answer', FOOTBALLPOOL_TEXT_DOMAIN )
														, __( 'wrong answer', FOOTBALLPOOL_TEXT_DOMAIN )
												);
							$chart->options[] = "tooltip: {
													formatter: function() {
														return this.x + '<br>'
															+ '<b>' + this.series.name + '</b>: '
															+ this.y;
													}
												}";
							$output .= $chart->draw();
							// remove last point from series; we don't need it :)
							// $output .= $chart->remove_last_point_from_series();
						}
					}
				default:
					// chart 2: scoreverloop
					if ( count( $users ) >= 1 ) {
						$output .= '<br class="clear" />';
						$raw_data = $chart_data->score_per_match_line_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart2', 'line', 720, 500 );
							$chart->data = $chart_data->score_per_match_line_series( $raw_data );
							$chart->title = __( 'points scored', FOOTBALLPOOL_TEXT_DOMAIN );
							$txt = __( 'points', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->options[] = "tooltip: {
													shared: true, crosshairs: true, 
													formatter: function() {
														s = '<b>' + categories[this.x] + '</b><br/>';
														jQuery.each( this.points, function( i, point ) {
															s += '<b style=\"color:' + point.series.color + '\">' 
																+ point.series.name + '</b>: ' 
																+ point.y + ' {$txt}<br>';
														} );
														return s;
													}
												}";
							$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
							$chart->JS_options[] = 'options.yAxis.min = -1';
							$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
							$output .= $chart->draw();
						}
						
						// chart 3: position of the players in the pool
						$raw_data = $chart_data->ranking_per_match_line_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart3', 'line', 720, 500 );
							$chart->data = $chart_data->ranking_per_match_line_series( $raw_data );
							$chart->title = __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN );
							// Translators: The ordinal suffixes th, st, nd, rd, th are used in the sentence 'Xth position in the pool'.
							$ordinal_suffixes = __( '["th", "st", "nd", "rd", "th"]', FOOTBALLPOOL_TEXT_DOMAIN );
							$txt = __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->options[] = "tooltip: {
													shared: true, crosshairs: true,
													formatter: function() {
														s = '<b>' + categories[this.x] + '</b><br/>';
														jQuery.each( this.points, function ( i, point ) {
															s += '<b style=\"color:' + point.series.color + '\">' 
																+ point.series.name + '</b>: ' 
																+ add_ordinal_suffix( point.y, {$ordinal_suffixes} ) 
																+ ' {$txt}<br>';
														} );
														return s;
													}
												}";
							$chart->JS_options[] = sprintf( 'options.yAxis.title.text = "%s"'
															, __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN )
													);
							//$chart->JS_options[] = 'options.yAxis.endOnTick = true';
							$chart->JS_options[] = 'options.yAxis.reversed = true';
							$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
							//$chart->JS_options[] = 'options.yAxis.min = 1';
							$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
							$output .= $chart->draw();
						}
					}
					break;
			}
		}
		
		return $output;
	}
}
