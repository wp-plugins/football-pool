=== Football Pool ===
Contributors: AntoineH
Donate link: 
Tags: football, pool, game, prediction, competition, euro2012, uefa2012, fifa worldcup, uefa championship
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 1.0.1

This plugin adds all the functionality for a football pool to your blog. 

== Description ==
Logged in users of your blog can predict outcomes of matches and earn extra points with bonus questions. Every player can view scores and charts of the other pool contenders. If you allready 
have users in your blog before you installed the plugin, make sure you fill in the extra user meta 
that comes with this plugin.

Use your own theme (but I guess you have to do some styling to get it right) and add the widgets 
that come with this plugin. I used Simply Works Core myself with my own custom skin and background-image (included in the assets folder). 

This plugin installs some custom tables in the database with match information for the 2012 UEFA championship, but can be easily manipulated with the match info for other championships (change the "data/data.txt" file for this). **Please note that deactivating this plugin also destroys all your pool data** (predictions, scores and comments on pages that this plugin created). So if you want to keep those, make sure you have a back-up of the database.

I originally coded this pool in PHP as a standalone site for the UEFA 2000 championship and rewrote the damn thing several times for every European Championship en World Cup since. This year I decided to transform it into 
a WordPress plugin. I hope you like it.
Btw. I'm not a programmer, so please don't use this code as an example for other plugins. It has 
some terrible coding. But, hey, it works. :)

**Features**

* Users can predict match outcomes.
* You can add bonus questions for extra fun.
* Configurable scoring options.
* Use different leagues for your users (optional).
* Automatic calculation of the pool ranking.
* Automatic calculation of championship standing.
* Users have charts where their scores are plotted. And they can compare themselves to other players.
* Widgets: ranking of your players, latest matches, countdown to next prediction.
* Shortcodes: countdown to the first match of the tournament, easy integration of some configuration options in your content (e.g. points).

== Installation ==
1. Upload `football-pool.zip` from the plugin panel or unzip the file and upload the folder `football-pool` to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` panel in WordPress
3. Edit the plugin configuration via the admin menu
4. Optional: add the pages for the pool to your menu, or use some other method to link to the pages
5. Optional: add the "Football pool" widgets to your sidebar
   (User Selector Widget is needed for the stats page)
6. Optional: add bonus questions
7. Optional: 'upgrade' allready existing users in your blog to pool-users 

After the pool has been set up, all you have to do is monitor the users that subscribe and fill in the right scores for the matches and the right answers for the bonus questions.

For easier/front-end user registration you may consider using an extra plugin and widget. E.g. <a href="http://wordpress.org/extend/plugins/custom-user-registration-lite/">Custom User Registration Lite</a>. Just don't forget the extra user meta that this plugin needs. But you can also use the Login/logout button Widget that is included with this plugin; the plugin adds the needed extra inputs to the WordPress register screen.

== Screenshots ==
1. Matches in the tournament
2. Score charts of multiple players
3. Admin Screen: change match outcomes
4. Group rankings

== Changelog ==

= 1.0.1 =
* Removed English texts because I couldn't get gettext to work for my Dutch version. All texts are in Dutch now. If someone wants to translate the plugin, please contact me. I can give you a po-file with Dutch->English translations,

= 1.0.0 =
* First release