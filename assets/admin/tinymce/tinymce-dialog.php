<?php
require_once( '../../../../../../wp-load.php' );
require_once( '../../../define.php' );

$site_url = get_option( 'siteurl' );
$admin_url = get_admin_url();
$tinymce_url = $site_url . '/wp-includes/js/tinymce/';
$pool = new Football_Pool_Pool;

function group_options() {
	$o = new Football_Pool_Groups;
	$groups = $o->get_groups();
	foreach ( $groups as $group ) {
		printf( '<option value="%d">%s</option>', $group->id, $group->name );
	}
}

function ranking_options() {
	global $pool;
	$rankings = $pool->get_rankings( 'user defined' );
	foreach ( $rankings as $ranking ) {
		printf( '<option value="%d">%s</option>', $ranking['id'], $ranking['name'] );
	}
}

function bonusquestion_options() {
	global $pool;
	$questions = $pool->get_bonus_questions();
	foreach( $questions as $question ) {
		if ( $question['match_id'] == 0 ) {
			printf( '<option value="%d">%d: %s</option>', $question['id'], $question['id'], $question['question'] );
		}
	}
}

function match_options() {
	$matches = new Football_Pool_Matches;
	$matches = $matches->matches;
	foreach ( $matches as $match ) {
		$option_text = sprintf( '%d: %s - %s (%s)'
								, $match['id']
								, $match['home_team']
								, $match['away_team']
								// , $match['match_datetime']
								, Football_Pool_Utils::date_from_gmt( $match['date'] )
						);
		printf( '<option value="%d">%s</option>', $match['id'], $option_text );
	}
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $tinymce_url; ?>tiny_mce_popup.js"></script>
	<script type="text/javascript" src="<?php echo $tinymce_url; ?>utils/mctabs.js"></script>
	<script type="text/javascript" src="<?php echo FOOTBALLPOOL_PLUGIN_URL ?>assets/admin/admin.min.js"></script>
	<base target="_self" />
	
	<style type="text/css">
	#tabs li, .dialog-table label { cursor: pointer; }
	.dialog-table { border: 0; border-collapse: collapse; }
	.dialog-table td { padding: 3px 5px 3px 3px; vertical-align: top; }
	/* .dialog-table td * { font-size: 12px!important; } */
	.dialog-table td p { margin-bottom: 3px; }
	#panel_wrapper div.current { height: 270px; }
	.atts { display: none; }
	.shortcode-select { background-color: #f1f1f1; }
	.shortcode-select td p { margin-bottom: 8px; }
	.shortcode-select td { font-weight: bold; }
	.info { font-style: italic; }
	</style>
	
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		tinyMCEPopup.executeOnLoad( 'tinymce_init()' );
		footballpool_tinymce_init_tabs( 'tabs' );
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
	
	function toggle_select_row( clicked, shortcode ) {
		clicked = jQuery( clicked ).attr( 'for' );
		jQuery( '.tr-' + shortcode + ' select' ).each( function() {
			if ( jQuery( this ).attr( 'id' ) == clicked ) {
				jQuery( this ).show( 'slow' );
			} else {
				jQuery( this ).hide( 'slow' );
			}
		});
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
		<!-- panel -->
		<div id="pool_panel" class="panel current"><br/>
			<table class="dialog-table">
			<tr class="shortcode-select">
				<td><label for="s-pool"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="s-pool" class="shortcode" onchange="toggle_atts( this.id, { 'fp-ranking': 'tr-ranking', 'fp-group': 'tr-group', 'fp-predictionform': 'tr-predictionform', 'fp-user-score': 'tr-user-score', 'fp-user-ranking': 'tr-user-ranking', 'fp-predictions': 'tr-predictions', 'fp-matches': 'tr-matches', 'fp-league-info': 'tr-league-info' } )">
						<option value="fp-ranking"><?php _e( 'Ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-predictions"><?php _e( 'Predictions for match or question', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-user-score"><?php _e( 'Score for a user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-user-ranking"><?php _e( 'Ranking for a user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-group"><?php _e( 'Group', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-predictionform"><?php _e( 'Prediction form', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-matches"><?php _e( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="fp-league-info"><?php _e( 'League info', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<!-- fp-ranking -->
			<tr class="tr-ranking">
				<td>
					<label for="ranking-id"><?php _e( 'Select a ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="ranking-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'all scores', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a user defined ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							ranking_options();
							?>
						</optgroup>
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
							$leagues = $pool->get_leagues( true );
							foreach ( $leagues as $league ) {
								printf( '<option value="%d">%s</option>'
										, $league['league_id'], $league['league_name'] 
								);
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
					<label for="ranking-show-num-predictions"><?php _e( 'Show number of predictions?', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input type="checkbox" id="ranking-show-num-predictions" <?php echo ( Football_Pool_Utils::get_fp_option( 'show_num_predictions_in_ranking' ) == 1 ? 'checked="checked" ' : '' ); ?>/>
				</td>
			</tr>
			<tr class="tr-ranking">
				<td>
					<label><a href="//php.net/manual/en/function.date.php" title="<?php _e( 'information about PHP\'s date format', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" target="_blank"><?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<label><input type="radio" id="ranking-date-now" name="ranking-date" value="now" checked="checked" /> <?php _e( 'now', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="ranking-date-postdate" name="ranking-date" value="postdate" /> <?php _e( 'postdate', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="ranking-date-custom" name="ranking-date" value="custom" onclick="jQuery( '#ranking-date-custom-value' ).focus();" /> <?php _e( 'custom date', FOOTBALLPOOL_TEXT_DOMAIN ); ?>:</label> <input type="text" id="ranking-date-custom-value" placeholder="Y-m-d H:i" onclick="jQuery( '#ranking-date-custom' ).prop( 'checked', true );" /><br />
				</td>
			</tr>
			<!-- fp-predictions -->
			<tr class="tr-predictions atts">
				<td>
					<label for="predictions-match"><?php _e( 'Match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="predictions-match">
						<option value="0"><?php _e( 'Select a match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<?php match_options(); ?>
					</select>
				</td>
			</tr>
			<tr class="tr-predictions atts">
				<td></td><td class="info"><?php _e( 'and/or', FOOTBALLPOOL_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr class="tr-predictions atts">
				<td>
					<label for="predictions-question"><?php _e( 'Question', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="predictions-question">
						<option value="0"><?php _e( 'Select a question', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<?php
						bonusquestion_options();
						?>
					</select>
				</td>
			</tr>
			<tr class="tr-predictions atts">
				<td>
					<label for="predictions-text"><?php _e( 'Text', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input type="text" id="predictions-text" style="width:100%" placeholder="<?php _e( 'Text to display if there is nothing to show', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" />
				</td>
			</tr>
			<!-- fp-user-score -->
			<tr class="tr-user-score atts">
				<td>
					<label for="user-score-user-id"><?php _e( 'Select a user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="user-score-user-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="" selected="selected"><?php _e( 'logged in user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose another user', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							$users = $pool->get_users( 1 );
							foreach ( $users as $user ) {
								printf( '<option value="%d">%s</option>', $user['user_id'], $user['user_name'] );
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-user-score atts">
				<td>
					<label for="user-score-ranking-id"><?php _e( 'Select a ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="user-score-ranking-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'all scores', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a user defined ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php ranking_options() ; ?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-user-score atts">
				<td>
					<label for="user-score-text"><?php _e( 'Text', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input type="text" id="user-score-text" placeholder="0" />
				</td>
			</tr>
			<tr class="tr-user-score atts">
				<td>
					<label><a href="//php.net/manual/en/function.date.php" title="<?php _e( 'information about PHP\'s date format', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" target="_blank"><?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<label><input type="radio" id="user-score-date-now" name="user-score-date" value="now" checked="checked" /> <?php _e( 'now', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="user-score-date-postdate" name="user-score-date" value="postdate" /> <?php _e( 'postdate', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="user-score-date-custom" name="user-score-date" value="custom" onclick="jQuery( '#user-score-date-custom-value' ).focus();" /> <?php _e( 'custom date', FOOTBALLPOOL_TEXT_DOMAIN ); ?>:</label> <input type="text" id="user-score-date-custom-value" placeholder="Y-m-d H:i" onclick="jQuery( '#user-score-date-custom' ).prop( 'checked', true );" /><br />
				</td>
			</tr>
			<!-- fp-user-ranking -->
			<tr class="tr-user-ranking atts">
				<td>
					<label for="user-ranking-user-id"><?php _e( 'Select a user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="user-ranking-user-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="" selected="selected"><?php _e( 'logged in user', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose another user', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							$users = $pool->get_users( 1 );
							foreach ( $users as $user ) {
								printf( '<option value="%d">%s</option>', $user['user_id'], $user['user_name'] );
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-user-ranking atts">
				<td>
					<label for="user-ranking-ranking-id"><?php _e( 'Select a ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="user-ranking-ranking-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'all scores', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a user defined ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php ranking_options(); ?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-user-ranking atts">
				<td>
					<label for="user-ranking-text"><?php _e( 'Text', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<input type="text" id="user-ranking-text" placeholder="" />
				</td>
			</tr>
			<tr class="tr-user-ranking atts">
				<td>
					<label><a href="//php.net/manual/en/function.date.php" title="<?php _e( 'information about PHP\'s date format', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" target="_blank"><?php _e( 'Date', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<label><input type="radio" id="user-ranking-date-now" name="user-ranking-date" value="now" checked="checked" /> <?php _e( 'now', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="user-ranking-date-postdate" name="user-ranking-date" value="postdate" /> <?php _e( 'postdate', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label><br />
					<label><input type="radio" id="user-ranking-date-custom" name="user-ranking-date" value="custom" onclick="jQuery( '#user-ranking-date-custom-value' ).focus();" /> <?php _e( 'custom date', FOOTBALLPOOL_TEXT_DOMAIN ); ?>:</label> <input type="text" id="user-ranking-date-custom-value" placeholder="Y-m-d H:i" onclick="jQuery( '#user-ranking-date-custom' ).prop( 'checked', true );" /><br />
				</td>
			</tr>
			<!-- fp-group -->
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
			<!-- fp-predictionform -->
			<tr class="tr-predictionform atts">
				<td colspan="2">
					<strong><?php _e( 'Click a label to show the options.', FOOTBALLPOOL_TEXT_DOMAIN );?></strong>
					<br />
					<?php _e( 'Use CTRL+click to select multiple values.', FOOTBALLPOOL_TEXT_DOMAIN );?>
				</td>
			<tr>
			<tr class="tr-predictionform atts">
				<td>
					<label for="match-id" onclick="toggle_select_row( this, 'predictionform' )"><?php _e( 'Select one or more matches', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="match-id" style="width:320px; height:100px; display:none;" multiple="multiple">
					<?php match_options(); ?>
					</select>
				</td>
			</tr>
			<tr class="tr-predictionform atts">
				<td>
					<label for="matchtype-id" onclick="toggle_select_row( this, 'predictionform' )"><?php _e( 'Select one or more match types', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="matchtype-id" style="width:320px; height:100px; display:none;" multiple="multiple">
					<?php
					$match_types = Football_Pool_Matches::get_match_types();
					foreach( $match_types as $match_type ) {
						printf( '<option value="%d">%s</option>', $match_type->id, $match_type->name );
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="tr-predictionform atts">
				<td>
					<label for="question-id" onclick="toggle_select_row( this, 'predictionform' )"><?php _e( 'Select one or more questions', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="question-id" style="width:320px; height:100px; display:none;" multiple="multiple">
					<?php bonusquestion_options(); ?>
					</select>
				</td>
			</tr>
			<!-- fp-matches -->
			<tr class="tr-matches atts">
				<td colspan="2">
					<strong><?php _e( 'Click a label to show the options.', FOOTBALLPOOL_TEXT_DOMAIN );?></strong>
					<br />
					<?php _e( 'Use CTRL+click to select multiple values.', FOOTBALLPOOL_TEXT_DOMAIN );?>
				</td>
			<tr>
			<tr class="tr-matches atts">
				<td>
					<label for="matches-match-id" onclick="toggle_select_row( this, 'matches' )"><?php _e( 'Select one or more matches', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="matches-match-id" style="width:320px; height:100px; display:none;" multiple="multiple">
					<?php match_options(); ?>
					</select>
				</td>
			</tr>
			<tr class="tr-matches atts">
				<td>
					<label for="matches-matchtype-id" onclick="toggle_select_row( this, 'matches' )"><?php _e( 'Select one or more match types', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="matches-matchtype-id" style="width:320px; height:100px; display:none;" multiple="multiple">
					<?php
					$match_types = Football_Pool_Matches::get_match_types();
					foreach( $match_types as $match_type ) {
						printf( '<option value="%d">%s</option>', $match_type->id, $match_type->name );
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="tr-matches atts">
				<td>
					<label for="matches-group-id" onclick="toggle_select_row( this, 'matches' )"><?php _e( 'Select a group', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="matches-group-id" style="width:320px; display:none;">
					<?php group_options(); ?>
					</select>
				</td>
			</tr>
			<!-- fp-league-info -->
			<tr class="tr-league-info atts">
				<td>
					<label for="league-info-league-id"><?php _e( 'Select a league', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="league-info-league-id">
						<?php
						$leagues = $pool->get_leagues( true );
						foreach ( $leagues as $league ) {
							printf( '<option value="%d">%s</option>'
									, $league['league_id'], $league['league_name'] 
							);
						}
						?>
					</select>
				</td>
			</tr>
			<tr class="tr-league-info atts">
				<td>
					<label><?php _e( 'Show this info', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<label><input type="radio" id="league-info-name" name="league-info-info" value="name" checked="checked" /> name</label><br />
					<label><input type="radio" id="league-info-points" name="league-info-info" value="points" /> points</label><br />
					<label><input type="radio" id="league-info-avgpoints" name="league-info-info" value="avgpoints" /> average points</label><br />
					<label><input type="radio" id="league-info-playernames" name="league-info-info" value="playernames" /> player names</label><br />
					<label><input type="radio" id="league-info-numplayers" name="league-info-info" value="numplayers" /> number of players</label><br />
				</td>
			</tr>
			<tr class="tr-league-info atts">
				<td>
					<label for="league-info-ranking-id"><?php _e( 'Select a ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="league-info-ranking-id">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'all scores', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a user defined ranking', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php
							ranking_options();
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="tr-league-info atts">
				<td>
					<label for="league-info-format"><a href="//php.net/manual/en/function.sprintf.php" target="_blank"><?php _e( 'Format', FOOTBALLPOOL_TEXT_DOMAIN ); ?></a></label>
				</td>
				<td>
					<input type="text" id="league-info-format" />
				</td>
			</tr>
			</table>
		</div>
		
		<!-- panel -->
		<div id="options_panel" class="panel"><br />
			<table class="dialog-table">
			<tr class="shortcode-select">
				<td><label for="shortcode"><?php _e( 'Select a shortcode', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label></td>
				<td>
					<select id="shortcode" class="shortcode">
						<option value="fp-jokermultiplier"><?php _e( 'Joker multiplier', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
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
			<tr class="shortcode-select">
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
			<tr class="shortcode-select">
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
					<select id="count-match" style="width:320px">
						<optgroup label="<?php _e( 'default', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<option value="0" selected="selected"><?php _e( 'first match', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'or choose a match', FOOTBALLPOOL_TEXT_DOMAIN ); ?>">
							<?php match_options(); ?>
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
					<br />
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
			<tr class="tr-count">
				<td>
					<label for="count-format"><?php _e( 'Time format', FOOTBALLPOOL_TEXT_DOMAIN ); ?></label>
				</td>
				<td>
					<select id="count-format">
						<option value="2"><?php _e( 'days, hours, minutes, seconds', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="3"><?php _e( 'hours, minutes, seconds', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
						<option value="1"><?php _e( 'only seconds', FOOTBALLPOOL_TEXT_DOMAIN ); ?></option>
					</select>
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
			<input type="submit" id="insert" name="insert" value="<?php _e( 'Insert', FOOTBALLPOOL_TEXT_DOMAIN ); ?>" onclick="footballpool_tinymce_insert_shortcode();" />
		</div>
	</div>

</form>
</body>
</html>