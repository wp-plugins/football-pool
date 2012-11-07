<?php
/**
 * Widget: Last Games Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget( "Football_Pool_Last_Games_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'last matches', FOOTBALLPOOL_TEXT_DOMAIN );

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
				'std' => 'last matches'
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
			
			$url_home = $url_away = '';
			$team_str = '%s%s';
			
			foreach ( $rows as $row ) {
				if ( $teams->show_team_links ) {
					$url_home = esc_url( add_query_arg( array( 'team' => $row['homeTeamId'] ), $teampage ) );
					$url_away = esc_url( add_query_arg( array( 'team' => $row['awayTeamId'] ), $teampage ) );
					$team_str = '<a href="%s">%s</a>';
				}
				$url_stats = esc_url( add_query_arg( 
											array( 'view' => 'matchpredictions', 'match' => $row['nr'] ),
											$statisticspage 
											) 
									);
				
				printf( '<tr><td>' . $team_str . '</td><td>-</td><td>' . $team_str . '</td>'
						, $url_home
						, $teams->team_names[ (int) $row['homeTeamId'] ]
						, $url_away
						, $teams->team_names[ (int) $row['awayTeamId'] ]
					);
				
				echo '<td class="score"><a href="', $url_stats, '" title="', __( 'view predictions', FOOTBALLPOOL_TEXT_DOMAIN ), '">', 
					$row['homeScore'], ' - ', $row['awayScore'], '</a></td></tr>';
			}
			echo '</table>';
		} else {
			echo '<p>', __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
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