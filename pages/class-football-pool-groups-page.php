<?php
class Football_Pool_Groups_Page {
	public function page_content()
	{
		$output = '';
		$teams = new Football_Pool_Teams;
		$team_names = $teams->team_names;

		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();

		$ranking = $groups->get_ranking_array();

		$group_id = Football_Pool_Utils::get_string('group');

		foreach ( $ranking as $group => $rank ) {
			if ( $group_id == '' || $group_id == $group ) {
				$output .= sprintf( '<div class="ranking"><h2>%s</h2>', $group_names[$group] );
				$output .= '<table class="ranking">
								<thead>
									<tr>
										<th class="team"></th>
										<th class="plays"></th>
										<th class="wins">w</th>
										<th class="draws">d</th>
										<th class="losses">l</th>
										<th class="points"></th>
										<th class="goals"></th>
									</tr>
								</thead>
								<tbody>';
				$teampage = Football_Pool::get_page_link( 'teams' );
				foreach ( $rank as $teamranking ) {
					$output .= sprintf( '
									<tr>
										<td class="team"><a href="%s?team=%d">%s</a></td>
										<td class="plays">%d</td>
										<td class="wins">%d</td>
										<td class="draws">%d</td>
										<td class="losses">%d</td>
										<td class="points">%d</td>
										<td class="goals">(%d-%d)</td>
									</tr>',
									$teampage,
									$teamranking['team'],
									$team_names[$teamranking['team']],
									$teamranking['plays'],
									$teamranking['wins'],
									$teamranking['draws'],
									$teamranking['losses'],
									$teamranking['points'],
									$teamranking['for'],
									$teamranking['against']
								);
				}
				$output .= '</tbody></table></div>';
			}
		}

		if ( $group_id ) {
			// the games for this group
			$output .= '<h2 style="clear: both;">' . __( 'wedstrijden in de voorrondes', FOOTBALLPOOL_TEXT_DOMAIN ) . '</h2>';
			$plays = $groups->get_plays_for_group( $group_id );
			
			$matches = new Matches;
			$output .= $matches->print_matches($plays);

			$output .= '<p style="clear: both;"><a href="' . get_page_link() . '">' 
					. __( 'bekijk alle poules', FOOTBALLPOOL_TEXT_DOMAIN ) 
					. '</a></p>';
		}
		
		return $output;
	}
}
?>