<?php
global $wpdb;
/* are we developing or not? */
if ( $_SERVER['HTTP_HOST'] == 'localhost' ) {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', true );
	$wpdb->show_errors();
	define( 'ALTERNATE_WP_CRON', true );  // wordpress.org/support/topic/scheduled-posts-still-not-working-in-282#post-1175405
} else {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', false );
	$wpdb->hide_errors();
}

// database and path constants
define( 'FOOTBALLPOOL_DB_PREFIX', 'pool_' . $wpdb->prefix );
define( 'FOOTBALLPOOL_OPTIONS', 'footballpool_plugin_options' );

define( 'FOOTBALLPOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FOOTBALLPOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FOOTBALLPOOL_PLUGIN_NAME', 'Football Pool' );
define( 'FOOTBALLPOOL_TEXT_DOMAIN', 'football-pool' );

define( 'FOOTBALLPOOL_ASSETS_URL', FOOTBALLPOOL_PLUGIN_URL . 'assets/' );
define( 'FOOTBALLPOOL_HIGHCHARTS_API', '/highcharts-js/highcharts.js' );

define( 'FOOTBALLPOOL_ERROR_LOG', FOOTBALLPOOL_PLUGIN_DIR . 'error_log.txt' );

// leagues
define( 'FOOTBALLPOOL_LEAGUE_ALL',     1 );
define( 'FOOTBALLPOOL_LEAGUE_DEFAULT', 3 );

// scorehistory
define( 'FOOTBALLPOOL_RANKING_DEFAULT', 1 );
define( 'FOOTBALLPOOL_TYPE_MATCH', 0 );
define( 'FOOTBALLPOOL_TYPE_QUESTION', 1 );
define( 'FOOTBALLPOOL_RECALC_USER_DIV', 20 );

// matches and scores
define( 'FOOTBALLPOOL_MAXPERIOD',  900 );
define( 'FOOTBALLPOOL_FULLPOINTS',   5 ); // 3
define( 'FOOTBALLPOOL_TOTOPOINTS',   2 ); // 2
define( 'FOOTBALLPOOL_GOALPOINTS',   0 ); // 1
// matches csv import and export
define( 'FOOTBALLPOOL_CSV_DELIMITER', ';' );
define( 'FOOTBALLPOOL_CSV_UPLOAD_DIR', FOOTBALLPOOL_PLUGIN_DIR . 'upload/' );
// groups page
define( 'FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE', 1 );

// others
define( 'FOOTBALLPOOL_SHOUTBOX_MAXCHARS', 150 );
define( 'FOOTBALLPOOL_DONATE_LINK', 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=S83YHERL39GHA');
define( 'FOOTBALLPOOL_MATCH_SORT', 0 ); // date asc
define( 'FOOTBALLPOOL_SMALL_AVATAR', 18 ); // size in px
define( 'FOOTBALLPOOL_MEDIUM_AVATAR', 28 ); // size in px
define( 'FOOTBALLPOOL_LARGE_AVATAR', 36 ); // size in px

// nonces
define( 'FOOTBALLPOOL_NONCE_CSV', 'football-pool-csv-download' );
define( 'FOOTBALLPOOL_NONCE_ADMIN', 'football-pool-admin' );
define( 'FOOTBALLPOOL_NONCE_SCORE_CALC', 'football-pool-score-calculation' );
define( 'FOOTBALLPOOL_NONCE_BLOG', 'football-pool-blog' );
define( 'FOOTBALLPOOL_NONCE_FIELD_BLOG', '_footballpool_wpnonce' );
define( 'FOOTBALLPOOL_NONCE_SHOUTBOX', 'football-pool-shoutbox' );
define( 'FOOTBALLPOOL_NONCE_FIELD_SHOUTBOX', '_footballpool_shoutbox_wpnonce' );
?>