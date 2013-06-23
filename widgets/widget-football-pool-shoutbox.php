<?php
/**
 * Widget: Shoutbox Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( "widgets_init", create_function( '', 'register_widget( "Football_Pool_Shoutbox_Widget" );' ) );

// dummy var for translation files
$fp_translate_this = __( 'shoutbox', FOOTBALLPOOL_TEXT_DOMAIN );

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
		$max_chars = Football_Pool_Utils::get_fp_option( 'shoutbox_max_chars'
														, FOOTBALLPOOL_SHOUTBOX_MAXCHARS, 'int' );
		
		global $current_user;
		get_currentuserinfo();
		$shoutbox = new Football_Pool_Shoutbox;
		
		// save a new shout?
		$shout = Football_Pool_Utils::post_string( 'shouttext' );
		$nonce = Football_Pool_Utils::post_string( FOOTBALLPOOL_NONCE_FIELD_SHOUTBOX );
		if ( wp_verify_nonce( $nonce, FOOTBALLPOOL_NONCE_SHOUTBOX ) !== false
				&& $shout != '' && $current_user->ID > 0 ) {
			$shoutbox->save_shout( $shout, $current_user->ID, $max_chars );
		}
		
		if ( $title != '' ) {
			echo $before_title, $title, $after_title;
		}
		
		$userpage = Football_Pool::get_page_link( 'user' );
		
		$messages = $shoutbox->get_messages( $num_messages );
		if ( count( $messages ) > 0 ) {
			echo '<div class="wrapper">';
			foreach ( $messages as $message ) {
				$time_format = get_option( 'time_format', FOOTBALLPOOL_TIME_FORMAT );
				$date_format = get_option( 'date_format', FOOTBALLPOOL_DATE_FORMAT );
				$url = esc_url( add_query_arg( array( 'user' => $message['user_id'] ), $userpage ) );
				$shout_date = new DateTime( Football_Pool_Utils::date_from_gmt( $message['shout_date'] ) );
				echo '<p><a class="name" href="', $url, '">', $message['user_name'], '</a>&nbsp;
					<span class="date">(', $shout_date->format( "{$date_format}, {$time_format}" ), ')</span></p>
					<p class="text">', htmlspecialchars( $message['shout_text'], null, 'UTF-8' ), '</p><hr />';
			}
			echo '</div>';
		} else {
			echo '<p></p>';
		}
		
		if ( $current_user->ID > 0 ) {
			echo '<form action="" method="post">';
			wp_nonce_field( FOOTBALLPOOL_NONCE_SHOUTBOX, FOOTBALLPOOL_NONCE_FIELD_SHOUTBOX );
			echo '<p><span class="notice">';
			printf( __( '(<span>%s</span> characters remaining)', FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars );
			echo '</span><br />';
			$id = Football_Pool_Utils::get_counter_value( 'fp_shoutbox_id' );
			printf( '<textarea id="shouttext-%d" name="shouttext" 
					onkeyup="footballpool_update_chars( this.id, %d )" title="%s"></textarea>'
					, $id
					, $max_chars
					, sprintf( __( 'all text longer than %s characters will be removed!'
								, FOOTBALLPOOL_TEXT_DOMAIN ), $max_chars 
							)
			);
			printf( '<input type="submit" name="submit" value="%s" />', __( 'save', FOOTBALLPOOL_TEXT_DOMAIN ) );
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
			$this->widget['description']
		);
	}
}
?>