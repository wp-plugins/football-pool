<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );
require_once 'class-football-pool-admin.php';

check_admin_referer( FOOTBALLPOOL_NONCE_SCORE_CALC );
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="../assets/admin/jquery-ui/css/start/jquery-ui-1.10.0.custom.min.css">
	<link rel="stylesheet" href="../assets/admin/calculate-score-history.css">
	<script src="../assets/admin/jquery-ui/js/jquery-1.9.0.js"></script>
	<script src="../assets/admin/jquery-ui/js/jquery-ui-1.10.0.custom.min.js"></script>
	<script> $( parent.document ).find( "#cboxClose" ).hide(); </script>
</head>
<body>
<?php
global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;
$pool = new Football_Pool_Pool;
$params = array();
$check = true;
$nonce = wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC );

//@todo: fix problem of multiple recalcs on options page. add recalc-all option?

// get step number and other parameters
$step = Football_Pool_Utils::get_int( 'step', 0 );
$sub_step = Football_Pool_Utils::get_int( 'sub_step', 1 );
$progress = Football_Pool_Utils::get_int( 'progress', 1 );
$user_set = Football_Pool_Utils::get_int( 'user_set', 0 );
$total_user_sets = Football_Pool_Utils::get_int( 'total_user_sets', 0 );
$total_users = Football_Pool_Utils::get_int( 'total_users', 0 );
$total_steps = Football_Pool_Utils::get_int( 'total_steps', 0 );

// is this a single ranking calculation?
$ranking_id = Football_Pool_Utils::get_int( 'single_ranking', 0 );
$is_single_ranking = ( $ranking_id > 0 );
if ( ! $is_single_ranking ) {
	$ranking_id = Football_Pool_Utils::get_int( 'ranking', FOOTBALLPOOL_RANKING_DEFAULT );
} elseif ( $step > 0 ) {
	// get ranking matches and ranking questions to narrow the results
	$ranking_matches = $pool->get_ranking_matches( $ranking_id );
	if ( $ranking_matches == null ) {
		$ranking_matches = '0';
	} else {
		$ids = array();
		foreach ( $ranking_matches as $key => $val ) {
			$ids[] = $val['match_id'];
		}
		$ranking_matches = implode( ',', $ids );
	}
	
	$ranking_questions = $pool->get_ranking_questions( $ranking_id );
	if ( $ranking_questions == null ) {
		$ranking_questions = '0';
	} else {
		$ids = array();
		foreach ( $ranking_questions as $key => $val ) {
			$ids[] = $val['question_id'];
		}
		$ranking_questions = implode( ',', $ids );
	}
}

if ( $total_user_sets > 0 ) {
	$from = ( $user_set * FOOTBALLPOOL_RECALC_STEP5_DIV ) + 1;
	$to = ( ( $user_set + 1 ) * FOOTBALLPOOL_RECALC_STEP5_DIV );
	if ( $to > $total_users ) $to = $total_users;
	$user_batch = sprintf( __( '(users %d - %d of %d)', FOOTBALLPOOL_TEXT_DOMAIN )
							, $from, $to, $total_users
					);
} else {
	$user_batch = '';
}

$step_string = '';
if ( in_array( $step, array( 2, 3, 4, 6 ) ) ) {
	$step_string = sprintf( __( '(step %d)', FOOTBALLPOOL_TEXT_DOMAIN ), $sub_step );
}
// steps:
$msg = array();
$msg[] = __( 'empty ranking table', FOOTBALLPOOL_TEXT_DOMAIN );
$msg[] = sprintf( __( 'check user predictions with actual results %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $step_string );
$msg[] = sprintf( __( 'update score with points %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $step_string );
$msg[] = sprintf( __( 'add bonus question points %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $step_string );
$msg[] = sprintf( __( 'ranking %d: update total score incrementally %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id, $user_batch
			);
$msg[] = sprintf( __( 'ranking %d: update ranking for users %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id, $step_string
			);
$msg[] = sprintf( __( 'ranking %d: calculate user ranking %s', FOOTBALLPOOL_TEXT_DOMAIN )
				, $ranking_id, $step_string
			);
$msg[] = sprintf( '<strong>%s</strong>', __( 'score (re)calculation finished', FOOTBALLPOOL_TEXT_DOMAIN ) );

printf( '<h2>%s</h2>', __( 'Score (re)calculation', FOOTBALLPOOL_TEXT_DOMAIN ) );

if ( $step > 0 ) {
	if ( $total_steps == 0 ) {
		// determine total calculation steps (sub steps are not counted)
		if ( $is_single_ranking ) {
			// only one loop through the steps, no pre-calculation of the default ranking
			$rankings = 0;
		} else {
			// get number of unique ranking ids from update log
			$sql = "SELECT COUNT( * ) FROM {$prefix}rankings r
					JOIN {$prefix}rankings_updatelog l
						ON ( r.id = l.ranking_id )
					WHERE r.user_defined = 1
					GROUP BY r.id";
			$rankings = $wpdb->get_var( $sql );
		}
		
		$users = get_users( 'orderby=ID&order=ASC' );
		$total_users = count( $users );
		$total_user_sets = ceil( $total_users / FOOTBALLPOOL_RECALC_STEP5_DIV ) - 1;
		$total_steps = count( $msg ) + ( $rankings * 3 )
						+ ( ( $rankings + 1 ) * $total_user_sets );
	}

	// print status messages
	printf( '<h3>%s</h3>', __( 'Please do not interrupt this process.', FOOTBALLPOOL_TEXT_DOMAIN ) );
	printf( '<p>%s</p>', __( 'Sit back and relax, this may take a while :-)', FOOTBALLPOOL_TEXT_DOMAIN ) );
	echo '<div id="progressbar"></div>';
	echo "<script>
			$( '#progressbar' ).progressbar({
				max: {$total_steps},
				value: {$progress}
			});
			</script>";
	printf( '<p>%s...</p>', $msg[$step - 1] );
}

// just for fun ;-)
// $img_dir = FOOTBALLPOOL_ASSETS_URL . 'admin/images';
// printf( '<p class="animation"><img src="%s/recalc-animation-%d.gif" width="160" /></p>'
		// , $img_dir
		// , 1 
// );

// calculation steps
switch ( $step ) {
	case 0:
		$acknowledge = Football_Pool_Utils::get_string( 'acknowledge' );
		if ( $acknowledge == 'yes' ) {
			$params['step'] = 1;
		} else {
			echo '<p>sure?</p>';
			echo '<p>';
			Football_Pool_Admin::secondary_button( 
										__( 'Yes', FOOTBALLPOOL_TEXT_DOMAIN ), 
										"?acknowledge=yes&_wpnonce={$nonce}", 
										false, 
										'js-button' 
									);
			Football_Pool_Admin::secondary_button( 
										__( 'No', FOOTBALLPOOL_TEXT_DOMAIN ), 
										array( '', 'parent.jQuery.fn.colorbox.close()' ), 
										false, 
										'js-button' 
									);
			echo '</p>';
		}
		break;
	case 1:
		// empty table
		if ( $is_single_ranking ) {
			$check = Football_Pool_Admin::empty_scorehistory( $ranking_id );
		} else {
			$check = Football_Pool_Admin::empty_scorehistory( 'all' );
		}
		
		$params['step'] = 2;
		break;
	case 2:
		// check predictions with actual match result (score type = 0)
		$sql = "INSERT INTO {$prefix}scorehistory
										( type, score_date, score_order, user_id
										, score, full, toto, goal_bonus
										, ranking, ranking_id )
				SELECT 
					%d AS score_type, m.play_date AS score_date, m.id AS match_id, u.ID AS user_id, 
					IF ( p.has_joker = 1, 2, 1 ) AS score,
					IF ( m.home_score = p.home_score AND m.away_score = p.away_score, 1, NULL ) AS full,
					IF ( m.home_score = p.home_score AND m.away_score = p.away_score, NULL, 
						IF (
							IF ( m.home_score > m.away_score, 1, IF ( m.home_score = m.away_score, 3, 2 ) )
							=
							IF ( p.home_score > p.away_score, 1, IF ( p.home_score = p.away_score, 3, 2 ) )
							, IF ( p.home_score IS NULL OR p.away_score IS NULL, NULL, 1 )
							, NULL 
							)
					) AS toto,
					IF ( m.home_score = p.home_score, 
							IF ( m.away_score = p.away_score, 2, 1 ),
							IF ( m.away_score = p.away_score, 1, NULL )
					) AS goal_bonus,
					0 AS ranking,
					%d AS ranking_id
				FROM {$wpdb->users} u ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}matches m ON ( 1 = 1 )
				LEFT OUTER JOIN {$prefix}predictions p
					ON ( p.match_id = m.id AND ( p.user_id = u.ID OR p.user_id IS NULL ) )
				WHERE m.home_score IS NOT NULL AND m.away_score IS NOT NULL ";
		if ( ! $pool->has_leagues ) $sql .= "AND ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		if ( $is_single_ranking ) $sql .= "AND m.id IN ( " . $ranking_matches . " ) ";
		$sql .= "ORDER BY 1, 2, 3, 4 LIMIT %d, %d";
		
		$offset = FOOTBALLPOOL_RECALC_STEP2_DIV * ( $sub_step - 1 );
		
		$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_MATCH, FOOTBALLPOOL_RANKING_DEFAULT
									, $offset, FOOTBALLPOOL_RECALC_STEP2_DIV );
		$result = $wpdb->query( $sql );			
		$check = ( $result !== false );
		
		if ( $result > 0 ) {
			$params['step'] = 2;
			$sub_step++;
		} else {
			$sub_step = 1;
			$params['step'] = 3;
		}
		break;
	case 3:
		// update score for matches
		$offset = FOOTBALLPOOL_RECALC_STEP3_DIV * ( $sub_step - 1 );
		$full = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
		$toto = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
		$goal = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
		$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory 
								SET score = score * ( ( full * {$full} ) 
											+ ( toto * {$toto} ) 
											+ ( goal_bonus * {$goal} ) ) 
								WHERE type = %d AND ranking_id = %d 
								AND user_id >= %d AND user_id < %d
								ORDER BY type ASC, score_date ASC, score_order ASC, user_id ASC"
								, FOOTBALLPOOL_TYPE_MATCH, FOOTBALLPOOL_RANKING_DEFAULT
								, $offset, ( $offset + FOOTBALLPOOL_RECALC_STEP3_DIV ) );
		$result = $wpdb->query( $sql );
		$check = ( $result !== false );
		
		if ( $result > 0 ) {
			$params['step'] = 3;
			$sub_step++;
		} else {
			$sub_step = 1;
			$params['step'] = 4;
		}
		break;
	case 4:
		// add bonusquestion scores (score type = 1)
		// make sure to take the userpoints into account (we can set an alternate score for an 
		// individual user in the admin)
		$sql = "INSERT INTO {$prefix}scorehistory 
					( type, score_date, score_order, user_id, 
					  score, full, toto, goal_bonus, ranking, ranking_id ) 
				SELECT 
					%d AS score_type, q.score_date AS score_date, q.id AS question_id,
					u.ID AS user_id, 
					IF ( a.points <> 0, a.points, q.points ) * IFNULL( a.correct, 0 ) AS score, 
					NULL, NULL, NULL, 
					0 AS ranking, %d AS ranking_id 
				FROM {$wpdb->users} u ";
		if ( $pool->has_leagues ) {
			$sql .= "INNER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
			$sql .= "INNER JOIN {$prefix}leagues l ON ( lu.league_id = l.id ) ";
		} else {
			$sql .= "LEFT OUTER JOIN {$prefix}league_users lu ON ( lu.user_id = u.ID ) ";
		}
		$sql .= "LEFT OUTER JOIN {$prefix}bonusquestions q
					ON ( 1 = 1 )
				LEFT OUTER JOIN {$prefix}bonusquestions_useranswers a 
					ON ( a.question_id = q.id AND ( a.user_id = u.ID OR a.user_id IS NULL ) )
				WHERE q.score_date IS NOT NULL ";
		if ( ! $pool->has_leagues ) $sql .= "AND ( lu.league_id <> 0 OR lu.league_id IS NULL ) ";
		if ( $is_single_ranking ) $sql .= "AND q.id IN ( " . $ranking_questions . " ) ";
		$sql .= "ORDER BY 1, 2, 3, 4 LIMIT %d, %d";
		
		$offset = FOOTBALLPOOL_RECALC_STEP4_DIV * ( $sub_step - 1 );
		
		$sql = $wpdb->prepare( $sql, FOOTBALLPOOL_TYPE_QUESTION, FOOTBALLPOOL_RANKING_DEFAULT
									, $offset, FOOTBALLPOOL_RECALC_STEP4_DIV );
		$result = $wpdb->query( $sql );			
		$check = ( $result !== false );
		
		if ( $result > 0 ) {
			$params['step'] = 4;
			$sub_step++;
		} else {
			$sub_step = 1;
			$params['step'] = 5;
		}
		break;
	case 5:
		// update score incrementally once for every ranking, start with the default one
		if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
			$sql_user_scores = sprintf( "SELECT * FROM {$prefix}scorehistory 
										WHERE user_id = %%d AND ranking_id = %d
										ORDER BY score_date ASC, type ASC, score_order ASC"
										, $ranking_id
								);
		} else {
			$sql_user_scores = sprintf( "SELECT s.* FROM {$prefix}scorehistory s
										LEFT OUTER JOIN {$prefix}rankings_matches rm
										  ON ( s.score_order = rm.match_id 
												AND rm.ranking_id = %d AND s.type = %d )
										LEFT OUTER JOIN {$prefix}rankings_bonusquestions rq
										  ON ( s.score_order = rq.question_id 
												AND rq.ranking_id = %d AND s.type = %d )
										WHERE s.user_id = %%d AND s.ranking_id = %d 
										AND ( rm.ranking_id IS NOT NULL OR rq.ranking_id IS NOT NULL )
										ORDER BY score_date ASC, type ASC, score_order ASC"
										, $ranking_id, FOOTBALLPOOL_TYPE_MATCH
										, $ranking_id, FOOTBALLPOOL_TYPE_QUESTION
										, FOOTBALLPOOL_RANKING_DEFAULT
								);
		}
		
		// cumulate scores for each user
		$offset = $user_set * FOOTBALLPOOL_RECALC_STEP5_DIV;
		$number = FOOTBALLPOOL_RECALC_STEP5_DIV;
		$users = get_users( "orderby=ID&order=ASC&offset={$offset}&number={$number}" );
		
		foreach ( $users as $user ) {
			$sql = $wpdb->prepare( $sql_user_scores, $user->ID );
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory 
									WHERE user_id = %d AND ranking_id = %d", $user->ID, $ranking_id );
			$result = $wpdb->query( $sql );
			$check = ( $result !== false ) && $check;
			
			$score = 0;
			foreach ( $rows as $row ) {
				$score += $row['score'];
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}scorehistory 
											( type, score_date, score_order, user_id, 
											  score, full, toto, goal_bonus, total_score, 
											  ranking, ranking_id ) 
										VALUES 
											( %d, %s, %d, 
											  %d, %d, 
											  %d, %d, %d, 
											  %d, 0, %d )",
										$row['type'], $row['score_date'], $row['score_order'], 
										$row['user_id'], $row['score'], 
										$row['full'], $row['toto'], $row['goal_bonus'], 
										$score, $ranking_id
								);
				$result = $wpdb->query( $sql );
				$check = ( $result !== false ) && $check;
			}
		}
		
		// repeat step until there are no more users
		$params['step'] = ( $user_set == $total_user_sets ) ? 6 : 5;
		
		$params['ranking'] = $ranking_id;
		$params['user_set'] = ++$user_set;
		break;
	case 6:
		// update ranking order for users
		$offset = FOOTBALLPOOL_RECALC_STEP6_DIV * ( $sub_step - 1 );
		$sql = $wpdb->prepare( "SELECT score_date, type FROM {$prefix}scorehistory 
								WHERE ranking_id = %d GROUP BY score_date, type
								LIMIT %d, %d"
								, $ranking_id
								, $offset, FOOTBALLPOOL_RECALC_STEP6_DIV );
		$ranking_dates = $wpdb->get_results( $sql, ARRAY_A );
		
		if ( is_array( $ranking_dates ) && count( $ranking_dates ) > 0 ) {
			$params['step'] = 6;
			$sub_step++;
			
			foreach ( $ranking_dates as $ranking_date ) {
				$sql = $pool->get_ranking_from_score_history( 0, $ranking_id, 
																$ranking_date['score_date'] 
															);
				$ranking_result = $wpdb->get_results( $sql, ARRAY_A );
				$rank = 1;
				foreach ( $ranking_result as $ranking_row ) {
					$sql = $wpdb->prepare( "UPDATE {$prefix}scorehistory SET ranking = %d 
											WHERE user_id = %d AND type = %d AND score_date = %s 
											AND ranking_id = %d"
											, $rank++
											, $ranking_row['user_id']
											, $ranking_date['type']
											, $ranking_date['score_date']
											, $ranking_id
									);
					$result = $wpdb->query( $sql );
					$check = ( $result !== false ) && $check;
				}
			}
		} else {
			$sub_step = 1;
			$params['step'] = 7;
			// this ranking is finished, so clear the update log for this ranking
			if ( $check === true ) {
				$sql = $wpdb->prepare( "DELETE FROM {$prefix}rankings_updatelog 
										WHERE ranking_id = %d", $ranking_id );
				$wpdb->query( $sql );
			}
		}
		
		$params['ranking'] = $ranking_id;
		break;
	case 7:
		if ( $is_single_ranking ) {
			$ranking_id = null;
		} else {
			// handle user defined rankings
			// only process rankings that have changes logged
			if ( $ranking_id == FOOTBALLPOOL_RANKING_DEFAULT ) {
				$sql = "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r
						JOIN {$prefix}rankings_updatelog l ON ( r.id = l.ranking_id )
						WHERE r.user_defined = 1
						ORDER BY r.id ASC LIMIT 1";
			} else {
				$sql = $wpdb->prepare( "SELECT DISTINCT( r.id ) AS id FROM {$prefix}rankings r
										JOIN {$prefix}rankings_updatelog l ON ( r.id = l.ranking_id )
										WHERE r.user_defined = 1 AND r.id > %d
										ORDER BY r.id ASC LIMIT 1"
										, $ranking_id
								);
			}
			$ranking_id = $wpdb->get_var( $sql );
		}
		// back to step 5 in case there are rankings left to be calculated
		// (and not in single ranking mode), otherwise (re)calculation is finished.
		$params['step'] = ( $ranking_id != null ) ? 5 : 8;
		$params['ranking'] = $ranking_id;
		break;
}

$js = '<script type="text/javascript">%s</script>';
$close_calculation = '$( parent.document ).find( "#cboxClose" ).show();';

if ( $check === true ) {
	if ( count( $params ) > 0 ) {
		if ( $sub_step == 1 ) $progress++;
		$params['progress'] = $progress;
		$params['sub_step'] = $sub_step;
		$params['total_steps'] = $total_steps;
		$params['total_user_sets'] = $total_user_sets;
		$params['total_users'] = $total_users;
		if ( $is_single_ranking ) $params['single_ranking'] = $ranking_id;
		$params['_wpnonce'] = $nonce;
		$url = add_query_arg( $params, $_SERVER['PHP_SELF'] );
		printf( $js, sprintf( 'location.href = "%s";', $url ) );
	} else {
		// last step finished or step 0
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