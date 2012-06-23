<?php
class Football_Pool_Admin_Groups extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Team Position', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$teams = new Football_Pool_Teams;
		if ( Football_Pool_Utils::post_string( 'form_action' ) == 'update' ) {
			$teams->update_teams();
			// reset the teams
			$teams = new Football_Pool_Teams;
			self::notice( __( 'Wijzigingen opgeslagen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		self::intro( __( "The first column contains a value that can be used for the ranking of teams in a group (Group Stage). In most cases the plugin will calculate this position automatically based on the results in the tournament. If two teams end up on the same position, even after considering all the tournament, then extra data is needed. Data this plugin doesn't have. You can manually tweak the last sort parameter here.", FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::intro( __( 'The start value is the position at the start of the tournament.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$team_names = $teams->team_names;
		$groups = new Football_Pool_Groups;
		$group_names = $groups->get_group_names();

		$ranking = $groups->get_ranking_array();

		foreach ( $ranking as $group => $rank ) {
			echo '<div>';
			echo '<h3>', $group_names[$group], '</h3>';
			echo '<table class="wp-list-table widefat" style="width:300px">';
			echo '<thead><tr><th></th><th class="team"></th><th class="plays"></th><th class="wins">w</th><th class="draws">d</th><th class="losses">l</th><th class="points"></th><th class="goals"></th></tr></thead>';
			echo '<tbody>';
			foreach( $rank as $teamranking ) {
				echo '<tr>',
						 '<td><input type="text" name="_order_', esc_attr( $teamranking['team'] ), '" size="1" maxlength="1" value="', esc_attr( $teams->get_group_order( (integer) $teamranking['team'] ) ), '" /></td>',
						 '<td class="team"><input type="text" name="_name_', esc_attr( $teamranking['team'] ), '" value="', esc_attr( $team_names[ $teamranking['team'] ] ), '" maxlength="30" class="regular-text" /></td>',
						 '<td class="plays">', $teamranking['plays'], '</td>',
						 '<td class="wins">', $teamranking['wins'], '</td>',
						 '<td class="draws">', $teamranking['draws'], '</td>',
						 '<td class="losses">', $teamranking['losses'], '</td>',
						 '<td class="points">', $teamranking['points'], '</td>',
						 '<td class="goals">(', $teamranking['for'], '-', $teamranking['against'], ')</td>',
					'</tr>';
			}
			echo '</tbody>';
			echo '</table>';
			echo '</div>';
		}
		
		self::hidden_input( 'action', 'save' );
		submit_button();
		
		self::admin_footer();
	}
}
?>