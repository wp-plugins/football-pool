<?php
/**
 * Widget: Ranking Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Ranking_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'standing', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Ranking_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Ranking Widget',
		'description' => 'Football pool plugin: this widget displays the top X players in the pool.',
		'do_wrapper' => true, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'standing'
			),
			array(
				'name' => 'Number of users to show',
				'desc' => '',
				'id' => 'num_users',
				'type' => 'text',
				'std' => '5'
			),
			array(
				'name'    => 'Show players from this league',
				'desc' => '',
				'id'      => 'league',
				'type'    => 'select',
				'options' => array() // get data from the database later on
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$num_users = $instance['num_users'];
		$league = ! empty( $instance['league'] ) ? $instance['league'] : FOOTBALLPOOL_LEAGUE_ALL;
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		global $current_user;
		get_currentuserinfo();
		$pool = new Football_Pool_Pool;
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		$rows = $pool->get_pool_ranking_limited( $league, $num_users );
		if ( count( $rows ) > 0 ) {
			$i = 1;
			echo '<table class="poolranking">';
			foreach ( $rows as $row ) {
				$class = ( $i % 2 == 0 ? 'even' : 'odd' );
				if ( $row['userId'] == $current_user->ID ) $class .= ' currentuser';
				
				$url = esc_url( add_query_arg( array( 'user' => $row['userId'] ), $userpage ) );
				echo '<tr class="', $class, '"><td>', $i++, '.</td>',
					'<td><a href="', $url, '">', $row['userName'], '</a></td>',
					'<td class="score">', $row['points'], '</td></tr>';
			}
			echo '</table>';
		} else {
			echo '<p>'. __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) . '</p>';
		}
	}
	
	public function __construct() {
		// fields data only needed in the admin
		if ( is_admin() ) {
			// get the league-options from the database
			$pool = new Football_Pool_Pool();
			$leagues = $pool->get_leagues();
			foreach ( $leagues as $league ) {
				$options[ $league['leagueId'] ] = $league['leagueName'];
			}
			$this->widget['fields'][2]['options'] = $options;
		}
		
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			array( 'description' => $this->widget['description'] )
		);
	}
}
?>