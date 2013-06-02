<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );
require_once 'class-football-pool-admin.php';

check_admin_referer( FOOTBALLPOOL_NONCE_SCORE_CALC );

// alles uit totaalqueries halen, via losse queries
// sets serializen (json_encode/decode) en in options table, array poppen/shiften tot volgende stap
// berekening voor 1 ranking ondersteunen
// in ranking-table bijhouden of een ranking moet worden bijgewerkt (save-actie bij matches, queries, users)
// 
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="../assets/admin/jquery-ui/css/start/jquery-ui-1.10.0.custom.min.css">
	<script src="../assets/admin/jquery-ui/js/jquery-1.9.0.js"></script>
	<script src="../assets/admin/jquery-ui/js/jquery-ui-1.10.0.custom.min.js"></script>
	<script> jQuery( parent.document ).find( "#cboxClose" ).hide(); </script>
	<style>
	body {
		margin: 0;
		padding: 0;
		color: #333;
		font-family: sans-serif;
		font-size: 12px;
		line-height: 1.4em;
	}
	h2 {
		color: #464646;
		font-family: "HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",sans-serif;
		font-size: 23px;
		padding: 9px 15px 4px 0;
		line-height: 29px;
		margin; 0;
		font-weight: normal;
		text-shadow: #fff 0 1px 0;
	}
	p {
		margin: 0;
		padding: 0;
	}
	div.updated, div.error {
		margin: 5px 0 15px;
		padding: 0 .6em;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		border-width: 1px;
		border-style: solid;
		outline: 0;
	}
	div.updated {
		background-color: #ffffe0;
		border-color: #e6db55;
	}
	div.error {
		background-color: #ffebe8;
		border-color: #c00;
	}
	div.updated p, div.error p {
		margin: .5em 0;
		padding: 2px;
	}
	.ui-progressbar {
		height: 15px;
	}
	</style>
</head>
<body>
<?php
global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;
$pool = new Football_Pool_Pool;
$params = array();
$check = true;

// get step number and other parameters
$step = Football_Pool_Utils::get_int( 'step', 1 );
$progress = Football_Pool_Utils::get_int( 'progress', 1 );
$ranking_id = Football_Pool_Utils::get_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
$user_set = Football_Pool_Utils::get_int( 'user_set', 0 );
$total_user_sets = Football_Pool_Utils::get_int( 'total_user_sets', 0 );
$total_users = Football_Pool_Utils::get_int( 'total_users', 0 );
$total_steps = Football_Pool_Utils::get_int( 'total_steps', 0 );

if ( $total_user_sets > 0 ) {
	$from = ( $user_set * FOOTBALLPOOL_RECALC_USER_DIV ) + 1;
	$to = ( ( $user_set + 1 ) * FOOTBALLPOOL_RECALC_USER_DIV );
	if ( $to > $total_users ) $to = $total_users;
	$user_batch = sprintf( __( '(users %d - %d of %d)', FOOTBALLPOOL_TEXT_DOMAIN ), $from, $to, $total_users );
} else {
	$user_batch = '';
}

// steps:
$msg = array();
$msg[] = __( 'empty ranking table', FOOTBALLPOOL_TEXT_DOMAIN );
$msg[] = __( 'check user predictions with actual results', FOOTBALLPOOL_TEXT_DOMAIN );
$msg[] = __( 'update score with points', FOOTBALLPOOL_TEXT_DOMAIN );
$msg[] = __( 'add bonus question points', FOOTBALLPOOL_TEXT_DOMAIN );
$msg[] = sprintf( __( 'ranking %d: update total score incrementally %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id, $user_batch
			);
$msg[] = sprintf( __( 'ranking %d: update ranking for users', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id
			);
$msg[] = sprintf( __( 'ranking %d: calculate user ranking', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id
			);
$msg[] = sprintf( '<strong>%s</strong>', __( 'score (re)calculation finished', FOOTBALLPOOL_TEXT_DOMAIN ) );

if ( $total_steps == 0 ) {
	// determine total calculation steps
	$users = get_users( 'orderby=ID&order=ASC' );
	$sql = "SELECT COUNT( * ) FROM {$prefix}rankings WHERE user_defined = 1 ORDER BY id DESC";
	$rankings = $wpdb->get_var( $sql );
	
	$total_users = count( $users );
	$total_user_sets = ceil( $total_users / FOOTBALLPOOL_RECALC_USER_DIV ) - 1;
	$total_steps = count( $msg ) + ( $rankings * 3 )
					+ ( ( $rankings + 1 ) * $total_user_sets );
}

// print status messages
printf( '<h2>%s</h2>', __( 'Score (re)calculation', FOOTBALLPOOL_TEXT_DOMAIN ) );
printf( '<h3>%s</h3>', __( 'Please do not interrupt this process.', FOOTBALLPOOL_TEXT_DOMAIN ) );
echo '<div id="progressbar"></div>';
echo "<script>
		$( '#progressbar' ).progressbar({
			max: {$total_steps},
			value: {$progress}
		});
		</script>";
printf( '<p>%s...</p>', $msg[$step - 1] );

// calculation steps
switch ( $step ) {
	case 1:
		// empty table
		$check = Football_Pool_Admin::empty_table( 'scorehistory' );
		
		$params['step'] = 2;
		break;
	case 2:
		// check predictions with actual match result (score type = 0)
		$sql = "INSERT INTO {$prefix}scorehistory
					( type, scoreDate, scoreOrder, userId, score, full, toto, goal_bonus
					, ranking, ranking_id ) 
				SELECT 
					%d, m.playDate, m.nr, u.ID, 
					IF ( p.hasJoker = 1, 2, 1 ) AS score,
					IF ( m.homeScore = p.homeScore AND m.awayScore = p.awayScore, 1, NULL ) AS full,
					IF ( m.homeScore = p.homeScore AND m.awayScore = p.awayScore, NULL, 
						IF (
							IF ( m.homeScore > m.awayScore, 1, IF ( m.homeScore = m.awayScore, 3, 2 ) )
							=
							IF ( p.homeScore > p.awayScore, 1, IF (p.homeScore = p.awayScore, 3, 2) )
							, IF ( p.homeScore IS NULL OR p.awayScore IS NULL, NULL, 1 )
							, NULL 
							)
					) AS toto,
					IF ( m.homeScore = p.homeScore, 
							IF ( m.awayScore = p.awayScore, 2, 1 ),
							IF ( m.awayScore = p.awayScore, 1, NULL )
					) AS goal_bonus,
					0,
					%d
				FROM {$wpdb->users} u ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}matches m ON ( 1 = 1 )
				LEFT OUTER JOIN {$prefix}predictions p
					ON ( p.matchNr = m.nr AND ( p.userId = u.ID OR p.userId IS NULL ) )
				WHERE m.homeScore IS NOT NULL AND m.awayScore IS NOT NULL ";
		if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_MATCH, FOOTBALLPOOL_RANKING_DEFAULT );
		$result = $wpdb->query( $sql );
		$check = ( $result !== false );
		
		$params['step'] = 3;
		break;
	case 3:
		// update score for matches
		$full = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
		$toto = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
		$goal = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
		$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory 
								SET score = score * ( ( full * {$full} ) 
											+ ( toto * {$toto} ) 
											+ ( goal_bonus * {$goal} ) ) 
								WHERE type = %d AND ranking_id = %d"
								, FOOTBALLPOOL_TYPE_MATCH, FOOTBALLPOOL_RANKING_DEFAULT );
		$result = $wpdb->query( $sql );
		$check = ( $result !== false );
		
		$params['step'] = 4;
		break;
	case 4:
		// add bonusquestion scores (score type = 1)
		// make sure to take the userpoints into account (we can set an alternate score for an 
		// individual user in the admin)
		$sql = "INSERT INTO {$prefix}scorehistory 
					( type, scoreDate, scoreOrder, userId, 
					  score, full, toto, goal_bonus, ranking, ranking_id ) 
				SELECT 
					%d, q.scoreDate, q.id, u.ID, 
					( IF ( a.points <> 0, a.points, q.points ) * IFNULL( a.correct, 0 ) ), NULL, NULL, NULL, 
					0, %d 
				FROM {$wpdb->users} u ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.leagueId = l.ID ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.userId = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}bonusquestions q
					ON ( 1 = 1 )
				LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a 
					ON ( a.questionId = q.id AND ( a.userId = u.ID OR a.userId IS NULL ) )
				WHERE q.scoreDate IS NOT NULL ";
		if ( ! $pool->has_leagues ) $sql .= "AND ( lu.leagueId <> 0 OR lu.leagueId IS NULL ) ";
		$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_QUESTION, FOOTBALLPOOL_RANKING_DEFAULT );
		$result = $wpdb->query( $sql );
		$check = ( $result !== false );
		
		$params['step'] = 5;
		break;
	case 5:
		// update score incrementally once for every ranking, start with the default one
		if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
			$sql_user_scores = sprintf( "SELECT * FROM {$prefix}scorehistory 
										WHERE userId = %%d AND ranking_id = %d
										ORDER BY scoreDate ASC, type ASC, scoreOrder ASC"
										, $ranking_id
								);
		} else {
			$sql_user_scores = sprintf( "SELECT s.* FROM {$prefix}scorehistory s
										LEFT OUTER JOIN {$prefix}rankings_matches rm
										  ON ( s.scoreOrder = rm.match_id 
												AND rm.ranking_id = %d AND s.type = %d )
										LEFT OUTER JOIN {$prefix}rankings_bonusquestions rq
										  ON ( s.scoreOrder = rq.question_id 
												AND rq.ranking_id = %d AND s.type = %d )
										WHERE s.userId = %%d AND s.ranking_id = %d 
										AND ( rm.ranking_id IS NOT NULL OR rq.ranking_id IS NOT NULL )
										ORDER BY scoreDate ASC, type ASC, scoreOrder ASC"
										, $ranking_id, FOOTBALLPOOL_TYPE_MATCH
										, $ranking_id, FOOTBALLPOOL_TYPE_QUESTION
										, FOOTBALLPOOL_RANKING_DEFAULT
								);
		}
		
		// cumulate scores for each user
		$offset = $user_set * FOOTBALLPOOL_RECALC_USER_DIV;
		$number = FOOTBALLPOOL_RECALC_USER_DIV;
		$users = get_users( "orderby=ID&order=ASC&offset={$offset}&number={$number}" );
		
		foreach ( $users as $user ) {
			$sql = $wpdb->prepare( $sql_user_scores, $user->ID );
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory 
									WHERE userId = %d AND ranking_id = %d", $user->ID, $ranking_id );
			$result = $wpdb->query( $sql );
			$check = ( $result !== false ) && $check;
			
			$score = 0;
			foreach ( $rows as $row ) {
				$score += $row['score'];
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}scorehistory 
											( type, scoreDate, scoreOrder, userId, 
											  score, full, toto, goal_bonus, totalScore, 
											  ranking, ranking_id ) 
										VALUES 
											( %d, %s, %d, %d, 
											  %d, %d, %d, %d, %d, 
											  0, %d )",
										$row['type'], $row['scoreDate'], $row['scoreOrder'], $row['userId'], 
										$row['score'], $row['full'], $row['toto'], $row['goal_bonus'], $score,
										$ranking_id
								);
				$result = $wpdb->query( $sql );
				$check = ( $result !== false ) && $check;
			}
		}
		
		$params['step'] = ( $user_set == $total_user_sets ) ? 6 : 5; // repeat step until there are no more users
		$params['ranking'] = $ranking_id;
		$params['user_set'] = ++$user_set;
		break;
	case 6:
		// update ranking order for users
		$sql = $wpdb->prepare( "SELECT scoreDate, `type` FROM {$prefix}scorehistory 
								WHERE ranking_id = %d GROUP BY scoreDate, `type`"
								, $ranking_id );
		$ranking_dates = $wpdb->get_results( $sql, ARRAY_A );
		
		if ( is_array( $ranking_dates ) ) {
			foreach ( $ranking_dates as $ranking_date ) {
				$sql = $pool->get_ranking_from_score_history( 0, $ranking_id, $ranking_date['scoreDate'] );
				$ranking_result = $wpdb->get_results( $sql, ARRAY_A );
				$rank = 1;
				foreach ( $ranking_result as $ranking_row ) {
					$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory SET ranking = %d 
											WHERE userId = %d AND type = %d AND scoreDate = %s 
											AND ranking_id = %d"
											, $rank++
											, $ranking_row['userId']
											, $ranking_date["type"]
											, $ranking_date['scoreDate']
											, $ranking_id
									);
					$result = $wpdb->query( $sql );
					$check = ( $result !== false ) && $check;
				}
			}
		}
		
		// this ranking is finished, so clear the update log for this ranking
		if ( $check === true ) {
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_updatelog 
									WHERE ranking_id = %d", $ranking_id );
			$wpdb->query( $sql );
		}
		
		$params['step'] = 7;
		$params['ranking'] = $ranking_id;
		break;
	case 7:
		// handle user defined rankings
		// only process rankings that have changes logged
		if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
			$sql = "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r
					JOIN `pool_wp_rankings_updatelog` l ON ( r.id = l.ranking_id )
					WHERE r.user_defined = 1
					ORDER BY r.id ASC LIMIT 1";
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r
									JOIN `pool_wp_rankings_updatelog` l ON ( r.id = l.ranking_id )
									WHERE r.user_defined = 1 AND r.id > %d
									ORDER BY r.id ASC LIMIT 1"
									, $ranking_id
							);
		}
		$ranking_id = $wpdb->get_var( $sql );
		// back to step 5 in case there are rankings left to be calculated, 
		// otherwise (re)calculation is finished.
		$params['step'] = ( $ranking_id != null ) ? 5 : 8;
		$params['ranking'] = $ranking_id;
		break;
}

$js = '<script type="text/javascript">%s</script>';
$close_calculation = 'jQuery( parent.document ).find( "#cboxClose" ).show();';

if ( $check === true ) {
	if ( count( $params ) > 0 ) {
		$params['progress'] = ++$progress;
		$params['total_steps'] = $total_steps;
		$params['total_user_sets'] = $total_user_sets;
		$params['total_users'] = $total_users;
		$params['_wpnonce'] = wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC );
		$url = add_query_arg( $params, $_SERVER['PHP_SELF'] );
		printf( $js, sprintf( 'location.href = "%s";', $url ) );
	} else {
		// last step finished
		printf( $js, $close_calculation );
	}
} else {
	Football_Pool_Admin::notice( sprintf( '%s %d: %s'
											, __( 'Step', FOOTBALLPOOL_TEXT_DOMAIN )
											, ( $params['step'] - 1 )
											, __( 'Something went wrong while (re)calculating the scores. Please check if TRUNCATE/DROP or DELETE rights are available at the database and try again.', FOOTBALLPOOL_TEXT_DOMAIN )
										)
								, 'important' );
	printf( $js, $close_calculation );
}
?>
</body>
</html>