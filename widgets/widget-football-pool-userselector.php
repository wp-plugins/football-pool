<?php
/**
 * Widget: User Selector Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( 'widgets_init', create_function( '', 'register_widget("Football_Pool_User_Selector_Widget");' ) );

// dummy var for translation files
$fp_dummy_var = __( 'players', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_User_Selector_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'User Selector Widget',
		'description' => 'Football pool plugin: this widget displays a list of users that can be selected. Only for use on the Statistics page; it won\'t even show on all other pages.',
		'do_wrapper' => true, 
		
		'fields' => array(
						array(
							'name' => 'Title',
							'desc' => '',
							'id' => 'title',
							'type' => 'text',
							'std' => 'spelers'
						),
						array(
							'name' => 'height (px)',
							'desc' => '',
							'id' => 'height',
							'type' => 'text',
							'std' => '200'
						),
					)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		// default 200px
		$height = (integer) $instance['height'] > 0 ? (integer) $instance['height'] : 200;
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$statisticspage = Football_Pool::get_page_link( 'statistics' );
		
		$users = Football_Pool_Utils::get_integer_array( 'users' );
		
		global $current_user;
		get_currentuserinfo();
		if ( ! in_array( $current_user->ID, $users ) ) $users[] = $current_user->ID;
		
		$pool = new Football_Pool_Pool;
		$rows = $pool->get_users( FOOTBALLPOOL_LEAGUE_ALL );
		if ( count($rows) > 0 ) {
			echo '<form action="', $statisticspage, '" method="get">';
			
			echo '<ol class="userselector" style="height: ', $height, 'px;">';
			foreach( $rows as $row ) {
				$selected = ( in_array( $row['userId'], $users ) ) ? true : false;
				echo '<li', ( $selected ? ' class="selected"' : '' ), '>
						<input type="checkbox" name="users[]" id="user', $row['userId'], '"
							value="', $row['userId'], '" ', ( $selected ? 'checked="checked" ' : '' ), '/>
						<label for="user', $row['userId'], '"> ', $row['userName'], '</label></li>';
			}
			echo '</ol>';
			echo '<p><input type="submit" value="', __( 'Change charts', FOOTBALLPOOL_TEXT_DOMAIN ), '" /></p>';
			echo '</form>';
		} else {
			echo '<p>', __( 'No users in the pool.', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
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
	
	public function widget( $args, $instance ) {
		$page_id = @get_the_ID(); // on a 404 error-page this was causing troubles
		$view = Football_Pool_Utils::get_string( 'view' );
		$stats_id = Football_Pool_Utils::get_fp_option( 'page_id_statistics' );
		
		// this widget is for the statistics page only, so return in all other cases
		if ( $page_id != $stats_id ) {
			return;
		} else {
			if ( $view == 'matchpredictions' || $view == 'bonusquestion' || $view == 'user' ) {  
				return;
			}
		}
		
		//initializing variables
		$this->widget['number'] = $this->number;
		if ( isset( $instance['title'] ) )
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		else
			$title = '';
			
		$do_wrapper = ( !isset( $this->widget['do_wrapper'] ) || $this->widget['do_wrapper'] );
		
		if ( $do_wrapper ) 
			echo $args['before_widget'];
		
		$this->html( $title, $args, $instance );
			
		if ( $do_wrapper ) 
			echo $args['after_widget'];
	}
}
?>