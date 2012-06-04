<?php
/**
 * Widget: Group Standing Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Group_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'stand', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Group_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Group Widget',
		'description' => 'Football pool plugin: this widget displays the tournament standing for a group.',
		'do_wrapper' => true, 
		
		'fields' => array(
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'stand'
			),
			array(
				'name'    => 'Show this group',
				'desc'    => '',
				'id'      => 'group',
				'type'    => 'select',
				'options' => array() // get data from the database later on (function form)
			),
			array(
				'name'    => 'Layout of the widget',
				'desc'    => '',
				'id'      => 'layout',
				'type'    => 'select',
				'options' => array() // get data later on (function form)
			),
		)
	);
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$group = ! empty( $instance['group'] ) ? $instance['group'] : '1';
		$layouts = array( 1 => 'wide', 2 => 'small' );
		$layout = $layouts[ $instance['layout'] ];
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$groups = new Football_Pool_Groups;
		echo $groups->print_group_standing( $group, $layout );
	}
	
	public function __construct() {
		// get the groups from the database
		$g = new Football_Pool_Groups();
		$groups = $g->get_group_names();
		foreach ( $groups as $id => $group ) {
			$options[ $id ] = $group;
		}
		$this->widget['fields'][1]['options'] = $options;
		
		// set the layout options
		$this->widget['fields'][2]['options'] = array ( 
														1 => __( 'breed', FOOTBALLPOOL_TEXT_DOMAIN ), 
														2 => __( 'smal', FOOTBALLPOOL_TEXT_DOMAIN ) 
												);
		
		$classname = str_replace( '_', '', get_class( $this ) );
		
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			array( 'description' => $this->widget['description'] )
		);
	}
}
?>