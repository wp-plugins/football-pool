<?php
/**
 * Widget: Last Games Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget( "Football_Pool_Last_Games_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'laatste wedstrijden', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Last_Games_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Last Games Widget',
		'description' => 'Football pool plugin: this widget displays the last X played games of the tournament.',
		'do_wrapper' => true, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'laatste wedstrijden'
			),
			array(
				'name' => 'Number of games to show',
				'desc' => '',
				'id' => 'num_games',
				'type' => 'text',
				'std' => '4'
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$num_games = $instance['num_games'];
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$matches = new Football_Pool_Matches;
		$teams = new Football_Pool_Teams;
		
		$teampage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$rows = $matches->get_last_games( $num_games );
		if ( count( $rows ) > 0 ) {
			echo '<table class="gamesbox">';
			foreach ( $rows as $row ) {
				echo '<tr><td><a href="', $teampage, '?team=', $row['homeTeamId'], '">', 
					$teams->team_names[ (integer) $row['homeTeamId'] ], '</a>',
					'</td><td>-</td>', 
					'<td><a href="', $teampage, '?team=', $row['awayTeamId'], '">', 
					$teams->team_names[ (integer) $row['awayTeamId'] ], '</a></td>';
				echo '<td class="score"><a href="', $statisticspage, '?view=matchpredictions&match=', $row['nr'], '"
						title="', __( 'bekijk voorspellingen', FOOTBALLPOOL_TEXT_DOMAIN ), '">', 
					$row['homeScore'], ' - ', $row['awayScore'], '</a></td></tr>';
			}
			echo '</table>';
		} else {
			echo '<p>', __( 'Geen wedstrijdgegevens beschikbaar.', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
		}
	}
	
	public function __construct() {
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			array( 'description' => $this->widget['description'] )
		);
	}
}
?>