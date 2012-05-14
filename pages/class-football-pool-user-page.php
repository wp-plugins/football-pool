<?php
class Football_Pool_User_Page {
	public function page_content() {
		$user_ID = Football_Pool_Utils::get_integer( 'user', 0 );
		$user = get_userdata( $user_ID );
		
		$output = '';
		
		if ( $user ) {
			$output .= sprintf( '<div class="statistics" title="%s">', __( 'bekijk alle statistieken van deze spelers', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$output .= sprintf( '<h5>%s</h5>', __( 'Statistieken', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$output .= sprintf( '<p><a class="statistics" href="%s?view=user&amp;user=%d">%s</a></p>',
								Football_Pool::get_page_link( 'statistics' ),
								$user->ID,
								__( 'Statistieken', FOOTBALLPOOL_TEXT_DOMAIN )
						);
			$output .= '</div>';

			$output .= sprintf( '<p>%s <span class="username">%s</span>.</p>',
								__( 'Hieronder staan de voorspellingen van', FOOTBALLPOOL_TEXT_DOMAIN ),
								$user->display_name
								);
			$output .= sprintf( '<p>%s</p>',
								__( 'Alleen voorspellingen en antwoorden die niet meer kunnen worden aangepast, worden getoond.', FOOTBALLPOOL_TEXT_DOMAIN )
								);
			$matches = new Matches;
			$matches->disable_edits();
			
			$result = $matches->get_match_info_for_user( $user_ID );
			
			$output .= $matches->print_matches_for_input( $result );
			
			$pool = new Football_Pool_Pool;
			$questions = $pool->get_bonus_questions( $user_ID );
			if ( $pool->has_bonus_questions ) {
				$output .= sprintf( '<h2>%s</h2>', __( 'bonusvragen', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$output .= $pool->print_bonus_question_for_user( $questions );
			}
		} else {
			$output .= sprintf( '<p></p>', __( 'Geen speler geselecteerd.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}

		return $output;
	}
}
?>