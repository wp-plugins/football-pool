<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );
require_once 'class-football-pool-admin.php';

global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;
$matches = new Football_Pool_Matches();
$pool = new Football_Pool_Pool();
$questions = $pool->get_bonus_questions();

$action = Football_Pool_Utils::get_str( 'action' );
$match = Football_Pool_Utils::get_int( 'match' );
$question = Football_Pool_Utils::get_int( 'question' );

function bonusquestion_options() {
	global $pool;
	$questions = $pool->get_bonus_questions();
	foreach( $questions as $question ) {
		printf( '<option value="%d">%d: %s</option>', $question['id'], $question['id'], $question['question'] );
	}
}

function match_options() {
	global $matches;
	$all_matches = $matches->matches;
	foreach ( $all_matches as $match ) {
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

function get_fp_option( $option ) {
	return Football_Pool_Utils::get_fp_option( $option );
}

function debug_line( $info, $value ) {
	echo "{$info}: {$value}\n";
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<script src="../assets/admin/ZeroClipboard/ZeroClipboard.min.js"></script>
	<script src="../assets/admin/jquery-ui/js/jquery-1.9.0.js"></script>
	<style type="text/css">
	body { font-family: Verdana; }
	#debug-info { border: 1px solid #ccc; padding: .5em; }
	#copy-to-clipboard-button { display: none; }
	</style>
</head>
<body>
<h2>Timezone Debug Info</h2>
<p>This page displays some debugging information about the different time settings of the plugin and de timezone settings of WordPress and the webserver.</p>
<p>More information about the settings on the help page or plugin options page.</p>

<form action="" method="get">
<input type="hidden" name="action" value="test">
<p>Select a match:</p>
<p>
	<select name="match">
		<option>select a match</option>
		<?php match_options(); ?>
	</select>
</p>
<p>Or, select a question:</p>
<p>
	<select name="question">
		<option>select a question</option>
		<?php bonusquestion_options(); ?>
	</select>
</p>

<input type="submit" value="Test">

<?php if ( $action == 'test' ) : ?>
<pre id="debug-info">
<?php
$date = new DateTime();
$sql = "SELECT 
			UTC_TIMESTAMP() AS mysql_utc_ts, 
			UNIX_TIMESTAMP() AS mysql_unix_ts, 
			NOW() AS mysql_now";
$row = $wpdb->get_row( $sql, ARRAY_A );
$mysql_datetime = $row['mysql_utc_ts'];
$mysql_timestamp = $row['mysql_unix_ts'];
$mysql_now = $row['mysql_now'];

if ( $match > 0 ) {
	$match = $matches->get_match_info( $match );
	$match_info = "({$match['id']}) {$match['home_team']} - {$match['away_team']}";
	debug_line( 'Match', $match_info );
	debug_line( 'Match date (database, should be UTC)', $match['play_date'] );
	debug_line( 'Match date (local)', 
				$matches->format_match_time( new DateTime( $match['play_date'] ), 'Y-m-d H:i' ) );
	debug_line( 'Match timestamp (database, should be UTC)', $match['match_timestamp'] );
	debug_line( 'Match is locked', ( ! $match['match_is_editable'] ? 'true' : 'false' ) );
}
if ( $question > 0 ) {
	$question = $pool->get_bonus_question_info( $question );
	$question_info = "({$question['id']}) {$question['question']}";
	debug_line( 'Question', $question_info );
	debug_line( 'Question date (database, should be local)', $question['answer_before_date'] );
	debug_line( 'Question timestamp (database, should be local)', $question['question_timestamp'] );
	debug_line( 'Question is locked', ( ! $question['question_is_editable'] ? 'true' : 'false' ) );
}
debug_line( 'WordPress timezone offset', get_option( 'gmt_offset' ) );
debug_line( 'WordPress current date (local)', current_time( 'mysql' ) );
debug_line( 'WordPress current timestamp (local)', current_time( 'timestamp' ) );
debug_line( 'WordPress current date (UTC)', current_time( 'mysql', true ) );
debug_line( 'WordPress current timestamp (UTC)', current_time( 'timestamp', true ) );
debug_line( 'Plugin prediction stop method matches', get_fp_option( 'stop_time_method_matches' ) );
debug_line( 'Plugin dynamic stop threshold (in seconds) for matches', get_fp_option( 'maxperiod' ) );
debug_line( 'Plugin prediction stop date for matches', get_fp_option( 'matches_locktime' ) );
debug_line( 'Plugin prediction stop method questions', get_fp_option( 'stop_time_method_questions' ) );
debug_line( 'Plugin match time display setting', get_fp_option( 'match_time_display' ) );
debug_line( 'PHP current date and time (UTC)', $date->format( 'Y-m-d H:i' ) );
debug_line( 'PHP current timestamp (UTC)', $date->format( 'U' ) );
debug_line( 'MySQL current date and time (UTC)', $mysql_datetime );
debug_line( 'MySQL current timestamp (UTC)', $mysql_timestamp );
debug_line( 'MySQL current date and time (local)', $mysql_now );
?>
</pre>
<input type="button" id="copy-to-clipboard-button" data-clipboard-target="debug-info" value="Copy To Clipboard">
<?php endif; ?>
</form>

<script>
$( document ).ready( function() {
	var clip = new ZeroClipboard( $( "#copy-to-clipboard-button" ), {
									moviePath: "../assets/admin/ZeroClipboard/ZeroClipboard.swf"
								} );

	clip.on( 'load', function ( client ) {
		$( '#copy-to-clipboard-button' ).show();
	} );
	
	clip.on( 'noFlash', function ( client ) {
		$( '#copy-to-clipboard-button' ).hide();
	} );
	
	clip.on( 'wrongFlash', function ( client, args ) {
		$( '#copy-to-clipboard-button' ).hide();
		// debugstr("Flash 10.0.0+ is required but you are running Flash " + args.flashVersion.replace(/,/g, "."));
	} );
	
	clip.on( 'complete', function ( client, args ) {
		// debugstr("Copied text to clipboard: " + args.text);
		// $( "#debug-info" ).effects( "shake" );
	} );
} );
</script>
</body>
</html>
