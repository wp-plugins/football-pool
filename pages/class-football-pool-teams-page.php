<?php 
class Football_Pool_Teams_Page {
	public function page_content() {
		$output = '';
		
		$team_ID = Football_Pool_Utils::get_integer( 'team' );
		$team = new Football_Pool_Team( $team_ID );
		
		// show details for team or show links for all teams
		if ( $team->id != 0 && $team->is_real == 1 && $team->is_active == 1 ) {
			// team details
			$output .= sprintf( '<h3><a href="%s" title="%s">%s</a></h3>', 
								$team->link, 
								__( 'go to the team site', FOOTBALLPOOL_TEXT_DOMAIN ), 
								$team->name
						);
			
			if ( $team->comments != '' ) {
				$output .= sprintf( '<p class="team bio">%s</p>', nl2br( $team->comments ) );
			}
			
			$output .= sprintf( '<table class="teaminfo">
								<tr>
									<th>%s:</th>
									<td><a href="%s">%s</a></td>
								</tr>', 
								__( 'plays in', FOOTBALLPOOL_TEXT_DOMAIN ),
								esc_url( 
									add_query_arg( 
										array( 'group' => $team->group_id ),
										Football_Pool::get_page_link('groups')
									)
								), 
								$team->group_name
						);
						
			// show venues?
			if ( Football_Pool_Utils::get_fp_option( 'show_venues_on_team_page', true ) ) {
				$stadiums = $team->get_stadiums();
				if ( is_array( $stadiums ) && count( $stadiums ) > 0 ) {
					$output .= sprintf( '<tr>
										<th>%s:</th>
										<td><ol class="stadium-list">',
										__( 'venues', FOOTBALLPOOL_TEXT_DOMAIN )
								);
					$stadium_page = Football_Pool::get_page_link( 'stadiums' );
					while ( $stadium = array_shift( $stadiums ) ) {
						$output .= sprintf( '<li><a href="%s">%s</a></li>'
											, esc_url( 
												add_query_arg( array( 'stadium' => $stadium->id ), $stadium_page )
											)
											, $stadium->name 
									);
					}
					$output .= '</ol></td></tr>';
				}
			}
			
			$output .= sprintf( '<tr><th valign="top">%s:</th>', __( 'photo', FOOTBALLPOOL_TEXT_DOMAIN ) );
			$output .= sprintf( '<td>%s</td></tr>', $team->HTML_thumb() );
			$output .= '</table>';
			
			// the games for this team
			$plays = $team->get_plays();
			if ( count( $plays ) > 0 ) {
				$matches = new Football_Pool_Matches;
				$output .= sprintf( '<h4>%s</h4>', __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
				$output .= $matches->print_matches( $plays );
			}
			
			$output .= sprintf( '<p><a href="%s">%s</a></p>'
								, get_page_link()
								, __( 'view all teams', FOOTBALLPOOL_TEXT_DOMAIN ) 
						);
		} else {
			// show all teams
			$teams = new Football_Pool_Teams();
			$output .= '<p><ol class="team-list">';
			$all_teams = $teams->get_teams();
			$output .= $teams->print_lines( $all_teams );
			$output .= '</ol></p>';
		}
		
		return $output;
	}
}
?>