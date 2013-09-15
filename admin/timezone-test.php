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
		$match_id = isset( $match['id'] ) ? $match['id'] : $match['nr'];
		$option_text = sprintf( '%d: %s - %s (%s)'
								, $match_id
								, $match['home_team']
								, $match['away_team']
								, Football_Pool_Utils::date_from_gmt( $match['date'] )
						);
		printf( '<option value="%d">%s</option>', $match_id, $option_text );
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
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="../assets/admin/ZeroClipboard/ZeroClipboard.min.js"></script>
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
		<option value="">select a match</option>
		<?php match_options(); ?>
	</select>
</p>
<p>Or, select a question:</p>
<p>
	<select name="question">
		<option valeu="">select a question</option>
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
			NOW() AS mysql_now,
			@@global.time_zone AS mysql_global_timezone,
			@@session.time_zone AS mysql_session_timezone";
$row = $wpdb->get_row( $sql, ARRAY_A );
$mysql_datetime = $row['mysql_utc_ts'];
$mysql_timestamp = $row['mysql_unix_ts'];
$mysql_now = $row['mysql_now'];
$mysql_global_timezone = $row['mysql_global_timezone'];
$mysql_session_timezone = $row['mysql_session_timezone'];

// Match info
if ( $match > 0 ) {
	$match = $matches->get_match_info( $match );
	if ( count( $match ) > 0 ) {
		$match_id = isset( $match['id'] ) ? $match['id'] : $match['nr'];
		$match_date = isset( $match['play_date'] ) ? $match['play_date'] : $match['playDate'];
		$match_info = "({$match_id}) {$match['home_team']} - {$match['away_team']}";

		$datetime = Football_Pool_Utils::get_fp_option( 'matches_locktime', '' );
		if ( Football_Pool_Utils::get_fp_option( 'stop_time_method_matches', 0, 'int' ) == 1 
				&& $datetime != '' ) {
			$lock_date = new DateTime( Football_Pool_Utils::date_from_gmt( $datetime ) );
		} else {
			$lock_date = new DateTime( Football_Pool_Utils::date_from_gmt( $match_date ) );
			$offset = Football_Pool_Utils::get_fp_option( 'maxperiod', FOOTBALLPOOL_MAXPERIOD, 'int' );
			$lock_date->modify( '-' . $offset . ' seconds' );
		}
		$lock_date = $lock_date->format( 'Y-m-d H:i' );

		debug_line( 'Match', $match_info );
		debug_line( 'Match date (database, should be UTC)', $match_date );
		debug_line( 'Match date (local)'
					, $matches->format_match_time( new DateTime( $match_date ), 'Y-m-d H:i' ) );
		debug_line( 'Match timestamp (database, should be UTC)', $match['match_timestamp'] );
		debug_line( 'Match is locked', ( ! $match['match_is_editable'] ? 'true' : 'false' ) );
		debug_line( 'Match was/will be locked at time (local)', $lock_date );
	}
}
// Question info
if ( $question > 0 ) {
	$question = $pool->get_bonus_question_info( $question );
	if ( isset( $question['id'] ) ) {
		$question_info = "({$question['id']}) {$question['question']}";
		
		if ( Football_Pool_Utils::get_fp_option( 'stop_time_method_questions', 0, 'int' ) == 1 ) {
			$lock_date = new DateTime( Football_Pool_Utils::date_from_gmt( Football_Pool_Utils::get_fp_option( 'bonus_question_locktime', '' ) ) );
		} else {
			$lock_date = new DateTime( Football_Pool_Utils::date_from_gmt( $question['answer_before_date'] ) );
		}
		$lock_date = $lock_date->format( 'Y-m-d H:i' );

		debug_line( 'Question', $question_info );
		debug_line( 'Question date (database, should be UTC)', $question['answer_before_date'] );
		debug_line( 'Question date (local)', Football_Pool_Utils::date_from_gmt( $question['answer_before_date'] ) );
		debug_line( 'Question timestamp (UTC)', $question['question_timestamp'] );
		debug_line( 'Question is locked', ( ! $question['question_is_editable'] ? 'true' : 'false' ) );
		debug_line( 'Question was/will be locked at time (local)', $lock_date );
	}
}
// WordPress
debug_line( 'WordPress timezone offset', get_option( 'gmt_offset' ) );
debug_line( 'WordPress timezone string', get_option( 'timezone_string' ) );
debug_line( 'WordPress current date (local)', current_time( 'mysql' ) );
debug_line( 'WordPress current timestamp (local)', current_time( 'timestamp' ) );
debug_line( 'WordPress current date (UTC)', current_time( 'mysql', true ) );
debug_line( 'WordPress current timestamp (UTC)', current_time( 'timestamp', true ) );
// Plugin
debug_line( 'Plugin prediction stop method matches', get_fp_option( 'stop_time_method_matches' ) );
debug_line( 'Plugin dynamic stop threshold (in seconds) for matches', get_fp_option( 'maxperiod' ) );
debug_line( 'Plugin prediction stop date for matches', get_fp_option( 'matches_locktime' ) );
debug_line( 'Plugin prediction stop method questions', get_fp_option( 'stop_time_method_questions' ) );
debug_line( 'Plugin match time display setting', get_fp_option( 'match_time_display' ) );
// PHP/web server
debug_line( 'PHP current date and time (UTC)', $date->format( 'Y-m-d H:i' ) );
debug_line( 'PHP current timestamp (UTC, time())', time() );
debug_line( 'PHP current timestamp (UTC, date-&gt;format("U"))', $date->format( 'U' ) );
debug_line( 'PHP default timezone setting', date_default_timezone_get() );
// MySQL/database server
debug_line( 'MySQL current date and time (UTC)', $mysql_datetime );
debug_line( 'MySQL current timestamp (UTC)', $mysql_timestamp );
debug_line( 'MySQL current date and time (local)', $mysql_now );
debug_line( 'MySQL global timezone setting', $mysql_global_timezone );
debug_line( 'MySQL session timezone setting', $mysql_session_timezone );
?>
</pre>
<input type="button" id="copy-to-clipboard-button" data-clipboard-target="debug-info" value="Copy To Clipboard">
<?php endif; ?>
</form>

<script>
$( document ).ready( function() {
	try {
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
	} catch( err ) {}
} );
</script>
</body>
</html>
