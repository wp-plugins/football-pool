<?php
class Football_Pool_Ranking_Page {
	public function page_content()
	{
		global $current_user;
		get_currentuserinfo();
		
		$pool = new Football_Pool_Pool;
		// $userleague = get_the_author_meta( 'footballpool_league', $current_user->ID );
		$userleague = $pool->get_league_for_user( $current_user->ID );
		$userleague = ( isset( $userleague ) && is_integer( $userleague ) ) ? $userleague : FOOTBALLPOOL_LEAGUE_ALL;
		$league =  Football_Pool_Utils::post_string( 'league', $userleague );
		
		$output = '';
		if ( $pool->has_leagues ) {
			// add a league choice before the list
			$output .= sprintf( '<form action="%s" method="post">
								<div style="margin-bottom: 1em;">
									%s: 
									%s
									<input type="submit" name="_submit" value="go" />
								</div>
								</form>',
								get_page_link(),
								__( 'Kies pool', FOOTBALLPOOL_TEXT_DOMAIN ),
								$pool->league_filter( $league )
							);
		}

		$output .= $pool->print_pool_ranking( $league, $current_user->ID );
		
		return $output;
	}
}
?>