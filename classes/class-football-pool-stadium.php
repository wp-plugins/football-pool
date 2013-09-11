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
		$matches = new Football_Pool_Matches;
		$matches = $matches->matches;
		
		$plays = array();
		foreach ( $matches as $match ) {
			if ( $match['stadium_id'] == $this->id ) $plays[] = $match;
		}
		
		return $plays;
	}
}
?>