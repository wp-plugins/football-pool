<?php
class Football_Pool_Stadium extends Football_Pool_Stadiums {
	public $id = 0;
	public $name = '';
	public $photo = '';
	
	public function __construct( $stadium = 0 ) {
		if ( is_int( $stadium ) && $stadium != 0 ) {
			$s = $this->get_stadium_by_ID( $stadium );
			if ( is_object( $s ) ) {
				$this->id = $s->id;
				$this->name = $s->name;
				$this->photo = $s->photo;
			}
		} elseif ( is_array( $stadium ) ) {
			$this->id = $stadium['id'];
			$this->name = $stadium['name'];
			$this->photo = $stadium['photo'];
		}
	}
	
	public function HTML_image() {
		$path = '';
		if ( strpos( $this->photo, 'http://' ) !== 0 && strpos( $this->photo, 'https://' ) !== 0 ) {
			$path = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/stadiums/';
		}
		return sprintf( '<img src="%s%s" title="%s" alt="%s" class="stadiumphoto" />'
						, $path
						, $this->photo
						, htmlentities( $this->name )
						, htmlentities( $this->name )
					);
	}
	
	public function get_plays() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT 
									UNIX_TIMESTAMP(m.playDate) AS match_timestamp, 
									m.homeTeamId, 
									m.awayTeamId, 
									m.homeScore, 
									m.awayScore, 
									s.name, 
									s.id, 
									t.name AS matchtype, 
									m.nr,
									m.playDate 
								FROM {$prefix}matches m, {$prefix}stadiums s, {$prefix}matchtypes t 
								WHERE m.stadiumId = s.id 
									AND m.matchtypeId = t.id 
									AND s.id = %d", 
							$this->id
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}
}
?>