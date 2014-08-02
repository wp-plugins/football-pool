<?php
class Football_Pool_Pool_Page {
	public function page_content() {
		global $current_user;
		get_currentuserinfo();

		$pool = new Football_Pool_Pool;
		$user_is_player = $pool->user_is_player( $current_user->ID );
		
		$output = $pool->prediction_form_update();
		
		if ( $current_user->ID != 0 && $user_is_player ) {
			$questions = $pool->get_bonus_questions_for_user( $current_user->ID );
			// determine if there are any questions not linked to a match
			$show_question_form = false;
			if ( $pool->has_bonus_questions ) {
				foreach ( $questions as $question ) {
					if ( $question['match_id'] == 0 ) {
						$show_question_form = true;
						break;
					}
				}
			}
			$matches = new Football_Pool_Matches;
			// example code for http://wordpress.org/support/topic/only-display-x-number-of-games-in-the-future
			// $ids = array( 0 ); // force empty set
			// // only matches between now and two weeks from now
			// $date_from = time();
			// $date_to = strtotime( '+2 week' );
			// foreach ( $matches->matches as $match ) {
				// if ( $match['match_timestamp'] >= $date_from  && $match['match_timestamp'] < $date_to ) {
					// $ids[] = $match['id'];
				// }
			// }
			// $result = $matches->get_match_info_for_user( $current_user->ID, $ids );
			$result = $matches->get_match_info_for_user( $current_user->ID );
			$result = apply_filters( 'footballpool_page_pool_matches_filter', $result, $current_user->ID );
			
			$id = Football_Pool_Utils::get_counter_value( 'fp_predictionform_counter' );
			
			$empty_prediction = $matches->first_empty_match_for_user( $current_user->ID );
			if ( $show_question_form && $pool->has_matches ) {
				$output .= sprintf( '<p><a href="#bonus">%s</a> | <a href="#match-%d-%d">%s</a></p>'
									, __( 'Bonus questions', FOOTBALLPOOL_TEXT_DOMAIN )
									, $empty_prediction
									, $id
									, __( 'Predictions', FOOTBALLPOOL_TEXT_DOMAIN )
							);
			}
			
			$output .= $pool->prediction_form_start( $id );
			
			if ( $pool->has_matches ) {
				$output .= sprintf( '<h2>%s</h2>', __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
				// the matches
				$output .= $pool->prediction_form_matches( $result, false, $id, 'matches pool-page' );
			}
			
			// the questions
			if ( $show_question_form ) {
				$nr = 1;
				$output .= sprintf( '<h2 id="bonus">%s</h2>', __( 'bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ) );
				foreach ( $questions as $question ) {
					if ( $question['match_id'] == 0 ) {
						$output .= $pool->print_bonus_question( $question, $nr++ );
					}
				}
				$output .= $pool->save_button( 'questions pool-page' );
			}
			
			$output .= $pool->prediction_form_end();
		} else {
			$output .= '<p>';
			$output .= sprintf( __( 'You have to be a registered user and <a href="%s">logged in</a> to play in this pool.', FOOTBALLPOOL_TEXT_DOMAIN ), 
								wp_login_url(
									apply_filters( 'the_permalink', get_permalink( get_the_ID() ) )
								)
						);
			$output .= '</p>';
		}
		
		return $output;
	}
	
}
