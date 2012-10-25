=== Football Pool ===
Contributors: AntoineH
Tags: football, soccer, voetbal, pool, poule, game, prediction, competition, euro2012, uefa2012, fifa worldcup, uefa championship, fantasy football
Requires at least: 3.1
Tested up to: 3.4.2
Stable tag: 1.4.5
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S83YHERL39GHA

This plugin adds a fantasy football pool to your blog. Standard installation contains data for the UEFA 2012 European championship. But you can change the teams and matches, e.g. to a national competition.

== Description ==
Logged in users of your blog can predict outcomes of matches and earn extra points with bonus questions. Every player can view scores and charts of the other pool contenders.

This plugin installs some custom tables in the database with match information for the 2012 UEFA championship, but can be easily manipulated with the match info for other championships. **Please note that deactivating this plugin also destroys all your pool data** (predictions, scores and comments on pages that this plugin created). So if you want to keep those, make sure you have a back-up of the database.

I originally coded this pool in PHP as a standalone site for the UEFA 2000 championship and rewrote the damn thing several times for every European Championship en World Cup since. Every year I added new features. In 2012 I decided to transform it into a WordPress plugin. I hope you like it.

**Features**

* Users can predict match outcomes.
* You can add bonus questions for extra fun (single answer and multiple choice).
* Add your own teams and match info to use the plugin for another (national) competition.
* Import or export the game schedule.
* Configurable scoring options.
* Use different leagues for your users (optional).
* Automatic calculation of the pool ranking.
* Automatic calculation of championship standing.
* Users have charts where their scores are plotted. And they can compare themselves to other players. (Only available if Highcharts chart API is downloaded seperately, see Help for details).
* Widgets: ranking of your players, last matches, shoutbox, group tournament standing, login button.
* Shortcodes: add the ranking in a post, show a group standing, countdown (e.g. to the first match of the tournament), easy integration of some configuration options in your content (e.g. points).
* Extra info pages with all the venues and teams.

**Translations**

At the moment the plugin is available in English, Dutch and Polish. If someone wants to help translate the plugin in another language, or make the existing translations better ;), please contact me at wordpressfootballpool [at] gmail [dot] com. The <a href="http://wordpress.org/extend/plugins/football-pool/faq/">FAQ</a> contains information on how to use a different language.

**Other things**

This plugin requires PHP 5.2 or higher. If you want to use the charts feature, please download the Highcharts API from http://www.highcharts.com/download (see "Installation" or Help page in the WordPress admin for details).
If you find bugs, please contact me via the support forum, or at wordpressfootballpool [at] gmail [dot] com.

== Installation ==
**Important:** If you want to use a translated version of the pool, make sure you set the correct WPLANG. To use your own custom translation see FAQ for more information on translating the plugin.

1. Upload `football-pool.zip` from the plugin panel or unzip the file and upload the folder `football-pool` to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` panel in WordPress
3. Edit the plugin configuration via the admin menu
4. Optional: add the pages for the pool to your menu, or use some other method to link to the pages
5. Optional: add the "Football pool" widgets to your sidebar
   (User Selector Widget is needed for the stats page)
6. Optional: add bonus questions
7. Optional: 'upgrade' existing users in your blog to pool-users
8. Optional: make the `upload` directory in the plugin folder writable (if you wanna use the import function)

9. If you want to use the charts feature please download the Highcharts API (http://www.highcharts.com/download) and put the files in the following directory: `/wp-content/plugins/football-pool/assets/highcharts`

After the pool has been set up, all you have to do is monitor the users that subscribe and fill in the right scores for the matches and the right answers for the bonus questions.

For easier/front-end user registration you may consider using an extra plugin and widget. E.g. <a href="http://wordpress.org/extend/plugins/custom-user-registration-lite/">Custom User Registration Lite</a>. Just don't forget the extra user meta that this plugin needs. But you can also use the Login/logout button Widget that is included with this plugin; the plugin adds the needed extra inputs to the WordPress register screen.

== Frequently Asked Questions ==

= The charts are gone! What happened? =
I had to remove the required library because of WordPress plugin license policies. If you want to enable the charts then see the Help page in the WordPress admin for details on how to install the required library.

= Do I need the "Predictions" page? =
Yes and no. The plugin needs this page to display predictions of users. So don't delete it. But you can remove it from your menu (WordPress Admin &raquo; Appearance &raquo; Menus).
Some themes or WordPress configurations automatically put all top level pages in the navigation. See information from the theme maker on how to make a custom menu or how to exclude pages from the menu.

= I don't see my blog users as players of the pool. ==

Go to the WordPress Admin &raquo; Football Pool &raquo; Users screen and check if these users are added in a league (if you are using leagues). Newly registered users are automatically added, but users that allready existed in your blog have to be updated in the admin screen. In order to make them a player in the pool add them to a league and save. If you delete a league, then the users in that league must be placed in another league.
If you're not using leagues, then make sure the users are not removed from the pool via the Users screen.

= I want to use the plugin for a national competition. Is that possible? =

Yes. There are two ways to do this: 
1. Upload a game schedule in the admin. Make sure you understand the required format; you can download the current schedule as an example.
2. Use the admin screens to edit the teams, groups, match types, matches, etc.

And, of course, choose a theme or make one yourself that fits your competition or blog. If you have a custom game schedule that other users can also use, it would be nice if you shared it with me. I will put it on the <a href="http://wordpressfootballpool.wordpress.com/">plugin's website</a> with credits to you of course.

= Is there a translation available? =

See the 'Other notes' section for the available languages (and their translators). The translations are in the 'languages' dir. To use translations, change the WPLANG constant in the wp-config.php to the right language code (e.g. "nl_NL").

If you want to make your own translation, you can use the pot-file that is included in the languages directory or make a copy of one of the football-pool-*locale*.po files in the languages directory and use an editor like POedit (http://www.poedit.net/) to create the mo-file. The default content for the rules page is in the 'rules-page-content-*locale*.txt' file (e.g. rules-page-content-nl_NL.txt). At the moment of writing only a Dutch file is available.
If you've made your own translation and mail it to me, I'll add it to the plugin and give you the credits.
You can put your custom translation files in the plugin-dir, but be careful they don't get overwritten with an update of the plugin. So, according to <a href="http://www.geertdedeckere.be/article/loading-wordpress-language-files-the-right-way">this site</a> (and the codex) it's better to put the translation file in a subfolder (named 'football-pool') of the WP_LANG_DIR. I support the fallback mechanism that is described on that site in my plugin.

Make sure you name the mo-file right: **football-pool-aa_BB.mo** (where aa_BB is your language code)

If your language is not shipped with the plugin, you might try the <a href="http://wordpressfootballpool.wordpress.com/">plugin's website</a>. Maybe I forgot to release a new version.

= I installed the plugin, but there are no matches. What happened? =

Versions 1.1.0-1.1.2 contained a bug that on a clean install did not insert the data in the custom tables. Users that did an update from the first version did not have this problem. The problem was fixed in version 1.1.3. If you experience this problem just deactivate the plugin and reinstall it. Just updating won't fix it.

= I installed the plugin, but it does not look like your screenshots. =

That's correct. The plugin has some basic styling, but it will not change your entire blog. If you want to take the style I used, then follow these steps:

* Install the Simply Works Core theme (http://wordpress.org/extend/themes/simply-works-core).
* Take the ek2012.css file from the plugin-dir (wp-content/plugins/football-pool/assets/simply works core skin/) and place it in the WordPress theme dir (wp-content/themes/simply-works-core/skins).
* Go to the theme options in de WordPress admin and select the ek2012 skin (Appearance &raquo; Theme Options &raquo; Theme Colors). If you also want the sidebar on the left you can change this under Layout Options.
* Change the background (Appearance &raquo; Background) to the background.jpg file (Display Options: center, no repeat) that came with the plugin ('simply works core skin' directory).
* Remove the header (Appearance &raquo; Header) and remove all standard widgets from the Header Ad sidebar (Appearance &raquo; Widgets), or move them to the Sidebar Top.
* Create 2 menu's (Appearance &raquo; Menus). Primary menu for the Pool menu-items, and a Secondary menu for information about the tournament (teams, etc.).

== Localizations ==

The Football Pool plugin is available in the following languages:

* Polish (`pl_PL`) by Łukasz Ciastoń.
* Dutch (`nl_NL`) by me.
* English (default) by me.

== Screenshots ==
1. Matches in the tournament
2. Score charts of multiple players
3. Admin Screen: change match outcomes
4. Group rankings
5. Using the ranking shortcode in a post

== Upgrade Notice ==

= 2.0.0 =
* Please notice that the default language is now English. If you were using Dutch as the language for the plugin you have to change de `WPLANG` constant in the `wp-config.php` file to `nl_NL`.

= 1.4.5 =
* Highcharts API was removed from the plugin. See FAQ or the <a href="http://wordpressfootballpool.wordpress.com/">plugin's website</a> for details.

== Changelog ==

= 2.0.0 =
* Added or changed admin screens for teams, groups, match types and matches so they can be easily manipulated. This way the plugin is not limited to the European championship, but can also be used for other competitions, e.g. the English Premier League.
* Changed default language to English. This makes the plugin easier to translate.
* Changed layout of the Plugin Option admin screen. More options and better grouped.
* Fixed a problem for DB users that don't have `TRUNCATE` rights. (thanks Millvi)
* Plugin now supports WordPress installs with default permalinks settings.
* Multiple choice questions with more than one answer (checkboxes) can now have a max number of answers a user may give.
* Bug fixed: prediction page for a user did not show the right points for a bonus question. (thanks Maly77)
* Bug fixed: User Selector Widget caused a notice on 404 pages.
* Added colorbox instead of fancybox for lightbox. Fancybox does not have a GPL-compatible license.

= 1.4.5 =
* Removed Highcharts library from the package on request from WordPress. The Highcharts library has a non-GPL-compatible license which violates WordPress plugin rules. If you want to keep using the charts you have to download the library yourself and enable the charts feature in the plugin option screen.

= 1.4.4 =
* New: list of email adresses of players in your pool available in the plugin User Admin screen. You can copy and paste it in an email to quickly mail your users (without the need of installing a mail plugin).
* New: if using leagues the ranking page now defaults to the league the user is in.
* Bug fixed: check if user is a player in the pool did not work correct for users that are added to the blog, but are not in a league.
* Refactored code for the widgets.

= 1.4.3 =
* Fixed a potential problem with magic quotes (wp_magic_quotes adds slashes regardless of your PHP setup).
* Performance update. Reduced number of database queries for a page request.
* New widget (bèta): countdown to next match.
* Extra options for countdown shortcode (see help page for details).

= 1.4.2 =
* DateTime::createFromFormat requires PHP 5.3 or higher. I replaced those calls in the core classes with code that doesn't break on PHP version 5.2.
* New version of the Fancybox javascript library (2.0.6).
* New version of the Highcharts javascript library (2.2.4).
* Added two more layout options to the plugin options (favicon and apple-touch-icon).
* Moved body font-styling from the global stylesheet to the theme skin.

= 1.4.1 =
* Some texts were lost in translation. I added them.
* New configuration options for a single 'lock time' for matches and bonus questions. If set, users have to finish all their predictions before this date and time.
* Plugin supports a maximum number of answers a user may select in a multiple choice question (checkbox). The check is only done client-side and requires a bit of javascript knowledge to use it. See top of pool.js file for usage. I recommend adding the javascript calls in a separate file, add them to your theme or use a plugin that helps adding custom javascript to your blog. (thanks srozemuller)
* Added Polish translation. (thanks Łukasz Ciastoń)

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
