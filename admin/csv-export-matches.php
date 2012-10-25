<?php
require_once( '../../../../wp-load.php' );
require_once( '../define.php' );

global $wpdb;
$prefix = FOOTBALLPOOL_DB_PREFIX;

$sql = "SELECT m.playDate AS play_date, ht.name AS home_team, at.name AS away_team, s.name AS stadium
				, t.name AS match_type
		FROM {$prefix}matches m
		JOIN {$prefix}teams ht ON m.homeTeamId = ht.id
		JOIN {$prefix}teams at ON m.awayTeamId = at.id
		JOIN {$prefix}matchtypes t ON m.matchtypeId = t.id
		JOIN {$prefix}stadiums s ON m.stadiumId = s.id
		ORDER BY m.playDate ASC";
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