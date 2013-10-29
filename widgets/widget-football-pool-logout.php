<?php
/**
 * Widget: Logout Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action("widgets_init", create_function('', 'register_widget("Football_Pool_Logout_Widget");'));

// dummy var for translation files
$fp_translate_this = __( 'Log out Widget', FOOTBALLPOOL_TEXT_DOMAIN );
$fp_translate_this = __( 'add a log out/log in button.', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Logout_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Log out Widget',
		'description' => 'add a log out/log in button.',
		'do_wrapper' => false, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => ''
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		//$return_url = apply_filters( 'the_permalink', get_permalink( @get_the_ID() ) );
		$return_url = Football_Pool_Utils::full_url();
		$output = '';
		
		global $current_user;
		get_currentuserinfo();
		if ( $current_user->ID > 0 ) {
			$output .= sprintf( '<a class="widget button logout" href="%s" title="%s">%s</a>'
								, wp_logout_url( $return_url )
								, esc_attr( __( 'Log out', FOOTBALLPOOL_TEXT_DOMAIN ) )
								, __( 'Log out', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		} else {
			$output .= sprintf( '<a class="widget button logout" href="%s" title="%s">%s</a>'
								, wp_login_url( $return_url )
								, esc_attr( __( 'Log in', FOOTBALLPOOL_TEXT_DOMAIN ) )
								, __( 'Log in', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		}
		
		echo apply_filters( 'footballpool_widget_html_logout', $output );
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
