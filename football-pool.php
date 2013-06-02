<?php 
/*
 Plugin Name: Football pool
 Plugin URI: http://wordpressfootballpool.wordpress.com/
 Description: This plugin adds a fantasy sports pool to your blog. Play against other users, predict outcomes of matches and earn points.
 Version: 2.3.0
 Author: Antoine Hurkmans
 Author URI: mailto:wordpressfootballpool@gmail.com
 Tags: football, pool, poule, voetbal, soccer, game, prediction, competition, fifa worldcup, uefa championship, american football, basketball, sport, sports
 */

define( 'FOOTBALLPOOL_DB_VERSION', '2.3.0' );

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
require_once 'classes/class-football-pool-widget.php';
require_once 'classes/class-football-pool-shortcodes.php';

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
require_once 'widgets/widget-football-pool-group.php';
require_once 'widgets/widget-football-pool-next-prediction.php';

// activate the plugin
register_activation_hook( __FILE__, array( 'Football_Pool', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Football_Pool', 'deactivate' ) );

// upgrading the plugin?
add_action( 'plugins_loaded', array( 'Football_Pool', 'update_db_check' ) );

// admin bar and content handling
add_filter( 'show_admin_bar', array( 'Football_Pool', 'show_admin_bar' ) );
add_action( 'init', array( 'Football_Pool', 'init' ) );
add_filter( 'the_content', array( 'Football_Pool', 'the_content' ) );
add_action( 'wp_head', array( 'Football_Pool', 'change_html_head' ) );


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
	require_once 'admin/class-football-pool-admin-teams.php';
	require_once 'admin/class-football-pool-admin-leagues.php';
	require_once 'admin/class-football-pool-admin-shoutbox.php';
	require_once 'admin/class-football-pool-admin-help.php';
	require_once 'admin/class-football-pool-admin-users.php';
	require_once 'admin/class-football-pool-admin-stadiums.php';
	require_once 'admin/class-football-pool-admin-matchtypes.php';
	require_once 'admin/class-football-pool-admin-groups.php';
	require_once 'admin/class-football-pool-admin-rankings.php';
	
	add_action( 'delete_user', array( 'Football_Pool_Admin_Users', 'delete_user_from_pool' ) );
	// add_action( 'user_deleted', array( 'Football_Pool_Admin_Users', 'admin_notice' ) );
	add_action( 'show_user_profile', array( 'Football_Pool_Admin_Users', 'add_extra_profile_fields' ) );
	add_action( 'edit_user_profile', array( 'Football_Pool_Admin_Users', 'add_extra_profile_fields' ) );
	add_action( 'personal_options_update', array( 'Football_Pool_Admin_Users', 'update_user_options' ) );
	add_action( 'edit_user_profile_update', array( 'Football_Pool_Admin_Users', 'update_user_options' ) );
	add_action( 'admin_menu', array( 'Football_Pool_Admin', 'init' ) );
	add_filter( 'plugin_action_links', array( 'Football_Pool_Admin', 'add_plugin_settings_link' ), 10, 2 );
	add_filter( 'gettext', array( 'Football_Pool_Admin', 'replace_text_in_thickbox' ), 1, 3 );
	add_action( 'wp_dashboard_setup', array( 'Football_Pool', 'add_dashboard_widgets' ) );
	if ( Football_Pool_Utils::get_fp_option( 'add_tinymce_button' ) == 1 ) {
		add_action( 'init', array( 'Football_Pool_Admin', 'tinymce_addbuttons' ) );
	}
	add_action( 'admin_notices', array( 'Football_Pool', 'admin_notice' ) );
	// add_action( 'admin_head', array( 'Football_Pool_Admin', 'adminhook_suffix' ) );
}
?>