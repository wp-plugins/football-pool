<?php
global $wpdb;

// WP constants
if ( function_exists( 'wp_enqueue_media' ) ) {
	define( 'FOOTBALLPOOL_WP_MEDIA', true );
} else {
	define( 'FOOTBALLPOOL_WP_MEDIA', false );
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
define( 'FOOTBALLPOOL_RANKING_AUTOCALCULATION', 1 );
define( 'FOOTBALLPOOL_RANKING_CALCULATION_FULL', 'full' );
define( 'FOOTBALLPOOL_RANKING_CALCULATION_SMART', 'smart' );
define( 'FOOTBALLPOOL_RANKING_DEFAULT', 1 );
define( 'FOOTBALLPOOL_TYPE_MATCH', 0 );
define( 'FOOTBALLPOOL_TYPE_QUESTION', 1 );
define( 'FOOTBALLPOOL_RECALC_STEP2_DIV', 500 );
define( 'FOOTBALLPOOL_RECALC_STEP3_DIV', 200 );
define( 'FOOTBALLPOOL_RECALC_STEP4_DIV', 500 );
define( 'FOOTBALLPOOL_RECALC_STEP5_DIV', 50 );
define( 'FOOTBALLPOOL_RECALC_STEP6_DIV', 4 );

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
define( 'FOOTBALLPOOL_TEAM_POINTS_WIN', 3 );
define( 'FOOTBALLPOOL_TEAM_POINTS_DRAW', 1 );

// predictions
define( 'FOOTBALLPOOL_DEFAULT_JOKERS', 1 );

// others
define( 'FOOTBALLPOOL_SHOUTBOX_MAXCHARS', 150 );
define( 'FOOTBALLPOOL_DONATE_LINK', 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=S83YHERL39GHA');
define( 'FOOTBALLPOOL_MATCH_SORT', 0 ); // date asc
define( 'FOOTBALLPOOL_SMALL_AVATAR', 18 ); // size in px
define( 'FOOTBALLPOOL_MEDIUM_AVATAR', 28 ); // size in px
define( 'FOOTBALLPOOL_LARGE_AVATAR', 36 ); // size in px
define( 'FOOTBALLPOOL_TIME_FORMAT', 'H:i' ); // http://php.net/manual/en/function.date.php
define( 'FOOTBALLPOOL_DATE_FORMAT', 'Y-m-d' ); // http://php.net/manual/en/function.date.php

// cache
define( 'FOOTBALLPOOL_CACHE_MATCHES', 'fp_match_info' );
define( 'FOOTBALLPOOL_CACHE_QUESTIONS', 'fp_bonus_question_info' );
define( 'FOOTBALLPOOL_CACHE_TEAMS', 'fp_teams_info' );

// nonces
define( 'FOOTBALLPOOL_NONCE_CSV', 'football-pool-csv-download' );
define( 'FOOTBALLPOOL_NONCE_ADMIN', 'football-pool-admin' );
define( 'FOOTBALLPOOL_NONCE_SCORE_CALC', 'football-pool-score-calculation' );
define( 'FOOTBALLPOOL_NONCE_BLOG', 'football-pool-blog' );
define( 'FOOTBALLPOOL_NONCE_FIELD_BLOG', '_footballpool_wpnonce' );
define( 'FOOTBALLPOOL_NONCE_SHOUTBOX', 'football-pool-shoutbox' );
define( 'FOOTBALLPOOL_NONCE_FIELD_SHOUTBOX', '_footballpool_shoutbox_wpnonce' );

// dev environment values
if ( $_SERVER['HTTP_HOST'] == 'localhost' ) {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', true );
	$wpdb->show_errors();
	// http://wordpress.org/support/topic/scheduled-posts-still-not-working-in-282#post-1175405
	define( 'ALTERNATE_WP_CRON', true );
} else {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', false );
	$wpdb->hide_errors();
}
?>