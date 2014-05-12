<?php
class Football_Pool_Statistics_Page {
	public static function the_title( $title ) {
		if ( in_the_loop() && is_page() && get_the_ID() == Football_Pool_Utils::get_fp_option( 'page_id_statistics' ) ) {
			$view = Football_Pool_Utils::get_string( 'view', 'stats' );
			if ( ! in_array( $view, array( 'bonusquestion' ,'matchpredictions' ) ) ) {
				$stats = new Football_Pool_Statistics;
				if ( $stats->data_available ) {
					$title .= sprintf( '<span title="%s" class="fp-icon-cog charts-settings-switch" onclick="jQuery( \'#fp-charts-settings\' ).slideToggle( \'slow\' )"></span>'
										, __( 'Chart settings', FOOTBALLPOOL_TEXT_DOMAIN )
									);
				}
			}
		}
		
		return $title;
	}
	
	private function settings_panel( $panel_content ) {
		if ( $panel_content == '' ) $panel_content = __( 'No settings available', FOOTBALLPOOL_TEXT_DOMAIN );
		
		$output = sprintf( '<div id="fp-charts-settings">%s<p><input type="submit" value="%s" /></p></div>'
							, $panel_content
							, __( 'Change charts', FOOTBALLPOOL_TEXT_DOMAIN ) 
						);
		$output .= sprintf( '<input type="hidden" name="view" value="%s" />', Football_Pool_Utils::get_string( 'view', 'stats' ) );
		$output .= sprintf( '<input type="hidden" name="user" value="%d" />', Football_Pool_Utils::get_int( 'user' ) );
		return $output;
	}
	
	public function page_content() {
		$output = sprintf( '<form action="%s" method="get">', get_page_link() );
		
		$stats = new Football_Pool_Statistics;
		$pool = new Football_Pool_Pool;
		
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
		
		$ranking_display = Football_Pool_Utils::get_fp_option( 'ranking_display', 0 );
		if ( $ranking_display == 1 ) {
			$ranking = Football_Pool_Utils::request_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} elseif ( $ranking_display == 2 ) {
			$ranking = Football_Pool_Utils::get_fp_option( 'show_ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} else {
			$ranking = FOOTBALLPOOL_RANKING_DEFAULT;
		}
		
		if ( ! $stats->data_available && $view != 'matchpredictions' ) {
			$output.= sprintf( '<h2>%s</h2><p>%s</p>'
								, __( 'Statistics not yet available', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( 'After the first match you can view your scores and those of other users here.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		} else {
			$chart_data = new Football_Pool_Chart_Data();
			
			// show the user selector
			if ( $view != 'matchpredictions' && $view != 'bonusquestion' && $view != 'user' ) {
				$rows = apply_filters( 'footballpool_userselector_users', $pool->get_users( $league_id ) );
				$user_selector = '';
				if ( count( $rows ) > 0 ) {
					$user_selector .= '<div class="user-selector">';
					// @todo: add user search
					$user_selector .= '<ol>';
					foreach( $rows as $row ) {
						$selected = ( in_array( $row['user_id'], $users ) ) ? true : false;
						$user_selector .= sprintf( '<li class="user-%d%s">
													<label><input type="checkbox" name="users[]" value="%d" %s/> %s</label>
													</li>'
												, $row['user_id']
												, ( $selected ? ' selected' : '' )
												, $row['user_id']
												, ( $selected ? 'checked="checked" ' : '' )
												, $pool->user_name( $row['user_id'] )
										);
					}
					$user_selector .= '</ol></div>';
				}
			}
			
			$ranking_selector = '';
			if ( in_array( $view, array( 'stats', 'user' ) ) ) {
				// show the ranking selector if applicable
				$user_defined_rankings = $pool->get_rankings( 'user defined' );
				if ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) {
					$ranking_selector .= '<div style="margin-bottom: 1em; clear: both;">';
					
					if ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) {
						$options = array();
						$options[FOOTBALLPOOL_RANKING_DEFAULT] = '';
						foreach( $user_defined_rankings as $user_defined_ranking ) {
							$options[$user_defined_ranking['id']] = $user_defined_ranking['name'];
						}
						$ranking_selector .= sprintf( '<br />%s: %s'
											, __( 'Choose ranking', FOOTBALLPOOL_TEXT_DOMAIN )
											, Football_Pool_Utils::select( 'ranking', $options, $ranking, '', 'statistics-page ranking-select' )
									);
					}
					$ranking_selector .= '</div>';
				}
			}
			
			switch ( $view ) {
				case 'bonusquestion': 
					$output .= $stats->show_bonus_question_info( $question );
					if ( $stats->stats_visible ) {
						$output .= $stats->show_answers_for_bonus_question( $question );
						if ( $stats->stats_enabled ) {
							// chart 1: pie, what did the players score on this bonus question?
							$raw_data = $chart_data->bonus_question_pie_chart_data( $question );
							$chart = new Football_Pool_Chart( 'chart1', 'pie' );
							$chart->data = $chart_data->bonus_question_pie_series_one_question( $raw_data );
							$chart->title = __( 'what dit other users score?', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
							$output .= $chart->draw();
						}
					}
					break;
				case 'matchpredictions':
					$m = new Football_Pool_Matches();
					$match_info = $m->get_match_info( $match );
					$output .= $stats->show_match_info( $match_info );
					if ( $stats->stats_visible ) {
						$output .= $stats->show_predictions_for_match( $match_info );
						if ( $stats->stats_enabled && $stats->data_available_for_match( $match ) ) {
							// chart 1: pie, what did the players score with the game predictions for this match?
							$raw_data = $chart_data->predictions_pie_chart_data( $match );
							$chart = new Football_Pool_Chart( 'chart1', 'pie' );
							$chart->data = $chart_data->predictions_pie_series( $raw_data );
							$chart->title = __( 'other users scores', FOOTBALLPOOL_TEXT_DOMAIN );
							// $chart->options[] = '';
							$chart->custom_css = 'stats-page';
							$output .= $chart->draw();
						}
					}
					break;
				case 'user':
					$user_info = get_userdata( $user );
					$output .= $stats->show_user_info( $user_info );
					if ( $stats->stats_visible ) {
						$pool = new Football_Pool_Pool;
						
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
											, $pool->user_name( $user_info->ID )
									);
						
						$output .= $this->settings_panel( $ranking_selector );
						
						$pool->get_bonus_questions_for_user( $user );
						// chart 1: pie, what did the players score with the match predictions?
						$raw_data = $chart_data->score_chart_data( array( $user ), $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart1', 'pie' );
							// only one user
							$chart->data = $chart_data->score_chart_series( $raw_data );
							$chart->data = array_shift( $chart->data );
							$chart->title = __( 'scores in matches', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
							if ( $pool->has_bonus_questions ) $chart->custom_css .= ' stats-pie';
							$output .= $chart->draw();
						}
						if ( $pool->has_bonus_questions ) {
							// chart 4: pie, bonus questions wrong or right
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data( 
																					array( $user ), 
																					$ranking 
																				);
							if ( count( $raw_data ) > 0 ) {
								$chart = new Football_Pool_Chart( 'chart4', 'pie' );
								// only one user
								$chart->data = array_shift( $chart_data->bonus_question_pie_series( $raw_data ) );
								$chart->title = __( 'scores in bonus questions', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->custom_css = 'stats-page stats-pie';
								$output .= $chart->draw();
							}
						}

						// chart 5: pie, percentage of total points scored
						$raw_data = $chart_data->points_total_pie_chart_data( $user, $ranking );
						if ( count( $raw_data ) ) {
							$chart = new Football_Pool_Chart( 'chart5', 'pie' );
							$chart->data = $chart_data->points_total_pie_series( $raw_data );
							/* xgettext:no-php-format */
							$chart->title = __( '% of the max points', FOOTBALLPOOL_TEXT_DOMAIN );
							if ( $pool->has_jokers ) {
								$chart->options[] = sprintf( "subtitle: { text: '(%s)' }"
															, __( 'with the joker used', FOOTBALLPOOL_TEXT_DOMAIN ) );
							}
							$chart->JS_options[] = "options.series[0].data[0].sliced = true";
							$chart->JS_options[] = "options.series[0].data[0].selected = true";
							$chart->custom_css = 'stats-page stats-pie';
							$output .= $chart->draw();
						}
						$output .= "</div>";
					}
				case 'stats':
					if ( $view != 'user' ) {
						if ( count( $users ) < 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'No users selected', FOOTBALLPOOL_TEXT_DOMAIN ) );
							$output .= sprintf( '<p>%s %s</p>', __( 'The top 5 players are shown below.', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'You can select other users in the chart settings.', FOOTBALLPOOL_TEXT_DOMAIN ) );
							
							$rows = $pool->get_pool_ranking_limited( FOOTBALLPOOL_LEAGUE_ALL, 5, $ranking );
							foreach( $rows as $row ) $users[] = $row['user_id'];
						} elseif ( count( $users ) == 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'You can select other users in the chart settings.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						}
					}
					
					if ( $view != 'user' ) {
						$output .= $this->settings_panel( $user_selector . $ranking_selector );
						// column charts
						// chart6: column, what did the players score with the game predictions?
						$raw_data = $chart_data->score_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart6', 'column' );
							$chart->data = $chart_data->score_chart_series( $raw_data );
							$chart->title = __( 'scores', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
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
							$chart = new Football_Pool_Chart( 'chart7', 'column' );
							$chart->data = $chart_data->bonus_question_pie_series( $raw_data, 'no open questions' );
							$chart->title = __( 'bonus question', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
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
					// chart 2: points over time
					if ( count( $users ) >= 1 ) {
						$output .= '<br class="clear" />';
						$raw_data = $chart_data->score_per_match_line_chart_data( $users, $ranking );
						if ( count( $raw_data ) > 0 ) {
							$chart = new Football_Pool_Chart( 'chart2', 'line' );
							$chart->data = $chart_data->score_per_match_line_series( $raw_data );
							$chart->title = __( 'points scored', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
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
							$chart = new Football_Pool_Chart( 'chart3', 'line' );
							$chart->data = $chart_data->ranking_per_match_line_series( $raw_data );
							$chart->title = __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->custom_css = 'stats-page';
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
																+ FootballPool.add_ordinal_suffix( point.y, {$ordinal_suffixes} ) 
																+ ' {$txt}<br>';
														} );
														return s;
													}
												}";
							$chart->JS_options[] = sprintf( 'options.yAxis.title.text = "%s"'
															, __( 'position in the pool', FOOTBALLPOOL_TEXT_DOMAIN )
													);
							// $chart->JS_options[] = 'options.yAxis.endOnTick = true';
							$chart->JS_options[] = 'options.yAxis.reversed = true';
							$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
							// $chart->JS_options[] = 'options.yAxis.min = 1';
							$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
							$output .= $chart->draw();
						}
					}
					break;
			}
		}
		
		$output .= sprintf( '<input type="hidden" name="page_id" value="%d" /></form>', get_the_ID() );
		return $output;
	}
}
