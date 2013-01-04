<?php
global $wpdb;
/* are we developing or not? */
if ( $_SERVER['HTTP_HOST'] == 'localhost' ) {
	define( 'LOCAL', true );
	$wpdb->show_errors();
	define( 'ALTERNATE_WP_CRON', true );  // wordpress.org/support/topic/scheduled-posts-still-not-working-in-282#post-1175405
} else {
	define( 'LOCAL', false );
	//$wpdb->hide_errors();
}

define( 'FOOTBALLPOOL_DB_PREFIX', 'pool_' . $wpdb->prefix );

define( 'FOOTBALLPOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FOOTBALLPOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FOOTBALLPOOL_PLUGIN_NAME', 'Football Pool' );
define( 'FOOTBALLPOOL_TEXT_DOMAIN', 'football-pool' );

define( 'FOOTBALLPOOL_ASSETS_URL', FOOTBALLPOOL_PLUGIN_URL . 'assets/' );
define( 'FOOTBALLPOOL_HIGHCHARTS_API', '/highcharts-js/highcharts.js' );

define( 'FOOTBALLPOOL_ERROR_LOG', FOOTBALLPOOL_PLUGIN_DIR . 'error_log.txt' );

define( 'FOOTBALLPOOL_LEAGUE_ALL',     1 );
define( 'FOOTBALLPOOL_LEAGUE_DEFAULT', 3 );

define( 'FOOTBALLPOOL_MAXPERIOD',  900 );
define( 'FOOTBALLPOOL_FULLPOINTS',   5 ); // 3
define( 'FOOTBALLPOOL_TOTOPOINTS',   2 ); // 2
define( 'FOOTBALLPOOL_GOALPOINTS',   0 ); // 1

define( 'FOOTBALLPOOL_SHOUTBOX_MAXCHARS', 150 );

define( 'FOOTBALLPOOL_DONATE_LINK', 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=S83YHERL39GHA');

define( 'FOOTBALLPOOL_CSV_DELIMITER', ';' );
define( 'FOOTBALLPOOL_CSV_UPLOAD_DIR', FOOTBALLPOOL_PLUGIN_DIR . 'upload/' );
?>