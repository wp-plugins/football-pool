<?php
/**
 * Widget: Shoutbox Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Shoutbox_Widget" );' ) );

// dummy var for translation files
$fp_dummy_var = __( 'shoutbox', FOOTBALLPOOL_TEXT_DOMAIN );

class Football_Pool_Shoutbox_Widget extends Football_Pool_Widget {
	protected $widget = array(
		'name' => 'Shoutbox Widget',
		'description' => 'Football pool plugin: a shoutbox for your players. Leave short messages.',
		'do_wrapper' => true, 
		
		'fields' => array(
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
	
	public function html( $title, $args, $instance ) {
		extract( $args );
		
		$num_messages = ( is_numeric( $instance['num_messages'] ) ? $instance['num_messages'] : 20 );
		$max_chars = Football_Pool_Utils::get_fp_option( 'shoutbox_max_chars', FOOTBALLPOOL_SHOUTBOX_MAXCHARS, 'int' );
		
		global $current_user;
		get_currentuserinfo();
		$shoutbox = new Football_Pool_Shoutbox;
		
		// save a new shout?
		$shout = Football_Pool_Utils::post_string( 'shouttext' );
		if ( $shout != '' && $current_user->ID > 0 ) {
			$shoutbox->save_shout( $shout, $current_user->ID, $max_chars );
		}
		
		if ( $title != '' ) {
			echo $before_title . $title . $after_title;
		}
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		$messages = $shoutbox->get_messages( $num_messages );
		if ( count( $messages ) > 0 ) {
			echo '<div class="wrapper">';
			foreach ( $messages as $message ) {
				$url = esc_url( add_query_arg( array( 'user' => $message['userId'] ), $userpage ) );
				$shout_date = new DateTime( Football_Pool_Utils::date_from_gmt( $message['shoutDate'] ) );
				echo '<p><a class="name" href="', $url, '">', $message['userName'], '</a>&nbsp;
					<span class="date">(', $shout_date->format( 'j-n-Y, H:i' ), ')</span></p>
					<p class="text">', htmlspecialchars($message['shoutText']), '</p><hr />';
			}
			echo '</div>';
		} else {
			echo '<p></p>';
		}
		
		if ( $current_user->ID > 0 ) {
			echo '<form action="" method="post">';
			echo '<p><span id="shouttext_notice" class="notice">';
			echo sprintf( __( '(<span>%s</span> characters remaining)', FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars );
			echo '</span><br />';
			echo '<textarea id="shouttext" name="shouttext" 
					onkeyup="footballpool_update_chars( this.id, ', $max_chars, ' )" 
					title="', sprintf( __( 'all text longer than %s characters will be removed!', FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars ), '"></textarea>';
			echo '<input type="submit" name="submit" value="', __( 'save', FOOTBALLPOOL_TEXT_DOMAIN ), '" />';
			echo '</p></form>';
		}
	}
	
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
}
?>