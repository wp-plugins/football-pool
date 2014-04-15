<?php
/**
 * Widget: Countdown to next prediction Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget( "Football_Pool_Next_Prediction_Widget" );' ) );

// dummy var for translation files
$fp_translate_this = __( 'Countdown Next Prediction Widget', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'this widget displays the time that is left to predict the next match (optionally only for a given team).', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'countdown', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Team', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Also show when not logged in?', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'Format', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Next_Prediction_Widget extends Football_Pool_Widget {
	protected $match;
	protected $widget = array(
		'name' => 'Countdown Next Prediction Widget',
		'description' => 'this widget displays the time that is left to predict the next match (optionally only for a given team).',
		'do_wrapper' => true, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'countdown'
			),
			array(
				'name' => 'Format',
				'desc' => '',
				'id' => 'format',
				'type' => 'select',
				'options' => array() // get data later on
			),
			array(
				'name' => 'Team',
				'desc' => '',
				'id' => 'team_id',
				'type' => 'select',
				'options' => array() // get data from the database later on
			),
			array(
				'name' => 'Also show when not logged in?',
				'desc' => '',
				'id' => 'all_users',
				'type' => 'checkbox',
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		if ( ! isset( $instance['format'] ) ) $instance['format'] = 3;
		
		$teams = new Football_Pool_Teams;
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		$predictionpage = Football_Pool::get_page_link( 'pool' ) . '#match-' . $this->match['id'];
		
		$output = '';
		if ( $title != '' ) {
			$output .= $before_title . $title . $after_title;
		}
		
		$countdown_date = new DateTime( Football_Pool_Utils::date_from_gmt( $this->match['play_date'] ) );
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
		$output .= sprintf( '<div class="wrapper next-prediction-countdown"><p><a href="%1$s" title="%3$s" id="next-prediction-countdown-%2$s">&nbsp;</a></p>'
				, $predictionpage
				, $id
				, __( 'click to enter prediction', FOOTBALLPOOL_TEXT_DOMAIN )
		);
		$output .= "<script>
				FootballPool.countdown( '#next-prediction-countdown-{$id}', {$extra_texts}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, {$instance['format']} );
				window.setInterval( function() { FootballPool.countdown( '#next-prediction-countdown-{$id}', {$extra_texts}, {$year}, {$month}, {$day}, {$hour}, {$min}, {$sec}, {$instance['format']} ); }, 1000 );
				</script>";
		if ( $teams->show_team_links ) {
			$teampage = Football_Pool::get_page_link( 'teams' );
			$url_home = esc_url( add_query_arg( array( 'team' => $this->match['home_team_id'] ), $teampage ) );
			$url_away = esc_url( add_query_arg( array( 'team' => $this->match['away_team_id'] ), $teampage ) );
			$team_str = '<a href="%s">%s</a>';
		} else {
			$url_home = $url_away = '';
			$team_str = '%s%s';
		}
		$output .= sprintf( '<p>' . $team_str . ' - ' . $team_str . '</p></div>'
							, $url_home
							, ( isset( $teams->team_names[ (int) $this->match['home_team_id'] ] ) ?
										$teams->team_names[ (int) $this->match['home_team_id'] ] : '' )
							, $url_away
							, ( isset( $teams->team_names[ (int) $this->match['away_team_id'] ] ) ?
										$teams->team_names[ (int) $this->match['away_team_id'] ] : '' )
						);
		
		echo apply_filters( 'footballpool_widget_html_next-prediction', $output );
	}
	
	public function __construct() {
		if ( is_admin() ) {
			$teams = new Football_Pool_Teams;
			// format options
			$this->widget['fields'][1]['options'] = array(
														2 => __( 'days, hours, minutes, seconds', FOOTBALLPOOL_TEXT_DOMAIN ),
														3 => __( 'hours, minutes, seconds', FOOTBALLPOOL_TEXT_DOMAIN ),
														1 => __( 'only seconds', FOOTBALLPOOL_TEXT_DOMAIN )
													);
			// get the team options from the database
			$teams = $teams->team_names;
			$options = array();
			$options[0] = '';
			foreach ( $teams as $team_id => $team_name ) {
				$options[$team_id] = $team_name;
			}
			$this->widget['fields'][2]['options'] = $options;
		}
		
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			$this->widget['description']
		);
	}
	
	public function widget( $args, $instance ) {
		// only for logged in users?
		if ( $instance['all_users'] != 'on' && ! is_user_logged_in() ) return;
		
		$matches = new Football_Pool_Matches;
		if ( $instance['team_id'] > 0 ) {
			$match = $matches->get_next_match( null, $instance['team_id'] );
		} else {
			$match = $matches->get_next_match();
		}
		// do not output a widget if there is no next match
		if ( $match != null ) {
			$this->match = $match;
			
			//initializing variables
			$this->widget['number'] = $this->number;
			if ( isset( $instance['title'] ) )
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			else
				$title = '';
			
			$do_wrapper = ( !isset( $this->widget['do_wrapper'] ) || $this->widget['do_wrapper'] );
			
			if ( $do_wrapper ) 
				echo $args['before_widget'];
			
			$this->widget_html( $title, $args, $instance );
				
			if ( $do_wrapper ) 
				echo $args['after_widget'];
		}
	}
}
