<?php
class Statistics_Page {
	public function page_content() {
		$output = '';
		$view = Utils::get_string( 'view', 'stats' );
		$match = Utils::get_integer( 'match' );
		$question = Utils::get_integer( 'question' );
		$user = Utils::get_integer( 'user' );
		
		global $current_user;
		get_currentuserinfo();
		
		$users = Utils::get_integer_array( 'users' );
		if ( $user > 0 && ! in_array( $user, $users ) ) $users[] = $user;
		if ( $current_user->ID != 0 && ! in_array( $current_user->ID, $users ) ) $users[] = $current_user->ID;

		$stats = new Statistics;

		if ( ! $stats->data_available && $view != 'matchpredictions' ) {
			$output.= sprintf( '<h2>%s</h2><p>%s</p>',
								__( 'Statistieken nog niet beschikbaar', FOOTBALLPOOL_TEXT_DOMAIN ),
								__( 'Na de eerste wedstrijd kan je hier de scores van jezelf en de andere spelers zien.', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		} else {
			$chart_data = new Chart_Data();
			switch ( $view ) {
				case 'bonusquestion': 
					$questionInfo = $stats->show_bonus_question_info( $question );
					if ( $stats->stats_visible ) {
						// chart 1: pie, what did the players score on this bonus question?
						$raw_data = $chart_data->bonus_question_pie_chart_data( $question );
						$chart = new Chart( 'chart1', 'pie', 300, 200 );
						$chart->data = $chart_data->bonus_question_pie_series_one_question( $raw_data );
						$chart->title = __( 'wat hebben de spelers gescoord?', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->custom_css = 'right';
						$output .= $chart->draw();
						
						$output .= $stats->show_answers_for_bonus_question( $question );
					}
					break;
				case 'matchpredictions':
					$m = new Matches();
					$match_info = $m->get_match_info( $match );
					$output .= $stats->show_match_info( $match_info );
					if ( $stats->stats_visible ) {
						if ($stats->data_available_for_match( $match ) ) {
							// chart 1: pie, wat hebben de deelnemers gescoord bij deze wedstrijd?
							$raw_data = $chart_data->predictions_pie_chart_data( $match );
							$chart = new Chart( 'chart1', 'pie', 300, 200 );
							$chart->data = $chart_data->predictions_pie_series( $raw_data );
							$chart->title = __( 'scoreverdeling alle spelers', FOOTBALLPOOL_TEXT_DOMAIN );
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
						$output .= sprintf( '<p><a href="?users[]=%d">' . __( 'Vergelijk de scores van %s met andere spelers.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a></p>',
											$userInfo->ID, $userInfo->display_name );
						$output .= "<div>";
						
						$pool = new Pool;
						$pool->get_bonus_questions( $user );
						// chart 1: pie, what did the players score with the game predictions?
						$raw_data = $chart_data->score_chart_data( array( $user ) );
						$chart = new Chart( 'chart1', 'pie', 300, 300 );
						$chart->data = array_shift( $chart_data->score_chart_series( $raw_data ) ); // only one user
						$chart->title = __( 'scoreverdeling wedstrijden', FOOTBALLPOOL_TEXT_DOMAIN );
						if ( $pool->has_bonus_questions ) $chart->custom_css = 'stats-pie left';
						$output .= $chart->draw();
						
						if ( $pool->has_bonus_questions ) {
							// chart 4: pie, verdeling juist/niet juist bij de bonusvragen
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data(array($user));
							if ( count( $raw_data ) > 0 ) {
								$chart = new Chart( 'chart4', 'pie', 300, 300 );
								$chart->data = array_shift( $chart_data->bonus_question_pie_series( $raw_data ) ); // only one user
								$chart->title = __( 'scoreverdeling bonusvragen', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->custom_css = 'stats-pie left';
								$output .= $chart->draw();
							}
						}

						// chart 5: pie, percentage van totaal aantal punten gescoord
						$raw_data = $chart_data->points_total_pie_chart_data( $user );
						$chart = new Chart( 'chart5', 'pie', 300, 300 );
						$chart->data = $chart_data->points_total_pie_series( $raw_data );
						$chart->title = __( '% van maximaal te halen punten', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "subtitle: { text: '(" . __( 'met inzet van joker', FOOTBALLPOOL_TEXT_DOMAIN ) . ")' }";
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
							$output .= sprintf( '<h2>%s</h2>', __( 'Geen speler(s) geselecteerd :\'(', FOOTBALLPOOL_TEXT_DOMAIN ) );
							$output .= sprintf( '<p>%s</p>', __( 'Tja, dan is hier niets te zien.<br />Je kan andere spelers selecteren aan de linkerzijde.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						} elseif ( count( $users ) == 1 ) {
							$output .= sprintf( '<h2>%s</h2>', __( 'Je kan andere spelers selecteren aan de linkerzijde.', FOOTBALLPOOL_TEXT_DOMAIN ) );
						}
						
						if ( count( $users ) >= 1 ) {
							// column charts
							// chart6: scoreverdeling
							$raw_data = $chart_data->score_chart_data( $users );
							$chart = new Chart( 'chart6', 'column', 720, 300 );
							$chart->data = $chart_data->score_chart_series( $raw_data );
							$chart->title = __( 'scoreverdeling', FOOTBALLPOOL_TEXT_DOMAIN );
							$chart->options[] = "xAxis: { 
														categories: [ '" . __( 'volle score', FOOTBALLPOOL_TEXT_DOMAIN ) . "', '" . __( 'toto score', FOOTBALLPOOL_TEXT_DOMAIN ) . "', '" . __( 'geen score', FOOTBALLPOOL_TEXT_DOMAIN ) . "' ]
												}";
							$chart->options[] = "tooltip: {
													formatter: function() {
														return this.x + '<br>'
															+ '<b>' + this.series.name + '</b>: '
															+ this.y;
													}
												}";
							$output .= $chart->draw();
							// chart7: bonusvragen
							$raw_data = $chart_data->bonus_question_for_users_pie_chart_data( $users );
							if ( count( $raw_data ) > 0 ) {
								$chart = new Chart( 'chart7', 'column', 720, 300 );
								$chart->data = $chart_data->bonus_question_pie_series( $raw_data );
								$chart->title = __( 'bonusvraag', FOOTBALLPOOL_TEXT_DOMAIN );
								$chart->options[] = "xAxis: { 
															categories: [ '" . __( 'antwoord goed', FOOTBALLPOOL_TEXT_DOMAIN ) . "', '" . __( 'antwoord fout', FOOTBALLPOOL_TEXT_DOMAIN ) . "' ]
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
						$chart = new Chart( 'chart2', 'line', 720, 500 );
						$chart->data = $chart_data->score_per_match_line_series( $raw_data );
						$chart->title = __( 'puntenopbouw', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "tooltip: {
												//shared: true, crosshairs: true 
												formatter: function() {
													return '<b>' + this.series.name + '</b>: ' 
														+ this.y + ' punten<br>'
														+ '(' + categories[this.x] + ')';
												}
											}";
						$chart->JS_options[] = 'options.xAxis.labels.enabled = false';
						$chart->JS_options[] = 'options.yAxis.min = -1';
						$chart->JS_options[] = 'options.yAxis.showFirstLabel = false';
						$output .= $chart->draw();

						//$output .= '<h2 id="c3">Stand van ' . $userInfo['name'] . ' in de pool (alle deelnemers)</h2>';
						// chart 3: position of the players in the pool
						$raw_data = $chart_data->ranking_per_match_line_chart_data( $users );
						$chart = new Chart( 'chart3', 'line', 720, 500 );
						$chart->data = $chart_data->ranking_per_match_line_series( $raw_data );
						$chart->title = __( 'positie in de pool', FOOTBALLPOOL_TEXT_DOMAIN );
						$chart->options[] = "tooltip: {
												//shared: true, crosshairs: true
												formatter: function() {
													return '<b>' + this.series.name + '</b>: ' 
														+ this.y + '" . __( 'e in de pool', FOOTBALLPOOL_TEXT_DOMAIN ) . "<br>'
														+ '(' + categories[this.x] + ')';
												}
											}";
						$chart->JS_options[] = 'options.yAxis.title.text = "' . __( 'positie in de pool', FOOTBALLPOOL_TEXT_DOMAIN ) . '"';
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