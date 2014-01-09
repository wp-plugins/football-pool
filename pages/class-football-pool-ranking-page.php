<?php
class Football_Pool_Ranking_Page {
	public function page_content()
	{
		global $current_user;
		get_currentuserinfo();
		
		$output = '';
		$pool = new Football_Pool_Pool;
		$userleague = $pool->get_league_for_user( $current_user->ID );
		$userleague = ( isset( $userleague ) && is_integer( $userleague ) ) ? $userleague : FOOTBALLPOOL_LEAGUE_ALL;
		$league =  Football_Pool_Utils::request_string( 'league', $userleague );
		
		$ranking_display = Football_Pool_Utils::get_fp_option( 'ranking_display', 0 );
		if ( $ranking_display == 1 ) {
			$ranking = Football_Pool_Utils::request_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} elseif ( $ranking_display == 2 ) {
			$ranking = Football_Pool_Utils::get_fp_option( 'show_ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} else {
			$ranking = FOOTBALLPOOL_RANKING_DEFAULT;
		}
		
		$user_defined_rankings = $pool->get_rankings( 'user defined' );
		if ( $pool->has_leagues || ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) ) {
			$output .= sprintf( '<form action="%s" method="get"><div style="margin-bottom: 1em;">'
								, get_page_link() 
						);
			if ( $pool->has_leagues ) {
				$output .= sprintf( '%s: %s',
									__( 'Choose league', FOOTBALLPOOL_TEXT_DOMAIN ),
									$pool->league_filter( $league )
							);
			}
			
			if ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) {
				$options = array();
				$options[FOOTBALLPOOL_RANKING_DEFAULT] = '';
				foreach( $user_defined_rankings as $user_defined_ranking ) {
					$options[$user_defined_ranking['id']] = $user_defined_ranking['name'];
				}
				$output .= sprintf( '<br />%s: %s'
									, __( 'Choose ranking', FOOTBALLPOOL_TEXT_DOMAIN )
									, Football_Pool_Utils::select( 
															'ranking', $options, $ranking, '', 'ranking-page ranking-select' )
							);
			}
			$output .= sprintf( '<input type="submit" name="_submit" value="%s" />'
								, __(  'go', FOOTBALLPOOL_TEXT_DOMAIN )
						);
			$output .= '</div></form>';
		}

		$output .= $pool->print_pool_ranking( $league, $current_user->ID, $ranking );
		
		return apply_filters( 'footballpool_ranking_page_html', $output );
	}
}
