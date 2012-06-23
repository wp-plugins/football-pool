<?php
/**
 * Widget: Countdown to next prediction Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget( "Football_Pool_Next_Prediction_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'countdown', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Next_Prediction_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Countdown Next Prediction Widget',
		
		'description' => 'Football pool plugin: this widget displays the time that is left to predict the next match.',
		
		'do_wrapper' => true, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'aftellen'
			),
			array(
				'name' => 'Also show when not logged in?',
				'desc' => '',
				'id' => 'all_users',
				'type' => 'checkbox',
			),
		)
	);
	
	public function html( $title, $match, $args, $instance ) {
		extract( $args );
		
		$teams = new Football_Pool_Teams;
		$teampage = Football_Pool::get_page_link( 'teams' );
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		$predictionpage = Football_Pool::get_page_link( 'pool' ) . '#match-' . $match['nr'];
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$countdown_date = new DateTime( $match['playDate'] );
		$year  = $countdown_date->format( 'Y' );
		$month = $countdown_date->format( 'm' );
		$day   = $countdown_date->format( 'd' );
		$hour  = $countdown_date->format( 'H' );
		$min   = $countdown_date->format( 'i' );
		$sec = 0;
		
		$cache_key = 'fp_countdown_id';
		$id = wp_cache_get( $cache_key );
		if ( $id === false ) {
			$id = 1;
		}
		wp_cache_set( $cache_key, $id + 1 );
		
		$extra_texts = sprintf( "{'pre_before':'%1\$s','post_before':'%2\$s','pre_after':'%3\$s','post_after':'%4\$s'}"
								, __( 'Just ', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( ' until', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( 'started ', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( ' ago:', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		printf( '<p><a href="%1$s" title="%3$s" class="next-prediction-countdown" id="next-prediction-countdown-%2$s">&nbsp;</a></p>'
				, $predictionpage
				, $id
				, __( 'click to enter prediction', FOOTBALLPOOL_TEXT_DOMAIN )
		);
		echo "<script type='text/javascript'>
				footballpool_do_countdown( '#next-prediction-countdown-{$id}', footballpool_countdown_time_text, {$extra_texts}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 3 );
				window.setInterval( function() { footballpool_do_countdown( '#next-prediction-countdown-{$id}', footballpool_countdown_time_text, {$extra_texts}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, 3 ); }, 1000 );
				</script>";
		echo '<p><a href="', $teampage, '?team=', $match['homeTeamId'], '">', 
			$teams->team_names[ (integer) $match['homeTeamId'] ], '</a>',
			' - ', 
			'<a href="', $teampage, '?team=', $match['awayTeamId'], '">', 
			$teams->team_names[ (integer) $match['awayTeamId'] ], '</a></p>';
	}
	
	public function __construct() {
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			array( 'description' => $this->widget['description'] )
		);
	}
	
	public function widget( $args, $instance ) {
		// only for logged in users?
		if ( $instance['all_users'] != 'on' && ! is_user_logged_in() ) return;
		
		// do not output a widget if there is no next match
		$matches = new Football_Pool_Matches;
		$match = $matches->get_next_match();
		if ( $match != null ) {
			//initializing variables
			$this->widget['number'] = $this->number;
			if ( isset( $instance['title'] ) )
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			else
				$title = '';
			
			$do_wrapper = ( !isset( $this->widget['do_wrapper'] ) || $this->widget['do_wrapper'] );
			
			if ( $do_wrapper ) 
				echo $args['before_widget'];
			
			$this->html( $title, $match, $args, $instance );
				
			if ( $do_wrapper ) 
				echo $args['after_widget'];
		}
	}
}
?>