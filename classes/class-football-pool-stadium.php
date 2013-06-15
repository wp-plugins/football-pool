<?php
class Football_Pool_Stadium extends Football_Pool_Stadiums {
	public $id = 0;
	public $name = '';
	public $photo = '';
	
	public function __construct( $stadium = 0 ) {
		if ( is_int( $stadium ) && $stadium != 0 ) {
			$s = $this->get_stadium_by_id( $stadium );
			if ( is_object( $s ) ) {
				$this->id = $s->id;
				$this->name = $s->name;
				$this->photo = $s->photo;
				$this->comments = $s->comments;
			}
		} elseif ( is_array( $stadium ) ) {
			$this->id = $stadium['id'];
			$this->name = $stadium['name'];
			$this->photo = $stadium['photo'];
			$this->comments = $stadium['comments'];
		}
	}
	
	private function get_photo_url( $photo ) {
		$path = '';
		if ( stripos( $photo, 'http://' ) !== 0 && stripos( $photo, 'https://' ) !== 0 ) {
			$path = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/stadiums/';
		}
		
		return $path . $photo;
	}
	
	public function HTML_image( $return = 'image' ) {
		$thumb = ( $return == 'thumb' ) ? ' thumb stadium-list' : '';
		return sprintf( '<img src="%s" title="%s" alt="%s" class="stadium-photo%s" />'
						, esc_attr( $this->get_photo_url( $this->photo ) )
						, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
						, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
						, $thumb
					);
	}
	
	public function get_plays() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sorting = Football_Pool_Matches::get_match_sorting_method();
		
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
								WHERE m.stadiumId = s.id  AND s.id = %d
									AND m.matchtypeId = t.id AND t.visibility = 1
								ORDER BY {$sorting}", 
							$this->id
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}
}
?>