<?php
require_once( '../../../../../../wp-load.php' );
require_once( '../../../define.php' );

$site_url = get_option( 'siteurl' );
$admin_url = get_admin_url();
$tinymce_url = $site_url . '/wp-includes/js/tinymce/';
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $tinymce_url; ?>tiny_mce_popup.js"></script>
	<script type="text/javascript" src="<?php echo $tinymce_url; ?>utils/mctabs.js"></script>
	<script type="text/javascript" src="<?php echo FOOTBALLPOOL_PLUGIN_URL ?>assets/admin/admin.js"></script>
	<base target="_self" />
	
	<style type="text/css">
	#tabs li, .dialog-table label { cursor: pointer; }
	.dialog-table { border: 0; }
	.dialog-table td { padding: 3px; vertical-align: top; }
	/* .dialog-table td * { font-size: 12px!important; } */
	.dialog-table td p { margin-bottom: 3px; }
	#panel_wrapper div.current { height: 270px; }
	.atts { display: none; }
	</style>
	
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		tinyMCEPopup.executeOnLoad( 'tinymce_init()' );
		tinymce_init_tabs( 'tabs' );
	});
	
	function toggle_atts( select_id, atts ) {
		var selected_val = jQuery( '#' + select_id ).val();
		jQuery.each( atts, function( key, value ) {
			if ( selected_val == key ) {
				jQuery( '.' + value ).show();
			} else {
				jQuery( '.' + value ).hide();
			}
		});
	}
	
	function toggle_count_texts( id ) {
		var text_ids = ['#text-1', '#text-2', '#text-3', '#text-4'];
		if ( jQuery( '#' + id ).is( ':checked' ) ) {
			set_input_param( 'placeholder', text_ids, 'none' );
		} else {
			restore_input_param( 'placeholder', text_ids );
		}
		disable_inputs( text_ids, id );
	}
	</script>
</head>
<body>
<form>
	<div id="tabs" class="tabs">
		<ul>
			<li id="pool_tab" class="current"><span><?php _e( 'Pool', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
			<li id="options_tab"><span><?php _e( 'Options', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
			<li id="links_tab"><span><?php _e( 'Links', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
			<li id="other_tab"><span><?php _e( 'Other', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
		</ul>
	</div>

	<div id="panel_wrapper" class="panel_wrapper">
<?php
// fp-ranking
// fp-group
?>
		<!-- panel -->
		<div id="pool_panel" class="panel current"><br/>
			<table class="dialog-table">
			<tr>
				<td><label for="s-pool"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="s-pool" class="shortcode" onchange="toggle_atts( this.id, { 'fp-ranking': 'tr-ranking', 'fp-group': 'tr-group' } )">
						<option value="fp-ranking"><?php _e( 'Ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-group"><?php _e( 'Group', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-ranking">
				<td>
					<label for="ranking-league"><?php _e( 'Select a league', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="ranking-league">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'all players', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a league', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							$leagues = Football_Pool_Pool::get_leagues( true );
							foreach ( $leagues as $league ) {
								printf( '<option value="%d">%s</option>', $league['leagueId'], $league['leagueName'] );
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-ranking">
				<td>
					<label for="ranking-num"><?php _e( 'Number of players', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input type="text" id="ranking-num" placeholder="5" />
				</td>
			</tr>
			<tr class="tr-ranking">
				<td>
					<label><a href="//php.net/manual/en/function.date.php" title="<?php _e( 'information about PHP\'s date format', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" target="_blank"><?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<label><input type="radio" id="ranking-date-now" name="ranking-date" value="now" checked="checked" onclick="toggle_linked_radio_options( '', '#tr-ranking-date' )" /> <?php _e( 'now', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="ranking-date-postdate" name="ranking-date" value="postdate" onclick="toggle_linked_radio_options( '', '#tr-ranking-date' )" /> <?php _e( 'postdate', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="ranking-date-custom" name="ranking-date" value="custom" onclick="toggle_linked_radio_options( '#tr-ranking-date', '' )" /> <?php _e( 'custom date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
				</td>
			</tr>
			<tr id="tr-ranking-date" class="tr-ranking-date atts">
				<td>
					<label></label>
				</td>
				<td>
					<span style="padding-left:24px"><input type="text" id="ranking-date-custom-value" placeholder="Y-m-d H:i" /></span>
				</td>
			</tr>
			<tr class="tr-group atts">
				<td>
					<label for="group-id"><?php _e( 'Select a group', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="group-id">
					<?php
					$groups = Football_Pool_Groups::get_groups();
					foreach( $groups as $group ) {
						printf( '<option value="%d">%s</option>', $group->id, $group->name );
					}
					?>
					</select>
				</td>
			</tr>
			</table>
		</div>
		
		<!-- panel -->
		<div id="options_panel" class="panel"><br />
			<table class="dialog-table">
			<tr>
				<td><label for="shortcode"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="shortcode" class="shortcode">
						<option value="fp-fullpoints"><?php _e( 'Full points', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-totopoints"><?php _e( 'Toto points', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-goalpoints"><?php _e( 'Goal bonus', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-webmaster"><?php _e( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-bank"><?php _e( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-money"><?php _e( 'Money', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-start"><?php _e( 'Start date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			</table>
		</div>
		
		<!-- panel -->
		<div id="links_panel" class="panel"><br />
			<table class="dialog-table">
			<tr>
				<td><label for="s-link"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="s-link" class="shortcode" onchange="toggle_atts( this.id, { 'fp-link': 'tr-slug', 'fp-register': 'tr-title' } )">
						<option value="fp-link"><?php _e( 'Link to page', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-register"><?php _e( 'Link to registration', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-slug">
				<td><label for="slug"><?php _e( 'Select a page', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="slug">
					<?php
					$pages = Football_Pool::get_pages();
					foreach ( $pages as $page ) {
						printf( '<option value="%s">%s</option>', $page['slug'], __( $page['title'], FOOTBALLPOOL_TEXT_DOMAIN ) );
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="tr-title atts">
				<td>
					<p><label for="link-title"><?php _e( 'Link title', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></p>
					<p><label for="link-window"><?php _e( 'New window?', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></p>
				</td>
				<td>
					<p><input id="link-title" type="text" /></p>
					<p><input id="link-window" type="checkbox" /></p>
				</td>
			</tr>
			</table>
		</div>
		
		<!-- panel -->
		<div id="other_panel" class="panel"><br />
			<table class="dialog-table">
			<tr>
				<td><label for="s-other"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="s-other" class="shortcode" onchange="toggle_atts( this.id, { 'fp-countdown': 'tr-count' } )">
						<option value="fp-countdown"><?php _e( 'Countdown', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-count">
				<td>
					<label for=""><?php _e( 'Countdown to', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td><label>
					<input type="radio" id="count-to-match" name="count_to" value="match" checked="checked" onclick="toggle_linked_radio_options( '#tr-count-match', '#tr-count-date' )" /> <?php _e( 'Match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="count-to-date" name="count_to" value="date" onclick="toggle_linked_radio_options( '#tr-count-date', '#tr-count-match' )" /> <?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
				</td>
			</tr>
			<tr id="tr-count-date" class="tr-count atts">
				<td>
					<label for="count-date"><a href="//php.net/manual/en/function.date.php" title="<?php _e( 'information about PHP\'s date format', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" target="_blank"><?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<input type="text" id="count-date" placeholder="Y-m-d H:i" />
				</td>
			</tr>
			<tr id="tr-count-match" class="tr-count">
				<td>
					<label for="count-match"><?php _e( 'Match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="count-match">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'first match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a match', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							$matches = new Football_Pool_Matches;
							$matches = $matches->matches;
							foreach ( $matches as $match ) {
								$option_text = sprintf( '%d: %s - %s (%s)'
														, $match['nr']
														, $match['home_team']
														, $match['away_team']
														// , $match['match_datetime']
														, Football_Pool_Utils::date_from_gmt( $match['date'] )
												);
								printf( '<option value="%d">%s</option>', $match['nr'], $option_text );
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-count">
				<td>
					<label for="text-1"><a target="_blank" href="<?php echo $admin_url; ?>admin.php?page=footballpool-help#shortcodes" title="<?php _e( "More information about this on the Help page.", FOOTBALLPOOL_TEXT_DOMAIN ); ?>"><?php _e( 'Texts for counter', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<p>
						<label><input type="checkbox" id="count-no-texts" value="1" onchange="toggle_count_texts( this.id )" /> <?php _e( 'no texts', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
					</p>
					<p>
						<input type="text" id="text-1" placeholder="<?php _e( 'before - time not passed', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" title="<?php _e( "Leave empty for the default texts. Don't forget spaces between a text and the timer.", FOOTBALLPOOL_TEXT_DOMAIN ); ?>" />
						<input type="text" id="text-2" placeholder="<?php _e( 'after - time not passed', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" title="<?php _e( "Leave empty for the default texts. Don't forget spaces between a text and the timer.", FOOTBALLPOOL_TEXT_DOMAIN ); ?>" />
					</p>
					<p>
						<input type="text" id="text-3" placeholder="<?php _e( 'before - time passed', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" title="<?php _e( "Leave empty for the default texts. Don't forget spaces between a text and the timer.", FOOTBALLPOOL_TEXT_DOMAIN ); ?>" />
						<input type="text" id="text-4" placeholder="<?php _e( 'after - time passed', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" title="<?php _e( "Leave empty for the default texts. Don't forget spaces between a text and the timer.", FOOTBALLPOOL_TEXT_DOMAIN ); ?>" />
					</p>
				</td>
			</tr>
			<tr class="tr-count">
				<td>
					<label for="count-inline"><?php _e( 'Display inline', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input id="count-inline" type="checkbox" />
				</td>
			</tr>
			
			<tr class="atts">
				<td>
				</td>
				<td>
				</td>
			</tr>
			</table>
			</table>
		</div>
		
	</div>
	
	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php _e( 'Cancel', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" onclick="tinyMCEPopup.close();" />
		</div>
		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php _e( 'Insert', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" onclick="tinymce_insert_shortcode();" />
		</div>
	</div>

</form>
</body>
</html>