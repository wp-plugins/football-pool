=== Football Pool ===
Contributors: AntoineH
Tags: football, pool, game, prediction, competition, euro2012, uefa2012, fifa worldcup, uefa championship
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 1.4

This plugin adds a football pool for the 2012 European Championship to your blog. 

== Description ==
Logged in users of your blog can predict outcomes of matches and earn extra points with bonus questions. Every player can view scores and charts of the other pool contenders.

Use your own theme (but I guess you have to do some styling to get it right) and add the widgets 
that come with this plugin. I used Simply Works Core myself with my own custom skin and background-image (included in the assets folder). 

This plugin installs some custom tables in the database with match information for the 2012 UEFA championship, but can be easily manipulated with the match info for other championships (change the "data/data.txt"-file for this). **Please note that deactivating this plugin also destroys all your pool data** (predictions, scores and comments on pages that this plugin created). So if you want to keep those, make sure you have a back-up of the database.

I originally coded this pool in PHP as a standalone site for the UEFA 2000 championship and rewrote the damn thing several times for every European Championship en World Cup since. Every year I added new features. This year I decided to transform it into a WordPress plugin. I hope you like it.

**Features**

* Users can predict match outcomes.
* You can add bonus questions for extra fun (single answer and multiple choice).
* Configurable scoring options.
* Use different leagues for your users (optional).
* Automatic calculation of the pool ranking.
* Automatic calculation of championship standing.
* Users have charts where their scores are plotted. And they can compare themselves to other players.
* Widgets: ranking of your players, last matches, shoutbox, group tournament standing, login button.
* Shortcodes: add the ranking in a post, show a group standing, countdown to the first match of the tournament, easy integration of some configuration options in your content (e.g. points).
* Extra info pages with all the venues and teams.

== Installation ==
**Important:** If you want to use a translated version of the pool, make sure you set the correct WPLANG before installing the plugin. See FAQ for more information on translating the plugin.

1. Upload `football-pool.zip` from the plugin panel or unzip the file and upload the folder `football-pool` to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` panel in WordPress
3. Edit the plugin configuration via the admin menu
4. Change the Permalink settings (WordPress admin &raquo; Settings &raquo; Permalinks) to something other than the default.
5. Optional: add the pages for the pool to your menu, or use some other method to link to the pages
6. Optional: add the "Football pool" widgets to your sidebar
   (User Selector Widget is needed for the stats page)
7. Optional: add bonus questions
8. Optional: 'upgrade' existing users in your blog to pool-users

After the pool has been set up, all you have to do is monitor the users that subscribe and fill in the right scores for the matches and the right answers for the bonus questions.

For easier/front-end user registration you may consider using an extra plugin and widget. E.g. <a href="http://wordpress.org/extend/plugins/custom-user-registration-lite/">Custom User Registration Lite</a>. Just don't forget the extra user meta that this plugin needs. But you can also use the Login/logout button Widget that is included with this plugin; the plugin adds the needed extra inputs to the WordPress register screen.

== Frequently Asked Questions ==

= I don't see my blog users as players of the pool. =

Go to the WordPress Admin &raquo; Football Pool &raquo; Users screen and check if these users are added in a league (if you are using leagues). Newly registered users are automatically added. But users that allready existed in your pool have to be updated in the admin screen. In order to make them a player in the pool add them to a league and save. If you delete a league, then the users in that league must be placed in another league.
If you're not using leagues, then make sure the users are not removed from the pool via the Users screen.

= The plugin is Dutch. Is there a translation available? =

The plugin comes with an **en_GB** translation file (in the 'languages' dir). To use the English texts, change the WPLANG constant in the wp-config.php to "en_GB".

If you want to make your own translation, you can use the pot-file (if you're familiar with Dutch) or make a copy of the football-pool-en_GB.po file in the languages directory and use an editor like POedit (http://www.poedit.net/) to create the mo-file.
You can put your custom translation files in the plugin-dir, but be careful they don't get overwritten with an update of the plugin. So, according to <a href="http://www.geertdedeckere.be/article/loading-wordpress-language-files-the-right-way">this site</a> (and the codex) it's better to put the translation file in a subfolder (named 'football-pool') of the WP_LANG_DIR. I support the fallback mechanism that is described on that site in my plugin.

Make sure you name the mo-file right: **football-pool-aa_BB.mo** (where aa_BB is your language code)

= I installed the plugin, but there are no matches. What happened? =

Versions 1.1.0-1.1.2 contained a bug that on a clean install did not insert the data in the custom tables. Users that did an update from the first version did not have this problem. The problem was fixed in version 1.1.3. If you experience this problem just deactivate the plugin and reinstall it. Just updating won't fix it.

= I installed the plugin, but it does not look like your screenshots. =

That's correct. The plugin has some basic styling, but it will not change your entire blog. If you want to take the style I used, then follow these steps:

* Install the Simply Works Core theme (http://wordpress.org/extend/themes/simply-works-core).
* Take the ek2012.css file from the plugin-dir (wp-content/plugins/football-pool/assets/simply works core skin/) and place it in the WordPress theme dir (wp-conten/themes/simply-works-core/skins).
* Go to the theme options in de WordPress admin and select the ek2012 skin (Appearance &raquo; Theme Options &raquo; Theme Colors). If you also want the sidebar on the left you can change this under Layout Options.
* Change the background (Appearance &raquo; Background) to the background.jpg file (Display Options: center, no repeat) that came with the plugin ('simply works core skin' directory).
* Remove the header (Appearance &raquo; Header) and remove all standard widgets from the Header Ad sidebar (Appearance &raquo; Widgets), or move them to the Sidebar Top.
* Create 2 menu's (Appearance &raquo; Menus). Primary menu for the Pool menu-items, and a Secondary menu for information about the tournament (teams, etc.).

= Why do some pages not show any content (e.g. when I click on a team name)? =

Change the Permalink settings (WordPress admin &raquo; Settings &raquo; Permalinks) to something other than the default.

== Screenshots ==
1. Matches in the tournament
2. Score charts of multiple players
3. Admin Screen: change match outcomes
4. Group rankings
5. Using the ranking shortcode in a post

== Changelog ==

= 1.4 =
* Translations (i18n) are working. Plugin contains en_GB translation for my Dutch version of the pool and a pot-file for users that want to make their own translation. See FAQ for more information. (thanks dcollis)
* Bonus questions and user answers can now contain more than 200 characters.
* Style updates.

= 1.3.3 =
* Removed custom fields for admin in the standard WordPress user profile. Editing of users can be done in the plugin user screen.
* Minor style updates.
* Bug fixed: updating users via the WordPress User screen put them in the wrong league (when using leagues).
* Bug fixed: new custom table for the question types was not prefixed properly; updated the install-script. (thanks sjonas)

= 1.3.2 =
* New widget on the WordPress Admin Dashboard: a quick link to the pool. Change the picture for the widget in the Plugin Options.
* Some reordering of files and minor style updates.
* Bug fixed: deactivation of the plugin did not remove all custom tables. (thanks sjonas)

= 1.3.1 =
* Bug fixed: new users were not added to the default league set in the plugin options.
* Bug fixed: adding multiple users to the pool in the new admin screen did not work when users were removed from the pool with league support off.

= 1.3 =
* New admin screen for Users. Add or remove them from the pool or change leagues of the players in one screen.
* Added support for multiple choice questions (very basic).
* Added support for photo questions (ask a question about an image).
* New shortcode [fp-register] for including a link to the WordPress register screen in a post or page. See help page for more information.
* New version of the Highcharts javascript library (2.2.3).
* Bug fixed: shortcode [countdown] used UTC+0 time not the CET time of the match which I use everywhere else in the pool. (thanks drsp58)

= 1.2 =
* New shortcode [fp-ranking] in case you don't want to use the ranking page or widget, but only want to display the ranking in a post. For complete help on this and other shortcodes, see the new help page in the admin section.
* New shortcode [fp-group] if you want to include the standing of a group in a page or post.
* New Group Widget that displays the standing for teams in the Group Stage in a sidebar.
* New version of the Highcharts javascript library (2.2.2).
* Added a helpscreen to the admin.
* Bug fixed: when not using leagues the plugin did not properly show WordPress users as players in the pool.

= 1.1.5 =
* Bug fixed: playDate index not found on the teams page.

= 1.1.4 =
* New version of Highcharts javascript library (2.2.1). Did a small (cosmetic) change in the line charts.
* Ranking page and ranking widget now show all users. Even the ones that registered for the pool after the first match was played. In previous versions the new user had to wait for an admin to save a match or bonusquestion to recalculate the points table.
* Bug fixed: a timezone problem in the display of match times. (Thanks Okoth1)
* Bug fixed: the admin screen for bonusquestions not displaying user answers.
* Bug fixed: user selector widget showed all blog users.

= 1.1.3 =
* Fix for the problem that - on a clean install - the default data for the pool was not loaded (matches, teams, etc.) in the database. (Thanks Okoth1)

= 1.1.2 =
* Fixed a bug in the shoutbox admin (unexpected T_PAAMAYIM_NEKUDOTAYIM).

= 1.1.1 =
* Added pot/po/mo files with the new texts from the shoutbox widget.

= 1.1.0 =
* Added a shoutbox widget for players in the pool. So they can leave short messages in a sidebar.
* Prefixed all class names.
* Fixed a bug with the bulk actions in the admin.

= 1.0.1 =
* Removed English texts because I couldn't get gettext to work for my Dutch version. All texts are in Dutch now. If someone wants to translate the plugin, please contact me. I can give you a po-file with Dutch->English translations,

= 1.0.0 =
* First release of the plugin version of the pool