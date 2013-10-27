<?php
/**
 * Widget: Last Games Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget( "Football_Pool_Last_Games_Widget" );' ) );

// dummy var for translation files
$fp_translate_this = __( 'Last Games Widget', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'this widget displays the last X played games of the tournament.', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'last matches', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Number of games to show', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Last_Games_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Last Games Widget',
		'description' => 'this widget displays the last X played games of the tournament.',
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
		
		$output = '';
		if ( $title != '' ) {
			$output .= $before_title . $title . $after_title;
		}
		
		$matches = new Football_Pool_Matches;
		$teams = new Football_Pool_Teams;
		
		$teampage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$rows = apply_filters( 'footballpool_lastgames_query', $matches->get_last_games( $num_games ) );
		if ( count( $rows ) > 0 ) {
			$output .= '<table class="gamesbox">';
			
			$url_home = $url_away = '';
			$team_str = '%s%s';
			
			foreach ( $rows as $row ) {
				if ( $teams->show_team_links ) {
					$url_home = esc_url( add_query_arg( array( 'team' => $row['home_team_id'] ), $teampage ) );
					$url_away = esc_url( add_query_arg( array( 'team' => $row['away_team_id'] ), $teampage ) );
					$team_str = '<a href="%s">%s</a>';
				}
				$url_stats = esc_url( add_query_arg( 
												array( 'view' => 'matchpredictions', 'match' => $row['id'] ),
												$statisticspage 
											) 
									);
				
				$output .= sprintf( '<tr><td>' . $team_str . '</td><td>-</td><td>' . $team_str . '</td>'
									, $url_home
									, $teams->team_names[ (int) $row['home_team_id'] ]
									, $url_away
									, $teams->team_names[ (int) $row['away_team_id'] ]
								);
				
				$output .= sprintf( '<td class="score"><a href="%s" title="%s">%s - %s</a></td></tr>'
								, $url_stats
								, esc_attr( __( 'view predictions', FOOTBALLPOOL_TEXT_DOMAIN ) )
								, $row['home_score']
								, $row['away_score']
							);
			}
			$output .= '</table>';
		} else {
			$output .= '<p>' . __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
		}
		
		echo apply_filters( 'footballpool_widget_html_last-games', $output );
	}
	
	public function __construct() {
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			$this->widget['description']
		);
	}
}
