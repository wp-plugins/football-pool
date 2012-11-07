<?php
class Football_Pool_Pool_Page {
	public function page_content() {
		global $current_user;
		get_currentuserinfo();

		$pool = new Football_Pool_Pool;
		$user_is_player = $pool->user_is_player( $current_user->ID );
		
		$output = '';
		$msg = '';
		
		if ( $current_user->ID != 0 && $user_is_player 
									&& Football_Pool_Utils::post_string( '_action' ) == 'update' ) {
			$success = $this->update_predictions( $current_user->ID );
			
			if ( $success ) {
				$msg = sprintf( '<p style="errormessage">%s</p>', __( 'Changes saved.', FOOTBALLPOOL_TEXT_DOMAIN ) );
			} else {
				$msg = sprintf( '<p style="error">%s</p>',
							__( 'Something went wrong during the save. Check if you are still logged in. If the problems persist, then contact your webmaster.', FOOTBALLPOOL_TEXT_DOMAIN )
						);
			}
		}
		$output .= $msg;
		
		if ( $current_user->ID != 0 && $user_is_player ) {
			$questions = $pool->get_bonus_questions_for_user( $current_user->ID );
			
			$matches = new Football_Pool_Matches;
			$result = $matches->get_match_info_for_user( $current_user->ID );
			
			$empty = $matches->first_empty_match_for_user( $current_user->ID );
			if ( $pool->has_bonus_questions ) {
				$output .= sprintf( '<p><a href="#bonus">%s</a> | <a href="#match-%d">%s</a></p>'
									, __( 'Bonus questions', FOOTBALLPOOL_TEXT_DOMAIN )
									, $empty
									, __( 'Predictions', FOOTBALLPOOL_TEXT_DOMAIN )
							);
				$output .= sprintf( '<h2>%s</h2>', __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			
			// the matches
			$output .= sprintf( '<form id="predictionform" action="%s" method="post">', get_page_link() );
			$output .= $matches->print_matches_for_input( $result );
			$joker = $matches->joker_value;
			$output .= $this->save_button();
			
			// the questions
			if ( $pool->has_bonus_questions ) {
				$nr = 1;
				$output .= sprintf( '<h2 id="bonus">%s</h2>', __( 'bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ) );
				foreach ( $questions as $question ) {
					$output .= $pool->print_bonus_question( $question, $nr++ );
				}
				$output .= $this->save_button();
			}
			
			$output .= sprintf( '<input type="hidden" id="_joker" name="_joker" value="%d" />', $joker );
			$output .= '<input type="hidden" id="_action" name="_action" value="update" /></form>';
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
	
	private function save_button() {
		return sprintf( '<div class="buttonblock"><input type="submit" name="_submit" value="%s" /></div>',
						__( 'Save', FOOTBALLPOOL_TEXT_DOMAIN )
				);
	}
	
	private function update_bonus_user_answers( $questions, $answers, $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$pool = new Football_Pool_Pool();
		
		foreach ( $questions as $question ) {
			if ( $pool->bonus_is_editable( $question['question_date'] ) && $answers[ $question['id'] ] != '') {
				$sql = $wpdb->prepare( "REPLACE INTO {$prefix}bonusquestions_useranswers 
										SET userId = %d,
											questionId = %d,
											answer = %s,
											points = 0",
										$user, $question['id'], $answers[ $question['id'] ]
									);
				$wpdb->query( $sql );
			}
		}
	}
	
	private function update_predictions( $user ) {
		$pool = new Football_Pool_Pool;
		$matches = new Football_Pool_Matches;
		
		// only allow logged in users and players in the pool to update their predictions
		if ( $user <= 0 || ! $pool->user_is_player( $user ) ) return false;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$joker = 0;
		
		// only allow setting of joker if it wasn't used before on a played match
		$sql = $wpdb->prepare( "SELECT m.playDate AS match_timestamp
								FROM {$prefix}predictions p, {$prefix}matches m 
								WHERE p.matchNr = m.nr 
									AND p.hasJoker = 1 AND p.userId = %d" 
								, $user
							);
		$play_date = $wpdb->get_var( $sql );
		if ( $play_date ) {
			$play_date = new DateTime( $play_date );
			$ts = $play_date->format( 'U' );
			if ( $matches->match_is_editable( $ts ) ) {
				$joker = $this->get_joker();
			}
		} else {
			$joker = $this->get_joker();
		}
		
		// get matches
		$rows = $matches->get_info();
		
		// update predictions for all matches
		foreach ( $rows as $row ) {
			$match = $row['nr'];
			$home = Football_Pool_Utils::post_integer( '_home_' . $match, 'NULL' );
			$away = Football_Pool_Utils::post_integer( '_away_' . $match, 'NULL' );
			
			if ( $matches->match_is_editable( $row['match_timestamp'] ) && is_integer( $home ) && is_integer( $away ) ) {
				$sql = $wpdb->prepare( "REPLACE INTO {$prefix}predictions
										SET userId = %d, 
											matchNr = %d, 
											homeScore = %d, 
											awayScore = %d, 
											hasJoker = %d",
									$user, $match, $home, $away, ( $joker == $match ? 1 : 0 )
								);
				$wpdb->query( $sql );
			}
		}
		
		// update bonusquestions
		$questions = $pool->get_bonus_questions();
		if ( $pool->has_bonus_questions ) {
			$answers = array();
			foreach ( $questions as $question ) {
				switch ( $question['type'] ) {
					case 3: // multiple n
						$user_answers = Football_Pool_Utils::post_string_array( '_bonus_' . $question['id'] );
						if ( $question['max_answers'] > 0 && count( $user_answers ) > $question['max_answers'] ) {
							// remove answers from the end of the array
							// (user is cheating or admin changed the max possible answers)
							while ( count( $user_answers ) > $question['max_answers'] ) 
								array_pop( $user_answers );
						}
						$answers[ $question['id'] ] = implode( ';', $user_answers );
						break;
					case 1: // text
					case 2: // multiple 1
					default:
						$answers[ $question['id'] ] = Football_Pool_Utils::post_string( '_bonus_' . $question['id'] );
				}
				
				// add user input to answer (for multiple choice questions) if there is some input
				$user_input = Football_Pool_Utils::post_string( '_bonus_' . $question['id'] . '_userinput' );
				if ( $user_input != '' )
					$answers[ $question['id'] ] .= " {$user_input}";
			}
			$this->update_bonus_user_answers( $questions, $answers, $user );
		}
		return true;
	}
	
	private function get_joker() {
		return Football_Pool_Utils::post_integer( '_joker' );
	}
}
?>