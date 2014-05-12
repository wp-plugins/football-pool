<?php
class Football_Pool_Ranking_Page {
	public function page_content()
	{
		$pool = new Football_Pool_Pool;
		
		global $current_user;
		get_currentuserinfo();
		
		$output = '';
		$userleague = $pool->get_league_for_user( $current_user->ID );
		if ( ! isset( $userleague ) && ! is_integer( $userleague ) ) $userleague = FOOTBALLPOOL_LEAGUE_ALL;
		$league = apply_filters( 'footballpool_rankingpage_league', Football_Pool_Utils::request_string( 'league', $userleague ) );
		
		$ranking_display = Football_Pool_Utils::get_fp_option( 'ranking_display', 0 );
		if ( $ranking_display == 1 ) {
			$ranking_id = Football_Pool_Utils::request_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} elseif ( $ranking_display == 2 ) {
			$ranking_id = Football_Pool_Utils::get_fp_option( 'show_ranking', FOOTBALLPOOL_RANKING_DEFAULT );
		} else {
			$ranking_id = FOOTBALLPOOL_RANKING_DEFAULT;
		}
		
		$user_defined_rankings = $pool->get_rankings( 'user defined' );
		
		if ( $pool->has_leagues || ( $ranking_display == 1 && count( $user_defined_rankings ) > 0 ) ) {
			$output .= sprintf( '<form class="ranking-select-form" action="%s" method="get"><div class="ranking-select-block">'
								, get_page_link() 
						);
			if ( $pool->has_leagues ) {
				$output .= sprintf( '<div class="league-select">%s: %s</div>',
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
				$output .= sprintf( '<br /><div class="ranking-select">%s: %s</div>'
									, __( 'Choose ranking', FOOTBALLPOOL_TEXT_DOMAIN )
									, Football_Pool_Utils::select( 
															'ranking', $options, $ranking_id, '', 'ranking-page ranking-select' )
							);
			}
			$output .= sprintf( '<input type="submit" name="_submit" value="%s" /><input type="hidden" name="page_id" value="%d" />'
								, __(  'go', FOOTBALLPOOL_TEXT_DOMAIN )
								, get_the_ID()
						);
			$output .= '</div></form>';
		}
		
		$rows = $pool->get_pool_ranking( $league, $ranking_id );
		$ranking = $users = array();
		if ( count( $rows ) > 0 ) {
			// there are results in the database, so get the ranking
			foreach ( $rows as $row ) {
				$ranking[] = $row;
				$users[] = $row['user_id'];
			}
		} else {
			// no results, show a list of users
			$rows = $pool->get_users( $league );
			if ( count( $rows ) > 0 ) {
				$output .= '<p>' . __( 'No results yet. Below is a list of all users.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
				$i = 0;
				foreach ( $rows as $row ) {
					$ranking[$i] = $row;
					$ranking[$i]['ranking'] = $i + 1;
					$users[$i] = $row['user_id'];
					$i++;
				}
			} else {
				$output .= '<p>'. __( 'No users have registered for this pool (yet).', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
			}
		}
		
		$filtered_ranking = apply_filters( 'footballpool_ranking_array', $ranking );
		$filtered_users = apply_filters( 'footballpool_ranking_users', $users );
		
		if ( count( $filtered_ranking ) > 0 ) {
			$output .= $pool->print_pool_ranking( $league, $current_user->ID, $ranking_id
													, $filtered_users, $filtered_ranking );
		}
		
		return apply_filters( 'footballpool_ranking_page_html', $output );
	}
}
