<?php
global $wpdb;

// admin screen options (defaults per page)
if ( ! defined( 'FOOTBALLPOOL_ADMIN_USERS_PER_PAGE' ) ) define( 'FOOTBALLPOOL_ADMIN_USERS_PER_PAGE', 20 );
if ( ! defined( 'FOOTBALLPOOL_ADMIN_MATCHES_PER_PAGE' ) ) define( 'FOOTBALLPOOL_ADMIN_MATCHES_PER_PAGE', 50 );
if ( ! defined( 'FOOTBALLPOOL_ADMIN_USER_ANWERS_PER_PAGE' ) ) define( 'FOOTBALLPOOL_ADMIN_USER_ANWERS_PER_PAGE', 50 );

// database and path constants
if ( ! defined( 'FOOTBALLPOOL_DB_PREFIX' ) ) define( 'FOOTBALLPOOL_DB_PREFIX', 'pool_' . $wpdb->prefix );
if ( ! defined( 'FOOTBALLPOOL_OPTIONS' ) ) define( 'FOOTBALLPOOL_OPTIONS', 'footballpool_plugin_options' );

if ( ! defined( 'FOOTBALLPOOL_PLUGIN_URL' ) ) define( 'FOOTBALLPOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'FOOTBALLPOOL_PLUGIN_DIR' ) ) define( 'FOOTBALLPOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FOOTBALLPOOL_PLUGIN_NAME', 'Football Pool' );
define( 'FOOTBALLPOOL_TEXT_DOMAIN', 'football-pool' );

if ( ! defined( 'FOOTBALLPOOL_HIGHCHARTS_API' ) ) define( 'FOOTBALLPOOL_HIGHCHARTS_API', '/highcharts-js/highcharts.js' );

define( 'FOOTBALLPOOL_ASSETS_URL', FOOTBALLPOOL_PLUGIN_URL . 'assets/' );
if ( ! defined( 'FOOTBALLPOOL_ERROR_LOG' ) ) define( 'FOOTBALLPOOL_ERROR_LOG', FOOTBALLPOOL_PLUGIN_DIR . '_error_log.txt' );

// leagues
if ( ! defined( 'FOOTBALLPOOL_LEAGUE_ALL' ) ) define( 'FOOTBALLPOOL_LEAGUE_ALL', 1 );
if ( ! defined( 'FOOTBALLPOOL_LEAGUE_DEFAULT' ) ) define( 'FOOTBALLPOOL_LEAGUE_DEFAULT', 3 );

// scorehistory
if ( ! defined( 'FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX' ) ) define( 'FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX', false );
define( 'FOOTBALLPOOL_RANKING_AUTOCALCULATION', 1 );
define( 'FOOTBALLPOOL_RANKING_CALCULATION_FULL', 'full' );
define( 'FOOTBALLPOOL_RANKING_CALCULATION_SMART', 'smart' );
define( 'FOOTBALLPOOL_RANKING_DEFAULT', 1 );
define( 'FOOTBALLPOOL_TYPE_MATCH', 0 );
define( 'FOOTBALLPOOL_TYPE_QUESTION', 1 );
if ( ! defined( 'FOOTBALLPOOL_RECALC_STEP2_DIV' ) ) define( 'FOOTBALLPOOL_RECALC_STEP2_DIV', 50 );
if ( ! defined( 'FOOTBALLPOOL_RECALC_STEP3_DIV' ) ) define( 'FOOTBALLPOOL_RECALC_STEP3_DIV', 100 );
if ( ! defined( 'FOOTBALLPOOL_RECALC_STEP4_DIV' ) ) define( 'FOOTBALLPOOL_RECALC_STEP4_DIV', 50 );
if ( ! defined( 'FOOTBALLPOOL_RECALC_STEP5_DIV' ) ) define( 'FOOTBALLPOOL_RECALC_STEP5_DIV', 50 );
if ( ! defined( 'FOOTBALLPOOL_RECALC_STEP6_DIV' ) ) define( 'FOOTBALLPOOL_RECALC_STEP6_DIV', 4 );

// matches and scores
define( 'FOOTBALLPOOL_MAXPERIOD', 900 );
define( 'FOOTBALLPOOL_JOKERMULTIPLIER', 2 );
define( 'FOOTBALLPOOL_FULLPOINTS', 5 ); // 3
define( 'FOOTBALLPOOL_TOTOPOINTS', 2 ); // 2
define( 'FOOTBALLPOOL_GOALPOINTS', 0 ); // 1
define( 'FOOTBALLPOOL_DIFFPOINTS', 0 ); // bonus points for correct goal difference
                                        // (e.g. match result is 4-0 and prediction is 6-2)

// matches csv import and export
if ( ! defined( 'FOOTBALLPOOL_CSV_DELIMITER' ) ) define( 'FOOTBALLPOOL_CSV_DELIMITER', ';' );
if ( ! defined( 'FOOTBALLPOOL_CSV_UPLOAD_DIR' ) ) define( 'FOOTBALLPOOL_CSV_UPLOAD_DIR', FOOTBALLPOOL_PLUGIN_DIR . 'upload/' );

// groups page
define( 'FOOTBALLPOOL_GROUPS_PAGE_DEFAULT_MATCHTYPE', 1 );
define( 'FOOTBALLPOOL_TEAM_POINTS_WIN', 3 );
define( 'FOOTBALLPOOL_TEAM_POINTS_DRAW', 1 );

// predictions
define( 'FOOTBALLPOOL_DEFAULT_JOKERS', 1 );

// others
if ( ! defined( 'FOOTBALLPOOL_CHANGE_STATS_TITLE' ) ) define( 'FOOTBALLPOOL_CHANGE_STATS_TITLE', true );
define( 'FOOTBALLPOOL_DEFAULT_PAGINATION_PAGE_SIZE', 20 );
define( 'FOOTBALLPOOL_SHOUTBOX_MAXCHARS', 150 );
if ( ! defined( 'FOOTBALLPOOL_SHOUTBOX_DOUBLE_POST_INTERVAL' ) ) define( 'FOOTBALLPOOL_SHOUTBOX_DOUBLE_POST_INTERVAL', 60 * 60 );	// time allowed between two (same) shoutbox messages from one user (in seconds)
define( 'FOOTBALLPOOL_DONATE_LINK', 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=S83YHERL39GHA');
define( 'FOOTBALLPOOL_MATCH_SORT', 0 ); // date asc
if ( ! defined( 'FOOTBALLPOOL_SMALL_AVATAR' ) ) define( 'FOOTBALLPOOL_SMALL_AVATAR', 18 ); // size in px
if ( ! defined( 'FOOTBALLPOOL_MEDIUM_AVATAR' ) ) define( 'FOOTBALLPOOL_MEDIUM_AVATAR', 28 ); // size in px
if ( ! defined( 'FOOTBALLPOOL_LARGE_AVATAR' ) ) define( 'FOOTBALLPOOL_LARGE_AVATAR', 36 ); // size in px
if ( ! defined( 'FOOTBALLPOOL_TIME_FORMAT' ) ) define( 'FOOTBALLPOOL_TIME_FORMAT', 'H:i' ); // http://php.net/manual/en/function.date.php
if ( ! defined( 'FOOTBALLPOOL_DATE_FORMAT' ) ) define( 'FOOTBALLPOOL_DATE_FORMAT', 'Y-m-d' ); // http://php.net/manual/en/function.date.php
if ( ! defined( 'FOOTBALLPOOL_TEMPLATE_PARAM_DELIMITER' ) ) define( 'FOOTBALLPOOL_TEMPLATE_PARAM_DELIMITER', '%' );
if ( ! defined( 'FOOTBALL_POOL_CONTENT_FILTER_PRIORITY' ) ) define( 'FOOTBALL_POOL_CONTENT_FILTER_PRIORITY', 30 );

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

// WP constants
if ( function_exists( 'wp_enqueue_media' ) ) {
	define( 'FOOTBALLPOOL_WP_MEDIA', true );
} else {
	define( 'FOOTBALLPOOL_WP_MEDIA', false );
}

// debug
// define( 'FOOTBALLPOOL_DEBUG_FORCE', 'file' );
define( 'FOOTBALLPOOL_DEBUG_EMAIL', 'wordpressfootballpool@gmail.com' );
if ( defined( 'FOOTBALLPOOL_LOCAL_MODE' ) ) {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', true );
	error_reporting( -1 );
	$wpdb->show_errors();
	// http://wordpress.org/support/topic/scheduled-posts-still-not-working-in-282#post-1175405
	define( 'ALTERNATE_WP_CRON', true );
	if ( ! class_exists( 'ChromePhp' ) && file_exists( FOOTBALLPOOL_PLUGIN_DIR . '_dev/ChromePhp.php' ) ) 
		require_once FOOTBALLPOOL_PLUGIN_DIR . '_dev/ChromePhp.php';
} else {
	define( 'FOOTBALLPOOL_ENABLE_DEBUG', false );
}
