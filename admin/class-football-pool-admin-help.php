<?php
class Football_Pool_Admin_Help extends Football_Pool_Admin {
	public function __construct() {}
	
	public static function admin() {
		$img_dir = FOOTBALLPOOL_ASSETS_URL . 'admin/images/';
		$totopoints = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
		$fullpoints = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
		$goalpoints = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
		$diffpoints = Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' );
		
		self::admin_header( __( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), '' );
		?>
		<div class="help-page">
		<h2>Index</h2>
		<p>
			<ol>
				<li><a href="#introduction">Introduction</a></li>
				<li><a href="#admin">Admin pages</a></li>
				<li><a href="#times">Time</a></li>
				<li><a href="#points">Points</a></li>
				<li><a href="#rankings">Rankings & Scoring</a></li>
				<li><a href="#leagues">Leagues</a></li>
				<li><a href="#players">Players</a></li>
				<li><a href="#bonusquestions">Bonus questions</a></li>
				<li><a href="#teams-groups-and-matches">Teams, groups and matches</a></li>
				<li><a href="#layout">Changing the plugin layout</a></li>
				<li><a href="#shortcodes">Shortcodes</a></li>
				<li><a href="#charts">Using charts</a></li>
				<li><a href="#hooks">Extending the plugin: Actions and Filters</a></li>
				<li><a href="#the-end">Anything else?</a></li>
			</ol>
		</p>

		<h2 id="introduction">Introduction</h2>
		<p>
		The Football Pool plugin install a pool in your WordPress blog. In the default configuration this plugin enables you to define matches between (football) teams and lets your blog visitors predict the outcomes of the matches. Players earn points for correct predictions and the best player wins the pool.
		</p>
		<p>
		There are several ways you can customize the plugin: different scores for correct answers, add bonus questions, add your own rankings, etc. See the contents of this help file for details. If you have any questions, you may leave them at the <a target="_blank" href="http://wordpress.org/support/plugin/football-pool">WordPress forum</a>.
		</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<h2 id="admin">Admin pages</h2>
		<p>
		The admin pages of the plugin let you define all necessary parts of the plugin. Every admin page contains contextual help: use the help tab at the top right of every screen if you need information about the admin page.<br />
		<img class="screenshot" src="<?php echo $img_dir; ?>screenshot-admin-help.png" alt="screenshot" />
		</p>
		<p>You can use this help file for more detailed information about all the aspects of the plugin. </p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<h2 id="times">Time</h2>
		<h3>What's with the stop times, dynamic times, etc.? I don't get it.</h3>
		<p>
		Users have only a limited amount of time to fill in or change their predictions. For matches you can choose between a certain amount of time before the kickoff of the match (dynamic time), or a single date/time for all matches. The default is a dynamic time setting of 900 seconds (= 15 minutes) before the start of a match.<br />
		Bonus questions each have an 'answer before' date and time. But you may override these individual values with a single stop time for all bonus questions. The default is to allow for a 'answer before' time per question.
		</p>
		
		<h3>Questions and plugin settings</h3>
		<p>
		The times in the plugin options and the 'answer before' times in the bonus question admin must be entered in local time (the plugin stores them in the database in UTC).
		</p>
		
		<h3>Matches</h3>
		<p>
		<strong>Matches have to be entered or imported with <a target="_blank" href="http://en.wikipedia.org/wiki/Coordinated_Universal_Time" title="Coordinated Universal Time">UTC</a> times</strong> for the kickoff. The admin screen also shows the times for the match in your own timezone (according to the <a href="options-general.php">setting in WordPress</a>) so you can check if the times are correct.
		</p>
		
		<div class="help important">
			<p><strong>Debugging timezone problems</strong></p>
			<p><strong>Tip:</strong> Always test if your <a href="options-general.php" title="WordPress general settings">timezone setting</a> and <a href="admin.php?page=footballpool-options" title="Football Pool plugin settings">plugin times</a> are correct. Change the date of one of your bonus questions and one of your matches (or the corresponding stop time in the plugin settings) and check if the question and match are correctly blocked or open. If not, check your plugin settings and WordPress settings.</p>
			<p>The plugin also has a helper page that displays some debug info on your plugin and server settings. The helper page can be found <a target="_blank" href="<?php echo FOOTBALLPOOL_PLUGIN_URL, 'admin/timezone-test.php'; ?>" title="debug info on date and time settings">here</a>.</p>
		</div>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="points">Points</h2>
		<p>The plugin uses 4 different scores that are rewarded to players for the match predictions they do: full points, toto points, goal bonus and goal difference bonus. The toto points are rewarded if the right match result is predicted (win, loss or draw). A player gets the full score if also the exact amount of goals was predicted.</p>
		<p>If you set the goal bonus to anything other than zero (default is zero), then this bonus is added to the scored points if the goals predicted are right; even if the match result was wrong (e.g. result is 2-1 and user predicted 1-1).</p>
		<p>If you set the goal difference bonus to anything other than zero (default is zero), then this bonus is added to scored points if the user predicted the correct winner and the user was right about the goal difference (e.g. result is 2-1 and the user predicted 3-2). This bonus is not rewarded if the user predicted the wrong winner (e.g. result is 2-1 and the user predicted 2-3) or when the match result is a draw (e.g. 2-2).</p>
		<p>
		Your current settings are:
		</p>
		<table>
			<tr><td>full points:</td><td><?php echo $fullpoints; ?></td></tr>
			<tr><td>toto points:</td><td><?php echo $totopoints; ?></td></tr>
			<tr><td>goal bonus:</td><td><?php echo $goalpoints; ?></td></tr>
			<tr><td>goal difference bonus:</td><td><?php echo $diffpoints; ?></td></tr>
		</table>
		<p></p>
		<table class="widefat help">
		<tr>
			<th>match result</th>
			<th>user predicted</th>
			<th>points scored</th>
		</tr>
		<tr>
			<td>3-1</td>
			<td>1-0</td>
			<td>
				toto points.<br />
				total = <?php echo $totopoints; ?>
			</td>
		</tr>
		<tr>
			<td>3-1</td>
			<td>2-0</td>
			<td>
				toto points plus goal difference bonus for the correct goal difference (2 goals difference).<br />
				total = <?php echo $totopoints; ?> + <?php echo $diffpoints; ?> = <?php echo $totopoints + $diffpoints; ?>
			</td>
		</tr>
		<tr>
			<td>3-1</td>
			<td>3-0</td>
			<td>
				toto points plus goal bonus for the correct amount of goals for the home team.<br />
				total = <?php echo $totopoints; ?> + <?php echo $goalpoints; ?> = <?php echo $totopoints + $goalpoints; ?>
			</td>
		</tr>
		<tr>
			<td>2-1</td>
			<td>2-1</td>
			<td>
				full points plus two times the goal bonus for the correct amount of goals for the home team and the away team.<br />
				total = <?php echo $fullpoints; ?> + <?php echo $goalpoints; ?> + <?php echo $goalpoints; ?> = <?php echo $fullpoints + ( 2 * $goalpoints ); ?>
			</td>
		</tr>
		<tr>
			<td>2-1</td>
			<td>1-1</td>
			<td>
				goal bonus for the correct amount of goals for the away team.<br />
				total = <?php echo $goalpoints; ?>
			</td>
		</tr>
		<tr>
			<td>2-1</td>
			<td>0-0</td>
			<td>no points</td>
		</tr>
		<tr>
			<td>1-1</td>
			<td>1-1</td>
			<td>
				full points plus two times the goal bonus for the correct amount of goals for the home team and the away team.<br />
				total = <?php echo $fullpoints; ?> + <?php echo $goalpoints; ?> + <?php echo $goalpoints; ?> = <?php echo $fullpoints + ( 2 * $goalpoints ); ?>
			</td>
		</tr>
		<tr>
			<td>1-1</td>
			<td>0-0</td>
			<td>
				toto points.<br />
				total = <?php echo $totopoints; ?>
			</td>
		</tr>
		</table>
		
		<h3>The golden ball (joker)</h3>
		<p>
		A player in the pool gets <strong>one</strong> golden ball. This golden ball can be placed next to a match to double the points for that match.<br />
		The golden ball may be placed and/or moved to other matches as long the matches are still changeable. A golden ball is activated at the moment the match it is placed on is no longer changeable. And once activated the golden ball cannot be moved.
		</p>
		<p>The plugin has an option on the settings page to disable the golden ball functionality.</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="rankings">Rankings & Scoring</h2>
		<p>
		The players of the plugin are ranked in a list (a ranking) that adds up the points scored for all matches and all questions in the pool (this is called the default ranking). <br />
		But the plugin also has the ability to calculate a ranking of just a subset of the matches and/or bonus questions (e.g. a ranking for the first half of the season and one for the second half). If you want to use this feature make a new <a href="?page=footballpool-rankings">ranking</a> and attach the required matches and/or questions; this is the ranking definition. The custom rankings can be used with the ranking shortcode, in a ranking widget or on the ranking and charts page.
		</p>
		<p>A custom ranking can be excluded from the recalculation process with the 'calculate' option for that ranking (set it in the <a href="?page=footballpool-rankings">ranking admin</a>). So if you have some matches grouped in a custom ranking that won't change anymore, you have the option to not recalculate them everytime a recalculation is done. This might speed up the calculation process for you. Custom rankings that are excluded from the calculation can still be manually recalculated with the single calculation button on the <a href="?page=footballpool-rankings">ranking admin</a> screen.
		</p>
		<p>See the <a href="#shortcodes">shortcode section</a> for details about the use of these custom rankings in your posts or pages.
		</p>
		<h3>Ranking calculation</h3>
		<p>By default an admin will be automatically notified for a (re)calculation of the rankings when saving a match or question, or when changing your pool players. If you want to (temporarily) disable this automatic calculation, e.g. when you want to enter multiple matches at once, you may disable this feature in the <a href="?page=footballpool-options">plugin options</a> and do a manual recalculation when you're finished editing.
		</p>
		<div class="help important">
			<p><strong>Important:</strong> calculating a ranking takes time. The more players or rankings you have, the more time it takes to (re)calculate the ranking tables. The rankings are 'cached' in the database. So, once calculated, your players/visitors shouldn't notice a delay when displaying a ranking, but an admin saving a match will have to wait for the ranking calculations to finish.</p>
		</div>
		<h3>Smart vs. full vs. single calculations</h3>
		<p>The plugin has 3 different kind of recalculations. The easiest to explain is the full calculation: everything is recalculated. If you have a small competition (e.g. World Cup), one ranking and not too many users (say 50 to 100) you can use this calculation. Success guaranteed, when in doubt, use this one.</p>
		<p>A smart calculation keeps track of changes you make in the plugin that might affect a ranking. For example: if you change the score or date for a match, this will affect rankings that include this match, or if you add users this will affect all rankings. When doing a smart calculation only the ranking that are marked as 'should get an update' will be recalculated. Always including the default ranking, that one is the base ranking and will always be updated in a smart recalculation. On the rankings admin page you can see which rankings will be updated.</p>
		<p>A single ranking can be done on the ranking admin page. Only this ranking will be updated. Nothing else.</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="leagues">Leagues</h2>
		<p>The plugin supports placing players in different leagues. For example when you want to group players per department, or friends and family, or paying and non-paying, etc. When playing with leagues an admin has to 'approve' the league for which a player subscribed. That can be done on the <a href="?page=footballpool-users">Users page</a> of the Football Pool plugin.</p>
		<p>When using leagues all players have to be a member of a league, otherwise they are not considered to be a pool player.</p>

		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="players">Players</h2>
		<p>There are two ways the plugin can handle your blog users: via leagues or not via leagues. If playing with leagues, your blog users have to be in an active league before they can participate in the pool. New subscribers to your blog choose a league when subscribing, but existing users have to change this setting after the plugin is installed (or the admin can do this for them on the <a href="?page=footballpool-users">Users page</a>).</p>
		<p>When not using leagues all your blog users are automatically players in the pool. If you want to exclude some players from the rankings (e.g. the admin), you can disable them in the <a href="?page=footballpool-users">Users page</a> of the Football Pool plugin.</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="bonusquestions">Bonus questions</h2>
		<h3>Types</h3>
		<p>There are 3 types of bonus questions: 
		<ol>
			<li>Text questions</li>
			<li>Multiple choice questions (one answer)</li>
			<li>Multiple choice questions (one or more answers)</li>
		</ol>
		Each question type can also show an (optional) image.
		</p>
		<p>For multiple choice questions you have to give 2 or more options to the players. The possible answers must be entered as a semicolon separated list.</p>
		<h3>Giving points</h3>
		<p>After the 'answer before' date has passed and your players may not alter their answers, an admin has to manually approve all answers for a question. For this, go to the <a href="?page=footballpool-bonus">Questions admin screen</a> and click on the "User Answers" link.<br />
		<img class="screenshot" src="<?php echo $img_dir; ?>example-bonus-user-answers-1.png" alt="screenshot" />
		</p>
		<p>In the answer screen information about the question is shown as a reference (1). The answer and default points are shown and - if an admin has filled in the answer - the answer is also shown.<br />
		For each player click the appropiate radiobutton for a right or wrong answer (2). If an answer is considered right you have the possibility to give a different amount of points to that user (3). For example to give extra bonuspoints or to give half the points for an incomplete answer. Leave blank if you want to give the default points for that question.<br />
		<img class="screenshot" src="<?php echo $img_dir; ?>example-bonus-user-answers-2.png" alt="screenshot" />
		</p>
		<div class="help important">
			<p>Please note that for points for bonus questions to be added to the total points for a player, an admin also has to fill in the score date field for that question. The score date is used to determine the order in which points are plotted in the charts. If the score date is not set by the admin, then the score date is automatically set to the current time and date upon a save of the user answers.</p>
		</div>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="teams-groups-and-matches">Teams, groups and matches</h2>
		<p>
		In the pool your blog users can predict the outcome of matches in a competition. A competition consists of multiple teams that play each other in matches. The game schedule can be entered on the <a href="?page=footballpool-games">Matches</a> admin page. On that page the matches may be entered manually or uploaded via a csv file. See information below about the format of the csv file, or export an existing game schedule for an example. The format of the export (full data or minimal data) can be set on the options page of the plugin.
		</p>
		<h3>Groups</h3>
		<p>
		Teams may be grouped in groups. For example Group A, Group B, etc. for a tournament. Or in one group in the case of a national competition. The groups page in the blog (/tournament/groups) shows the ranking table for matches played in match type 1 by default. If you wish to use another match type for the calculation of points for a team, or use multiple match types, you can alter this in the <a href="?page=footballpool-options">plugin options</a>.
		</p>
		
		<h3>csv file import</h3>
		<p>
		The csv file (must be in <a target="_blank" href="http://superuser.com/questions/479756/eol-in-notepad-and-notepad" title="tip: use Notepad++ to convert to the correct EOL format">UNIX or Windows/DOS EOL format</a>) can be uploaded in one of the following formats:
		<ol>
			<li>minimal data (only the basic information about teams);</li>
			<li>full data (all information).</li>
		</ol>
		If you choose the minimal data, extra information about stadiums and teams may be entered on the individual admin pages. If a team, stadium, group or match type in the csv file does not already exist, it will be added to the database.<br />
		For the full data all information about teams, venues, etc. must be given. If a team, venue, etc. already exists, it won't be updated. If a team does not exist, the information (e.g. photo) in the first row where that item appears, will be added in the database.
		</p>
		<p>If a culture code is included in the filename, e.g. <span class="code">uefa2012-en_US.txt</span>, then the plugin can filter the files according to the culture that is set as the locale for the blog.
		</p>
		<p>
		The header of the file may contain optional meta information about the author of the import and/or the location of the assets for the teams and venues. If meta information exists in the csv file, the information is added on the file select list. File header example:
		</p>
		<pre class="code">
		/*
		 Contributor: Antoine Hurkmans
		 Assets URI: https://dl.dropbox.com/u/397845/wordpressfootballpool/uefa-european-championship-2012.zip
		*/
		</pre>
		<h4>Minimal data</h4>
		<!--p>
		<em>csv file header:</em> play_date;home_team;away_team;stadium;match_type
		</p-->
		<p>
		<table class="widefat help" caption="Minimal data">
			<tr><th>column</th><th>description</th><th>example</th></tr>
			<tr>
				<td class="row-title">play_date</td>
				<td>The date and start time of the match in Y-m-d H:i notation (<a href="#times">UTC</a>).</td>
				<td>2012-10-28 18:00</td>
			</tr>
			<tr>
				<td class="row-title">home_team</td>
				<td>Name of a team. Teams may be added upfront on the <a href="?page=footballpool-teams">teams admin page</a>.</td>
				<td>The Netherlands</td>
			</tr>
			<tr>
				<td class="row-title">away_team</td>
				<td>Name of a team. Teams may be added upfront on the <a href="?page=footballpool-teams">teams admin page</a>.</td>
				<td>England</td>
			</tr>
			<tr>
				<td class="row-title">stadium</td>
				<td>Name of a stadium. Stadiums may be added upfront on the <a href="?page=footballpool-venues">venues admin page</a>.</td>
				<td>Olympic Stadium</td>
			</tr>
			<tr>
				<td class="row-title">match_type</td>
				<td>Matches may be grouped with a match type. Match types may be added upfront on the <a href="?page=footballpool-matchtypes">match type admin page</a>.</td>
				<td>Quarter final</td>
			</tr>
		</table>
		</p>
		<h4>Full data</h4>
		<!--p>
		<em>csv file header:</em> play_date;home_team;away_team;stadium;match_type;home_team_photo;home_team_flag;home_team_link;home_team_group;home_team_group_order;home_team_is_real;away_team_photo;away_team_flag;away_team_link;away_team_group;away_team_group_order;away_team_is_real;stadium_photo
		</p-->
		<p>
		<table class="widefat help" caption="Full data">
			<tr><th>column</th><th>description</th><th>example</th></tr>
			<tr>
				<td class="row-title">play_date</td>
				<td>The date and start time of the match in Y-m-d H:i notation (<a href="#times">UTC</a>).</td>
				<td>2012-10-28 18:00</td>
			</tr>
			<tr>
				<td class="row-title">home_team</td>
				<td>Name of a team. Teams may be added upfront on the <a href="?page=footballpool-teams">teams admin page</a>.</td>
				<td>The Netherlands</td>
			</tr>
			<tr>
				<td class="row-title">away_team</td>
				<td>Name of a team. Teams may be added upfront on the <a href="?page=footballpool-teams">teams admin page</a>.</td>
				<td>England</td>
			</tr>
			<tr>
				<td class="row-title">stadium</td>
				<td>Name of a stadium. Stadiums may be added upfront on the <a href="?page=footballpool-venues">venues admin page</a>.</td>
				<td>Olympic Stadium</td>
			</tr>
			<tr>
				<td class="row-title">match_type</td>
				<td>Matches may be grouped with a match type. Match types may be added upfront on the <a href="?page=footballpool-matchtypes">match type admin page</a>.</td>
				<td>Quarter final</td>
			</tr>
			<tr>
				<td class="row-title">home_team_photo</td>
				<td>Team photo for the home team. Full URL or path relative to "assets/images/teams/".</td>
				<td>netherlands.jpg</td>
			</tr>
			<tr>
				<td class="row-title">home_team_flag</td>
				<td>Flag image for the home team. Full URL or path relative to "assets/images/flags/".</td>
				<td>netherlands.png</td>
			</tr>
			<tr>
				<td class="row-title">home_team_link</td>
				<td>Link to a page or website with information about the home team.</td>
				<td>http://www.uefa.com/uefaeuro/season=2012/teams/team=95/index.html</td>
			</tr>
			<tr>
				<td class="row-title">home_team_group</td>
				<td>The group in which the home team is placed.</td>
				<td>Group A</td>
			</tr>
			<tr>
				<td class="row-title">home_team_group_order</td>
				<td>The order in a group in case multiple teams have the same scores.</td>
				<td>1</td>
			</tr>
			<tr>
				<td class="row-title">home_team_is_real</td>
				<td>Is the home team a real team? Example of a real team "The Netherlands", a non-real team "Winner match 30". Can be 1 or 0.</td>
				<td>1</td>
			</tr>
			<tr>
				<td class="row-title">away_team_photo</td>
				<td>Team photo for the away team. Full URL or path relative to "assets/images/teams/".</td>
				<td>england.jpg</td>
			</tr>
			<tr>
				<td class="row-title">away_team_flag</td>
				<td>Flag image for the away team. Full URL or path relative to "assets/images/flags/".</td>
				<td>england.png</td>
			</tr>
			<tr>
				<td class="row-title">away_team_link</td>
				<td>Link to a page or website with information about the away team.</td>
				<td>http://www.uefa.com/uefaeuro/season=2012/teams/team=39/index.html</td>
			</tr>
			<tr>
				<td class="row-title">away_team_group</td>
				<td>The group in which the away team is placed.</td>
				<td>Group A</td>
			</tr>
			<tr>
				<td class="row-title">away_team_group_order</td>
				<td>The order in a group in case multiple teams have the same scores.</td>
				<td>1</td>
			</tr>
			<tr>
				<td class="row-title">away_team_is_real</td>
				<td>Is the away team a real team? Example of a real team "The Netherlands", a non-real team "Winner match 30". Can be 1 or 0.</td>
				<td>1</td>
			</tr>
			<tr>
				<td class="row-title">stadium_photo</td>
				<td>Photo of the stadium where the match is played. Full URL or path relative to "assets/images/stadiums/".</td>
				<td>olympic-stadium.jpg</td>
			</tr>
		</table>
		</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<h2 id="layout">Changing the plugin layout</h2>
		<h3>Style</h3>
		<p>The plugin has some basic styling that will hopefully not interfere with your theme. If you want to change the style of the plugin, you can do so by using a seperate CSS file, or by adding rules to the CSS file of your theme. Just follow the CSS rules about specificity to overwrite the plugin's style (see Keegan Street's <a target="_blank" href="http://specificity.keegan.st/">specificity calculator</a> for a cool help in determining the specificity of a selector). I don't recommend changing the CSS of the plugin, as it will be overwritten on every update.
		</p>
		<h3>Templates</h3>
		<p>Some data that is displayed in the plugin is handled via a template. These templates consist of HTML and parameters. See the table below for an overview of the templates that are available in the plugin at the moment and the parameters that can be used. A parameter must be surrounded by "<?php echo FOOTBALLPOOL_TEMPLATE_PARAM_DELIMITER; ?>", e.g. <?php echo FOOTBALLPOOL_TEMPLATE_PARAM_DELIMITER; ?>home_team<?php echo FOOTBALLPOOL_TEMPLATE_PARAM_DELIMITER; ?>.</p>
		<p>The templates and the available data (the parameters) can be changed via hooks (see the <a href="#hooks">section about extending the plugin</a> for more information about WordPress hooks).</p>
		<p>
		<table class="widefat help">
			<tr>
				<th>functionality</th><th>hook</th><th>description</th><th>parameters</th>
			</tr>
			<tr>
				<td>prediction form</td>
				<td>footballpool_predictionform_template_start</td>
				<td>Opening HTML for the prediction form.</td>
				<td>form_id<br />user_id</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_predictionform_template_end</td>
				<td>Closing HTML for the prediction form.</td>
				<td>form_id<br />user_id</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_predictionform_match_template</td>
				<td>HTML for a match row.</td>
				<td>form_id<br />
					match_id<br />
					match_type_id<br />
					match_type<br />
					match_timestamp<br />
					match_date<br />
					match_time<br />
					match_day<br />
					match_datetime_formatted<br />
					match_utcdate<br />
					match_stats_url<br />
					stadium_id<br />
					stadium_name<br />
					home_team_id<br />
					away_team_id<br />
					home_team<br />
					away_team<br />
					home_team_flag<br />
					away_team_flag<br />
					home_score<br />
					away_score<br />
					group_id<br />
					group_name<br />
					home_input<br />
					away_input<br />
					joker<br />
					user_score<br />
					stats_link<br />
					css_class
				</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_predictionform_match_type_template</td>
				<td>HTML for the match type row (placed between matches when there is a new match type).</td>
				<td>see match row</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_predictionform_date_row_template</td>
				<td>HTML for the date row (placed between matches when there is a new match date).</td>
				<td>see match row</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_predictionform_linked_questions_template</td>
				<td>HTML for questions that are linked to a match (placed after the match).</td>
				<td>form_id<br />
					match_id<br />
					question_id<br />
					question
				</td>
			</tr>
			<tr>
				<td>match table</td>
				<td>footballpool_match_table_template_start</td>
				<td>Opening HTML for the match table.</td>
				<td>-</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_match_table_template_end</td>
				<td>Closing HTML for the match table.</td>
				<td>-</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_match_table_match_template</td>
				<td>HTML for a match row.</td>
				<td>match_id<br />
					match_type_id<br />
					match_type<br />
					match_timestamp<br />
					match_date<br />
					match_time<br />
					match_day<br />
					match_datetime_formatted<br />
					match_utcdate<br />
					match_stats_url<br />
					stadium_id<br />
					stadium_name<br />
					home_team_id<br />
					away_team_id<br />
					home_team<br />
					away_team<br />
					home_team_flag<br />
					away_team_flag<br />
					home_score<br />
					away_score<br />
					group_id<br />
					group_name<br />
					css_class
				</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_match_table_match_type_template</td>
				<td>HTML for the match type row (placed between matches when there is a new match type).</td>
				<td>see match row</td>
			</tr>
			<tr>
				<td></td>
				<td>footballpool_match_table_date_row_template</td>
				<td>HTML for the date row (placed between matches when there is a new match date).</td>
				<td>see match row</td>
			</tr>
		</table>
		</p>
		<p>Template example (for a match row in the prediction form):<br /><br />
		<span class="code"><?php echo htmlentities( '<tr><td>%match_time%</td><td>%home_team% %home_team_flag%</td><td>%home_input% - %away_input%</td><td>%away_team_flag% %away_team%</td></tr>' ); ?>
		</span><br />
		</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<h2 id="shortcodes">Shortcodes</h2>
		<p>This plugin has several shortcodes that can be added in the content of your posts or pages. Because adding a shortcode and remembering all the options of a shortcode can be a hassle, the visual editor of WordPress is extended with a button that makes adding these shortcodes a bit easier.
		</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<p>
		<img class="screenshot" src="<?php echo $img_dir; ?>screenshot-shortcode-button-editor.png" alt="screenshot" />
		</p>
		<p>The different shortcodes are explained in the following paragraphs.</p>
		
		<h3>[fp-predictions]</h3>
		<p>Shows the predictions for a given match and/or question.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">match</td>
				<td>The numeric id for the match</td>
				<td><a href="?page=footballpool-games">match id</a> (integer)</td>
				<td>none</td>
			</tr>
			<tr>
				<td class="row-title">question</td>
				<td>The numeric id for the question</td>
				<td><a href="?page=footballpool-bonus">question id</a> (integer)</td>
				<td>none</td>
			</tr>
			<tr>
				<td class="row-title">text</td>
				<td>text to display if no predictions can be shown (invalid id, or predictions not publicly viewable)</td>
				<td>string</td>
				<td>empty string</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-predictions match=1]</span><br />
		</p>
		
		<h3>[fp-user-score]</h3>
		<p>Shows the score for a given user.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">user</td>
				<td>The numeric id for user</td>
				<td><a href="users.php">user id</a> (integer)</td>
				<td>current user</td>
			</tr>
			<tr>
				<td class="row-title">ranking</td>
				<td>The numeric id for the ranking from which the score has to be taken</td>
				<td><a href="?page=footballpool-rankings">ranking id</a> (integer)</td>
				<td>default ranking</td>
			</tr>
			<tr>
				<td class="row-title">date</td>
				<td>Calculate the score untill this date.</td>
				<td>one of the following strings:<ul><li>now: current date is used</li><li>postdate: the date of the post is used</li><li>any valid formatted date (Y-m-d H:i)</li></ul></td>
				<td>now</td>
			</tr>
			<tr>
				<td class="row-title">text</td>
				<td>text to display if no user or no score is found</td>
				<td>string</td>
				<td>0</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-user-score user=1 text="no score"]</span><br />
		<span class="code">[fp-user-score user=58 ranking=2 text="no score"]</span><br />
		<span class="code">[fp-user-score user=5 date="2013-06-01 12:00"]</span><br />
		</p>
		
		<h3>[fp-user-ranking]</h3>
		<p>Shows the ranking for a given user.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">user</td>
				<td>The numeric id for user</td>
				<td><a href="users.php">user id</a> (integer)</td>
				<td>current user</td>
			</tr>
			<tr>
				<td class="row-title">ranking</td>
				<td>The numeric id for the ranking from which the ranking has to be taken</td>
				<td><a href="?page=footballpool-rankings">ranking id</a> (integer)</td>
				<td>default ranking</td>
			</tr>
			<tr>
				<td class="row-title">date</td>
				<td>Get the ranking for this date.</td>
				<td>one of the following strings:<ul><li>now: current date is used</li><li>postdate: the date of the post is used</li><li>any valid formatted date (Y-m-d H:i)</li></ul></td>
				<td>now</td>
			</tr>
			<tr>
				<td class="row-title">text</td>
				<td>text to display if no user or no ranking is found</td>
				<td>string</td>
				<td>empty string</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-user-ranking user=1 text="not ranked"]</span><br />
		<span class="code">[fp-user-ranking user=58 ranking=2 text="not ranked"]</span><br />
		<span class="code">[fp-user-ranking user=5 date="2013-06-01 12:00"]</span><br />
		</p>
		
		<h3>[fp-league-info]</h3>
		<p>Shows info about a league. E.g the total points or the average points (points divided by the number of players) of a league.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">league</td>
				<td>The league ID</td>
				<td><a href="?page=footballpool-leagues">league id</a> (integer)</td>
				<td>all users (default league)</td>
			</tr>
			<tr>
				<td class="row-title">ranking</td>
				<td>The numeric id for the ranking from which the ranking has to be taken</td>
				<td><a href="?page=footballpool-rankings">ranking id</a> (integer)</td>
				<td>default ranking</td>
			</tr>
			<tr>
				<td class="row-title">info</td>
				<td>What info about the league to show.</td>
				<td>one of the following strings:<ul><li>name: name of the league</li><li>points: total points scored for users in the league</li><li>avgpoints: the average points (total points divided by number of players)</li><li>numplayers: the number of players in the league</li><li>playernames: a list of players is returned</li></ul></td>
				<td>name</td>
			</tr>
			<tr>
				<td class="row-title">format</td>
				<td>optional format for the output (uses <a href="http://php.net/sprintf" target="_blank">sprintf</a> notation)</td>
				<td>string</td>
				<td></td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-league-info league=3 info="name"]</span><br />
		<span class="code">[fp-league-info league=3 info="avgpoints" format="%.1f"]</span><br />
		<span class="code">[fp-league-info league=3 info="playernames"]</span><br />
		</p>
		
		<h3>[fp-group]</h3>
		<p>Shows a group standing for the group stage of the tournament. Parameter "id" must be given. If "id" is 
		ommited, or not a valid group id, then nothing will be returned.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">id</td>
				<td>The numeric id for the group</td>
				<td><a href="?page=footballpool-groups">group id</a> (integer)</td>
				<td>1</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-group id=2]</span><br />
		<img class="screenshot" src="<?php echo $img_dir; ?>example-shortcode-groups.png" alt="screenshot" />
		</p>
		
		<h3>[fp-ranking]</h3>
		<p>Shows the ranking at a given moment in time. Accepts multiple parameters. And just like the widget, if a logged in user of your blog (current_user) is in the ranking, his/her name will be highlighted.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">num</td>
				<td>The number of rows in the ranking (top N)</td>
				<td>1..n (integer)</td>
				<td>5</td>
			</tr>
			<tr>
				<td class="row-title">league</td>
				<td>Show ranking for this league.<br />If the pool does not use leagues, then this parameter is ignored.</td>
				<td><a href="?page=footballpool-leagues">league id</a> (integer)</td>
				<td>all users</td>
			</tr>
			<tr>
				<td class="row-title">date</td>
				<td>Calculate the ranking untill this date.</td>
				<td>one of the following strings:<ul><li>now: current date is used</li><li>postdate: the date of the post is used</li><li>any valid formatted date (Y-m-d H:i)</li></ul></td>
				<td>now</td>
			</tr>
			<tr>
				<td class="row-title">ranking</td>
				<td>Show scores calculated in this ranking.<br />Defaults to all matches and all questions.</td>
				<td><a href="?page=footballpool-rankings">ranking id</a> (integer)</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">show_num_predictions</td>
				<td>If set to true also the number of predictions a user saved (matches and answers to questions) is shown in the ranking.</td>
				<td>1 = true<br/>0 = false</td>
				<td>depends on the 'Show number of predictions?' setting on the <a href="?page=footballpool-options">options page</a></td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-ranking num=5 ranking=4]</span><br />
		<span class="code">[fp-ranking num=5 show_num_predictions=1]</span><br />
		<span class="code">[fp-ranking num=5 date="postdate"]</span><br />
		<span class="code">[fp-ranking num=5 date="2012-06-22 11:00"]</span><br />
		<img class="screenshot" src="<?php echo $img_dir; ?>example-shortcode-ranking.png" alt="screenshot" />
		</p>
		
		<h3>[fp-predictionform]</h3>
		<p>Shows a prediction form for the selected matches, matches in a matchtype and/or bonus questions. All parameters are cumulative, so all given matches and matches in a matchtype are put together in one form.</p>
		<p>All arguments can be entered in the following formats (example for matches):
		<table>
			<tr><td>match 1</td><td>&rarr;</td><td>match="1"</td></tr>
			<tr><td>matches 1 to 5</td><td>&rarr;</td><td>match="1-5"</td></tr>
			<tr><td>matches 1, 3 and 6</td><td>&rarr;</td><td>match="1,3,6"</td></tr>
			<tr><td>matches 1 to 5 and 10</td><td>&rarr;</td><td>match="1-5,10"</td></tr>
		</table>
		</p>
		<p>If an argument is left empty it is ignored. Matches are always displayed first in a prediction form.</p>
		<p>If the current visitor is not logged in, a default text is shown (the default message can be changed with the <span class="code">text</span> parameter).</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">match</td>
				<td>Collection of <a href="?page=footballpool-games">match ids</a>.</td>
				<td>see formats above</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">matchtype</td>
				<td>Collection of <a href="?page=footballpool-matchtypes">match type ids</a>.</td>
				<td>see formats above</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">question</td>
				<td>Collection of <a href="?page=footballpool-bonus">question ids</a>.</td>
				<td>see formats above</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">text</td>
				<td>The text to display when a visitor is not logged on. Use with an empty string to display nothing (<span class="code">text=""</span>). To use the default text, just omit the parameter</td>
				<td>string</td>
				<td></td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-predictionform match="1-5"]</span><br />
		<span class="code">[fp-predictionform match="1-4,9-12" question="1,5,10"]</span><br />
		<span class="code">[fp-predictionform matchtype="1" text=""]</span><br />
		</p>

		<h3>[fp-matches]</h3>
		<p>Shows the info table for the selected matches, matches in a matchtype or matches for a group in the group phase. The matches and match types parameter are cumulative, so all given match ids and matches in a matchtype are put together in one table.</p>
		<p>All arguments (except the group parameter) can be entered in the following formats (example for matches):
		<table>
			<tr><td>match 1</td><td>&rarr;</td><td>match="1"</td></tr>
			<tr><td>matches 1 to 5</td><td>&rarr;</td><td>match="1-5"</td></tr>
			<tr><td>matches 1, 3 and 6</td><td>&rarr;</td><td>match="1,3,6"</td></tr>
			<tr><td>matches 1 to 5 and 10</td><td>&rarr;</td><td>match="1-5,10"</td></tr>
		</table>
		</p>
		<p>If an argument is left empty it is ignored. If a group ID is given the other parameters are ignored.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">match</td>
				<td>Collection of <a href="?page=footballpool-games">match ids</a>.</td>
				<td>see formats above</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">matchtype</td>
				<td>Collection of <a href="?page=footballpool-matchtypes">match type ids</a>.</td>
				<td>see formats above</td>
				<td></td>
			</tr>
			<tr>
				<td class="row-title">group</td>
				<td>The <a href="?page=footballpool-groups">group id</a> for which the matches have to be displayed.</td>
				<td><a href="?page=footballpool-groups">group id</a> (integer)</td>
				<td></td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-matches match="1-5"]</span><br />
		<span class="code">[fp-matches match="1-4,9-12" matchtype="2"]</span><br />
		<span class="code">[fp-matches matchtype="1"]</span><br />
		<span class="code">[fp-matches group="1"]</span><br />
		</p>

		<h3>[fp-register]link text[/fp-register]</h3>
		<p>Shows a link to the register page of WordPress. Text between the tags will be the text for the link. If no content is given, then a default text is shown as the link text. A redirect link to the post or page is automatically added if the get_permalink function does not return false.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">title</td>
				<td>Title parameter for the &lt;a href&gt;</td>
				<td>string</td>
				<td>empty; don't display a tooltip</td>
			</tr>
			<tr>
				<td class="row-title">new</td>
				<td>Open link in a new window/tab.</td>
				<td>integer: 0 (no) or 1 (yes)</td>
				<td>0</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">Click [fp-register]here[/fp-register] to register for this pool.</span><br />
		<span class="code">Click [fp-register new=1 title="Go to the registration page"]here[/fp-register] to register for this pool.</span><br />
		</p>
		
		<h3>[fp-countdown]</h3>
		<p>Counts down to a date and time. If no date is given, the time of the first match of the tournament is used. If a valid match number is given, it counts down to that match. A textual countdown is added to the post (or page) wich updates automatically.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr>
				<td class="row-title">date</td>
				<td>The date and time to count down to.</td>
				<td>Y-m-d H:i</td>
				<td>empty</td>
			</tr>
			<tr>
				<td class="row-title">match</td>
				<td>ID of the match to count down to.</td>
				<td><a href="?page=footballpool-games">match id</a> (integer)</td>
				<td>empty</td>
			</tr>
			<tr>
				<td class="row-title">texts</td>
				<td>A semi colon separated string with texts to put in front of and behind the counter. Don't forget spaces (if applicable). Must contain 4 texts:<ol><li>before counter if time has not passed</li><li>after counter if time has not passed</li><li>before counter if time has passed</li><li>after counter if time has passed</li></ol><br />
				If value is "none" then no texts are added.<br />
				If left empty or ommitted then the default texts are used.</td>
				<td>One of the following:<ul><li>string;string;string;string</li><li>none</li></ul></td>
				<td>empty; default texts are used.</td>
			</tr>
			<tr>
				<td class="row-title">display</td>
				<td>Display counter inline or as a separate block.</td>
				<td>One of the following strings:<ul><li>inline</li><li>block</li></ul></td>
				<td>block</td>
			</tr>
			<tr>
				<td class="row-title">format</td>
				<td>The time format for the countdown.</td>
				<td>One of the following numbers:<ul><li>1 (only seconds)</li><li>2 (days, hours, minutes, seconds)</li><li>3 (hours, minutes, seconds)</li></ul></td>
				<td>2</td>
			</tr>
		</table>
		</p>
		<p>examples:<br />
		<span class="code">[fp-countdown]</span><br />
		<span class="code">[fp-countdown date="2012-06-22 11:00"]</span><br />
		<span class="code">[fp-countdown match="3"]</span><br />
		<span class="code">[fp-countdown date="2012-06-22 11:00" texts="Wait ; until this date;; have passed since the date"]</span><br />
		<span class="code">[fp-countdown display="inline" match="3" format="1"]</span><br />
		</p>
		
		<h3>Other shortcodes</h3>
		<p>See <a href="?page=footballpool-options">Football Pool plugin settings</a> for some basic shortcodes that  will display the value for a plugin setting.</p>
		<p>

		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="charts">Using charts</h2>
		<p>
		The charts feature uses the Highcharts API to display the interactive charts. Because of the <a target="_blank" href="http://wordpress.org/extend/plugins/about/">WordPress license guidelines</a> I may not include this library in the package. Maybe if I find a library in the near future that has the same nice features and design (and I find the time to rewrite the charts code) I will change the plugin.</p>
		<p>For now you have to follow these steps:
		<ol>
			<li>Download the Highcharts API from <a target="_blank" href="http://www.highcharts.com/download">http://www.highcharts.com/download</a>.</li>
			<li>Place the <span class="code">highcharts.js</span> file in the following path <span class="code">/wp-content/plugins/highcharts-js/highcharts.js</span>.</li>
			<li>Enable the charts on the <a href="?page=footballpool-options">Options page</a>.</li>
		</ol>
		</p>
		<p>
			<img class="screenshot" src="<?php echo $img_dir; ?>example-chart.png" alt="screenshot" />
		</p>
		<p>
		If you don't want to use charts, then disable this option on the <a href="?page=footballpool-options">Options page</a>.
		</p>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>

		<h2 id="hooks">Extending the plugin: Actions and Filters</h2>
		<p>If you want to alter the output or behavior of the plugin there are several hooks you can use. If you want to learn more about hooks, see <a target="_blank" href="http://wp.tutsplus.com/tutorials/plugins/writing-extensible-plugins-with-actions-and-filters/">this tutorial</a> or <a target="_blank" href="http://codex.wordpress.org/Plugin_API">the Codex</a>. Place your custom code in your theme's functions.php file or in your own plugin (<a href="http://codex.wordpress.org/Writing_a_Plugin" target="_blank">how write your own plugin</a>).</p>
		<p>Search for <span class="code">do_action</span> or <span class="code">apply_filters</span> in the plugin's PHP files for the exact location of the different hooks.
		</p>
		<div class="help important">
			<p>Please note that some of the examples below use <a title="more on closures" href="http://www.php.net/manual/en/functions.anonymous.php" target="_blank">closures</a>. If you don't have PHP version 5.3 or higher, you'll have to rewrite the example to a named function.</p>
		</div>
		
		<h3>Simple and/or short examples:</h3>
		<?php 
		Football_Pool_Utils::highlight_string( '<?php
// show the page ID at the top of a page from the plugin
add_filter( \'footballpool_pages_html\', \'show_page_id\', null, 2 );
function show_page_id( $content, $id ) {
	return "<p>page id = {$id}</p>{$content}";
}
?>' );

		Football_Pool_Utils::highlight_string( '<?php
// add an extra div around the ranking table (when displayed with the fp-ranking shortcode)
add_filter( \'footballpool_shortcode_html_fp-ranking\', function ( $html ) {
	return \'<div class="extra-div">\' . $html . \'</div>\';
} );
?>' );

		Football_Pool_Utils::highlight_string( '<?php
// only show the first 20 users in the user selector
add_filter( \'footballpool_userselector_widget_users\', function ( $a ) {
	return array_slice( $a, 0, 20 );
} );
?>' );
		
		Football_Pool_Utils::highlight_string( '<?php
// Show number of predictions in the ranking table.
// If you want the page, shortcode or widget to have different layouts,
// you can differentiate with the $type.
add_filter( \'footballpool_ranking_template_start\', 
				function( $template_start, $league, $user, $ranking_id, $all_user_view, $type ) {
	// add a row with column headers
	$template_start .= sprintf( \'<tr>
									<th></th>
									<th class="user">%s</th>
									<th class="num-predictions">%s</th>
									<th class="score">%s</th>
									%s</tr>\'
								, __( \'user\', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( \'predictions\', FOOTBALLPOOL_TEXT_DOMAIN )
								, __( \'points\', FOOTBALLPOOL_TEXT_DOMAIN )
								, ( $all_user_view ? \'<th></th>\' : \'\' )
						);
	return $template_start;
}, null, 6 );
add_filter( \'footballpool_ranking_ranking_row_template\', function( $template, $all_user_view, $type ) {
	if ( $all_user_view ) {
		$ranking_template = \'<tr class="%css_class%">
								<td style="width:3em; text-align: right;">%rank%.</td>
								<td><a href="%user_link%">%user_avatar%%user_name%</a></td>
								<td class="num-predictions">%num_predictions%</td>
								<td class="ranking score">%points%</td>
								<td>%league_image%</td>
								</tr>\';
	} else {
		$ranking_template = \'<tr class="%css_class%">
								<td style="width:3em; text-align: right;">%rank%.</td>
								<td><a href="%user_link%">%user_avatar%%user_name%</a></td>
								<td class="num-predictions">%num_predictions%</td>
								<td class="ranking score">%points%</td>
								</tr>\';
	}
	return $ranking_template;
}, null, 3 );
?>' );
		?>
		<h3>A bit more advanced examples:</h3>
		<?php
		Football_Pool_Utils::highlight_string( '<?php
// add a simple pagination to the ranking page
add_filter( \'footballpool_ranking_array\', \'fp_pagination\' );
add_filter( \'footballpool_ranking_html\', \'fp_pagination_html\', null, 2 );
// and, with the same functions, add a simple pagination to the statistics page (view=matchpredictions)
add_filter( \'footballpool_statistics_matchpredictions\', \'fp_pagination\' );
add_filter( \'footballpool_statistics_matchpredictions_html\', \'fp_pagination_html\', null, 2 );

function fp_pagination( $items ) {
	$pagination = new Football_Pool_Pagination( count( $items ) );
	$pagination->page_param = \'fp_page\';
	$pagination->set_page_size( 10 );
	$offset = ( ( $pagination->current_page - 1 ) * $pagination->get_page_size() );
	$length = $pagination->get_page_size();
	return array_slice( $items, $offset, $length );
}

function fp_pagination_html( $html, $items ) {
	$pagination = new Football_Pool_Pagination( count( $items ), true );
	$pagination->page_param = \'fp_page\';
	$pagination->set_page_size( 10 );
	return $html . $pagination->show( \'return\' );
}
?>' );

		Football_Pool_Utils::highlight_string( '<?php
// don\'t use admin approval for league registration of new users
// just put them in the league they chose
add_filter( \'footballpool_new_user\', function( $user_id, $league_id ) {
	Football_Pool::update_user_custom_tables( $user_id, $league_id );
}, null, 2 );
?>' );

		Football_Pool_Utils::highlight_string( '<?php
// add a column with the group order in the group widget using PHP Simple HTML DOM Parser
// (download it from http://simplehtmldom.sourceforge.net/ and add it to your themes dir)
add_filter( \'footballpool_widget_html_group\', function( $html ) {
	require_once \'simple_html_dom.php\';
	
	$html_dom = new simple_html_dom();
	$html_dom->load( $html );
	
	// add extra column in the header
	$th = $html_dom->find( \'th.team\', 0 );
	$th->outertext = \'<th></th>\' . $th->outertext;
	// add numbering before the team name
	$i = 1;
	foreach ( $html_dom->find( \'tr\' ) as $tr ) {
		foreach( $tr->find( \'td.team\' ) as $td ) {
			$td->outertext = sprintf( \'<td>%d</td>%s\', $i++, $td->outertext );
		}
	}
	
	$output = $html_dom->save();
	$html_dom->clear();
	unset( $html_dom );
	
	return $output;
} );
?>' );

		?>
		
		<p class="help back-to-top"><a href="#">back to top</a></p>
		
		<h2 id="the-end">Anything else?</h2>
		<p>It was real fun writing this plugin and I hope you had/have as much fun using it. If not, please let me know. You can leave a question, feature request or a bug report at the <a target="_blank" href="http://wordpress.org/support/plugin/football-pool">WordPress forum</a>.</p>
		<p>Writing this plugin and maintaining it takes a lot of time. If you liked using this plugin please consider a small donation.<br />
		Or a little fan mail is also appreciated :)</p>
		<?php self::admin_footer(); ?>
		<p>
		<?php self::donate_button(); ?>
		Thank you!<br />
		Antoine Hurkmans<br /><br />
		<em>wordpressfootballpool [ at ] gmail [ dot ] com</em>
		</p>
		
		</div> <!-- end help page -->
		<?php
	}

}
