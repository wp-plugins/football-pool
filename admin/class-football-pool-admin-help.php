<?php
class Football_Pool_Admin_Help extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), '' );
		?>
		<h2>Shortcodes</h2>
		<h3>[fp-groups]</h3>
		<p>Shows a group standing for the group stage of the tournament. Parameter "id" must be given. If "id" is 
		ommited, or not a valid group id, then nothing will be returned.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr class="">
				<td class="row-title">id</td>
				<td>The numeric id for the group</td>
				<td>1..4 (integer)</td>
				<td>1</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-groups id=2]</span><br />
		<img src="<?php echo FOOTBALLPOOL_PLUGIN_URL; ?>admin/assets/example-shortcode-groups.png" alt="screenshot" />
		</p>
		
		<h3>[fp-ranking]</h3>
		<p>Shows the ranking at a given moment in time. Accepts multiple parameters. And just like the widget, if a logged in user of your blog (current_user) is in the ranking, his/her name will be highlighted.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr class="">
				<td class="row-title">num</td>
				<td>The number of rows in the ranking (top N)</td>
				<td>1..n (integer)</td>
				<td>5</td>
			</tr>
			<tr class="alternate">
				<td class="row-title">league</td>
				<td>Show ranking for this league.<br />If the pool does not use leagues, then this parameter is ignored.</td>
				<td><a href="?page=footballpool-leagues">league id</a> (integer)</td>
				<td>all users</td>
			</tr>
			<tr class="">
				<td class="row-title">date</td>
				<td>Calculate the ranking untill this date.</td>
				<td>one of the following strings<ul><li>- now: current date is used</li><li>- postdate: the date of the post is used</li><li>- any valid formatted date (Y-m-d H:i)</li></ul></td>
				<td>now</td>
			</tr>
		</table>
		</p>
		<p>example:<br />
		<span class="code">[fp-ranking num=5 date="2012-06-22 11:00"]</span><br />
		<img src="<?php echo FOOTBALLPOOL_PLUGIN_URL; ?>admin/assets/example-shortcode-ranking.png" alt="screenshot" />
		</p>

		<h3>[fp-register]link text[/fp-register]</h3>
		<p>Shows a link to the register page of WordPress. Text between the tags will be the text for the link. If no content is given, then a default text is shown as the link text. A redirect link to the post or page is automatically added if the get_permalink function does not return false.</p>
		<p>
		<table class="widefat help">
			<tr><th>parameter</th><th>description</th><th>values</th><th>default</th></tr>
			<tr class="">
				<td class="row-title">title</td>
				<td>Title parameter for the &lt;a href&gt;</td>
				<td>string</td>
				<td>empty; don't display a tooltip</td>
			</tr>
			<tr class="alternate">
				<td class="row-title">new</td>
				<td>Open link in a new window/tab.</td>
				<td>integer: 0 (no) or 1 (yes)</td>
				<td>0</td>
			</tr>
		</table>
		</p>
		
		<h3>Other shortcodes</h3>
		<p>See <a href="?page=footballpool-options">Football Pool plugin settings</a> for some basic shortcodes that  will display the value for a plugin setting.</p>
		<p>

		<h2>Leagues</h2>
		<p>The plugin supports placing players in different leagues. For example when you want to group players per department, or friends and family, or paying and non-paying, etc. When playing with leagues an admin has to 'approve' the league for which a player subscribed. That can be done in the <a href="users.php">User</a> section of the WordPress admin, or the <a href="?page=footballpool-users">Users page</a> of the Football Pool plugin.</p>
		<p>If using leagues all players have to be a member of a league, otherwise they are not considered to be a football pool player.</p>

		<h2>Players</h2>
		<p>There's two ways the plugin can handle your blog users: via leagues or not via leagues. If playing with leagues your blog users have to be added to an active league. New subscribers to your blog must choose a league when subscribing, but existing users have to change this setting after the plugin is installed (or the admin can do this for them).<br />
		If not playing with leagues all your blog users are automatically players in the pool. If you want to exclude some players from the rankings (e.g. the admin), you can disable them in the <a href="?page=footballpool-users">Users page</a> of the Football Pool plugin.</p>
		<?php
		self::admin_footer();
	}

}
?>