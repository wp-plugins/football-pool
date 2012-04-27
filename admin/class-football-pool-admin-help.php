<?php
class Football_Pool_Admin_Help extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), '' );
		?>
		
		<h3>Shortcodes</h3>
		<p>hier wat uitleg over de shortcodes.</p>
		
		<?php
		self::admin_footer();
	}

}
?>