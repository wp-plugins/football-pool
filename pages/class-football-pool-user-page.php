<?php
class Football_Pool_User_Page {
	public function page_content() {
		$user_ID = Football_Pool_Utils::get_integer( 'user', 0 );
		$user = get_userdata( $user_ID );
		
		$output = '';
		
		if ( $user ) {
			$stats = new Football_Pool_Statistics;
			if ( $stats->stats_enabled ) {
				$output .= sprintf( '<div class="statistics" title="%s">', __( 'view all statistics for this user', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$output .= sprintf( '<h5>%s</h5>', __( 'Statistics', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$output .= sprintf( '<p><a class="statistics" href="%s">%s</a></p>',
									esc_url(
										add_query_arg(
											array( 'view' => 'user', 'user' => $user->ID ),
											Football_Pool::get_page_link( 'statistics' )
										)
									),
									__( 'Statistics', FOOTBALLPOOL_TEXT_DOMAIN )
							);
				$output .= '</div>';
			}

			$matches = new Football_Pool_Matches;
			$matches->disable_edits();

			$output .= sprintf( '<p>%s <span class="username">%s</span>.</p>',
								__( 'Below are all the predictions for', FOOTBALLPOOL_TEXT_DOMAIN ),
								$user->display_name
								);
			if ( ! $matches->always_show_predictions ) {
				$output .= sprintf( '<p>%s</p>',
									__( 'Only matches and bonus questions that can\'t be changed are shown here.', FOOTBALLPOOL_TEXT_DOMAIN )
									);
			}
			
			$result = $matches->get_match_info_for_user( $user_ID );
			
			$output .= $matches->print_matches_for_input( $result );
			
			$pool = new Football_Pool_Pool;
			$questions = $pool->get_bonus_questions_for_user( $user_ID );
			if ( $pool->has_bonus_questions ) {
				$output .= sprintf( '<h2>%s</h2>', __( 'bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$output .= $pool->print_bonus_question_for_user( $questions );
			}
		} else {
			$output .= sprintf( '<p></p>', __( 'No user selected.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}

		return $output;
	}
}
?>