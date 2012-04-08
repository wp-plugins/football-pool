<?php 
/*
 Plugin Name: Football pool
 Plugin URI: http://wordpress.org/extend/plugins/football-pool/
 Description: This plugin adds all the functionality for a football pool to your blog. Logged in users of your blog can predict outcomes of matches and earn extra points with bonus questions. View scores and charts of the pool contenders. Use your own theme (or use the skin for Simply Works Core that is included in the assets folder) and add the widgets that come with this plugin. The plugin installs some custom tables in the database with match information for the 2012 UEFA championship, but can be easily manipulated with the match info for other championships (change the data.php file for this). <strong>Please note that deactivating this plugin also destroys all your pool data</strong> (predictions, scores and comments on pages that this plugin created). So if you want to keep those, make sure you have a back-up of the database.
 Version: 1.1.1
 Author: Antoine Hurkmans
 Author URI: http://twitter.com/AntoineH
 Tags: football, pool, game, prediction, competition, euro2012, uefa2012, fifa worldcup, uefa championship
 Text Domain: football-pool
 */

define( 'FOOTBALLPOOL_DB_VERSION', '1.1.1' );

/*
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; version 2 of the License.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once 'define.php';
require_once 'shortcodes.php';

require_once 'classes/class-football-pool.php';
require_once 'classes/class-football-pool-utils.php';
require_once 'classes/class-football-pool-teams.php';
require_once 'classes/class-football-pool-team.php';
require_once 'classes/class-football-pool-matches.php';
require_once 'classes/class-football-pool-stadiums.php';
require_once 'classes/class-football-pool-stadium.php';
require_once 'classes/class-football-pool-groups.php';
require_once 'classes/class-football-pool-pool.php';
require_once 'classes/class-football-pool-chart.php';
require_once 'classes/class-football-pool-chart-data.php';
require_once 'classes/class-football-pool-statistics.php';
require_once 'classes/class-football-pool-shoutbox.php';

if ( ! is_admin() ) {
	// pages, not needed in the admin
	require_once 'pages/class-football-pool-tournament-page.php';
	require_once 'pages/class-football-pool-teams-page.php';
	require_once 'pages/class-football-pool-groups-page.php';
	require_once 'pages/class-football-pool-stadiums-page.php';
	require_once 'pages/class-football-pool-ranking-page.php';
	require_once 'pages/class-football-pool-statistics-page.php';
	require_once 'pages/class-football-pool-user-page.php';
	require_once 'pages/class-football-pool-pool-page.php';
}

// widgets
require_once 'widgets/widget-football-pool-ranking.php';
require_once 'widgets/widget-football-pool-lastgames.php';
require_once 'widgets/widget-football-pool-userselector.php';
require_once 'widgets/widget-football-pool-logout.php';
require_once 'widgets/widget-football-pool-shoutbox.php';
//require_once 'widgets/widget-football-pool-next-prediction.php';

// activate the plugin
register_activation_hook( __FILE__, array( 'Football_Pool', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Football_Pool', 'deactivate' ) );

// upgrading the plugin?
add_action( 'plugins_loaded', array( 'Football_Pool', 'update_db_check' ) );

// admin bar and content handling
add_filter( 'show_admin_bar', array( 'Football_Pool', 'show_admin_bar' ) );
add_action( 'init', array( 'Football_Pool', 'init' ) );
add_filter( 'the_content', array( 'Football_Pool', 'the_content' ) );

// user registration extension
add_action( 'user_register', array( 'Football_Pool', 'new_pool_user' ) );
add_action( 'register_form', array( 'Football_Pool', 'registration_form_extra_fields' ) );
add_action( 'register_post', array( 'Football_Pool', 'registration_form_post' ), 10, 3 );
add_filter( 'registration_errors', array( 'Football_Pool', 'registration_check_fields' ), 10, 3 );

if ( is_admin() ) {
	// admin pages
	require_once 'admin/class-football-pool-admin.php';
	require_once 'admin/class-football-pool-admin-options.php';
	require_once 'admin/class-football-pool-admin-games.php';
	require_once 'admin/class-football-pool-admin-bonusquestions.php';
	require_once 'admin/class-football-pool-admin-groups.php';
	require_once 'admin/class-football-pool-admin-leagues.php';
	require_once 'admin/class-football-pool-admin-shoutbox.php';

	add_action( 'delete_user', array( 'Football_Pool', 'delete_user_from_pool' ) );
	add_action( 'show_user_profile', array( 'Football_Pool', 'add_extra_profile_fields' ) );
	add_action( 'edit_user_profile', array( 'Football_Pool', 'add_extra_profile_fields' ) );
	add_action( 'personal_options_update', array( 'Football_Pool', 'update_user_options' ) );
	add_action( 'edit_user_profile_update', array( 'Football_Pool', 'update_user_options' ) );
	add_action( 'admin_menu', array( 'Football_Pool_Admin', 'init' ) );
}
?>