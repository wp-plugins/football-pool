<?php
require_once( '../../../../../../wp-load.php' );
require_once( '../../../define.php' );

$tinymce_url = get_option( 'siteurl' ) . '/wp-includes/js/tinymce/';
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
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		tinyMCEPopup.executeOnLoad( 'tinymce_init()' );
		tinymce_init_tabs( 'tabs' );
	});
	
	function toggle_atts( select_id, atts ) {
		var selected_val = jQuery( '#' + select_id ).val();
		jQuery.each( atts, function( key, value ) {
			if ( selected_val == key ) {
				jQuery( '#' + value ).show();
			} else {
				jQuery( '#' + value ).hide();
			}
		});
	}
	</script>
	<style type="text/css">
	#tabs li { cursor: pointer; }
	.dialog-table { border: 0; }
	.dialog-table td { padding: 5px; }
	.dialog-table td p { margin-bottom: 3px; }
	#panel_wrapper div.current { height: 200px; }
	.atts { display: none; }
	</style>
</head>
<body>
<form>
	<div id="tabs" class="tabs">
		<ul>
			<li id="pool_tab" class="current"><span><?php _e( 'Pool', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
			<li id="options_tab"><span><?php _e( 'Options', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
			<li id="links_tab"><span><?php _e( 'Links', FOOTBALLPOOL_TEXT_DOMAIN ); ?></span></li>
		</ul>
	</div>

	<div id="panel_wrapper" class="panel_wrapper">
<?php
// add_shortcode( 'fp-countdown', array( 'Football_Pool_Shortcodes', 'shortcode_countdown' ) );
// add_shortcode( 'fp-ranking', array( 'Football_Pool_Shortcodes', 'shortcode_ranking' ) );
// add_shortcode( 'fp-group', array( 'Football_Pool_Shortcodes', 'shortcode_group' ) );
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
			<tr id="tr-ranking" class="">
				<td>
					<label for=""><?php _e( 'Select a page', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
				</td>
			</tr>
			<tr id="tr-group" class="atts">
				<td>
				</td>
				<td>
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
			<tr id="tr-slug" class="">
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
			<tr id="tr-title" class="atts">
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