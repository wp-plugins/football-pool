<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );

check_admin_referer( FOOTBALLPOOL_NONCE_CSV );

$full_data = ! ( isset( $_GET['format'] ) && $_GET['format'] == 'minimal' );

global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;

if ( $full_data ) {
	$sql = "SELECT
				m.play_date, ht.name AS home_team, at.name AS away_team, 
				COALESCE( s.name, '' ) AS stadium, t.name AS match_type, 
				ht.photo AS home_team_photo, ht.flag AS home_team_flag, 
				ht.link AS home_team_link, COALESCE( htg.name, '' ) AS home_team_group, 
				ht.group_order AS home_team_group_order, ht.is_real AS home_team_is_real, 
				at.photo AS away_team_photo, at.flag AS away_team_flag, at.link AS away_team_link,
				COALESCE( atg.name, '' ) AS away_team_group, at.group_order AS away_team_group_order, 
				at.is_real AS away_team_is_real, COALESCE( s.photo, '' ) AS stadium_photo
			FROM {$prefix}matches m
			JOIN {$prefix}teams ht ON m.home_team_id = ht.id
			JOIN {$prefix}teams at ON m.away_team_id = at.id
			JOIN {$prefix}matchtypes t ON m.matchtype_id = t.id
			LEFT OUTER JOIN {$prefix}stadiums s ON m.stadium_id = s.id
			LEFT OUTER JOIN {$prefix}groups htg ON ht.group_id = htg.id
			LEFT OUTER JOIN {$prefix}groups atg ON at.group_id = atg.id
			ORDER BY m.play_date ASC";
} else {
	$sql = "SELECT 
				m.play_date, ht.name AS home_team, at.name AS away_team, 
				COALESCE( s.name, '' ) AS stadium, t.name AS match_type	
			FROM {$prefix}matches m
			JOIN {$prefix}teams ht ON m.home_team_id = ht.id
			JOIN {$prefix}teams at ON m.away_team_id = at.id
			JOIN {$prefix}matchtypes t ON m.matchtype_id = t.id
			LEFT OUTER JOIN {$prefix}stadiums s ON m.stadium_id = s.id
			ORDER BY m.play_date ASC";
}

$matches = $wpdb->get_results( $sql, ARRAY_A );

$file_name = date( 'Y-m-d-H-i-s' ) . '-match-export.csv';
header( 'Content-Description: File Transfer' );
header( 'Content-Disposition: attachment; filename=' . $file_name );
//header( 'Content-Type: text/csv; charset=utf-8', true );
header( 'Content-Type: text/csv', true );
header( 'Expires: 0' );
header( 'Pragma: public' );

$fp = @fopen( 'php://output', 'w' );
if ( $fp && count( $matches ) > 0 ) {
	fputcsv( $fp, array_keys( $matches[0] ), FOOTBALLPOOL_CSV_DELIMITER );
	foreach ( $matches as $match ) {
		fputcsv( $fp, $match, FOOTBALLPOOL_CSV_DELIMITER );
	}
}
fclose( $fp );
exit;
