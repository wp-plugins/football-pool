<?php
class Football_Pool_Team extends Football_Pool_Teams {
	public $id = 0;
	public $name = '';
	public $photo = '';
	public $flag = '';
	public $link = '';
	public $group_id = 0;
	public $group_name = '';
	public $group_order;
	public $is_real = 1;
	public $is_active = 1;
	
	public function __construct( $team = 0 ) {
		if ( is_int( $team ) && $team != 0 ) {
			$t = $this->get_team_by_id( $team );
			if ( is_object( $t ) ) {
				$this->id = $t->id;
				$this->name = $t->name;
				$this->photo = $t->photo;
				$this->flag = $t->flag;
				$this->link = $t->link;
				$this->group_id = $t->group_id;
				$this->group_name = $t->group_name;
				$this->group_order = $t->group_order;
				$this->is_real = $t->is_real;
				$this->is_active = $t->is_active;
				$this->comments = $t->comments;
			}
		} elseif ( is_array( $team ) ) {
			$this->id = $team['id'];
			$this->name = $team['name'];
			$this->photo = $team['photo'];
			$this->flag = $team['flag'];
			$this->link = $team['link'];
			$this->group_id = $team['group_id'];
			$this->group_name = $team['group_name'];
			$this->group_order = $team['group_order'];
			$this->is_real = $team['is_real'];
			$this->is_active = $team['is_active'];
			$this->comments = $team['comments'];
		}
	}
	
	private function get_photo_url( $photo ) {
		$path = '';
		if ( stripos( $photo, 'http://' ) !== 0 && stripos( $photo, 'https://' ) !== 0 ) {
			$path = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/teams/';
		}
		
		return $path . $photo;
	}
	
	public function HTML_thumb( $return = 'all' ) {
		if ( $this->photo != '' ) {
			$photo = $this->get_photo_url( $this->photo );
			if ( $return == 'thumb' ) {
				$thumb = sprintf( '<img src="%s" title="%s" alt="%s %s" class="team-photo thumb team-list" />'
									, esc_attr( $photo )
									, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
									, esc_attr( __( 'team photo for', FOOTBALLPOOL_TEXT_DOMAIN ) )
									, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
								);
			} else {
				$thumb = sprintf( '<a class="thumb fp-lightbox" href="%s"><img src="%s" title="%s %s" alt="%s %s" 
										class="team-photo thumb" /></a>'
									, esc_attr( $photo )
									, esc_attr( $photo )
									, esc_attr( __( 'Click to enlarge:', FOOTBALLPOOL_TEXT_DOMAIN ) )
									, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
									, esc_attr( __( 'team photo for', FOOTBALLPOOL_TEXT_DOMAIN ) )
									, esc_attr( htmlentities( $this->name, null, 'UTF-8' ) )
								);
			}
		} else {
			$thumb = '';
		}
		return $thumb;
	}
	
	public function HTML_image() {
		return sprintf( '<img src="%s" title="%s" alt="%s" class="team-photo" />'
						, esc_attr( str_replace( '_t', '', $this->get_photo_url( $this->photo ) ) )
						, esc_attr( __( 'close', FOOTBALLPOOL_TEXT_DOMAIN ) )
						, esc_attr( __( 'team photo for', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $this->name )
					);
	}
	
	public function get_plays() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sorting = Football_Pool_Matches::get_match_sorting_method();
		
		$sql = $wpdb->prepare( "SELECT 
									m.home_team_id, 
									m.away_team_id, 
									m.home_score, 
									m.away_score, 
									s.id AS stadium_id, 
									s.name, 
									t.name AS matchtype, 
									m.id AS match_id, 
									m.play_date 
								FROM {$prefix}matches m, {$prefix}stadiums s, {$prefix}matchtypes t 
								WHERE m.stadium_id = s.id 
									AND m.matchtype_id = t.id AND t.visibility = 1
									AND ( m.home_team_id = %d OR m.away_team_id = %d )
								ORDER BY {$sorting}",
								$this->id,
								$this->id
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT DISTINCT s.id, s.name, s.photo, s.comments
								FROM {$prefix}stadiums s, {$prefix}matches m 
								WHERE s.id = m.stadium_id 
									AND ( m.home_team_id = %d OR away_team_id = %d )
								ORDER BY s.name ASC",
								$this->id,
								$this->id
							);
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		$stadiums = array();
		foreach ( $rows as $row ) {
			$stadiums[] = new Football_Pool_Stadium( $row );
		}
		
		return $stadiums;
	}
}
?>