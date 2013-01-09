<?php
class Football_Pool_Statistics_Page {
	public function page_content() {
		$output = '';
		$view = Football_Pool_Utils::get_string( 'view', 'stats' );
		$match = Football_Pool_Utils::get_integer( 'match' );
		$question = Football_Pool_Utils::get_integer( 'question' );
		$user = Football_Pool_Utils::get_integer( 'user' );
		
		$goal_bonus = ( Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' ) > 0 );
		
		global $current_user;
		get_currentuserinfo();
		
		$users = Football_Pool_Utils::get_integer_array( 'users' );
		if ( $user > 0 && ! in_array( $user, $users ) ) $users[] = $user;
		if ( $current_user->ID != 0 && ! in_array( $current_user->ID, $users ) ) $users[] = $current_user->ID;

		$stats = new Football_Pool_Statistics;

		if ( ! $stats->data_available && $view != 'matchpredictions' ) {
			$output.= sprintf( '<h2>%s</h2><p>%s</p>',
								__( 'Statistics not yet available', FOOTBALLPOOL_TEXT_DOMAIN ),
								__( 'After the first match you can view your scores and those of other users here.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		} else {
			$chart_data = new Football_Pool_Chart_Data();
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
					$userInfo = get_userdata( $user );
					$output .= $stats->show_user_info( $userInfo );
					if ( $stats->stats_visible ) {
						// can't use esc_url() here because it also strips the square brackets from users[]
						$url = add_query_arg( 
												array( 
													'user' => false,
													'view' => false,
													'users[]' => $userInfo->ID
												) 
								);
						$output .= sprintf( '<p><a href="%s">' . __( 'Compare the scores of %s with other users.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a></p>'
											, $url
											, $userInfo->display_name
									);
						$output .= "<div>";
						
						$pool = new Football_Pool_Pool;
						$pool->get_bonus_questions_for_user( $user );
						// chart 1: pie, what did the players score with the game predictions?
						$raw_data = $chart_data->score_chart_data( array( $user ) );
						$chart = new Football_Pool_Chart( 'chart1', 'pie', 300, 300 );
						$chart->data = array_shift( $chart_data->score_chart_series( $raw_data ) ); // only one user
						$chart->title = __( 'scores in matches', FOOTBALLPOOL_TEXT_DOMAIN );
						if ( $pool->has_bonus_questions ) $chart->custom_css = 'stats-pie left';
						$output .= $chart->draw();
						
						if ( $pool->has_bonus_questions ) {
							// chart 4: pie, verdeling juist/niet juist bij de bonusvragen
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data(array($user));
							if ( count( $raw_data ) > 0 ) {
								$chart = new Football_Pool_Chart( 'chart4', 'pie', 300, 300 );
								$chart->data = array_shift( $chart_data->bonus_question_pie_series( $raw_data ) ); // only one user
								$chart->title = __( 'scores in bonus questions', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->custom_css = 'stats-pie left';
								$output .= $chart->draw();
							}
						}

						// chart 5: pie, percentage of total points scored
						$raw_data = $chart_data->points_total_pie_chart_data( $user );
						$chart = new Football_Pool_Chart( 'chart5', 'pie', 300, 300 );
						$chart->data = $chart_data->points_total_pie_series( $raw_data );
						$chart->title = __( '% of the max points', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "subtitle: { text: '(" . __( 'with the joker used', FOOTBALLPOOL_TEXT_DOMAIN ) . ")' }";
						$chart->JS_options[] = "options.series[0].data[0].sliced = true";
						$chart->JS_options[] = "options.series[0].data[0].selected = true";
						//if ( $pool->has_bonus_questions ) $chart->custom_css = 'stats-pie left';
						$chart->custom_css = 'stats-pie left';
						$output .= $chart->draw();

						$output .= "</div>";
					}
				case 'stats':
					if ( $view == 'stats' ) {
						if ( count( $users ) < 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'No users selected :\'(', FOOTBALLPOOL_TEXT_DOMAIN ) );
							$output .= sprintf( '<p>%s</p>', __( 'You can select other users on the left side.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						} elseif ( count( $users ) == 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'You can select other users on the left side.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						}
						
						if ( count( $users ) >= 1 ) {
							// column charts
							// chart6: column, what did the players score with the game predictions?
							$raw_data = $chart_data->score_chart_data( $users );
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
							$chart->options[] = "xAxis: { 
														categories: [ '" . implode( "', '", $axis ) . "' ]
												}";
							$chart->options[] = "tooltip: {
													formatter: function() {
														return this.x + '<br>'
															+ '<b>' + this.series.name + '</b>: '
															+ this.y;
													}
												}";
							$output .= $chart->draw();
							// chart7: bonus questions
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data( $users );
							if ( count( $raw_data ) > 0 ) {
								$chart = new Football_Pool_Chart( 'chart7', 'column', 720, 300 );
								$chart->data = $chart_data->bonus_question_pie_series( $raw_data );
								$chart->title = __( 'bonus question', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->options[] = "xAxis: { 
															categories: [ '" . __( 'correct answer', FOOTBALLPOOL_TEXT_DOMAIN ) . "', '" . __( 'false answer', FOOTBALLPOOL_TEXT_DOMAIN ) . "' ]
													}";
								$chart->options[] = "tooltip: {
														formatter: function() {
															return this.x + '<br>'
																+ '<b>' + this.series.name + '</b>: '
																+ this.y;
														}
													}";
								$output .= $chart->draw();
								// remove last point from series; we don't need it :)
								$output .= $chart->remove_last_point_from_series();
							}
						}
					}
				default:
					// chart 2: scoreverloop
					if ( count( $users ) >= 1 ) {
						$output .= '<br class="clear" />';
						$raw_data = $chart_data->score_per_match_line_chart_data( $users );
						$chart = new Football_Pool_Chart( 'chart2', 'line', 720, 500 );
						$chart->data = $chart_data->score_per_match_line_series( $raw_data );
						$chart->title = __( 'points scored', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "tooltip: {
												shared: true, crosshairs: true, 
												formatter: function() {
													s = '<b>' + categories[this.x] + '</b><br/>';
													jQuery.each( this.points, function( i, point ) {
														s += '<b style=\"color:' + point.series.color + '\">' 
															+ point.series.name + '</b>: ' 
															+ point.y + ' " . __( 'points', FOOTBALLPOOL_TEXT_DOMAIN ) . "<br>';
													} );
													return s;
												}
											}";
						$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
						$chart->JS_options[] = 'options.yAxis.min = -1';
						$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
						$output .= $chart->draw();

						// chart 3: position of the players in the pool
						$raw_data = $chart_data->ranking_per_match_line_chart_data( $users );
						$chart = new Football_Pool_Chart( 'chart3', 'line', 720, 500 );
						$chart->data = $chart_data->ranking_per_match_line_series( $raw_data );
						$chart->title = __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN );
						// Translators: The ordinal suffixes th, st, nd, rd, th are used in the sentence 'Xth position in the pool'.
						$ordinal_suffixes = __( '["th", "st", "nd", "rd", "th"]', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "tooltip: {
												shared: true, crosshairs: true,
												formatter: function() {
													s = '<b>' + categories[this.x] + '</b><br/>';
													jQuery.each( this.points, function ( i, point ) {
														s += '<b style=\"color:' + point.series.color + '\">' 
															+ point.series.name + '</b>: ' 
															+ add_ordinal_suffix( point.y, {$ordinal_suffixes} ) 
															+ ' " . __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN ) . "<br>';
													} );
													return s;
												}
											}";
						$chart->JS_options[] = 'options.yAxis.title.text = "' . __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
						//$chart->JS_options[] = 'options.yAxis.endOnTick = true';
						$chart->JS_options[] = 'options.yAxis.reversed = true';
						$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
						//$chart->JS_options[] = 'options.yAxis.min = 1';
						$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
						$output .= $chart->draw();
					}
					break;
			}
		}
		
		return $output;
	}
}
?>