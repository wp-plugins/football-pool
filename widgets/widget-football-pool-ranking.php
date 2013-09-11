<?php
/**
 * Widget: Ranking Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Ranking_Widget" );' ) );

// dummy var for translation files
$fp_translate_this = __( 'Ranking Widget', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'this widget displays the top X players in the pool.', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'standing', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Ranking', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Number of users to show', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Show players from this league', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Show number of predictions?', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Ranking_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Ranking Widget',
		'description' => 'this widget displays the top X players in the pool.',
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
				'name'    => 'Ranking',
				'desc' => '',
				'id'      => 'ranking_id',
				'type'    => 'select',
				'options' => array() // get data from the database later on
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
			array(
				'name'    => 'Show number of predictions?',
				'desc' => '',
				'id'      => 'show_num_predictions',
				'type'    => 'checkbox',
				'std'	=> false, // set this to the default later on
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$num_users = $instance['num_users'];
		$league = ! empty( $instance['league'] ) ? $instance['league'] : FOOTBALLPOOL_LEAGUE_ALL;
		$ranking_id = ! empty( $instance['ranking_id'] ) ? $instance['ranking_id'] : FOOTBALLPOOL_RANKING_DEFAULT;
		$show_num_predictions = ! empty( $instance['show_num_predictions'] ) ? 
											$instance['show_num_predictions'] : 
											Football_Pool_Utils::get_fp_option( 'show_num_predictions_in_ranking' );
		$show_num_predictions = ( $show_num_predictions != false );
		
		if ( $title != '' ) {
			echo $before_title, $title, $after_title;
		}
		
		global $current_user;
		get_currentuserinfo();
		$pool = new Football_Pool_Pool;
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		$rows = $pool->get_pool_ranking_limited( $league, $num_users, $ranking_id );
		if ( count( $rows ) > 0 ) {
			$users = array();
			foreach ( $rows as $row ) $users[] = $row['user_id'];
			if ( $show_num_predictions ) {
				$predictions = $pool->get_prediction_count_per_user( $users, $ranking_id );
			}
			
			$show_avatar = ( Football_Pool_Utils::get_fp_option( 'show_avatar' ) == 1 );
			
			$i = 1;
			echo '<table class="pool-ranking ranking-widget">';
			if ( $show_num_predictions ) {
				printf( '<tr>
							<th></th>
							<th class="user">%s</th>
							<th class="num-predictions">%s</th>
							<th class="score">%s</th>
						</tr>'
						, __( 'user', FOOTBALLPOOL_TEXT_DOMAIN )
						, __( 'predictions', FOOTBALLPOOL_TEXT_DOMAIN )
						, __( 'points', FOOTBALLPOOL_TEXT_DOMAIN )
				);
			}
			
			foreach ( $rows as $row ) {
				$class = ( $i % 2 == 0 ? 'even' : 'odd' );
				if ( $row['user_id'] == $current_user->ID ) $class .= ' currentuser';
				if ( $show_num_predictions ) {
					if ( array_key_exists( $row['user_id'], $predictions ) ) {
						$num_predictions = $predictions[$row['user_id']];
					} else {
						$num_predictions = 0;
					}
					$num_predictions = sprintf( '<td class="num-predictions">%d</td>', $num_predictions );
				} else {
					$num_predictions = '';
				}
				
				$url = esc_url( add_query_arg( array( 'user' => $row['user_id'] ), $userpage ) );
				printf( '<tr class="%s">
							<td>%d.</td>
							<td><a href="%s">%s%s</a></td>
							%s<td class="score">%d</td>
						</tr>'
						, $class
						, $i++
						, $url
						, $pool->get_avatar( $row['user_id'], 'small' )
						, $row["user_name"]
						, $num_predictions
						, $row['points']
				);
			}
			echo '</table>';
		} else {
			printf( '<p>%s</p>', __( 'No match data available.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
	}
	
	public function __construct() {
		// fields data only needed in the admin
		if ( is_admin() ) {
			$pool = new Football_Pool_Pool();
			// get the ranking-options from the database
			$rankings = $pool->get_rankings();
			$options = array();
			foreach ( $rankings as $ranking ) {
				$options[$ranking['id']] = $ranking['name'];
			}
			$this->widget['fields'][1]['options'] = $options;
			// get the league-options from the database
			$leagues = $pool->get_leagues();
			$options = array();
			foreach ( $leagues as $league ) {
				$options[$league['league_id']] = $league['league_name'];
			}
			$this->widget['fields'][3]['options'] = $options;
			$this->widget['fields'][4]['std'] = ( Football_Pool_Utils::get_fp_option( 'show_num_predictions_in_ranking' ) == 1 );
		}
		
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			$this->widget['description']
		);
	}
}
?>