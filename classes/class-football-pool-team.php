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
			}
		} elseif ( is_array( $team ) ) {
			$this->id = $team['id'];
			$this->name = __( $team['name'], FOOTBALLPOOL_TEXT_DOMAIN );
			$this->photo = $team['photo'];
			$this->flag = $team['flag'];
			$this->link = $team['link'];
			$this->group_id = $team['groupId'];
			$this->group_name = __( $team['groupName'], FOOTBALLPOOL_TEXT_DOMAIN );
			$this->group_order = $team['group_order'];
			$this->is_real = $team['is_real'];
			$this->is_active = $team['is_active'];
		}
	}
	
	function HTML_thumb() {
		if ( $this->photo != '' ) {
			$img_url = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/teams/' . $this->photo;
			$thumb = sprintf( '<a class="thumb fp-lightbox" href="%s"><img src="%s" title="%s %s" alt="%s %s" 
									class="teamphotothumb" /></a>',
							$img_url,
							$img_url,
							__( 'Click to enlarge:', FOOTBALLPOOL_TEXT_DOMAIN ),
							$this->name,
							__( 'team photo for', FOOTBALLPOOL_TEXT_DOMAIN ),
							$this->name
							);
		} else {
			$thumb = '';
		}
		return $thumb;
	}
	
	function HTML_image() {
		return '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/teams/' . str_replace( '_t', '', $this->photo ) . '" title="' . __( 'close', FOOTBALLPOOL_TEXT_DOMAIN ) . '" alt="' . __( 'team photo for', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $this->name . '" class="teamphoto" onclick="window.close();" />';
	}
	
	function get_plays() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT 
									UNIX_TIMESTAMP(m.playDate) AS match_timestamp, 
									m.homeTeamId, 
									m.awayTeamId, 
									m.homeScore, 
									m.awayScore, 
									s.id, 
									s.name, 
									t.name AS matchtype, 
									m.nr,
									m.playDate 
								FROM {$prefix}matches m, {$prefix}stadiums s, {$prefix}matchtypes t 
								WHERE m.stadiumId = s.id 
									AND m.matchtypeId = t.id 
									AND (m.homeTeamId = %d OR m.awayTeamId = %d)",
								$this->id,
								$this->id
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT DISTINCT s.id, s.name, s.photo
								FROM {$prefix}stadiums s, {$prefix}matches m 
								WHERE s.id = m.stadiumId 
								AND (m.homeTeamId = %d OR awayTeamId = %d)
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