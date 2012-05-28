<?php
/**
 * Widget: Shoutbox Widget
 */


/**
 * Based on Empty Widget template
 * https://gist.github.com/1229641
 */

/**
 * Protection 
 * 
 * This string of code will prevent hacks from accessing the file directly.
 */
defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );

/**
 * Actions and Filters
 * 
 * Register any and all actions here. Nothing should actually be called 
 * directly, the entire system will be based on these actions and hooks.
 */
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Shoutbox_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'shoutbox', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Shoutbox_Widget extends WP_Widget {
	/**
	 * Widget settings
	 * 
	 * Simply use the following field examples to create the WordPress Widget options that
	 * will display to administrators. These options can then be found in the $params 
	 * variable within the widget method.
	 * 
	 * 
		array(
			'name' => 'Title',
			'desc' => '',
			'id' => 'title',
			'type' => 'text',
			'std' => 'Your widgets title'
		),
		array(
			'name' => 'Textarea',
			'desc' => 'Enter big text here',
			'id' => 'textarea_id',
			'type' => 'textarea',
			'std' => 'Default value 2'
		),
		array(
		    'name'    => 'Select box',
			'desc' => '',
		    'id'      => 'select_id',
		    'type'    => 'select',
		    'options' => array( 'KEY1' => 'Value 1', 'KEY2' => 'Value 2', 'KEY3' => 'Value 3' )
		),
		array(
			'name' => 'Radio',
			'desc' => '',
			'id' => 'radio_id',
			'type' => 'radio',
			'options' => array(
				array('name' => 'Name 1', 'value' => 'Value 1'),
				array('name' => 'Name 2', 'value' => 'Value 2')
			)
		),
		array(
			'name' => 'Checkbox',
			'desc' => '',
			'id' => 'checkbox_id',
			'type' => 'checkbox'
		),
	 */
	
	protected $widget = array(
		// If not set, then name of class will be used (underscores replaced with spaces).
		'name' => 'Shoutbox Widget',
		
		// this description will display within the administrative widgets area
		// when a user is deciding which widget to use.
		'description' => 'Football pool plugin: a shoutbox for your players. Leave short messages.',
		
		// determines whether or not to use the sidebar _before and _after html
		'do_wrapper' => true, 
		
		'fields' => array(
			// You should always offer a widget title
			array(
				'name' => 'Title',
				'desc' => '',
				'id' => 'title',
				'type' => 'text',
				'std' => 'shoutbox'
			),
			array(
				'name' => 'Number of messages to display',
				'desc' => '',
				'id' => 'num_messages',
				'type' => 'text',
				'std' => '20'
			),
		)
	);
	
	/**
	 * Widget HTML
	 * 
	 * If you want to have an all inclusive single widget file, you can do so by
	 * dumping your css styles with base_encoded images along with all of your 
	 * html string, right into this method.
	 *
	 * @param array $title
	 * @param array $args
	 * @param array $instance
	 */
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$num_messages = ( is_integer( $instance['num_messages'] ) ? $instance['num_messages'] : 20 );
		$max_chars = get_option( 'footballpool_shoutbox_max_chars', 150 );
		
		global $current_user;
		get_currentuserinfo();
		$shoutbox = new Football_Pool_Shoutbox;
		
		if ( Football_Pool_Utils::post_string( 'shouttext' ) != '' && $current_user->ID > 0 ) {
			// save the new shout
			$shoutbox->save_shout( Football_Pool_Utils::post_string( 'shouttext' ), $current_user->ID, $max_chars );
		}
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		$messages = $shoutbox->get_messages( $num_messages );
		if ( count( $messages ) > 0 ) {
			echo '<div class="wrapper">';
			foreach ( $messages as $message ) {
				echo '<p><a class="name" href="', $userpage, '?user=', $message['userId'], '">', 
					$message['userName'], '</a>&nbsp;
					<span class="date">(', $message['shoutDate'], ')</span></p>
					<p class="text">', htmlspecialchars($message['shoutText']), '</p><hr />';
			}
			echo '</div>';
		} else {
			echo '<p></p>';
		}
		
		if ( $current_user->ID > 0 ) {
			echo '<form action="" method="post">';
			echo '<p><span id="shouttext_notice" class="notice">';
			echo sprintf( __( '(nog <span>%s</span> karakters)', FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars );
			echo '</span><br />';
			echo '<textarea id="shouttext" name="shouttext" 
					onkeyup="update_chars( this.id, ', $max_chars, ' )" 
					title="', sprintf( __( 'tekst langer dan %s karakters wordt afgekapt!', FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars ), '"></textarea>';
			echo '<input type="submit" name="submit" value="', __( 'opslaan', FOOTBALLPOOL_TEXT_DOMAIN ), '" />';
			echo '</p></form>';
		}
	}
	
	/**
	 * Constructor
	 * 
	 * Registers the widget details with the parent class, based off of the options
	 * that were defined within the widget property. This method does not need to be
	 * changed.
	 */
	public function __construct() {
		//Initializing
		$classname = str_replace( '_', '', get_class( $this ) );
		
		// widget actual processes
		parent::__construct( 
			$classname, 
			( isset( $this->widget['name'] ) ? $this->widget['name'] : $classname ), 
			array( 'description' => $this->widget['description'] )
		);
	}
	
	/**
	 * output widget
	 * 
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		//initializing variables
		$this->widget['number'] = $this->number;
		if ( isset( $instance['title'] ) )
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		else
			$title = '';
			
		$do_wrapper = ( ! isset( $this->widget['do_wrapper'] ) || $this->widget['do_wrapper'] );
		
		if ( $do_wrapper ) 
			echo $args['before_widget'];
		
		$this->html( $title, $args, $instance );
			
		if ( $do_wrapper ) 
			echo $args['after_widget'];
	}
	
	/**
	 * Administration Form
	 * 
	 * This method is called from within the wp-admin/widgets area when this
	 * widget is placed into a sidebar. The resulting is a widget options form
	 * that allows the administration to modify how the widget operates.
	 * 
	 * You do not need to adjust this method what-so-ever, it will parse the array
	 * parameters given to it from the protected widget property of this class.
	 *
	 * @param array $instance
	 * @return boolean
	 */
	public function form( $instance ) {
		//reasons to fail
		if ( empty( $this->widget['fields'] ) ) return false;
		
		// translate the default title
		if ( $this->widget['fields'][0]['name'] == 'Title' )
			$this->widget['fields'][0]['std'] = __( $this->widget['fields'][0]['std'], FOOTBALLPOOL_TEXT_DOMAIN );
		
		// get the league-options from the database
		$pool = new Football_Pool_Pool();
		$leagues = $pool->get_leagues();
		foreach ( $leagues as $league ) {
			$options[ $league['leagueId'] ] = $league['leagueName'];
		}
		$this->widget['fields'][2]['options'] = $options;
		
		$defaults = array(
			'id' => '',
			'name' => '',
			'desc' => '',
			'type' => '',
			'options' => '',
			'std' => '',
		);
		
		//do_action( get_class( $this ) . '_before' );
		foreach ( $this->widget['fields'] as $field ) {
			//making sure we don't throw strict errors
			$field = wp_parse_args( $field, $defaults );

			$meta = false;
			if ( isset( $field['id'] ) && array_key_exists( $field['id'], $instance ) )
				@$meta = attribute_escape( $instance[ $field['id'] ] );

			if ( $field['type'] != 'custom' && $field['type'] != 'metabox' ) {
				echo '<p><label for="', $this->get_field_id(  $field['id'] ), '">';
			}
			if ( isset( $field['name'] ) && $field['name'] ) echo $field['name'], ':';

			switch ( $field['type'] ) {
				case 'text':
					echo '<input type="text" name="', $this->get_field_name( $field['id'] ), '" id="', $this->get_field_id( $field['id'] ), '" value="', ( $meta ? $meta : @$field['std'] ), '" class="vibe_text" />', 
					'<br/><span class="description">', @$field['desc'], '</span>';
					break;
				case 'textarea':
					echo '<textarea class="vibe_textarea" name="', $this->get_field_name( $field['id'] ), '" id="', $this->get_field_id( $field['id'] ), '" cols="60" rows="4" style="width:97%">', $meta ? $meta : @$field['std'], '</textarea>', 
					'<br/><span class="description">', @$field['desc'], '</span>';
					break;
				case 'select':
					echo '<select class="vibe_select" name="', $this->get_field_name( $field['id'] ), '" id="', $this->get_field_id( $field['id'] ), '">';

					foreach ($field['options'] as $value => $option)
					{
 					   $selected_option = ( $value ) ? $value : $option;
					    echo '<option', ( $value ? ' value="' . $value . '"' : '' ), ( $meta == $selected_option ? ' selected="selected"' : '' ), '>', $option, '</option>';
					}

					echo '</select>', 
					'<br/><span class="description">', @$field['desc'], '</span>';
					break;
				case 'radio':
					foreach ( $field['options'] as $option ) {
						echo '<input class="vibe_radio" type="radio" name="', $this->get_field_name( $field['id'] ), '" value="', $option['value'], '"', ( $meta == $option['value'] ? ' checked="checked"' : '' ), ' />', 
						$option['name'];
					}
					echo '<br/><span class="description">', @$field['desc'], '</span>';
					break;
				case 'checkbox':
					echo '<input type="hidden" name="', $this->get_field_name( $field['id'] ), '" id="', $this->get_field_id( $field['id'] ), '" /> ', 
						 '<input class="vibe_checkbox" type="checkbox" name="', $this->get_field_name( $field['id'] ), '" id="', $this->get_field_id( $field['id'] ), '"', $meta ? ' checked="checked"' : '', ' /> ', 
					'<br/><span class="description">', @$field['desc'], '</span>';
					break;
				case 'custom':
					echo $field['std'];
					break;
			}

			if ( $field['type'] != 'custom' && $field['type'] != 'metabox' ) {
				echo '</label></p>';
			}
		}
		//do_action( get_class( $this ) . '_after' );
		return true;
	}

	/**
	 * Update the Administrative parameters
	 * 
	 * This function will merge any posted paramters with that of the saved
	 * parameters. This ensures that the widget options never get lost. This
	 * method does not need to be changed.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$new_instance = array_map( 'strip_tags', $new_instance );
		$instance = wp_parse_args( $new_instance, $old_instance );
		return $instance;
	}
}
?>