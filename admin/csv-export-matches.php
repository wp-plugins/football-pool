<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );

$full_data = ! ( isset( $_GET['format'] ) && $_GET['format'] == 'minimal' );

global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;

if ( $full_data ) {
	$sql = "SELECT
				m.playDate AS play_date, ht.name AS home_team, at.name AS away_team, 
				COALESCE( s.name, '' ) AS stadium, t.name AS match_type, 
				ht.photo AS home_team_photo, ht.flag AS home_team_flag, 
				ht.link AS home_team_link, COALESCE( htg.name, '' ) AS home_team_group, 
				ht.groupOrder AS home_team_group_order, ht.is_real AS home_team_is_real, 
				at.photo AS away_team_photo, at.flag AS away_team_flag, at.link AS away_team_link,
				COALESCE( atg.name, '' ) AS away_team_group, at.groupOrder AS away_team_group_order, 
				at.is_real AS away_team_is_real, COALESCE( s.photo, '' ) AS stadium_photo
			FROM {$prefix}matches m
			JOIN {$prefix}teams ht ON m.homeTeamId = ht.id
			JOIN {$prefix}teams at ON m.awayTeamId = at.id
			JOIN {$prefix}matchtypes t ON m.matchtypeId = t.id
			LEFT OUTER JOIN {$prefix}stadiums s ON m.stadiumId = s.id
			LEFT OUTER JOIN {$prefix}groups htg ON ht.groupId = htg.id
			LEFT OUTER JOIN {$prefix}groups atg ON at.groupId = atg.id
			ORDER BY m.playDate ASC";
} else {
	$sql = "SELECT 
				m.playDate AS play_date, ht.name AS home_team, at.name AS away_team, 
				COALESCE( s.name, '' ) AS stadium, t.name AS match_type	
			FROM {$prefix}matches m
			JOIN {$prefix}teams ht ON m.homeTeamId = ht.id
			JOIN {$prefix}teams at ON m.awayTeamId = at.id
			JOIN {$prefix}matchtypes t ON m.matchtypeId = t.id
			LEFT OUTER JOIN {$prefix}stadiums s ON m.stadiumId = s.id
			ORDER BY m.playDate ASC";
}

$matches = $wpdb->get_results( $sql, ARRAY_A );

$file_name = date( 'Y-m-d-H-i-s' ) . '-match-export.csv';
header( 'Content-Description: File Transfer' );
header( 'Content-Disposition: attachment; filename=' . $file_name );
//header( 'Content-Type: text/csv; charset=utf-8', true );
header( 'Content-Type: text/csv', true );
header( 'Expires: 0' );
header( 'Pragma: public' );

$header_added = false;

$fp = @fopen( 'php://output', 'w' );
foreach ( $matches as $match ) {
	if ( ! $header_added ) {
		fputcsv( $fp, array_keys( $match ), FOOTBALLPOOL_CSV_DELIMITER );
		$header_added = true;
	}
	fputcsv( $fp, $match, FOOTBALLPOOL_CSV_DELIMITER );
}
fclose( $fp );
exit;

?>