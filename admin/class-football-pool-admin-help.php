<?php
class Football_Pool_Admin_Help extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Shortcodes', FOOTBALLPOOL_TEXT_DOMAIN ), '' );
		?>
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

		<h3>Other shortcodes</h3>
		<p>See <a href="?page=footballpool-options">Football Pool plugin settings</a> for some basic shortcodes that  will display the value for a plugin setting.</p>
		<p>
		<?php
		self::admin_header( __( 'Leagues', FOOTBALLPOOL_TEXT_DOMAIN ), '' );
		?>
		<p>The plugin supports placing players in different leagues. For example when you want to group players per department, or friends and family, or paying and non-paying, etc. When playing with leagues an admin has to 'approve' the league for which a player subscribed. That can be done in the <a href="users.php">User</a> section of the WordPress admin.</p>
		<?php
		self::admin_footer();
	}

}
?>